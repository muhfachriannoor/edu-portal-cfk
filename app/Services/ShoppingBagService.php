<?php

namespace App\Services;

use App\Models\ShoppingBagItem;
use App\Models\CheckoutSession;
use App\Models\Courier;
use App\Models\Store;
use App\Models\UserAddress;
use App\Models\Voucher;
use App\Services\VoucherService;
use App\Services\Support\PriceFormatter;
use App\View\Data\ProductData;

class ShoppingBagService
{
    private $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }
    /**
     * Select or unselect all items in the same store.
     *
     * @param int $userId User ID
     * @param int $storeId Store ID
     * @param bool $selectAll True to select all, false to unselect all
     * @return bool True if operation succeeded, false if validation failed
     */
    public function selectAllOrUnselectAll(int $userId, int $storeId, bool $selectAll): bool
    {
        // Get all items from the same store
        $items = ShoppingBagItem::where('user_id', $userId)
            ->where('store_id', $storeId)
            ->get();

        // Validate if there are selected items from other stores
        foreach ($items as $item) {
            if ($selectAll && $item->is_selected) {
                continue;
            }
            if (!$selectAll && !$item->is_selected) {
                continue;
            }

            // Check condition: if trying to select all and there are selected items from other stores
            return false;
        }

        // Update is_selected status for all items
        foreach ($items as $item) {
            $item->is_selected = $selectAll;
            $item->save();
        }

        return true;
    }

    /**
     * Validate store selection so that only one store can have selected items.
     *
     * @param int      $userId
     * @param int      $storeId
     * @param bool     $targetSelectedState
     * @param int|null $exceptItemId
     * @return bool
     */
    public function validateStoreSelection(int $userId, int $storeId, bool $targetSelectedState, ?int $exceptItemId = null): bool
    {
        // If we are trying to select an item, ensure no other store has selected items
        if ($targetSelectedState) {
            if ($this->isAnotherStoreSelected($userId, $storeId)) {
                return false;
            }
        }

        // For unselecting, always allow
        return true;
    }

    /**
     * Validate brand selection.
     * Currently we only need to respect the single-store rule,
     * so this simply delegates to validateStoreSelection().
     *
     * @param int $userId User ID
     * @param int $brandId Brand ID (not used currently, kept for future use)
     * @param bool $targetSelectedState Target selection state
     * @param int $storeId Store ID
     * @return bool True if selection is valid, false otherwise
     */
    public function validateBrandSelection(int $userId, int $brandId, bool $targetSelectedState, int $storeId): bool
    {
        return $this->validateStoreSelection($userId, $storeId, $targetSelectedState);
    }

    /**
     * Check whether there is any selected item from a different store.
     *
     * @param int $userId
     * @param int $currentStoreId
     * @return bool True if there is a selected item from a different store, false otherwise.
     */
    public function isAnotherStoreSelected(int $userId, int $currentStoreId): bool
    {
        return ShoppingBagItem::where('user_id', $userId)
            ->where('is_selected', true)
            ->where('store_id', '!=', $currentStoreId)
            ->exists();
    }

    /**
     * Unselect items from other stores when selecting items from a specific store.
     *
     * @param int $userId
     * @param int $keepStoreId
     * @return void
     */
    public function unselectItemsFromOtherStores(int $userId, int $keepStoreId): void
    {
        ShoppingBagItem::where('user_id', $userId)
            ->where('store_id', '!=', $keepStoreId)
            ->where('is_selected', true)
            ->update(['is_selected' => false]);
    }

    /**
     * Synchronize the user's checkout session with the store's fulfillment capabilities.
     * Priority: Delivery (delivery-sarinah) > Pickup.
     * 
     * @param int $userId
     * @param int|Store $storeId Pass Store object to optimize and prevent extra queries.
     */
    public function syncFulfillmentSession(int $userId, $storeOrId): void
    {
        // 1. Resolve store object (Optimization: avoids query if object is already provided)
        $store = $storeOrId instanceof Store ? $storeOrId : Store::find($storeOrId);

        if (!$store) return;

        // 2. Determine Courier Key based on priority (Delivery First)
        $courierKey = null;

        if ($store->is_delivery) {
            $courierKey = 'delivery-sarinah';
        } elseif ($store->is_pickup) {
            $courierKey = 'pickup';
        }

        // If store supports neither, we don't update the session
        if (!$courierKey) return;
        
        // 3. Resolve Courier ID
        $courier = Courier::where('key', $courierKey)->first();

        if (!$courier) return;

        // 4. Atomic update/create of the checkout session
        CheckoutSession::updateOrCreate(
            ['user_id' => $userId],
            ['courier_id' => $courier->id]
        );
    }

    /**
     * Check if the specific item supports the currently active fulfillment method.
     * 
     * @param ShoppingBagItem $item
     * @param string $method ('pickup' or 'delivery')
     * @return bool
     */
    public function isItemSupportMethod(ShoppingBagItem $item, string $method): bool
    {
        $store = $item->store;
        if (!$store) return false;

        if ($method === 'pickup') {
            return (bool) $store->is_pickup;
        }

        return (bool) $store->is_delivery;
    }

    /**
     * Build the shopping bag snapshot for the given user.
     *
     * This method:
     * - Loads all shopping bag items with their related product/brand.
     * - Calculates base/final price, discount, item_subtotal.
     * - Adds stock availability and low stock warnings.
     * - Groups items by brand.
     * - Calculates brand_subtotal and grand_total using only items where is_selected = true.
     * - Also returns a global summary block for the order summary box:
     *   - selected_items_count (line items)
     *   - selected_items_quantity (sum of quantities)
     *   - subtotal (before discount, selected items only)
     *   - discount (total discount amount, selected items only)
     *   - select-all metadata (total_items, total_selectable_items, selected_selectable_items, select_all_state)
     *
     * @param int $userId
     * @param string $locale
     * @return array
     */
    public function buildShoppingBagSnapShot(int $userId, string $locale): array
    {
        // Retrieve the current active courier key from the checkout session
        $session = CheckoutSession::where('user_id', $userId)->with('courier')->first();
        $activeMethod = $session->courier->key ?? 'delivery-sarinah';
        $displayType = ($activeMethod === 'delivery-sarinah') ? 'delivery' : 'pickup';

        $bagItems = ShoppingBagItem::where('user_id', $userId)
            ->with([
                'productVariant:id,product_id,quantity,combination,price',
                'productVariant.product:id,brand_id,main_image_index',
                'productVariant.product.brand:id,name',
                'productVariant.product.defaultImageFile',
                'store:id,location_id,slug,phone,email,verified_at,is_active,is_delivery,is_pickup,created_at,updated_at',
            ])
            ->orderBy('id', 'DESC')
            ->get();

        $mappedItems = $bagItems->map(fn($item) => $this->transformBagItem($item, $locale, $activeMethod))->filter();

        // Summary calculations
        $selectedItems = $mappedItems->filter(fn ($item) => $item['is_selected']);
        $subtotalBaseRaw = $selectedItems->sum('item_base_subtotal_raw');
        $subtotalFinalRaw = $selectedItems->sum('item_subtotal_raw');
        $discountTotalRaw = max(0, $subtotalBaseRaw - $subtotalFinalRaw);
        $summaryDiscountPercent = ($subtotalBaseRaw > 0 && $discountTotalRaw > 0)
            ? ($discountTotalRaw / $subtotalBaseRaw) * 100
            : 0.0;
        $selectedLineItemsCount = $selectedItems->count();
        $selectedItemsQuantity = $selectedItems->sum('quantity_in_bag');

        // Select-all meta
        $selectAllMeta = $this->calculateSelectAllMeta($mappedItems);

        // Group by store and brand
        $groups = $mappedItems->groupBy('store_id')->map(function ($storeItems, $storeId) {
            $firstItem = $storeItems->first();
            $storeName = $firstItem['store_name'];

            $isDeliveryAvailable = (bool) ($firstItem['store_is_delivery'] ?? false);
            $isPickupAvailable = (bool) ($firstItem['store_is_pickup'] ?? false);

            $storeSelectableItems = $storeItems->filter(fn ($item) => $item['is_available_for_checkout'] === true);
            $isStoreSelected = $storeSelectableItems->isNotEmpty() && $storeSelectableItems->every(fn ($item) => $item['is_selected'] === true);

            $brands = $storeItems->groupBy('brand_id')->map(function ($brandItems, $brandId) {
                $brandName = $brandItems->first()['brand_name'];
                $brandSelectableItems = $brandItems->filter(fn ($item) => $item['is_available_for_checkout'] === true);
                $isBrandSelected = $brandSelectableItems->isNotEmpty() && $brandSelectableItems->every(fn ($item) => $item['is_selected'] === true);
                $brandSelectedSubtotalRaw = $brandSelectableItems->filter(fn ($item) => $item['is_selected'] === true)->sum('item_subtotal_raw');
                $items = $brandItems->map(function ($item) {
                    unset(
                        $item['brand_id'],
                        $item['brand_name'],
                        $item['item_subtotal_raw'],
                        $item['item_base_subtotal_raw'],
                        $item['store_id'],
                        $item['store_name'],
                        $item['store_is_delivery'],
                        $item['store_is_pickup'],
                    );
                    return $item;
                })->values()->toArray();

                return [
                    'brand_id' => $brandId,
                    'brand_name' => $brandName,
                    'is_selected' => $isBrandSelected,
                    'brand_subtotal' => number_format($brandSelectedSubtotalRaw, 0, ',', '.'),
                    'items' => $items,
                ];
            })->values()->toArray();

            return [
                'store_id' => $storeId,
                'store_name' => $storeName,
                'is_selected' => $isStoreSelected,
                // 'can_delivery' => $isDeliveryAvailable,
                // 'can_pickup' => $isPickupAvailable,
                'brands' => $brands,
            ];
        })->values()->toArray();

        $user = auth()->user();

        // Check if user profile is complete (name, title, mobile_number, date_of_birth)
        $userProfileComplete = !empty($user->name) && !empty($user->title) && !empty($user->mobile_number) && !empty($user->date_of_birth);

        // Check if user has at least one address
        $userHasAddress = $user->addresses()->exists();
        $hasSpecialPrice = $selectedItems->some(fn ($item) => $item['has_special_price']);

        return [
            'type' => $displayType,
            'groups' => $groups,
            'user_profile_complete' => $userProfileComplete && $userHasAddress,
            'summary' => [
                'selected_items_count' => $selectedLineItemsCount,
                'selected_items_quantity' => $selectedItemsQuantity,
                'subtotal' => number_format($subtotalBaseRaw, 0, ',', '.'),
                'discount' => number_format($discountTotalRaw, 0, ',', '.'),
                'discount_percent' => $summaryDiscountPercent > 0
                    ? PriceFormatter::formatPercentage($summaryDiscountPercent)
                    : '0%',
                'grand_total' => number_format($subtotalFinalRaw, 0, ',', '.'),
                'has_special_price' => $hasSpecialPrice,
                ...$selectAllMeta,
            ],
        ];
    }

    /**
     * Build shopping bag snapshot for checkout (selected items only).
     * This is used both in checkout and updateUserAddress to return the same data structure.
     *
     * @param int $userId User ID
     * @param string $locale Locale string (e.g., 'en', 'id')
     * @return array Checkout snapshot array with groups, user_address, couriers, order_summary, and optional voucher
     */
    public function buildShoppingBagSnapshotForUser(int $userId, string $locale)
    {
        // Get snapshot from shopping bag
        $bagSnapshot = $this->buildShoppingBagSnapShot($userId, $locale);
        $selectedItemsIds = $this->getSelectedItemsIds($bagSnapshot);
        $selectedBagItems = $this->getSelectedBagItems($userId, $selectedItemsIds);
        $selectedGroups = $this->getSelectedGroups($bagSnapshot, $selectedBagItems);
        $pickupLocation = $this->getPickupLocation($selectedBagItems);
        $userAddressPayload = $this->getUserAddressPayload($userId);
        $courierPayload = $this->getCourierPayload($userId, $pickupLocation);
        $orderSummary = $this->getOrderSummary($bagSnapshot, $courierPayload);
        $activeType = $bagSnapshot['type'] ?? 'pickup';
        
        $checkoutSnapshot = [
            'type' => $activeType,
            'groups' => $selectedGroups,
            'user_address' => $userAddressPayload,
            'couriers' => $courierPayload,
            'order_summary' => $orderSummary,
        ];

        // Step 9: Attach voucher if any
        $checkoutSnapshot = $this->attachVoucherFromSessionIfAny(
            $userId,
            $locale,
            $bagSnapshot,
            $checkoutSnapshot
        );

        return $checkoutSnapshot;
    }

    /**
     * Transform a ShoppingBagItem into a flat array with pricing & stock data.
     */
    private function transformBagItem(ShoppingBagItem $item, string $locale, string $activeFulfillment)
    {
        // Retrieve variant and related product/brand info
        $variant = $item->productVariant;
        if (!$variant || !$variant->product || !$variant->product->brand) {
            return null;
        }

        $variantId = $variant->id;
        $brand = $variant->product->brand;
        $store = $item->store;
        $quantityInBag = (int) $item->quantity;
        $isSelected = (bool) $item->is_selected;

        // Get display details and promotion details
        $displayDetails = ProductData::getVariantDisplayDetails($variantId, $locale);
        $promoDetails = ProductData::getActiveSpecialPrice($variantId);

        if (!$displayDetails) {
            return null;
        }

        // Base price and initialization of pricing variables
        $imageUrl = $variant->product->default_image;
        $basePrice = (float) $displayDetails['base_price'];
        $finalPrice = $basePrice;
        $discountAmount = 0.0;
        $hasSpecialPrice = !is_null($promoDetails);

        // If there's a special price, apply the price change (not a discount)
        if ($hasSpecialPrice && $promoDetails['type'] === 'absolute_reduction') {
            // Set final price directly to the value specified in promoDetails
            $finalPrice = (float) $promoDetails['value']; // Directly set final price to the special price
            // Set the discount amount to the difference between the base price and the final price
            $discountAmount = $basePrice - $finalPrice; // Calculate discount amount based on change in price
        }

        // Calculate discount percentage if applicable
        $itemDiscountPercent = null;
        if ($hasSpecialPrice && $basePrice > 0) {
            // If there is a percentage discount, calculate it
            $percentValue = isset($promoDetails['percentage'])
                ? (float) $promoDetails['percentage']
                : 0.0;

            if ($percentValue > 0) {
                $itemDiscountPercent = PriceFormatter::formatPercentage($percentValue);
            }
        }

        // Subtotal calculations
        $itemFinalSubtotal = $finalPrice * $quantityInBag;
        $itemBaseSubtotal = $basePrice * $quantityInBag;

        // Stock check and warnings
        $liveStock = (int) $variant->quantity;
        $isStockAvailable = true;
        $warningMessage = null;

        if ($liveStock === 0) {
            $isStockAvailable = false;
            $warningMessage = trans('api.shopping_bag.out_of_stock_warning');
        } elseif ($quantityInBag > $liveStock) {
            $isStockAvailable = false;
            $warningMessage = trans('api.shopping_bag.exceeds_stock_detailed', [
                'requested' => $quantityInBag,
                'stock' => $liveStock
            ]);
        } elseif ($liveStock <= 10) {
            $warningMessage = trans('api.shopping_bag.low_stock_warning', ['stock' => $liveStock]);
        }

        // Check Fulfillment Compatibility
        $isFulfillmentSupported = true;
        if ($activeFulfillment === 'delivery-sarinah' && !$store->is_delivery) {
            $isFulfillmentSupported = false;
            $warningMessage = trans('api.shopping_bag.delivery_not_supported');
        } elseif ($activeFulfillment === 'pickup' && !$store->is_pickup) {
            $isFulfillmentSupported = false;
            $warningMessage = trans('api.shopping_bag.pickup_not_found');
        }

        // Combine all availability checks
        $isAvailableForCheckout = $isStockAvailable && $isFulfillmentSupported;

        // Auto-unselect item in database if it's no longer available for checkout
        if (!$isAvailableForCheckout && $isSelected) {
            $item->is_selected = false;
            $item->save();
            $isSelected = false;
        }

        // Return transformed item
        return [
            'id' => $item->id,
            'product_slug' => $displayDetails['product_slug'] ?? "",
            'product_variant_id' => $variantId,
            'brand_id' => $brand->id,
            'brand_name' => $brand->name,
            'product_name' => $displayDetails['product_name'] ?? "",
            'variant_details' => $displayDetails['variant_names'] ?? "",
            'image_url' => $imageUrl,
            'quantity_in_bag' => $quantityInBag,
            'store_id' => $item->store->id,
            'store_name' => $item->store->name,
            'store_is_delivery' => $item->store->is_delivery,
            'store_is_pickup' => $item->store->is_pickup,
            'item_subtotal_raw' => $itemFinalSubtotal,
            'item_base_subtotal_raw' => $itemBaseSubtotal,
            'base_unit_price' => number_format($basePrice, 0, ',', '.'),
            'final_unit_price' => number_format($finalPrice, 0, ',', '.'), // Final price after special price applied
            'item_subtotal' => number_format($itemFinalSubtotal, 0, ',', '.'),
            'has_special_price' => $hasSpecialPrice,
            'discount_amount' => number_format($discountAmount, 0, ',', '.'), // Correctly set the discount amount
            'discount_percent' => $itemDiscountPercent,
            'is_selected' => $isSelected,
            'is_available_for_checkout' => $isAvailableForCheckout,
            // 'is_mismatch' => !$isFulfillmentSupported,
            'max_available_quantity' => $liveStock,
            'warning_message' => $warningMessage,
        ];
    }

    /**
     * Calculate select-all state and summary meta.
     */
    private function calculateSelectAllMeta($mappedItems)
    {
        $totalItems = $mappedItems->count();
        $selectableItems = $mappedItems->filter(fn ($item) => $item['is_available_for_checkout'] === true);
        $totalSelectableItems = $selectableItems->count();
        $selectedSelectableItems = $selectableItems->filter(fn ($item) => $item['is_selected'] === true)->count();

        $selectAllState = 'disabled';
        if ($totalSelectableItems === 0) {
            $selectAllState = 'disabled';
        } elseif ($selectedSelectableItems === 0) {
            $selectAllState = 'none';
        } elseif ($selectedSelectableItems === $totalSelectableItems) {
            $selectAllState = 'all';
        } else {
            $selectAllState = 'partial';
        }

        return [
            'total_items' => $totalItems,
            'total_selectable_items' => $totalSelectableItems,
            'selected_selectable_items' => $selectedSelectableItems,
            'select_all_state' => $selectAllState,
        ];
    }

    /**
     * Extract selected item IDs from shopping bag snapshot.
     *
     * @param array $bagSnapshot Shopping bag snapshot array
     * @return array Array of selected item IDs
     */
    public function getSelectedItemsIds(array $bagSnapshot): array
    {
        $selectedItemsIds = [];

        foreach ($bagSnapshot['groups'] as $group) {
            if (isset($group['brands']) && !empty($group['brands'])) {
                foreach ($group['brands'] as $brand) {
                    if (isset($brand['items']) && !empty($brand['items'])) {
                        foreach ($brand['items'] as $item) {
                            // Ensure item is selected and add to list
                            if (!empty($item['is_selected']) && $item['is_selected'] === true) {
                                $selectedItemsIds[] = $item['id'];
                            }
                        }
                    }
                }
            }
        }

        return $selectedItemsIds;
    }

    /**
     * Get selected shopping bag items by user ID and item IDs.
     *
     * @param int $userId User ID
     * @param array $selectedItemsIds Array of selected item IDs
     * @return \Illuminate\Support\Collection Collection of ShoppingBagItem models keyed by ID
     */
    public function getSelectedBagItems(int $userId, array $selectedItemsIds)
    {
        return ShoppingBagItem::where('user_id', $userId)
            ->whereIn('id', $selectedItemsIds)
            ->with('store.masterLocation')
            ->get()
            ->keyBy('id');
    }

    /**
     * Build selected groups of items based on snapshot data for checkout.
     *
     * @param array $bagSnapshot Shopping bag snapshot array
     * @param \Illuminate\Support\Collection $selectedBagItems Collection of selected bag items
     * @return array Array of selected groups organized by store and brand
     */
    public function getSelectedGroups(array $bagSnapshot, $selectedBagItems): array
    {
        $selectedGroups = [];

        // Iterate through each group
        foreach ($bagSnapshot['groups'] as $group) {
            $selectedItems = [];

            // Check if 'brands' exists and is not empty
            if (isset($group['brands']) && !empty($group['brands'])) {
                // Iterate through each brand in the group
                foreach ($group['brands'] as $brand) {
                    $brandSelectedItems = []; // Store selected items for this brand

                    // Check if 'items' exists and is not empty
                    if (isset($brand['items']) && !empty($brand['items'])) {
                        // Iterate through each item in the brand
                        foreach ($brand['items'] as $item) {
                            // Ensure selected items are included in brandSelectedItems
                            if ((bool) $item['is_selected']) {
                                $brandSelectedItems[] = $item;
                            }
                        }
                    }

                    // Only add brand to selectedGroups if there are selected items
                    if (!empty($brandSelectedItems)) {
                        $selectedItems[] = [
                            'brand_id' => $brand['brand_id'],
                            'brand_name' => $brand['brand_name'],
                            // 'is_selected' => $brand['is_selected'],
                            'brand_subtotal' => $brand['brand_subtotal'],
                            'items' => $brandSelectedItems,
                        ];
                    }
                }
            }

            // Add selectedItems to selectedGroups if there are selected items in the group
            if (!empty($selectedItems)) {
                $selectedGroups[] = [
                    'store_id' => $group['store_id'],
                    'store_name' => $group['store_name'],
                    // 'is_selected' => $group['is_selected'],
                    // 'can_delivery' => $group['can_delivery'],
                    // 'can_pickup' => $group['can_pickup'],
                    'brands' => $selectedItems, // Add selected brands to the store group
                ];
            }
        }

        return $selectedGroups;
    }

    /**
     * Get pickup location information for checkout if applicable.
     *
     * @param \Illuminate\Support\Collection $selectedBagItems Collection of selected shopping bag items
     * @return array|null Pickup location array or null if not applicable
     */
    public function getPickupLocation($selectedBagItems)
    {
        $locale = app()->getLocale();
        $pickupLocation = null;

        $bagItemWithLocation = $selectedBagItems->first(function (ShoppingBagItem $bagItem) {
            return $bagItem->store && $bagItem->store->masterLocation;
        });

        if ($bagItemWithLocation && $bagItemWithLocation->store && $bagItemWithLocation->store->masterLocation) {
            $store    = $bagItemWithLocation->store;
            $location = $store->masterLocation;

            $pickupLocation = [
                'store_id'          => $store->id,
                'store_name'        => $store->name,
                'store_slug'        => $store->slug,
                'location_name'     => $location->location,
                'address'           => $location->address,
                'city'              => $location->city,
                'type_label'        => $location->type_label,
                'phone'             => $store->phone,
                'email'             => $store->email,
                'warning_pickup'    => $location->warning_pickup,
                'point_operational' => $location->translation($locale)?->name,

                // For delivery usage
                'postal_code'       => $location->postal_code,
                'city_name'         => $location->master_address?->city_name,
                'district_name'     => $location->master_address?->district_name,
                'subdistrict_name'  => $location->master_address?->subdistrict_name,
                'subdistrict_id'    => $location->master_address_id,
            ];
        }

        return $pickupLocation;
    }

    /**
     * Get warehouse location information for checkout if applicable.
     *
     * @param \Illuminate\Support\Collection $selectedBagItems Collection of selected shopping bag items
     * @return array|null Warehouse location array or null if not applicable
     */
    public function getWarehouseLocation($selectedBagItems)
    {
        $locale = app()->getLocale();
        $warehouseLocation = null;

        $bagItemWithLocation = $selectedBagItems->first(function (ShoppingBagItem $bagItem) {
            return $bagItem->store && $bagItem->store->warehouse;
        });

        if ($bagItemWithLocation && $bagItemWithLocation->store && $bagItemWithLocation->store->warehouse) {
            $store    = $bagItemWithLocation->store;
            $location = $store->warehouse;

            $warehouseLocation = [
                'store_id'      => $store->id,
                'store_name'    => $store->name,
                'store_slug'    => $store->slug,
                'location_name' => $location->name,
                'address'       => $location->address,
                'subdistrict_id' => $store->warehouse?->master_address_id,
                'subdistrict_name' => $store->warehouse?->master_address?->subdistrict_name,
                'district_name' => $store->warehouse?->master_address?->district_name,
                'city_name' => $store->warehouse?->master_address?->city_name,
                'province_name' => $store->warehouse?->master_address?->province_name,
                'phone'         => $store->phone,
                'email'         => $store->email,
                'postal_code' => $store->warehouse?->postal_code
            ];
        }

        return $warehouseLocation;
    }

    /**
     * Get user address payload for checkout.
     * Priority: CheckoutSession address > default address.
     *
     * @param int $userId User ID
     * @return array|null Address payload array or null if no address found
     */
    public function getUserAddressPayload(int $userId)
    {
        $checkoutSession = CheckoutSession::where('user_id', $userId)->first();

        // Priority 1: Address stored in CheckoutSession (userAddress relation)
        if ($checkoutSession && $checkoutSession->userAddress) {
            $address = $checkoutSession->userAddress;
        } else {
            // Priority 2: Default address in user_addresses
            $address = UserAddress::where('user_id', $userId)
                ->where('is_default', true)
                ->first();
        }

        if (!$address) {
            return null;
        }

        return [
            'id'                => $address->id,
            'receiver_name'     => $address->receiver_name,
            'phone_number'      => $address->phone_number,
            'label'             => $address->label,
            'address_line'      => $address->address_line,
            'province'          => $address->masterAddress?->province_name,
            'city'              => $address->masterAddress?->city_name,
            'district'          => $address->masterAddress?->district_name,
            'subdistrict'       => $address->masterAddress?->subdistrict_name,
            'subdistrict_id'    => $address->subdistrict_id,
            'postal_code'       => $address->postal_code,
            'is_default'        => (bool) $address->is_default,
        ];
    }

    /**
     * Get active courier data and embed pickup_location (if any).
     *
     * @param array|null $pickupLocation Pickup location array or null
     * @return array|null Courier payload array with pickup_location or null if no active courier
     */
    public function getCourierPayload(int $userId, ?array $pickupLocation = null)
    {
        $currentSession = CheckoutSession::where('user_id', $userId)->with('courier')->first();
        $courierModel = $currentSession->courier ?? Courier::where('key', 'delivery-sarinah')->first();
        if (!$courierModel) return null;

        return $courierModel ? [
            'id' => $courierModel->id,
            'name' => $courierModel->name,
            'is_active' => (bool) $courierModel->is_active,
            'type' => $courierModel->key,
            'shipment' => $checkoutSession->delivery_payload ?? null,
            'pickup_location' => $pickupLocation
        ] : null;
    }

    /**
     * Build order summary data from shopping bag snapshot.
     *
     * @param array $bagSnapshot Shopping bag snapshot array
     * @return array Order summary array with formatted values
     */
    public function getOrderSummary(array $bagSnapshot, $courierPayload): array
    {
        $summary = $bagSnapshot['summary'] ?? [
            'selected_items_count'     => 0,
            'selected_items_quantity'  => 0,
            'subtotal'                 => 0,
            'discount'                 => 0,
            'grand_total'              => 0,
        ];

        $subtotalRaw = PriceFormatter::parseMoneyStringToInt($summary['subtotal']);  // Before discount
        $discountRaw = PriceFormatter::parseMoneyStringToInt($summary['discount']);  // Total discount
        $deliveryFeeRaw = PriceFormatter::parseMoneyStringToInt($courierPayload['shipment']['fee'] ?? 0);  // Total discount

        $hasSummaryItems = ($summary['selected_items_count'] ?? 0) > 0;

        // Hard-coded delivery and other fees
        $deliveryLabel  = ($deliveryFeeRaw > 0) ? null : 'Free';
        $otherFeesRaw = $hasSummaryItems ? 0 : 0;

        $totalRaw = max(0, $subtotalRaw - $discountRaw + $deliveryFeeRaw + $otherFeesRaw);
        $totalUnitRaw = max(0, $subtotalRaw - $discountRaw);

        $summaryDiscountPercent = 0.0;
        if ($subtotalRaw > 0 && $discountRaw > 0) {
            $summaryDiscountPercent = ($discountRaw / $subtotalRaw) * 100;
        }

        // Cek apakah ada item yang memiliki has_special_price true
        $hasSpecialPrice = collect($bagSnapshot['groups'])->flatMap(function ($group) {
            return isset($group['brands']) 
                ? collect($group['brands'])->flatMap(fn ($brand) => $brand['items'] ?? [])
                : [];
        })->contains(fn ($item) => $item['has_special_price'] === true && $item['is_selected'] === true);

        return [
            'subtotal_items'    => $summary['selected_items_count'],
            'subtotal_quantity' => $summary['selected_items_quantity'],
            'subtotal'          => number_format($subtotalRaw, 0, ',', '.'),
            'discount'          => number_format($discountRaw, 0, ',', '.'),
            'discount_percent'  => $summaryDiscountPercent > 0
                ? PriceFormatter::formatPercentage($summaryDiscountPercent)
                : '0%',
            'delivery_fee'      => number_format($deliveryFeeRaw, 0, ',', '.'),
            'delivery_label'    => $deliveryLabel,
            'other_fees'        => number_format($otherFeesRaw, 0, ',', '.'),
            'other_fees_raw'    => $otherFeesRaw,
            'total'             => number_format($totalRaw, 0, ',', '.'),
            'total_raw'         => $totalRaw,
            'total_unit_raw'    => $totalUnitRaw,
            'has_special_price' => $hasSpecialPrice,
            'insurance_raw'     => (string) ($totalRaw * (config('lazop.insurance') / 100)) // no separator
        ];
    }

    /**
     * Attach voucher info from CheckoutSession (if any) into checkout snapshot.
     * Uses VoucherService::validateForCart() to keep rules consistent
     * with applyVoucher() and OrderController.
     *
     * @param int $userId User ID
     * @param string $locale Locale string (e.g., 'en', 'id')
     * @param array $bagSnapshot Shopping bag snapshot array
     * @param array $checkoutSnapshot Checkout snapshot array (will be modified)
     * @return array Updated checkout snapshot with voucher info if applicable
     */
    public function attachVoucherFromSessionIfAny(int $userId, string $locale, array $bagSnapshot, array $checkoutSnapshot): array
    {
        /** @var CheckoutSession|null $checkoutSession */
        $checkoutSession = CheckoutSession::where('user_id', $userId)->first();

        // No checkout session or no voucher applied
        if (! $checkoutSession || ! $checkoutSession->voucher_id) {
            return $checkoutSnapshot;
        }

        // Load voucher from DB (with required relations for payload)
        /** @var Voucher|null $voucher */
        $voucher = Voucher::with(['translations', 'files'])
            ->find($checkoutSession->voucher_id);

        if (!$voucher) {
            $checkoutSession->update([
                'voucher_id'              => null,
                'voucher_code'            => null,
                'voucher_discount_amount' => null,
            ]);
            return $checkoutSnapshot;
        }

        // Base amount is the same as in applyVoucher():
        // summary.grand_total from shopping bag snapshot = items total after special price
        // (before delivery fee and other fees).
        $itemsTotalAfterSpecialRaw = PriceFormatter::parseMoneyStringToInt(
            $bagSnapshot['summary']['grand_total'] ?? '0'
        );

        if ($itemsTotalAfterSpecialRaw <= 0) {
            return $checkoutSnapshot;
        }

        // Use a single validation source for cart rules
        $validation = $this->voucherService->validateForCart(
            $voucher,
            $itemsTotalAfterSpecialRaw
        );

        if (!($validation['ok'] ?? false)) {
            // At this point voucher is considered invalid for the current cart:
            // - not active / expired / not started
            // - usage_limit reached
            // - min_transaction_amount not met
            // - calculated discount is 0
            //
            // Optionally: also clear voucher from checkout session:
            $checkoutSession->update([
                'voucher_id'              => null,
                'voucher_code'            => null,
                'voucher_discount_amount' => null,
            ]);
            return $checkoutSnapshot;
        }

        $voucherDiscountRaw = (int) ($validation['discount'] ?? 0);
        if ($voucherDiscountRaw <= 0) {
            return $checkoutSnapshot;
        }

        // Keep stored discount in session consistent with recalculated discount
        if ((int) $checkoutSession->voucher_discount_amount !== $voucherDiscountRaw) {
            $checkoutSession->voucher_discount_amount = $voucherDiscountRaw;
            $checkoutSession->save();
        }

        // Adjust order_summary.total and inject voucher discount info
        $orderSummary = $checkoutSnapshot['order_summary'] ?? [];

        $totalRawBefore = isset($orderSummary['total'])
            ? PriceFormatter::parseMoneyStringToInt($orderSummary['total'])
            : 0;

        $totalRawAfter = max(0, $totalRawBefore - $voucherDiscountRaw);

        $orderSummary['total']                = PriceFormatter::formatMoney($totalRawAfter);
        $orderSummary['total_raw']            = $totalRawAfter;
        $orderSummary['voucher_discount_raw'] = $voucherDiscountRaw;
        $orderSummary['voucher_discount']     = PriceFormatter::formatMoney($voucherDiscountRaw);

        $checkoutSnapshot['order_summary'] = $orderSummary;

        // Add voucher block to root payload (same structure as applyVoucher())
        $checkoutSnapshot['voucher'] = $this->voucherService->buildVoucherPayloadForApply(
            $voucher,
            $locale,
            $voucherDiscountRaw,
            $itemsTotalAfterSpecialRaw
        );

        return $checkoutSnapshot;
    }
}