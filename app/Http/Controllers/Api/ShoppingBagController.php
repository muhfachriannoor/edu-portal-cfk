<?php

namespace App\Http\Controllers\Api;

use App\Models\Courier;
use App\Models\Voucher;
use App\Models\UserAddress;
use App\Services\LazadaApi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ProductVariant;
use App\Models\CheckoutSession;
use App\Models\ShoppingBagItem;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\ShoppingBagService;
use App\Services\Support\PriceFormatter;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UpdateShoppingBagRequest;
use App\Http\Requests\AddOrUpdateShoppingBagRequest;

/**
 * @OA\Tag(
 *      name="Shopping Bag",
 *      description="Endpoints for managing the user's shopping bag (Cart)."
 * )
 *
 * @OA\Schema(
 *      schema="ShoppingBagItemResponse",
 *      title="Shopping Bag Item Details",
 *      type="object",
 *
 *      @OA\Property(property="id", type="integer", example=10, description="Shopping Bag Item ID."),
 *      @OA\Property(property="product_variant_id", type="integer", example=101, description="ID of the selected product variant."),
 *      @OA\Property(property="product_name", type="string", example="Batik Premium Product Test 1"),
 *      @OA\Property(property="variant_details", type="string", example="Black / L (Large)", description="Readable combination of option values."),
 *      @OA\Property(property="image_url", type="string", format="url", example="http://localhost/storage/path/image.jpg", nullable=true),
 *      @OA\Property(property="quantity_in_bag", type="integer", example=3, description="Quantity the user has in the cart."),
 *      @OA\Property(property="base_unit_price", type="string", example="30.000", description="Base unit price before any special price."),
 *      @OA\Property(property="final_unit_price", type="string", example="25.000", description="Final price per unit (after special price)."),
 *      @OA\Property(property="item_subtotal", type="string", example="75.000", description="Subtotal price for this item (final_unit_price * quantity)."),
 *      @OA\Property(property="has_special_price", type="boolean", example=true),
 *      @OA\Property(property="discount_amount", type="string", example="5.000", description="Absolute discount amount per unit if special price is applied."),
 *      @OA\Property(property="discount_percent", type="string", example="10%",nullable=true,description="Discount percentage per unit if special price is applied."),
 *      @OA\Property(
 *          property="is_selected",
 *          type="boolean",
 *          example=true,
 *          description="Whether this item is selected for checkout and included in brand_subtotal and summary.grand_total."
 *      ),
 *      @OA\Property(
 *          property="is_available_for_checkout",
 *          type="boolean",
 *          example=true,
 *          description="Real-time stock flag: false if quantity_in_bag exceeds live stock or stock is zero."
 *      ),
 *      @OA\Property(
 *          property="max_available_quantity",
 *          type="integer",
 *          example=10,
 *          description="Real-time stock level available."
 *      ),
 *      @OA\Property(
 *          property="warning_message",
 *          type="string",
 *          example="Only 5 item(s) left in stock.",
 *          nullable=true,
 *          description="Warning message if stock is low or insufficient."
 *      )
 * )
 *
 * @OA\Schema(
 *      schema="ShoppingBagBrandGroup",
 *      title="Shopping Bag Brand Group",
 *      type="object",
 *      @OA\Property(property="brand_id", type="integer", example=1),
 *      @OA\Property(property="brand_name", type="string", example="Arinex"),
 *      @OA\Property(
 *          property="brand_subtotal",
 *          type="string",
 *          example="200.000",
 *          description="Total price for selected items in this brand group (after discount)."
 *      ),
 *      @OA\Property(
 *          property="items",
 *          type="array",
 *          @OA\Items(ref="#/components/schemas/ShoppingBagItemResponse")
 *      )
 * )
 *
 * @OA\Schema(
 *      schema="ShoppingBagSummary",
 *      title="Shopping Bag Summary",
 *      type="object",
 *      @OA\Property(
 *          property="selected_items_count",
 *          type="integer",
 *          example=2,
 *          description="Number of selected line items (rows with is_selected = true)."
 *      ),
 *      @OA\Property(
 *          property="selected_items_quantity",
 *          type="integer",
 *          example=3,
 *          description="Total quantity across all selected items."
 *      ),
 *      @OA\Property(
 *          property="subtotal",
 *          type="string",
 *          example="768.000",
 *          description="Subtotal before discount for all selected items."
 *      ),
 *      @OA\Property(
 *          property="discount",
 *          type="string",
 *          example="334.000",
 *          description="Total discount amount applied to selected items."
 *      ),
 *      @OA\Property(
 *          property="discount_percent",
 *          type="string",
 *          example="10%",
 *          description="Total discount percentage across selected items (discount / subtotal * 100)."
 *      ),
 *      @OA\Property(
 *          property="grand_total",
 *          type="string",
 *          example="434.000",
 *          description="Total to be paid after discount for selected items."
 *      ),
 *      @OA\Property(
 *          property="has_special_price",
 *          type="boolean",
 *          example=true,
 *          description="Indicates whether there are any items with special prices in the shopping bag."
 *      ),
 *      @OA\Property(
 *          property="total_items",
 *          type="integer",
 *          example=3,
 *          description="Total number of items in the shopping bag (selected + unselected, including non-checkoutable items)."
 *      ),
 *      @OA\Property(
 *          property="total_selectable_items",
 *          type="integer",
 *          example=2,
 *          description="Number of items that are eligible for checkout (is_available_for_checkout = true)."
 *      ),
 *      @OA\Property(
 *          property="selected_selectable_items",
 *          type="integer",
 *          example=1,
 *          description="Number of selectable items that are currently selected (subset of total_selectable_items)."
 *      ),
 *      @OA\Property(
 *          property="select_all_state",
 *          type="string",
 *          example="partial",
 *          description="Select-all state: 'none', 'all', 'partial', or 'disabled'."
 *      )
 * )
 *
 * @OA\Schema(
 *      schema="ShoppingBagSnapshot",
 *      title="Shopping Bag Snapshot",
 *      type="object",
 *      @OA\Property(
 *          property="groups",
 *          type="array",
 *          description="Shopping bag items grouped by brand.",
 *          @OA\Items(ref="#/components/schemas/ShoppingBagBrandGroup")
 *      ),
 *      @OA\Property(property="user_profile_complete", type="boolean", example="true", description="Indicates whether the user's profile is complete. This is determined based on the availability of the user's name, title, mobile number, and date of birth."),
 *      @OA\Property(
 *          property="summary",
 *          ref="#/components/schemas/ShoppingBagSummary"
 *      )
 * )
 * 
 * @OA\Schema(
 *      schema="CheckoutVoucher",
 *      title="Checkout Voucher",
 *      type="object",
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="code", type="string", example="WELCOME10"),
 *      @OA\Property(property="title", type="string", example="Welcome 10% Off"),
 *      @OA\Property(property="type", type="string", example="percentage", description="Voucher type: 'percentage' or 'fixed_amount'."),
 *      @OA\Property(property="amount", type="string", example="10%", description="Display label for voucher amount (e.g. '10%' or '50.000')."),
 *      @OA\Property(property="amount_raw", type="integer", example=10, description="Raw numeric value for voucher amount (percentage or fixed amount)."),
 *      @OA\Property(property="min_transaction_amount", type="integer", nullable=true, example=500000),
 *      @OA\Property(property="min_transaction_amount_label", type="string", nullable=true, example="500.000"),
 *      @OA\Property(property="max_discount_amount", type="integer", nullable=true, example=50000),
 *      @OA\Property(property="max_discount_amount_label", type="string", nullable=true, example="50.000"),
 *      @OA\Property(property="start_date", type="string", format="date-time", nullable=true, example="2025-01-01 00:00:00"),
 *      @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example="2025-12-31 23:59:59"),
 *      @OA\Property(property="expiration_date_label", type="string", nullable=true, example="31/12/2025"),
 *      @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/voucher.png"),
 *      @OA\Property(property="discount_raw", type="integer", example=50000, description="Discount amount applied to current order in smallest currency unit."),
 *      @OA\Property(property="discount", type="string", example="50.000"),
 *      @OA\Property(property="applied_to_amount_raw", type="integer", example=250000),
 *      @OA\Property(property="applied_to_amount_label", type="string", example="250.000")
 * )
 * 
 * @OA\Schema(
 *      schema="CheckoutOrderSummary",
 *      title="Checkout Order Summary",
 *      type="object",
 *      @OA\Property(property="subtotal_items", type="integer", example=3),
 *      @OA\Property(property="subtotal_quantity", type="integer", example=5),
 *      @OA\Property(property="subtotal", type="string", example="4.050.000"),
 *      @OA\Property(property="discount", type="string", example="150.000"),
 *      @OA\Property(property="discount_percent", type="string", example="3.7%"),
 *      @OA\Property(property="delivery_fee", type="string", example="0"),
 *      @OA\Property(property="delivery_label", type="string", example="Free"),
 *      @OA\Property(property="other_fees", type="string", example="2.000"),
 *      @OA\Property(property="other_fees_raw", type="integer", example=2000),
 *      @OA\Property(property="total", type="string", example="3.902.000"),
 *      @OA\Property(property="total_raw", type="integer", example=3902000),
 *      @OA\Property(property="has_special_price", type="boolean", example=true),
 *      @OA\Property(
 *          property="voucher_discount_raw",
 *          type="integer",
 *          example=50000,
 *          nullable=true,
 *          description="Voucher discount in smallest currency unit. Present only if voucher is applied."
 *      ),
 *      @OA\Property(
 *          property="voucher_discount",
 *          type="string",
 *          example="50.000",
 *          nullable=true,
 *          description="Formatted voucher discount. Present only if voucher is applied."
 *      )
 * )
 *
 * @OA\Schema(
 *      schema="CheckoutUserAddress",
 *      title="Checkout User Address",
 *      type="object",
 *      nullable=true,
 *      @OA\Property(property="id", type="integer", example=2),
 *      @OA\Property(property="receiver_name", type="string", example="Natalie Wiyoko"),
 *      @OA\Property(property="phone_number", type="string", example="6281234567890"),
 *      @OA\Property(property="label", type="string", example="Home"),
 *      @OA\Property(property="address_line", type="string", example="Jl. Mawar Indah No. 5, RT 03 RW 08"),
 *      @OA\Property(property="city", type="string", example="Jakarta"),
 *      @OA\Property(property="province", type="string", example="DKI Jakarta"),
 *      @OA\Property(property="postal_code", type="string", example="12345"),
 *      @OA\Property(property="is_default", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *      schema="CheckoutPickupLocation",
 *      title="Checkout Pickup Location",
 *      type="object",
 *      nullable=true,
 *      @OA\Property(property="store_id", type="integer", example=1),
 *      @OA\Property(property="store_slug", type="string", example="zone-store"),
 *      @OA\Property(property="location_name", type="string", example="Sarinah"),
 *      @OA\Property(property="address", type="string", example="Kantor Pusat PT Sarinah Jl. M.H. Thamrin No. 11"),
 *      @OA\Property(property="city", type="string", example="Jakarta"),
 *      @OA\Property(property="type_label", type="string", example="Sarinah Department Store"),
 *      @OA\Property(property="phone", type="string", example="12345678"),
 *      @OA\Property(property="email", type="string", example="admin@unictive.net")
 * )
 *
 * @OA\Schema(
 *      schema="CheckoutCourier",
 *      title="Checkout Courier",
 *      type="object",
 *      nullable=true,
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="name", type="string", example="Pick up in store"),
 *      @OA\Property(property="is_active", type="boolean", example=true),
 *      @OA\Property(property="type", type="string", example="pickup"),
 *      @OA\Property(
 *          property="pickup_location",
 *          ref="#/components/schemas/CheckoutPickupLocation",
 *          nullable=true
 *      )
 * )
 *
 * @OA\Schema(
 *      schema="CheckoutSnapshot",
 *      title="Checkout Snapshot",
 *      type="object",
 *      @OA\Property(
 *          property="groups",
 *          type="array",
 *          description="Selected shopping bag items grouped by brand.",
 *          @OA\Items(ref="#/components/schemas/ShoppingBagBrandGroup")
 *      ),
 *      @OA\Property(
 *          property="user_address",
 *          ref="#/components/schemas/CheckoutUserAddress"
 *      ),
 *      @OA\Property(
 *          property="couriers",
 *          ref="#/components/schemas/CheckoutCourier"
 *      ),
 *      @OA\Property(
 *          property="order_summary",
 *          ref="#/components/schemas/CheckoutOrderSummary"
 *      ),
 *      @OA\Property(
 *          property="voucher",
 *          ref="#/components/schemas/CheckoutVoucher",
 *          nullable=true,
 *          description="Present only when a voucher is applied."
 *      )
 * )
 */
class ShoppingBagController extends Controller
{
    use LazadaApi;
    
    private $voucherService;
    private $shoppingBagService;

    public function __construct(VoucherService $voucherService, ShoppingBagService $shoppingBagService)
    {
        $this->voucherService = $voucherService;
        $this->shoppingBagService = $shoppingBagService;
    }

    /**
     * Create or update the user address in the CheckoutSession.
     *
     * @param int $userId User ID
     * @param int $addressId Address ID to set in checkout session
     * @return CheckoutSession Updated or created checkout session
     * @throws \Exception If address not found or does not belong to user
     */
    private function createOrUpdateCheckoutSessionAddress(int $userId, int $addressId, array $snapshot = []): CheckoutSession
    {
        // Check if the CheckoutSession already exists
        $checkoutSession = CheckoutSession::where('user_id', $userId)->first();

        if (!$checkoutSession) {
            // Create a new CheckoutSession if it does not exist
            $checkoutSession = new CheckoutSession();
            $checkoutSession->user_id = $userId;
        }

        // Find the user address by the provided addressId and ensure it belongs to the user
        $userAddress = UserAddress::where('id', $addressId)
            ->where('user_id', $userId)  // Ensure the address belongs to the current user
            ->first();

        if ($userAddress) {
            // Update the CheckoutSession with the new address
            $checkoutSession->user_address_id = $userAddress->id;
            $checkoutSession->save();
        } else {
            // If the address does not exist or does not belong to the user, throw an error
            throw new \Exception('Address not found or does not belong to the current user.');
        }

        return $checkoutSession;
    }

    /**
     * Check new shipping fee and generate delivery payload only if courier type is 'delivery'.
     *
     * @param $userAddress User Address
     * @param $snapshot Address ID to set in checkout session
     * @return ?array New delivery first lists or null
     * @throws \Exception If Lazada API not connected
     */
    private function generateDeliveryPayload(UserAddress $userAddress, array $snapshot): ?array
    {
        // Only calculate if shopping bags has items and courier is 'delivery'
        if( count($snapshot['groups']) > 0 && ($snapshot['couriers']['type'] == 'delivery-lazada') ){
            $lazadaOp = $this->getShippingFee($snapshot, $userAddress);

            if(!$lazadaOp)
                throw new \Exception("Lazada API not connected");

            if ($lazadaOp?->success === true) {
                $dataShippingFee = $this->calculateShippingFee($lazadaOp);

                $selected = collect($dataShippingFee)->first();

                return $selected;
            } else {
                throw new \Exception($lazadaOp->errorMessage);
            }
        }

        return null;
    }
    
    /**
     * Create or update the selected courier in the CheckoutSession.
     *
     * @param int $userId User ID
     * @param int $addressId Address ID to set in checkout session
     * @return CheckoutSession Updated or created checkout session
     * @throws \Exception If address not found or does not belong to user
     */
    private function createOrUpdateCheckoutSessionCourier(int $userId, string $courierType, $deliveryPayload = null): CheckoutSession
    {
        // Check if the CheckoutSession already exists
        $checkoutSession = CheckoutSession::where('user_id', $userId)->first();

        if (!$checkoutSession) {
            // Create a new CheckoutSession if it does not exist
            $checkoutSession = new CheckoutSession();
            $checkoutSession->user_id = $userId;
        }

        // Find the courier key by the provided couriers
        $courier = Courier::where('key', $courierType)
            ->first();

        if ($courier) {
            // Update the CheckoutSession with the selected courier
            $checkoutSession->courier_id = $courier->id;
            $checkoutSession->save();
        } else {
            // If the courier does not exist or does not belong to the user, throw an error
            throw new \Exception('Courier not found.');
        }

        return $checkoutSession;
    }

    /**
     * Create or update the CheckoutSession voucher fields.
     *
     * @param int $userId User ID
     * @param Voucher $voucher Voucher model instance
     * @param int $voucherDiscountRaw Voucher discount amount in smallest currency unit
     * @return CheckoutSession Updated or created checkout session
     */
    private function createOrUpdateCheckoutSessionVoucher(int $userId, Voucher $voucher, int $voucherDiscountRaw): CheckoutSession
    {
        $checkoutSession = CheckoutSession::firstOrNew(['user_id' => $userId]);

        // Set default address if not already set
        if (empty($checkoutSession->user_address_id)) {
            $defaultAddress = UserAddress::where('user_id', $userId)
                ->where('is_default', true)
                ->first();

            if ($defaultAddress) {
                $checkoutSession->user_address_id = $defaultAddress->id;
            }
        }

        $checkoutSession->voucher_id = $voucher->id;
        $checkoutSession->voucher_code = $voucher->voucher_code;
        $checkoutSession->voucher_discount_amount = $voucherDiscountRaw;

        $checkoutSession->save();

        return $checkoutSession;
    }

    /**
     * @OA\Get(
     *      path="/shopping-bag",
     *      operationId="getShoppingBag",
     *      tags={"Shopping Bag"},
     *      summary="Retrieve the current user's shopping bag items with real-time stock and price check, grouped by brand, including base and final price.",
     *      description="brand_subtotal and summary.grand_total are calculated using only items where is_selected = true.",
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Response(
     *          response=200,
     *          description="Shopping bag successfully retrieved.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Shopping bag items retrieved successfully."),
     *              @OA\Property(
     *                  property="data",
     *                  ref="#/components/schemas/ShoppingBagSnapshot"
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getShoppingBag(): JsonResponse
    {
        $userId = auth()->id();
        $locale = app()->getLocale();
        
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapShot($userId, $locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.shopping_bag.retrieve_success'),
            'data' => $snapshot,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/shopping-bag",
     *     operationId="addItemToShoppingBag",
     *     tags={"Shopping Bag"},
     *     summary="Adds a product variant to the bag or updates quantity.",
     *     description="Handles adding items with stock validation. Supports 'Buy Now' mode which unselects all other items and prioritizes delivery fulfillment.",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="product_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID of the parent product."
     *             ),
     *             @OA\Property(
     *                 property="option_value_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 5},
     *                 description="Array of selected product_option_value IDs (e.g., Color ID, Size ID)."
     *             ),
     *             @OA\Property(
     *                 property="quantity",
     *                 type="integer",
     *                 example=1,
     *                 description="Quantity to add to the cart."
     *             ),
     *             @OA\Property(
     *                 property="buy_now",
     *                 type="boolean",
     *                 example=false,
     *                 description="If true, unselects all other items in the bag and syncs fulfillment session to delivery (priority) or pickup."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product successfully added or quantity updated. Returns the updated shopping bag snapshot.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product successfully added to the shopping bag."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/ShoppingBagSnapshot"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=400, description="Requested quantity exceeds available stock."),
     *     @OA\Response(response=404, description="Selected product variant combination is invalid or not found."),
     *     @OA\Response(response=422, description="Validation failed.")
     * )
     */
    public function store(AddOrUpdateShoppingBagRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $user = auth()->user();
        $validatedData = $request->validated();
        $newQuantity = (int) $validatedData['quantity'];
        $productId = $validatedData['product_id'];
        $isBuyNow = filter_var($validatedData['buy_now'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Cek User Profile jika $isBuyNow true
        if ($isBuyNow) {
            $userProfileComplete = !empty($user->name) 
                && !empty($user->title) 
                && !empty($user->mobile_number) 
                && !empty($user->date_of_birth);

            if (!$userProfileComplete) {
                return response()->json([
                    'success' => false,
                    'message' => trans('api.order.user_profile_validation'),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Step 1: Rebuild combination and find variant
        $optionValueIds = $validatedData['option_value_ids'] ?? [];
        sort($optionValueIds);

        $variant = null;

        $baseQuery = ProductVariant::where('product_id', $productId)
            ->with('product.store');

        if (empty($optionValueIds)) {
            // Case 1: Non-variant product
            $variant = $baseQuery->where(function ($query) {
                $query->whereNull('combination')
                    ->orWhere('combination', '[]')
                    ->orWhere('combination', '');
            })->first();
        } else {
            // Case 2: Variant product
            $countOptions = count($optionValueIds);
            $initialQuery = $baseQuery->whereJsonContains('combination', $optionValueIds)
                ->get();

            $variant = $initialQuery->filter(function ($v) use ($countOptions) {
                $combinationArray = $v->combination;
                return is_array($combinationArray) && count($combinationArray) === $countOptions;
            })->first();
        }

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.product_variant_validation'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $store = $variant->product->store;
        $storeId = $store->id ?? null;
        $brandId = $variant->product->brand_id;

        if (!$storeId) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.product_not_have_store_validation'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $variantId = $variant->id;
        $liveStock = (int) $variant->quantity;

        if ($variant->is_active === false) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.product_variant_unavailable_validation'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($liveStock === 0) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.product_out_of_stock'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Step 2: Handle Selection Exclusivity
        if ($isBuyNow) {
            // Jika Buy Now: Matikan SEMUA centang di keranjang (global)
            ShoppingBagItem::where('user_id', $userId)->update(['is_selected' => false]);
        } else {
            // Jika Add to Cart biasa: Matikan centang dari toko lain saja
            $this->shoppingBagService->unselectItemsFromOtherStores($userId, $storeId);
        }

        // Step 3: Sync fulfillment session with the new store's capabilities
        $this->shoppingBagService->syncFulfillmentSession($userId, $store);

        // Step 4: Check for existing cart item from the same store
        $bagItem = ShoppingBagItem::where('user_id', $userId)
            ->where('store_id', $storeId)
            ->where('product_variant_id', $variantId)
            ->first();

        $oldQuantity = $bagItem ? (int) $bagItem->quantity : 0;
        $totalRequestedQuantity = $oldQuantity + $newQuantity;

        // Step 5: Ensure the requested quantity does not exceed the available stock
        if ($totalRequestedQuantity > $liveStock) {
            $availableToAdd = $liveStock - $oldQuantity;

            if ($availableToAdd <= 0) {
                $errorMessage = trans('api.shopping_bag.stock_reached', [
                    'stock' => $liveStock
                ]);
            } else {
                $errorMessage = trans('api.shopping_bag.stock_limit_exceeded', [
                    'available' => $availableToAdd,
                    'requested' => $totalRequestedQuantity,
                    'stock' => $liveStock
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();

        try {
            if ($bagItem) {
                $bagItem->quantity = $totalRequestedQuantity;
                $bagItem->is_selected = true;
                $bagItem->save();
                $message = trans('api.shopping_bag.update_success', ['total' => $totalRequestedQuantity]);
            } else {
                // Add new item to the shopping bag
                $bagItem = ShoppingBagItem::create([
                    'user_id' => $userId,
                    'store_id' => $storeId,
                    'brand_id' => $brandId,
                    'product_variant_id' => $variantId,
                    'quantity' => $totalRequestedQuantity,
                    'is_selected' => true,
                ]);

                $message = trans('api.shopping_bag.add_success', ['total' => $totalRequestedQuantity]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Shopping Bag Store failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.create_internal_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Build snapshot so response shape matches getShoppingBag()
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapShot($userId, app()->getLocale());

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $snapshot,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Put(
     *      path="/shopping-bag/{variant_id}",
     *      operationId="updateShoppingBagItem",
     *      tags={"Shopping Bag"},
     *      summary="Updates the quantity of an existing item in the shopping bag.",
     *      description="Sets the quantity to a new specific value. If quantity is 0, the item is deleted. This does not change the is_selected flag. For non-zero quantity, the response returns the updated shopping bag snapshot.",
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Parameter(
     *          name="variant_id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer", example=101),
     *          description="The product_variant_id of the item to update."
     *      ),
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="quantity", type="integer", example=5, description="The new desired quantity (0 to delete).")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Quantity successfully updated or item deleted. For non-zero quantity, returns the updated shopping bag snapshot.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Item quantity successfully updated."),
     *              @OA\Property(
     *                  property="data",
     *                  ref="#/components/schemas/ShoppingBagSnapshot",
     *                  nullable=true
     *              )
     *          )
     *      ),
     *      @OA\Response(response=400, description="Requested quantity exceeds available stock."),
     *      @OA\Response(response=404, description="Item not found in your shopping bag."),
     *      @OA\Response(response=422, description="Validation failed.")
     * )
     */
    public function update(UpdateShoppingBagRequest $request, int $variant_id): JsonResponse
    {
        $userId = auth()->id();
        $newQuantity = (int) $request->validated('quantity');

        $bagItem = ShoppingBagItem::where('user_id', $userId)
            ->where('product_variant_id', $variant_id)
            ->first();

        if (!$bagItem) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.item_not_found'),
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // If quantity is 0, delete the item
        if ($newQuantity === 0) {
            $bagItem->delete();

            return response()->json([
                'success' => true,
                'message' => trans('api.shopping_bag.item_remove_success'),
            ], Response::HTTP_OK);
        }
        
        // Validate stock for non-zero quantity
        $variant = ProductVariant::find($variant_id);
        
        if (!$variant) {
            $bagItem->delete();

            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.product_variant_exists'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $liveStock = (int) $variant->quantity;

        if ($newQuantity > $liveStock) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.exceeds_stock', ['stock' => $liveStock]),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Update quantity
        $bagItem->quantity = $newQuantity;
        $bagItem->save();

        $snapshot = $this->shoppingBagService->buildShoppingBagSnapShot($userId, app()->getLocale());

        return response()->json([
            'success' => true,
            'message' => trans('api.shopping_bag.item_update_success'),
            'data' => $snapshot,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *      path="/shopping-bag/{variant_id}",
     *      operationId="deleteShoppingBagItem",
     *      tags={"Shopping Bag"},
     *      summary="Removes an item from the shopping bag and returns the updated snapshot.",
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Parameter(
     *          name="variant_id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer", example=101),
     *          description="The product_variant_id of the item to delete."
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Item successfully removed and updated shopping bag snapshot returned.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Item successfully removed from the shopping bag."),
     *              @OA\Property(
     *                  property="data",
     *                  ref="#/components/schemas/ShoppingBagSnapshot"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Item not found in your shopping bag.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Item not found in your shopping bag.")
     *          )
     *      )
     * )
     */
    public function destroy(int $variant_id): JsonResponse
    {
        $userId = auth()->id();

        $deleted = ShoppingBagItem::where('user_id', $userId)
            ->where('product_variant_id', $variant_id)
            ->delete();

        if ($deleted) {
            $snapshot = $this->shoppingBagService->buildShoppingBagSnapShot($userId, app()->getLocale());

            return response()->json([
                'success' => true,
                'message' => trans('api.shopping_bag.item_remove_success'),
                'data' => $snapshot,
            ], Response::HTTP_OK);
        }

        return response()->json([
            'success' => false,
            'message' => trans('api.shopping_bag.item_not_found'),
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @OA\Patch(
     *      path="/shopping-bag/{id}/toggle-selected",
     *      operationId="toggleShoppingBagItemSelection",
     *      tags={"Shopping Bag"},
     *      summary="Toggle the is_selected flag of a shopping bag item and return the updated cart snapshot.",
     *      description="Flips is_selected for a single ShoppingBagItem (true -> false, false -> true). The response includes the same structure as GET /shopping-bag, and summary.grand_total is recalculated using only items with is_selected = true.",
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer", example=10),
     *          description="Shopping bag item ID to toggle selection for."
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Selection toggled and shopping bag snapshot returned.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Shopping bag selection updated successfully."),
     *              @OA\Property(
     *                  property="data",
     *                  ref="#/components/schemas/ShoppingBagSnapshot"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Shopping bag item not found.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Shopping bag item not found.")
     *          )
     *      )
     * )
     */
    public function toggleSelection(int $id): JsonResponse
    {
        $userId = auth()->id();
        $locale = app()->getLocale();
        $errorMessage = null;

        // 1. Fetch item with eager loading to prevent N+1 queries
        $bagItem = ShoppingBagItem::where('user_id', $userId)
            ->where('id', $id)
            ->with(['productVariant', 'store'])
            ->first();
        
        if (!$bagItem) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.not_found'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $variant = $bagItem->productVariant;
        $targetSelectedState = !(bool) $bagItem->is_selected;
        
        // 2. Layered validation logic
        if (!$variant) {
            // Force unselect if variant is missing from the database
            if ($bagItem->is_selected) $bagItem->update(['is_selected' => false]);
            $errorMessage = trans('api.shopping_bag.product_variant_unavailable_and_unselected_validation');
        } elseif ($targetSelectedState) {
            // Check real-time stock availability
            if ((int) ($variant->quantity ?? 0) < (int) $bagItem->quantity) {
                $errorMessage = trans('api.shopping_bag.item_out_of_stock');
            } elseif (!$this->shoppingBagService->validateStoreSelection($userId, (int) $bagItem->store_id, true, $bagItem->id)) {
                // Validate single-store checkout rule
                return response()->json([
                    'success' => false,
                    'message' => trans('api.shopping_bag.select_only_one_store'),
                ], Response::HTTP_BAD_REQUEST);
            } else {
                // Check fulfillment method compatibility (Delivery vs Pickup)
                $session = CheckoutSession::where('user_id', $userId)->with('courier')->first();
                $currentType = ($session && $session->courier && $session->courier->key === 'pickup') ? 'pickup' : 'delivery';

                if (!$this->shoppingBagService->isItemSupportMethod($bagItem, $currentType)) {
                    $errorMessage = trans('api.shopping_bag.type_not_supported', [
                        'type' => $currentType
                    ]);
                }
            }
        }

        // 3. Perform update only if no validation errors occurred
        if (!$errorMessage) {
            $bagItem->update(['is_selected' => $targetSelectedState]);
        }

        // 4. Generate final shapshot (Single call for better performance)
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapShot($userId, $locale);

        return response()->json([
            'success' => $errorMessage ? false : true,
            'message' => $errorMessage ?? trans('api.shopping_bag.update_item_success'),
            'data' => $snapshot,
        ], $errorMessage ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK);
    }

    /**
     * @OA\Patch(
     *      path="/shopping-bag/toggle-selected",
     *      operationId="bulkToggleShoppingBagSelection",
     *      tags={"Shopping Bag"},
     *      summary="Bulk update is_selected flag for all selectable shopping bag items of the current user based on store or brand.",
     *      description="Sets is_selected = true/false for all items that are eligible for checkout (based on real-time stock). The response includes the same structure as GET /shopping-bag, including select-all summary metadata.",
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="is_selected",
     *                  type="boolean",
     *                  example=true,
     *                  description="Target selection state for all selectable items: true to select all, false to unselect all."
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  example="store",
     *                  description="The type of selection: 'store' or 'brand'."
     *              ),
     *              @OA\Property(
     *                  property="store_id",
     *                  type="integer",
     *                  example=1,
     *                  description="ID of the store to select/unselect items."
     *              ),
     *              @OA\Property(
     *                  property="brand_id",
     *                  type="integer",
     *                  example=3,
     *                  description="ID of the brand to select/unselect items. Required if type is 'brand'."
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Bulk selection updated and shopping bag snapshot returned.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Shopping bag selection updated successfully."),
     *              @OA\Property(
     *                  property="data",
     *                  ref="#/components/schemas/ShoppingBagSnapshot"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation failed for request body.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Validation failed.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Validation failed for selection constraints.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="You cannot select items from a different store while another item from the same store is selected.")
     *          )
     *      )
     * )
     */
    public function bulkToggleSelection(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $locale = app()->getLocale();

        $validated = $request->validate([
            'is_selected' => ['required', 'boolean'],
            'type' => ['required', 'in:store,brand'],
            'store_id' => ['required', 'integer'],
            'brand_id' => ['required_if:type,brand', 'integer'],
        ]);

        $targetSelectedState = (bool) $validated['is_selected'];
        $storeId = (int) $validated['store_id'];
        $brandId = isset($validated['brand_id']) ? (int) $validated['brand_id'] : null;

        // 1. Enforce single-store rule before proceding
        if (!$this->shoppingBagService->validateStoreSelection($userId, $storeId, $targetSelectedState)) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.select_only_one_store'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // 2. Get current fulfillment method to check compatibility
        $session = CheckoutSession::where('user_id', $userId)->with('courier')->first();
        $currentMethod = ($session && $session->courier && $session->courier->key === 'pickup') ? 'pickup' : 'delivery';

        // 3. Fetch all affected items with their variants and store info
        $bagItems = ShoppingBagItem::where('user_id', $userId)
            ->where('store_id', $storeId)
            ->when($validated['type'] === 'brand' && $brandId !== null, function ($query) use ($brandId) {
                return $query->where('brand_id', $brandId);
            })
            ->with(['productVariant', 'store'])
            ->get();

        $idsToSelect = [];
        $idsToUnselect = [];

        foreach ($bagItems as $item) {
            $variant = $item->productVariant;

            // Validation: Variant existence, Stock, and Fulfillment support
            $isAvailable = $variant && (int) $variant->quantity >= (int) $item->quantity && (int) $variant->quantity > 0;
            $isSupported = $this->shoppingBagService->isItemSupportMethod($item, $currentMethod);

            if ($targetSelectedState && $isAvailable && $isSupported) {
                $idsToSelect[] = $item->id;
            } else {
                // Force unselect if target is false, or if item fails validation
                $idsToUnselect[] = $item->id;
            }
        }

        // 4. Optimized bulk updates (only 2 queries instead of N queries)
        if (!empty($idsToSelect)) {
            ShoppingBagItem::whereIn('id', $idsToSelect)->update(['is_selected' => true]);
        }
        if (!empty($idsToUnselect)) {
            ShoppingBagItem::whereIn('id', $idsToUnselect)->update(['is_selected' => false]);
        }

        // 5. Build final snapshot (Single call)
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapShot($userId, $locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.shopping_bag.update_item_success'),
            'data' => $snapshot,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     * path="/shopping-bag/switch-type",
     * operationId="switchShoppingBagType",
     * tags={"Shopping Bag"},
     * summary="Switch between delivery or pickup method.",
     * description="Updates the global fulfillment method in the checkout session. Important: If the new method is not supported by a store, all items from that store will be automatically unselected (is_selected = false) and marked with is_mismatch = true.",
     * security={{"bearerAuth": {}}},
     *
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"type"},
     * @OA\Property(
     * property="type",
     * type="string",
     * enum={"delivery", "pickup"},
     * example="pickup",
     * description="The preferred fulfillment type."
     * )
     * )
     * ),
     *
     * @OA\Response(
     * response=200,
     * description="Method updated successfully. Returns updated snapshot with recalculated totals.",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Fulfillment method updated successfully."),
     * @OA\Property(
     * property="data",
     * ref="#/components/schemas/ShoppingBagSnapshot"
     * )
     * )
     * ),
     *
     * @OA\Response(
     * response=400,
     * description="Invalid method provided or method not found in database."
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated."
     * )
     * )
     */
    public function switchType(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:delivery,pickup'
        ]);

        $userId = auth()->id();
        $locale = app()->getLocale();

        $courierKey = ($request->type === 'delivery') ? 'delivery-sarinah' : 'pickup';
        $courier = Courier::where('key', $courierKey)->first();

        if (!$courier) {
            return response()->json([
                'message' => trans('api.shopping_bag.type_not_found')
            ], Response::HTTP_BAD_REQUEST);
        }

        CheckoutSession::updateOrCreate(
            ['user_id' => $userId],
            ['courier_id' => $courier->id]
        );

        $snapshot = $this->shoppingBagService->buildShoppingBagSnapShot($userId, $locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.shopping_bag.switch_type_success'),
            'data' => $snapshot
        ]);
    }

    /**
     * @OA\Get(
     *     path="/shopping-bag/checkout",
     *     operationId="getCheckoutData",
     *     tags={"Shopping Bag"},
     *     summary="Initialize or update checkout data with shipping method.",
     *     description="Updates the checkout session with the chosen shipping method (courier or pickup) and returns the current checkout snapshot.",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Checkout data retrieved successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Checkout data retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/CheckoutSnapshot"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No selected items in shopping bag for checkout.",
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function checkout(): JsonResponse
    {
        $userId = auth()->id();
        $locale = app()->getLocale();

        // Get snapshot for user with selected items only for checkout
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($userId, $locale);

        // Step 1: Check if there are selected items for checkout
        $selectedCount = $snapshot['order_summary']['subtotal_items'] ?? 0;

        if ($selectedCount < 1) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.checkout_no_items'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Step 2: Since there are selected items, groups[0] should exist.
        $storeId = isset($snapshot['groups'][0]['store_id'])
            ? (int) $snapshot['groups'][0]['store_id']
            : 0;

        // Only run single-store validation if storeId was successfully retrieved
        if ($storeId > 0 && $this->shoppingBagService->isAnotherStoreSelected($userId, $storeId)) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.select_item_only_one_store'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Step 3: Return response with structured checkout snapshot
        return response()->json([
            'success' => true,
            'message' => trans('api.shopping_bag.checkout_retrieve_success'),
            'data'    => $snapshot,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Patch(
     *     path="/shopping-bag/update-address",
     *     operationId="updateUserAddressCheckout",
     *     tags={"Shopping Bag"},
     *     summary="Update the user address in the checkout session.",
     *     description="Updates or creates a CheckoutSession with the selected user address. Returns the same checkout snapshot structure (groups, user_address, couriers, order_summary and optional voucher).",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="address_id", type="integer", example=2, description="ID of the selected user address to update.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User address successfully updated in the checkout session.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User address updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/CheckoutSnapshot"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Address ID is required or invalid."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Address not found or does not belong to the current user."
     *     )
     * )
     */
    public function updateUserAddress(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $locale = app()->getLocale();
        $addressId = (int) $request->input('address_id');

        // Get snapshot for user with selected items only for checkout
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($userId, $locale);

        // Step 1: Check if there are selected items for checkout
        $selectedCount = $snapshot['order_summary']['subtotal_items'] ?? 0;

        if ($selectedCount < 1) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.checkout_no_items'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Step 2: Since there are selected items, groups[0] should exist.
        $storeId = isset($snapshot['groups'][0]['store_id'])
            ? (int) $snapshot['groups'][0]['store_id']
            : null;

        if ($storeId !== null && $this->shoppingBagService->isAnotherStoreSelected($userId, $storeId)) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.select_item_only_one_store'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$addressId) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.validation_address'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Create or update the CheckoutSession address
        $checkoutSession = $this->createOrUpdateCheckoutSessionAddress($userId, $addressId, $snapshot);

        // Get the updated checkout data
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($userId, $locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.shopping_bag.address_update_success'),
            'data' => $snapshot,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/shopping-bag/apply-voucher",
     *     operationId="applyVoucherToShoppingBag",
     *     tags={"Shopping Bag"},
     *     summary="Apply a voucher code to current checkout session.",
     *     description="Validates voucher, stores it in CheckoutSession, and returns the checkout snapshot with voucher block and updated order_summary.total and voucher_discount fields.",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="voucher_code",
     *                 type="string",
     *                 example="WELCOME10",
     *                 description="Voucher code to apply (case-insensitive)."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Voucher applied successfully, returns same payload as /shopping-bag/checkout plus voucher block.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Voucher applied successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/CheckoutSnapshot"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Voucher invalid for current cart (no selected items, not yet valid, expired, min/max transaction not met or no discount for current order)."),
     *     @OA\Response(response=404, description="Voucher not found or inactive."),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function applyVoucher(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $locale = app()->getLocale();

        // Get snapshot for user with selected items only for checkout
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($userId, $locale);

        // Step 1: Check if there are selected items for checkout
        $selectedCount = $snapshot['order_summary']['subtotal_items'] ?? 0;

        if ($selectedCount < 1) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.checkout_no_items'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Step 2: Since there are selected items, groups[0] should exist.
        //          Still use isset for extra safety.
        $storeId = isset($snapshot['groups'][0]['store_id'])
            ? (int) $snapshot['groups'][0]['store_id']
            : null;

        if ($storeId !== null && $this->shoppingBagService->isAnotherStoreSelected($userId, $storeId)) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.select_item_only_one_store'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Step 3: Basic validation for input
        $validated = $request->validate([
            'voucher_code' => ['required', 'string', 'max:50'],
        ]);

        $voucherCode = strtoupper(trim($validated['voucher_code']));

        // Step 4: Load voucher with translations & files for title + image
        $voucher = Voucher::with(['translations', 'files'])
            ->where('voucher_code', $voucherCode)
            ->first();

        if (! $voucher) {
            return response()->json([
                'success' => false,
                'message' => trans('api.voucher.invalid_or_inactive'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Step 5:
        // grand_total from snapshot = total items after special price (before delivery & other fees)
        $itemsTotalAfterSpecialRaw = PriceFormatter::parseMoneyStringToInt(
            $snapshot['order_summary']['total'] ?? '0'
        );

        // Step 6: Use central validation in VoucherService
        $validation = $this->voucherService->validateForCart(
            $voucher,
            $itemsTotalAfterSpecialRaw
        );

        if (!$validation['ok']) {
            if ($validation['message'] === trans('api.voucher.invalid_or_inactive')) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message'],
                ], Response::HTTP_BAD_REQUEST);
            }

            return response()->json([
                'success' => false,
                'message' => $validation['message'],
            ], Response::HTTP_BAD_REQUEST);
        }

        $voucherDiscountRaw = $validation['discount'];

        // Step 8: Save voucher choice into CheckoutSession
        $this->createOrUpdateCheckoutSessionVoucher($userId, $voucher, $voucherDiscountRaw);

        // Step 9: Build final checkout-style payload (same as /checkout, already includes voucher)
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($userId, $locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.voucher.applied_success'),
            'data'    => $snapshot,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *     path="/shopping-bag/apply-voucher",
     *     operationId="removeVoucherFromShoppingBag",
     *     tags={"Shopping Bag"},
     *     summary="Remove currently applied voucher from checkout session.",
     *     description="Clears voucher fields on the CheckoutSession and returns the same checkout snapshot without the voucher block. order_summary.total will be restored to the value before voucher discount.",
     *     security={{"bearerAuth": {}}}, 
     *
     *     @OA\Response(
     *         response=200,
     *         description="Voucher removed successfully. Returns same payload as /shopping-bag/checkout without voucher block.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Voucher removed successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/CheckoutSnapshot"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No voucher is currently applied to the checkout session.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No voucher is currently applied to your checkout session.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function removeVoucher(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $locale = app()->getLocale();

        // Get snapshot for user with selected items only for checkout
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($userId, $locale);

        // Step 1: Check if there are selected items for checkout
        $selectedCount = $snapshot['order_summary']['subtotal_items'] ?? 0;

        if ($selectedCount < 1) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.checkout_no_items'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Step 2: Since there are selected items, groups[0] should exist.
        $storeId = isset($snapshot['groups'][0]['store_id'])
            ? (int) $snapshot['groups'][0]['store_id']
            : null;

        if ($storeId !== null && $this->shoppingBagService->isAnotherStoreSelected($userId, $storeId)) {
            return response()->json([
                'success' => false,
                'message' => trans('api.shopping_bag.select_item_only_one_store'),
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var CheckoutSession|null $checkoutSession */
        $checkoutSession = CheckoutSession::where('user_id', $userId)->first();

        // No session, or voucher is not applied
        if (!$checkoutSession || !$checkoutSession->voucher_id) {
            return response()->json([
                'success' => false,
                'message' => trans('api.voucher.not_applied_in_checkout_session'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Step 3: Clear voucher fields
        $checkoutSession->voucher_id = null;
        $checkoutSession->voucher_code = null;
        $checkoutSession->voucher_discount_amount = null;
        $checkoutSession->save();

        // Step 4: Build snapshot normally; helper attachVoucherFromSessionIfAny()
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($userId, $locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.voucher.removed_success'),
            'data'    => $snapshot,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/shopping-bag/apply-delivery",
     *     operationId="getCheckoutApplyDelivery",
     *     tags={"Shopping Bag"},
     *     summary="Get checkout data for the current user after select delivery type.",
     *     description="Returns selected cart items grouped by brand, user address (if any), a single active courier option including pickup_location, and the order summary box. If a voucher is applied, a voucher block and voucher_discount values are included.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"courier"},
     *             @OA\Property(
     *                 property="courier",
     *                 type="string",
     *                 enum={"delivery", "pickup"},
     *                 example="delivery"
     *             ),
     *             @OA\Property(
     *                 property="delivery_payload",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="name", type="string", example="Standard"),
     *                 @OA\Property(property="key", type="string", example="standard"),
     *                 @OA\Property(property="fee_raw", type="integer", example=36812),
     *                 @OA\Property(property="fee", type="string", example="36.812"),
     *                 @OA\Property(property="is_discount", type="boolean", example=false),
     *                 @OA\Property(property="estimated", type="string", format="date", example="2026-01-02")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Checkout data retrieved successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Checkout data retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/CheckoutSnapshot"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No selected items in shopping bag for checkout.",
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function applyDelivery(Request $request)
    {
        $userId = auth()->id();
        $locale = app()->getLocale();

        // Inputs
        $courierType = $request->input('courier');
        $deliveryPayload = $request->input('delivery_payload') ?? null;

        // Step 1: Check selected courier
        $validator = Validator::make($request->all(), [
            'courier' => ['required', 'in:pickup,delivery'],
            'delivery_payload' => ['nullable', 'array']
        ],[
            'courier.in' => "The courier field must be either 'pickup' or 'delivery'."
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();

        // Step 2: Get courier_id from selected courier, insert into CheckoutSession field courier_id & delivery_payload
        $checkoutSession = $this->createOrUpdateCheckoutSessionCourier($userId, $courierType, $deliveryPayload);

        // Step 3: Build final checkout-style payload (same as /checkout, already includes courier)
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($userId, $locale);

        return response()->json([
            'success' => true,
            'message' => 'Courier applied successfully.',
            'data'    => $snapshot,
        ], Response::HTTP_OK);
    }
}