<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Channel;
use App\Models\Courier;
use App\Models\Payment;
use App\Models\Voucher;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\ShoppingBagItem;
use App\Models\CheckoutSession;
use App\Models\PaymentConfirmation;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use App\View\Data\ProductData;
use App\Services\LazadaApi;
use App\Services\VoucherService;
use App\Services\ActivityService;
use App\Services\ShoppingBagService;
use App\Services\NotificationService;
use App\Services\Support\PriceFormatter;
use App\Services\EmailNotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    use LazadaApi;
    
    private $voucherService;
    private $activityService;
    private $shoppingBagService;
    private $notificationService;
    private $xenditPaymentService;
    private $emailService;

    // Status mapping for easier maintenance
    private const STATUS_MAP = [
        'pending'        => 0,
        'sent_to_courier'=> 1,
        'preparing'      => 2,
        'on_delivery'    => 3,
        'ready_pick_up'  => 4,
        'completed'      => 5,
        'expired'        => 98,
        'cancel'         => 99,
    ];

    public function __construct(
        VoucherService $voucherService,
        ActivityService $activityService,
        ShoppingBagService $shoppingBagService,
        NotificationService $noticationService,
        XenditPaymentService $xenditPaymentService,
        EmailNotificationService $emailService,
    ) {
        $this->voucherService = $voucherService;
        $this->activityService = $activityService;
        $this->shoppingBagService = $shoppingBagService;
        $this->notificationService = $noticationService;
        $this->xenditPaymentService = $xenditPaymentService;
        $this->emailService = $emailService;
    }

    /**
     * Generate unique order number based on courier type and year-month.
     */
    public function generateOrderNumber(bool $isPickupCourier, string $yearMonth): string
    {
        $prefix = $isPickupCourier ? 'SRN-PU' : 'SRN-SH';
        do {
            $randomNumber = str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $orderNumber = "{$prefix}-{$yearMonth}-{$randomNumber}";
        } while (Order::where('order_number', $orderNumber)->exists());
        return $orderNumber;
    }

    /**
     * Generate unique pickup number 8 digit.
     */
    public function generatePickupNumber(): string
    {
        do {
            // Generate 8 digit random number
            $pickupNumber = str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Order::where('pickup_number', $pickupNumber)->exists());
        return $pickupNumber;
    }

    /**
     * Helper for error response array.
     */
    private function errorResponse($message, $httpCode, $errors = null)
    {
        return [
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
            'http_code' => $httpCode,
        ];
    }

    /**
     * Create a new order from shopping bag items (checkout process).
     */
    public function createOrder($user, string $locale, string $channelId, string $courierType): array
    {
        // Step 0: Load checkout_session (if any)
        $checkoutSession = CheckoutSession::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        // Step 1: Validate channel
        $channel = Channel::find($channelId);
        if (! $channel) {
            return $this->errorResponse(trans('api.order.channel_not_found'), Response::HTTP_BAD_REQUEST);
        }

        // Step 2: Validate courier
        $courier = Courier::with('translations')->where('key', $courierType)->first();
        if (! $courier || ! $courier->is_active) {
            return $this->errorResponse(trans('api.order.courier_not_found'), Response::HTTP_BAD_REQUEST);
        }
        $isPickupCourier = (bool) ($courier->is_pickup ?? false);

        $yearMonth = now()->format('ym');
        $orderNumber = $this->generateOrderNumber($isPickupCourier, $yearMonth);

        // Step 3: Resolve shipping address
        $userAddress = null;
        $userAddressDetail = null;
        $deliveryPayload = null;
        $phoneNumber = $user->mobile_number ?? '';

        // If it's not a pickup courier, resolve the address
        if (!$isPickupCourier) {
            $addressIdFromSession = $checkoutSession?->user_address_id;
            $deliveryPayload = $this->getDeliveryPayload($courier, $user, $locale);
            $userAddress = $addressIdFromSession
                ? $user->addresses()->find($addressIdFromSession)
                : $user->addresses()->where('is_default', true)->first();

            if (!$userAddress) {
                return $this->errorResponse(trans('api.order.must_have_shipping_address'), Response::HTTP_BAD_REQUEST);
            }
            if (empty($userAddress->phone_number)) {
                return $this->errorResponse(trans('api.order.phone_number_required'), Response::HTTP_BAD_REQUEST);
            }

            $phoneNumber = $userAddress->phone_number ?? $phoneNumber;
            $userAddressDetail = [
                'receiver_name' => $userAddress->receiver_name,
                'phone_number'  => $phoneNumber,
                'label'         => $userAddress->label,
                'address_line'  => $userAddress->address_line,
                'province'      => $userAddress->province_name,
                'city'          => $userAddress->city_name,
                'district'      => $userAddress->district_name,
                'subdistrict'   => $userAddress->subdistrict_name,
                'postal_code'   => $userAddress->postal_code ?? '',
                'province_id'   => $userAddress->province_id,
                'city_id'       => $userAddress->city_id,
            ];
        }

        // Step 4: Load SELECTED shopping bag items
        $selectedItems = ShoppingBagItem::where('user_id', $user->id)
            ->where('is_selected', true)
            ->get();

        if ($selectedItems->isEmpty()) {
            return $this->errorResponse(trans('api.shopping_bag.checkout_no_items'), Response::HTTP_BAD_REQUEST);
        }

        $firstStoreId = $selectedItems->first()->store_id;
        if ($this->shoppingBagService->isAnotherStoreSelected($user->id, $firstStoreId)) {
            return $this->errorResponse(trans('api.shopping_bag.checkout_only_one_store'), Response::HTTP_BAD_REQUEST);
        }

        $orderStoreId = $selectedItems->pluck('store_id')->unique()->first();

        // Step 5: Load voucher from checkout_session (if any)
        $voucher = null;
        $voucherDiscountNominal = 0;
        $voucherDetail = null;
        if ($checkoutSession && $checkoutSession->voucher_id) {
            $voucher = Voucher::find($checkoutSession->voucher_id);
            if ($voucher && $voucher->is_active) {
                $voucherDiscountNominal = (int) ($checkoutSession->voucher_discount_amount ?? 0);
                $voucherDetail = [
                    'id'                     => $voucher->id,
                    'code'                   => $checkoutSession->voucher_code,
                    'name'                   => $voucher->voucher_name,
                    'type'                   => $voucher->type,
                    'amount'                 => (int) $voucher->amount,
                    'min_transaction_amount' => (int) $voucher->min_transaction_amount,
                    'max_discount_amount'    => (int) $voucher->max_discount_amount,
                    'discount_from_session'  => $voucherDiscountNominal,
                ];
            }
        }

        // Step 6: Start transaction (lock stock + create order)
        DB::beginTransaction();
        try {
            $orderItems = [];
            $productVariantIds = [];
            $rawSubtotal = 0;
            $specialPriceDiscountTotal = 0;
            $errors = [];
            $requestedQuantities = [];

            // Preload all variants to avoid N+1
            $variantIds = $selectedItems->pluck('product_variant_id')->unique()->all();
            $variants = ProductVariant::with('product')->whereIn('id', $variantIds)->get()->keyBy('id');

            foreach ($selectedItems as $item) {
                $variantId = $item->product_variant_id;
                $quantity  = $item->quantity;
                $variant = $variants->get($variantId);

                if (! $variant) {
                    $errors[] = trans('api.order.one_product_not_found');
                    continue;
                }
                if (! $variant->is_active) {
                    $errors[] = trans('api.order.one_product_not_active');
                    continue;
                }
                $displayDetails = ProductData::getVariantDisplayDetails($variantId, $locale);
                if (! $displayDetails) {
                    $errors[] = trans('api.order.product_information_not_found');
                    continue;
                }

                $productLabel = trim(($displayDetails['product_name'] ?? '') . ' ' . ($displayDetails['variant_names'] ?? '')) ?: 'selected product';
                $alreadyRequested = $requestedQuantities[$variantId] ?? 0;
                $totalRequested   = $alreadyRequested + $quantity;

                if ($totalRequested > $variant->quantity) {
                    $errors[] = trans('api.order.stock_insufficient', [
                        'product'   => $productLabel,
                        'available' => $variant->quantity,
                        'requested' => $totalRequested
                    ]);
                    continue;
                }
                $requestedQuantities[$variantId] = $totalRequested;

                $basePrice = (float) $displayDetails['base_price'];
                $rawSubtotal += $basePrice * $quantity;

                $finalPrice = $basePrice;
                $discountPerUnit = 0;
                $discountAmount = 0;
                $specialPriceData = null;

                $promoDetails = ProductData::getActiveSpecialPrice($variantId);
                if ($promoDetails && $promoDetails['type'] === 'absolute_reduction') {
                    // Set the final price to the discount value
                    $discountPerUnit  = (float) $promoDetails['value'];
                    $finalPrice       = $discountPerUnit; // Final price is the discount value directly
                    $specialPriceData = $promoDetails;
                    $discountAmount = $basePrice - $discountPerUnit;
                }

                $itemSpecialDiscountTotal   = $discountAmount * $quantity;
                $specialPriceDiscountTotal += $itemSpecialDiscountTotal;
                $itemSubtotalFinal = $finalPrice * $quantity;

                $orderItems[] = [
                    'product_variant_id'   => $variantId,
                    'product_name'         => $displayDetails['product_name'],
                    'product_variant_name' => $displayDetails['variant_names'],
                    'base_price'           => (int) $basePrice,
                    'selling_price'        => (int) $finalPrice,
                    'notes'                => $item->notes,
                    'subtotal'             => (int) $itemSubtotalFinal,
                    'special_price_data'   => $specialPriceData ?? [],
                    'quantity'             => $quantity,
                ];
                $productVariantIds[] = $variantId;
            }

            if (!empty($errors)) {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.failed_validation_item'), Response::HTTP_BAD_REQUEST, $errors);
            }

            // Step 7: Calculate totals
            $subtotal = (int) $rawSubtotal;
            $deliveryCost = $isPickupCourier ? 0 : $this->sumDeliveryCost($courier, $deliveryPayload);
            
            $itemsTotalAfterSpecialRaw = (int) max(0, $subtotal - $specialPriceDiscountTotal);
            
            $voucherDiscount = 0;
            if ($voucher) {
                $validation = $this->voucherService->validateForCart($voucher, $itemsTotalAfterSpecialRaw);
                if (! $validation['ok']) {
                    DB::rollBack();
                    return $this->errorResponse($validation['message'] ?? trans('api.voucher.not_valid'), Response::HTTP_BAD_REQUEST);
                }
                $calculatedDiscount = $validation['discount'];
                $voucherDiscount = $voucherDiscountNominal > 0
                    ? min($calculatedDiscount, $voucherDiscountNominal)
                    : $calculatedDiscount;
            }

            // Step 7a: update voucher used_count (if voucher actually used)
            if ($voucher && $voucherDiscount > 0) {
                $lockResult = $this->voucherService->lockAndValidateForCheckout($voucher->id, $itemsTotalAfterSpecialRaw);
                if (! $lockResult['ok']) {
                    DB::rollBack();
                    return $this->errorResponse($lockResult['message'] ?? trans('api.voucher.not_available'), Response::HTTP_BAD_REQUEST);
                }
                $voucherRow = $lockResult['voucher'];
                $voucherRow->increment('used_count');
            }

            $discount = (int) ($specialPriceDiscountTotal + $voucherDiscount);
            $otherFees = $channel->cost;
            $grandTotal = max(0, $subtotal - $discount + $deliveryCost + $otherFees);

            $buyerName = $user->name;
            $recipientName = $userAddress?->receiver_name ?? $user->name;
            if (empty($buyerName)) {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.user_profile_validation'), Response::HTTP_BAD_REQUEST);
            }

            // If it's a pickup courier, we generate the pickup_number
            $pickupNumber = $isPickupCourier ? $this->generatePickupNumber() : null;

            // Step 8: Create order
            $order = Order::create([
                'order_number'          => $orderNumber,
                'user_id'               => $user->id,
                'store_id'              => $orderStoreId,
                'user_addresses_id'     => $userAddress?->id,
                'user_addresses_detail' => $userAddressDetail,
                'buyer_name'            => $buyerName,
                'recipient_name'        => $recipientName,
                'phone_number'          => $phoneNumber,
                'recipient_data'        => $userAddressDetail,
                'voucher_id'            => $voucher?->id,
                'voucher_detail'        => $voucherDetail,
                'channel_id'            => $channel->id,
                'subtotal'              => $subtotal,
                'delivery_cost'         => $deliveryCost,
                'discount'              => $specialPriceDiscountTotal,
                'discount_voucher'      => $voucherDiscount,
                'other_fees'            => $otherFees,
                'grand_total'           => $grandTotal,
                'courier_id'            => $courier->id,
                'courier_name'          => $courier->translation('en')->name,
                'delivery_payload'      => $deliveryPayload,
                'status'                => self::STATUS_MAP['pending'],
                'pickup_number'         => $pickupNumber,
            ]);

            // Step 9: Create order_items + decrement stock
            foreach ($orderItems as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    ...$itemData,
                ]);
                ProductVariant::where('id', $itemData['product_variant_id'])
                    ->decrement('quantity', $itemData['quantity']);
            }

            // Step 10: Clear shopping bag & checkout session
            ShoppingBagItem::where('user_id', $user->id)
                ->whereIn('product_variant_id', $productVariantIds)
                ->delete();

            $checkoutQuery = CheckoutSession::where('user_id', $user->id);
            if ($userAddress) {
                $checkoutQuery->where('user_address_id', $userAddress->id);
            }
            $checkoutQuery->delete();

            // Step 11: Log activity
            $this->activityService->orderLogActivity(
                $order,
                'order_created',
                'Order created by customer',
                [
                    'user_id'      => $user->id,
                    'channel_id'   => $channel->id,
                    'courier_id'   => $courier->id,
                    'subtotal'     => $subtotal,
                    'grand_total'  => $grandTotal,
                    'items_count'  => count($orderItems),
                    'voucher_id'   => $voucher?->id,
                    'code_voucher' => $checkoutSession?->voucher_code ?? null,
                ]
            );

            // Step 12: Create payment (gateway or manual)
            $response = null;

            if (!((bool) ($channel->is_manual ?? false))) {
                // === GATEWAY FLOW (existing) ===
                $amountXendit = $grandTotal - $otherFees;
            
                $params = [
                    'name'        => $user->name,
                    'reference'   => $order->id,
                    'code'        => $channel->code,
                    'external_id' => $order->id,
                    'amount'      => (int) $amountXendit,
                ];

                $payment = Payment::create([
                    'channel_id' => $channel->id,
                    'order_id'   => $order->id,
                    'status'     => 'WAITING',
                    'code'       => $params['external_id'],
                    'reference'  => $params['reference'],
                    'amount'     => $grandTotal,
                ]);

                $response = $channel->process($params);

                $payment->payment_details = $response;
                $payment->save();
            } else {
                // === MANUAL TRANSFER FlOW ===

                // Basic validation for manual bank fields
                if (empty($channel->account_number) || empty($channel->account_name)) {
                    DB::rollBack();
                    return $this->errorResponse(
                        trans('api.order.manual_transfer_channel_not_configured'),
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $expiresInHours = (int) ($channel->expires_in_hours ?? 24);
                $expirationDate = now('Asia/Jakarta')
                    ->addHours($expiresInHours)
                    ->utc()
                    ->format('Y-m-d\TH:i:s.v\Z');

                // Build internal payment_details (BCA-like shape)
                $manualDetails = [
                    'id'              => Str::uuid(),  // internal id for tracking
                    'name'            => $user->name,
                    'status'          => 'PENDING',
                    'country'         => 'ID',
                    'currency'        => 'IDR',
                    'bank_code'       => $channel->code, // e.g. "BCA_MANUAL" (your channel.code)
                    'external_id'     => (string) $order->id,
                    'reference_id'    => (string) $order->id,
                    'expected_amount' => (int) $grandTotal,
                    'account_number'  => (string) $channel->account_number,
                    'account_name'    => (string) $channel->account_name,
                    'expiration_date' => $expirationDate,
                    'type'            => 'MANUAL_BANK_TRANSFER',
                ];

                $payment = Payment::create([
                    'channel_id'      => $channel->id,
                    'order_id'        => $order->id,
                    'status'          => 'WAITING',
                    'code'            => (string) $order->id,
                    'reference'       => (string) $order->id,
                    'amount'          => $grandTotal,
                    'payment_details' => $manualDetails,
                ]);

                $response = $manualDetails;
            }

            DB::commit();

            // Create notif to frontend (user only)
            try {
                $this->notificationService->send($user->id, 'order_created', [
                    'order_number' => $order->order_number
                ], "/order-detail/{$order->id}");
            } catch (\Exception $e) {
                Log::error('Notification order_created failed for order ' . $order->order_number . ': ' . $e->getMessage());
            }

            // Send Email to user and Superadmin and another Role
            try {
                $payload = $this->prepareEmailPayload($order, 'waiting_payment');

                $this->emailService->send(
                    'pending',
                    $order,
                    $payload,
                    $order->user->email
                );
            } catch (\Exception $e) {
                Log::error('Notification Email order_created failed for order ' . $order->order_number . ': ' . $e->getMessage());
            }
            
            return [
                'success' => true,
                'data' => [
                    'order_id'      => $order->id,
                    'order_number'  => $order->order_number,
                    'subtotal'      => $order->subtotal,
                    'delivery_cost' => $order->delivery_cost,
                    'discount'      => $order->discount,
                    'other_fees'    => $order->other_fees,
                    'grand_total'   => $order->grand_total,
                    'items_count'   => count($orderItems),
                    'status'        => $order->status,
                    'payment'       => $response,
                ],
                'message' => trans('api.order.created_success'),
                'errors' => null,
                'http_code' => Response::HTTP_CREATED,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'trace'   => $e->getTraceAsString(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            $errorMessage = config('app.debug')
                ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
                : trans('api.order.failed_create');
            return $this->errorResponse($errorMessage, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Confirm manual bank transfer for an order.
     * - Only allowed for manual channel (channel.is_manual = true)
     * - Max 3 attempts
     * - Cannot submit if latest is SUBMITTED (waiting review) or APPROVED
     * - Cannot submit if payment is expired (based on payment_details.expiration_date)
     *
     * @param  mixed $user
     * @param  string $orderId
     * @param  array $payload  validated payload from ConfirmTransferRequest
     * @param  UploadedFile $receipt
     * @return array
     */
    public function confirmTransfer($user, string $orderId, array $payload, UploadedFile $receipt): array
    {
        $order = Order::with(['channel'])->find($orderId);
        if (!$order) {
            return $this->errorResponse(trans('api.order.not_found'), Response::HTTP_BAD_REQUEST);
        }

        // Ownership check
        if ((int) $order->user_id !== (int) $user->id) {
            return $this->errorResponse(trans('api.order.not_allowed_access'), Response::HTTP_FORBIDDEN);
        }

        // Must be manual channel
        $channel = $order->channel;
        if (! $channel || !((bool) ($channel->is_manual ?? false))) {
            return $this->errorResponse(trans('api.order.validation_manual_transfer'), Response::HTTP_BAD_REQUEST);
        }

        $storedFilePath = null;
        $storedDisk = null;

        DB::beginTransaction();
        try {
            // Lock payment row to avoid double submission race
            $payment = Payment::where('order_id', $order->id)
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.payment_not_found_from_order'), Response::HTTP_BAD_REQUEST);
            }

            // Validate transfer_date window (server-side authoritative)
            try {
                $transferDate = Carbon::createFromFormat('Y-m-d', $payload['transfer_date'], 'Asia/Jakarta')->startOfDay();
            } catch (\Throwable $e) {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.validation_date_format'), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // (A) min = order created date (Jakarta)
            $orderCreatedDay = $order->created_at->copy()->timezone('Asia/Jakarta')->startOfDay();
            if ($transferDate->lt($orderCreatedDay)) {
                DB::rollBack();
                return $this->errorResponse(
                    trans('api.order.validation_date_earlier'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // (B) max = expiration date from payment_details or today
            $expiresAt = data_get($payment->payment_details, 'expiration_date');
            $todayJakarta = now('Asia/Jakarta')->startOfDay();

            if (!empty($expiresAt)) {
                $expires = Carbon::parse($expiresAt)->timezone('Asia/Jakarta')->startOfDay();
                // Validate if transferDate is after expiration date
                if ($transferDate->gt($expires)) {
                    DB::rollBack();
                    return $this->errorResponse(
                        trans('api.order.validation_date_after_expiration'),
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
            }

            // Determine next attempt (max 3) and validate current state
            $latestConfirmation = PaymentConfirmation::where('payment_id', $payment->id)
                ->orderByDesc('attempt_no')
                ->lockForUpdate()
                ->first();

            $nextAttempt = 1;
            if ($latestConfirmation) {
                if ($latestConfirmation->status === PaymentConfirmation::STATUS_SUBMITTED) {
                    DB::rollBack();
                    return $this->errorResponse(
                        trans('api.order.confirmation_under_review'),
                        Response::HTTP_CONFLICT
                    );
                }

                if ($latestConfirmation->status === PaymentConfirmation::STATUS_APPROVED) {
                    DB::rollBack();
                    return $this->errorResponse(
                        trans('api.order.confirmation_approved'),
                        Response::HTTP_CONFLICT
                    );
                }

                if ($latestConfirmation->status !== PaymentConfirmation::STATUS_REJECTED) {
                    DB::rollBack();
                    return $this->errorResponse(
                        trans('api.order.confirmation_current_state'),
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $nextAttempt = ((int) $latestConfirmation->attempt_no) + 1;
                if ($nextAttempt > 3) {
                    DB::rollBack();
                    return $this->errorResponse(
                        trans('api.order.confirmation_max_limit'),
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }

            // Create confirmation
            $confirmation = PaymentConfirmation::create([
                'payment_id'          => $payment->id,
                'attempt_no'          => $nextAttempt,
                'sender_bank_name'    => $payload['sender_bank_name'],
                'sender_account_name' => $payload['sender_account_name'],
                'transfer_amount'     => (int) $payload['transfer_amount'],
                'transfer_date'       => $payload['transfer_date'],
                'status'              => PaymentConfirmation::STATUS_SUBMITTED,
            ]);

            // Store receipt file
            $disk = config('filesystems.default', 'public');
            $dir  = "payment_confirmations/{$confirmation->id}";
            $path = Storage::disk($disk)->putFile($dir, $receipt);

            $storedFilePath = $path;
            $storedDisk = $disk;

            // Decide file_type based on extension (pdf => DOCUMENT, else IMAGE)
            $ext = strtolower($receipt->getClientOriginalExtension());
            $fileType = ($ext === 'pdf') ? 'DOCUMENT' : 'IMAGE';

            // Create file_payments row via morph relation (safe with your table columns)
            $confirmation->files()->create([
                'field'     => 'payment_confirm',
                'file_type' => $fileType, // IMAGE / DOCUMENT
                'disk'      => $disk,
                'name'      => $receipt->getClientOriginalName(),
                'path'      => $path,
            ]);

            // Update payment status
            $payment->status = 'WAITING_CONFIRMATION';
            $payment->save();

            DB::commit();

            return [
                'success' => true,
                'data' => [
                    'confirmation_id' => $confirmation->id,
                    'payment_id'      => $confirmation->payment_id,
                    'attempt_no'      => $confirmation->attempt_no,
                    'status'          => $confirmation->status,
                    'file_confirm'    => $confirmation->file_confirm, // accessor dari model kamu
                ],
                'message' => trans('api.order.confirmation_created'),
                'errors' => null,
                'http_code' => Response::HTTP_CREATED,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            // Cleanup stored file if transaction failed after upload
            if ($storedFilePath && $storedDisk) {
                Storage::disk($storedDisk)->delete($storedFilePath);
            }

            Log::error('Confirm transfer failed: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'user_id'  => $user->id ?? null,
                'trace'    => $e->getTraceAsString(),
            ]);

            $errorMessage = config('app.debug')
                ? $e->getMessage()
                : trans('api.order.confirmation_failed');

            return $this->errorResponse($errorMessage, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Ambil data order Pick Up in Store untuk CMS (dengan filter status & search)
     */
    public function getPickupOrdersForCms($status = null, $search = null, &$meta = null)
    {
        $query = Order::with(['courier', 'user', 'orderItems.productVariant.product', 'store.masterLocation', 'payment.channel', 'channel'])
            ->whereHas('courier', function ($q) {
                $q->where('is_pickup', 1); // Pick Up in Store
            });

        $allowedStatus = [
            self::STATUS_MAP['pending'],
            self::STATUS_MAP['preparing'],
            self::STATUS_MAP['ready_pick_up'],
            self::STATUS_MAP['completed']
        ];

        $statusInt = $this->statusKeyToInt($status);

        if ($status && in_array($statusInt, $allowedStatus, true)) {
            $query->where('status', $statusInt);
        } else {
            $query->whereIn('status', $allowedStatus);
        }

        // ✅ Only for pending filter: show manual payment orders only
        if ($status && $statusInt === self::STATUS_MAP['pending']) {
            $query->where(function ($q) {
                $q->whereHas('payment.channel', function ($qq) {
                    $qq->where('is_manual', true);
                })
                ->orWhereHas('channel', function ($qq) {
                    $qq->where('is_manual', true);
                });
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%$search%")
                    ->orWhere('buyer_name', 'like', "%$search%");
            });
        }

        // ✅ TOTAL FULL (tanpa limit)
        $total = (clone $query)->count();

        // LIST YANG DITAMPILKAN (limit 50)
        $limit = 50;
        $orders = $query->orderByDesc('created_at')->limit($limit)->get();

        $paymentIds = $orders->pluck('payment.id')->filter()->unique()->values()->all();

        $latestConfirmByPaymentId = [];
        if (!empty($paymentIds)) {
            $confirmations = PaymentConfirmation::whereIn('payment_id', $paymentIds)
                ->orderByDesc('attempt_no')
                ->get();

            foreach ($confirmations as $c) {
                if (!isset($latestConfirmByPaymentId[$c->payment_id])) {
                    $latestConfirmByPaymentId[$c->payment_id] = $c;
                }
            }
        }

        $mapped = $orders->map(function($order) use ($latestConfirmByPaymentId) {
            $payment = $order->payment;
            $isManual = (bool) ($payment?->channel?->is_manual ?? $order->channel?->is_manual ?? false);

            $latestConf = $payment ? ($latestConfirmByPaymentId[$payment->id] ?? null) : null;

            $awaitingReview = $isManual
                && $latestConf
                && $latestConf->status === PaymentConfirmation::STATUS_SUBMITTED
                && $order->status_key === 'pending';

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'date' => optional($order->created_at)->format('Y-m-d H:i'),
                'location' => $order->store->name ?? '  -',
                'status' => $order->status_key,
                'status_label' => $order->status_name,
                'pickup' => [
                    'address' => $order->store->masterLocation->address ?? '',
                    'customer' => $order->buyer_name ?? '-',
                    'number' => $order->pickup_number,
                ],
                'payment' => [
                    'is_manual' => $isManual,
                    'status' => $payment?->status,
                    'expected_amount' => (int) ($payment?->amount ?? 0),
                ],
                'manual_review' => [
                    'awaiting_review' => $awaitingReview,
                    'confirmation' => $latestConf ? [
                        'id' => $latestConf->id,
                        'status' => $latestConf->status,
                        'attempt_no' => $latestConf->attempt_no,
                        'sender_bank_name' => $latestConf->sender_bank_name,
                        'sender_account_name' => $latestConf->sender_account_name,
                        'transfer_amount' => (int) $latestConf->transfer_amount,
                        'transfer_date' => optional($latestConf->transfer_date)->format('Y-m-d'),
                        'receipt_url' => $latestConf->file_confirm,
                    ] : null,
                ],
                'items' => $order->orderItems->map(function($item) {
                    return [
                        'img' => $item->productVariant?->product?->file_url ?? '',
                        'name' => $item->product_name ?? $item->productVariant?->product?->name ?? '-',
                        'variant' => $item->product_variant_name ?? $item->productVariant?->getVariantName() ?? '-',
                        'qty' => $item->quantity,
                        'price' => 'Rp ' . number_format($item->selling_price ?? 0, 0, ',', '.'),
                    ];
                })->toArray(),
            ];
        })->toArray();

        // ✅ META buat badge + "Showing..."
        $meta = [
            'total' => $total,
            'shown' => count($mapped),
            'limit' => $limit,
        ];

        return $mapped;
    }

    /**
     * Ambil data order Ship by Courier untuk CMS (dengan filter status & search)
     */
    public function getCourierOrdersForCms($status = null, $search = null, &$meta = null)
    {
        $query = Order::with(['courier', 'user', 'orderItems.productVariant.product', 'store.masterLocation', 'payment.channel', 'channel'])
            ->whereHas('courier', function ($q) {
                $q->where('is_pickup', 0) // Ship by Courier
                  ->whereIn('key', ['delivery-sarinah', 'delivery-lazada']);
            });
        
        $allowedStatus = [
            self::STATUS_MAP['pending'],
            self::STATUS_MAP['sent_to_courier'],
            self::STATUS_MAP['preparing'],
            self::STATUS_MAP['on_delivery'],
            self::STATUS_MAP['completed'],
        ];

        $statusInt = $this->statusKeyToInt($status);

        if ($status && in_array($statusInt, $allowedStatus, true)) {
            $query->where('status', $statusInt);
        } else {
            $query->whereIn('status', $allowedStatus);
        }

        // ✅ Only for pending filter: show manual payment orders only
        if ($status && $statusInt === self::STATUS_MAP['pending']) {
            $query->where(function ($q) {
                $q->whereHas('payment.channel', function ($qq) {
                    $qq->where('is_manual', true);
                })
                ->orWhereHas('channel', function ($qq) {
                    $qq->where('is_manual', true);
                });
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%$search%")
                    ->orWhere('buyer_name', 'like', "%$search%");
            });
        }

        // Hitung total
        $total = (clone $query)->count();

        // Ambil data yang ditampilkan (limit 50)
        $limit = 50;
        $orders = $query->orderByDesc('created_at')->limit($limit)->get();

        // Ambil payment confirmation untuk setiap order
        $paymentIds = $orders->pluck('payment.id')->filter()->unique()->values()->all();

        $latestConfirmByPaymentId = [];
        if (!empty($paymentIds)) {
            $confirmations = PaymentConfirmation::whereIn('payment_id', $paymentIds)
                ->orderByDesc('attempt_no')
                ->get();

            foreach ($confirmations as $c) {
                if (!isset($latestConfirmByPaymentId[$c->payment_id])) {
                    $latestConfirmByPaymentId[$c->payment_id] = $c;
                }
            }
        }

        // Mapping orders untuk dikirim
        $mapped = $orders->map(function($order) use ($latestConfirmByPaymentId) {
            $payment = $order->payment;
            $isManual = (bool) ($payment?->channel?->is_manual ?? $order->channel?->is_manual ?? false);

            $latestConf = $payment ? ($latestConfirmByPaymentId[$payment->id] ?? null) : null;

            $awaitingReview = $isManual
                && $latestConf
                && $latestConf->status === PaymentConfirmation::STATUS_SUBMITTED
                && $order->status_key === 'pending';

            // Ambil data recipient_data dan format
            $recipientData = $order->recipient_data; // Data yang sudah di-cast menjadi array
            $shippingAddress = null;

            if ($recipientData) {
                $receiverName = $recipientData['receiver_name'] ?? '';
                $phoneNumber = $recipientData['phone_number'] ?? '';
                $addressLine = $recipientData['address_line'] ?? '';
                $province = $recipientData['province'] ?? '';
                $city = $recipientData['city'] ?? '';
                $district = $recipientData['district'] ?? '';
                $subdistrict = $recipientData['subdistrict'] ?? '';
                $postalCode = $recipientData['postal_code'] ?? '';

                // Format address sesuai dengan yang diinginkan
                $shippingAddress = "$receiverName, $phoneNumber\n$addressLine, $province, $city, $district, $subdistrict $postalCode";
            }

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'date' => optional($order->created_at)->format('Y-m-d H:i'),
                'location' => $order->store->name ?? '  -',
                'status' => $order->status_key,
                'status_label' => $order->status_name,
                'shipping' => [
                    'address' => $shippingAddress,
                    'courier_service' => $order->courier_name,
                    'shipping_id' => $order->tracking_number,
                    'courier_key' => $order->courier->key,
                    'external_courier_name' => $order->external_courier_name ?? null,
                ],
                'payment' => [
                    'is_manual' => $isManual,
                    'status' => $payment?->status,
                    'expected_amount' => (int) ($payment?->amount ?? 0),
                ],
                'manual_review' => [
                    'awaiting_review' => $awaitingReview,
                    'confirmation' => $latestConf ? [
                        'id' => $latestConf->id,
                        'status' => $latestConf->status,
                        'attempt_no' => $latestConf->attempt_no,
                        'sender_bank_name' => $latestConf->sender_bank_name,
                        'sender_account_name' => $latestConf->sender_account_name,
                        'transfer_amount' => (int) $latestConf->transfer_amount,
                        'transfer_date' => optional($latestConf->transfer_date)->format('Y-m-d'),
                        'receipt_url' => $latestConf->file_confirm,
                    ] : null,
                ],
                'items' => $order->orderItems->map(function($item) {
                    return [
                        'img' => $item->productVariant?->product?->file_url ?? '',
                        'name' => $item->product_name ?? $item->productVariant?->product?->name ?? '-',
                        'variant' => $item->product_variant_name ?? $item->productVariant?->getVariantName() ?? '-',
                        'qty' => $item->quantity,
                        'price' => 'Rp ' . number_format($item->selling_price ?? 0, 0, ',', '.'),
                    ];
                })->toArray(),
            ];
        })->toArray();

        // Set meta untuk badge "Showing x of y"
        $meta = [
            'total' => $total,
            'shown' => count($mapped),
            'limit' => $limit,
        ];

        return $mapped;
    }
    
    /**
     * Helper: konversi status key string ke int sesuai Order::getStatusKeyAttribute
     */
    private function sumDeliveryCost($courier, $deliveryPayload = null)
    {
        if ($courier->key === 'delivery-lazada') {
            if (!array_key_exists('fee_raw', $deliveryPayload) || $deliveryPayload['fee_raw'] === null) {
                throw new \Exception(trans('lazada.fee_not_found'));
            }

            return $deliveryPayload['fee_raw'];
        }


        return $courier->fee;
    }

    /**
     * Helper: konversi status key string ke int sesuai Order::getStatusKeyAttribute
     */
    private function statusKeyToInt($key)
    {
        return self::STATUS_MAP[$key] ?? null;
    }

    /**
     * Get all data needed for invoice generation.
     * Returns null if order not found.
     * @param int|string $orderId
     * @return array|null
     */
    public function getInvoiceData($orderId)
    {
        $order = Order::with([
            'user',
            'orderItems.productVariant.product',
            'store.masterLocation',
            'payment',
        ])->find($orderId);
        if (!$order) {
            return null;
        }

        // Determine paid/unpaid
        $unpaidStatuses = [
            self::STATUS_MAP['pending'],
            self::STATUS_MAP['expired'],
            self::STATUS_MAP['cancel'],
        ];
        $isPaid = !in_array($order->status, $unpaidStatuses, true);

        // Payment method
        $paymentMethod = $order->payment?->channel?->name ?? $order->payment?->code ?? '-';

        // Invoice items
        $items = $order->orderItems->map(function($item, $idx) {
            $productName = $item->product_name ?? $item->productVariant?->product?->name ?? '-';
            $variantName = $item->product_variant_name ?? $item->productVariant?->getVariantName() ?? '';
            $desc = trim(($idx+1) . '. ' . $productName . ($variantName ? ' ' . $variantName : ''));
            return [
                'description' => $desc,
                'quantity' => $item->quantity,
                'unit_price' => $item->selling_price,
                'total_price' => $item->selling_price * $item->quantity,
            ];
        })->toArray();

        // Delivery address (if not pickup)
        $isPickup = $order->courier?->is_pickup ?? false;

        $deliveryAddress = null;
        if (!$isPickup) {
            $recipientData = $order->recipient_data;
            if ($recipientData) {
                // $receiverName = $recipientData['receiver_name'] ?? '';
                // $phoneNumber = $recipientData['phone_number'] ?? '';
                $addressLine = $recipientData['address_line'] ?? '';
                $province = $recipientData['province'] ?? '';
                $city = $recipientData['city'] ?? '';
                $district = $recipientData['district'] ?? '';
                $subdistrict = $recipientData['subdistrict'] ?? '';
                $postalCode = $recipientData['postal_code'] ?? '';

                $deliveryAddress = "$addressLine, $province, $city, $district, $subdistrict $postalCode";
            }
        }

        // Summary
        $summary = [
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'post_discount' => $order->subtotal - $order->discount,
            'packaging_fee' => $order->other_fees + $order->delivery_cost,
            'total' => $order->grand_total,
        ];

        // Invoice meta
        $invoiceData = [
            'order_number' => $order->order_number,
            'customer_name' => $order->buyer_name,
            'customer_email' => $order->user?->email,
            'customer_phone' => $order->user?->mobile_number,
            'transaction_date' => optional($order->created_at)->format('j F Y'),
            'delivery_address' => $deliveryAddress,
            'store_name' => $order->store?->name ?? '-',
            'store_address' => $order->store?->masterLocation?->address ?? '-',
            'store_phone' => $order->store?->phone ?? '-',
            'store_email' => $order->store?->email ?? '-',
            'invoice_date' => optional($order->created_at)->format('j F Y'),
            'payment_method' => $paymentMethod,
            'is_paid' => $isPaid,
            'items' => $items,
            'summary' => $summary,
        ];
        return $invoiceData;
    }

    /**
     * Generate invoice PDF response for download (for CMS)
     * @param int|string $orderId
     * @return \Illuminate\Http\Response
     */
    public function generateInvoicePdf($orderId)
    {
        $invoice = $this->getInvoiceData($orderId);
        if (!$invoice) {
            abort(400, trans('api.order.not_found'));
        }
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('cms.order.invoice', [
            'invoice' => $invoice,
            'isPdf' => true,
        ]);
        $filename = 'Invoice-' . $invoice['order_number'] . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
    
    /**
     * Generate invoice PDF response for download (for API)
     * Using Format BLOB
     * @param int|string $orderId
     * @return \Illuminate\Http\Response
     */
    public function generateInvoicePdfApi($orderId)
    {
        $invoice = $this->getInvoiceData($orderId);
        if (!$invoice) {
            abort(400, trans('api.order.not_found'));
        }

        // Generate PDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('cms.order.invoice', [
            'invoice' => $invoice,
            'isPdf' => true,
        ]);
        
        $filename = 'Invoice-' . $invoice['order_number'] . '.pdf';

        // Streaming the PDF to the client
        return response()->stream(function () use ($pdf) {
            echo $pdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate invoice HTML response for print (for API or CMS)
     * @param int|string $orderId
     * @return \Illuminate\Http\Response
     */
    public function generateInvoiceHtml($orderId)
    {
        $invoice = $this->getInvoiceData($orderId);
        if (!$invoice) {
            abort(400, trans('api.order.not_found'));
        }
        $html = view('cms.order.invoice', [
            'invoice' => $invoice,
        ])->render();
        return response($html, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     * Review
     */
    public function reviewManualTransferConfirmation(
        $admin,
        string $confirmationId,
        string $action, // approve|reject
        ?string $rejectReason,
        bool $ackMismatch
    ): array {
        DB::beginTransaction();

        try {
            /** @var PaymentConfirmation $confirmation */
            $confirmation = PaymentConfirmation::where('id', $confirmationId)
                ->lockForUpdate()
                ->first();

            if (!$confirmation) {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.confirmation_not_found'), Response::HTTP_BAD_REQUEST);
            }

            // Only SUBMITTED can be reviewed
            if ($confirmation->status !== PaymentConfirmation::STATUS_SUBMITTED) {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.confirmation_awaiting_review'), Response::HTTP_CONFLICT);
            }

            /** @var Payment $payment */
            $payment = Payment::where('id', $confirmation->payment_id)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.payment_not_found'), Response::HTTP_BAD_REQUEST);
            }

            $order = Order::with(['orderItems', 'channel', 'courier'])
                ->where('id', $payment->order_id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.not_found'), Response::HTTP_BAD_REQUEST);
            }

            // Order must be pending to be reviewed
            if ((int) $order->status !== self::STATUS_MAP['pending']) {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.confirmation_status_only_pending'), Response::HTTP_CONFLICT);
            }

            // Must be manual channel
            $channel = $order->channel;
            if (! $channel || !((bool) ($channel->is_manual ?? false))) {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.validation_manual_transfer'), Response::HTTP_BAD_REQUEST);
            }

            // Expected amount comes from payment.amount (as agreed)
            $expectedAmount = (int) ($payment->amount ?? 0);
            $transferAmount = (int) ($confirmation->transfer_amount ?? 0);
            $isMismatch = $expectedAmount !== $transferAmount;

            if ($action === 'approve') {
                // If mismatch, require ack checkbox
                if ($isMismatch && !$ackMismatch) {
                    DB::rollBack();
                    return $this->errorResponse(
                        trans('api.order.confirmation_alert_not_match'),
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $confirmation->status = PaymentConfirmation::STATUS_APPROVED;
                $confirmation->reviewed_by = $admin->id;
                $confirmation->reviewed_at = now();
                $confirmation->reject_reason = null;
                $confirmation->save();

                $payment->status = 'SUCCEEDED';
                $payment->save();

                $isPickup = (bool) $order->courier->is_pickup;
                $order->status = $isPickup 
                    ? self::STATUS_MAP['preparing'] 
                    : self::STATUS_MAP['sent_to_courier'];

                $order->save();

                $notificationEvents = ['payment_confirmation_accept'];

                $notificationEvents[] = !$isPickup ? 'order_confirmed' : 'order_packed';

                // Create notif to frontend (user only)
                foreach ($notificationEvents as $index => $event) {
                    try {
                        $timestampWithOffset = now()->valueOf() + ($index * 10);

                        $this->notificationService->send(
                            $order->user_id, 
                            $event, 
                            ['order_number' => $order->order_number], 
                            "/order-detail/{$order->id}",
                            $timestampWithOffset
                        );
                    } catch (\Exception $e) {
                        Log::error("Notification {$event} failed for order {$order->order_number}: {$e->getMessage()}");
                    }
                }

                // Create notification to email (Admin and User)
                try {
                    $payload = $this->prepareEmailPayload($order, 'order_confirmed');
                    $statusOrderToEmail = !$isPickup
                        ? 'preparing' 
                        : 'sent_to_courier';
                    
                    $this->emailService->send(
                        $statusOrderToEmail,
                        $order,
                        $payload,
                        $order->user->email
                    );
                } catch (\Exception $e) {
                    Log::error('Notification Email order_confirmed failed for order ' . $order->order_number . ': ' . $e->getMessage());
                }

                if ($order->courier->key === 'delivery-lazada') {
                    $this->createPackage($order->id);
                }

                $this->appendOrderLog($order, 'manual_transfer_approved', 'Manual transfer approved by admin', [
                    'admin_id'        => $admin->id,
                    'admin_name'      => $admin->name ?? null,
                    'confirmation_id' => $confirmation->id,
                    'attempt_no'      => $confirmation->attempt_no,
                    'expected_amount' => $expectedAmount,
                    'transfer_amount' => $transferAmount,
                    'amount_mismatch' => $isMismatch,
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'data' => [
                        'order_id'        => $order->id,
                        'order_status'    => $order->status,
                        'payment_status'  => $payment->status,
                        'confirmation_id' => $confirmation->id,
                        'confirmation_status' => $confirmation->status,
                    ],
                    'message' => trans('api.order.confirmation_success'),
                    'errors' => null,
                    'http_code' => Response::HTTP_OK,
                ];
            }

            // === reject ===
            $reason = trim((string) $rejectReason);
            if ($reason === '') {
                DB::rollBack();
                return $this->errorResponse(trans('api.order.confirmation_reason_validation'), Response::HTTP_BAD_REQUEST);
            }

            $confirmation->status = PaymentConfirmation::STATUS_REJECTED;
            $confirmation->reviewed_by = $admin->id;
            $confirmation->reviewed_at = now();
            $confirmation->reject_reason = $reason;
            $confirmation->save();

            try {
                $this->notificationService->send($order->user_id, 'order_rejected', [
                    'order_number' => $order->order_number
                ], "/order-detail/{$order->id}");
            } catch (\Exception $e) {
                Log::error('Notification order_rejected failed for order ' . $order->order_number . ': ' . $e->getMessage());
            }

            $maxAttempts = 3;
            $isTerminal = ((int) $confirmation->attempt_no) >= $maxAttempts;

            if (! $isTerminal) {
                $payment->status = 'WAITING_CONFIRMATION';
                $payment->save();

                $this->appendOrderLog($order, 'manual_transfer_rejected', 'Manual transfer rejected by admin', [
                    'admin_id'        => $admin->id,
                    'admin_name'      => $admin->name ?? null,
                    'confirmation_id' => $confirmation->id,
                    'attempt_no'      => $confirmation->attempt_no,
                    'reject_reason'   => $reason,
                    'expected_amount' => $expectedAmount,
                    'transfer_amount' => $transferAmount,
                    'amount_mismatch' => $isMismatch,
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'data' => [
                        'order_id'        => $order->id,
                        'order_status'    => $order->status,
                        'payment_status'  => $payment->status,
                        'confirmation_id' => $confirmation->id,
                        'confirmation_status' => $confirmation->status,
                        'is_terminal'     => false,
                    ],
                    'message' => trans('api.order.confirmation_rejected'),
                    'errors' => null,
                    'http_code' => Response::HTTP_OK,
                ];
            }

            // Terminal reject => auto cancel + restock + voucher decrement
            foreach ($order->orderItems as $item) {
                ProductVariant::where('id', $item->product_variant_id)
                    ->increment('quantity', $item->quantity);
            }

            if ($order->voucher_id) {
                $voucher = Voucher::where('id', $order->voucher_id)->lockForUpdate()->first();
                if ($voucher && (int) $voucher->used_count > 0) {
                    $voucher->decrement('used_count');
                }
            }

            $order->status = self::STATUS_MAP['cancel'];
            $order->reason = 'Payment confirmation rejected 3 times. Order cancelled.';
            $order->save();

            $payment->status = 'FAILED';
            $payment->save();

            $this->appendOrderLog($order, 'manual_transfer_failed_max_attempts', 'Manual transfer rejected (max attempts). Order cancelled.', [
                'admin_id'        => $admin->id,
                'admin_name'      => $admin->name ?? null,
                'confirmation_id' => $confirmation->id,
                'attempt_no'      => $confirmation->attempt_no,
                'reject_reason'   => $reason,
                'expected_amount' => $expectedAmount,
                'transfer_amount' => $transferAmount,
                'amount_mismatch' => $isMismatch,
                'auto_cancel'     => true,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => [
                    'order_id'        => $order->id,
                    'order_status'    => $order->status,
                    'payment_status'  => $payment->status,
                    'confirmation_id' => $confirmation->id,
                    'confirmation_status' => $confirmation->status,
                    'is_terminal'     => true,
                ],
                'message' => trans('api.order.confirmation_reject_limit'),
                'errors' => null,
                'http_code' => Response::HTTP_OK,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('CMS review manual transfer failed: ' . $e->getMessage(), [
                'confirmation_id' => $confirmationId,
                'action' => $action,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                config('app.debug') ? $e->getMessage() : trans('api.order.confirmation_review_failed'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get Delivery Payload (CMS-friendly).
     */
    private function getDeliveryPayload($courier, $user, $locale): ?array
    {
        if($courier->key === 'delivery-lazada'){
            $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($user->id, $locale);

            $lazadaOp = $this->getShippingFee($snapshot);

            if(!$lazadaOp)
                return null;

            if ($lazadaOp?->success === true)
                return $this->calculateShippingFee($lazadaOp);
            
            \Log::error($lazadaOp->errorMessage ?? '');
            throw new \Exception(trans('lazada.calculate_something_wrong'));
        }        
        return null;
    }

    /**
     * Append order log (CMS-friendly).
     */
    private function appendOrderLog(Order $order, string $action, string $description, array $additionalData = []): void
    {
        if (method_exists($order, 'orderLog')) {
            $order->orderLog()->create([
                'action' => $action,
                'description' => $description,
                'additional_data' => $additionalData,
            ]);
        }
    }

    public function buildShoppingBagSnapShot(string $orderId, string $locale)
    {
        $bagItems = OrderItem::where('order_id', $orderId)        
            ->with([
                'productVariant:id,product_id,quantity,combination,price',
                'productVariant.product:id,brand_id,main_image_index',
                'productVariant.product.brand:id,name',
                'productVariant.product.defaultImageFile',
            ])
            ->orderBy('id', 'DESC')
            ->get();

        // STEP 1: Transform each item to a flat array with pricing & stock data
        $mappedItems = $bagItems->map(function (OrderItem $item) use ($locale) {
            $variant = $item->productVariant;

            // Ensure variant, product and brand exist
            if (!$variant || !$variant->product || !$variant->product->brand) {
                return null;
            }

            $variantId = $variant->id;
            $brand = $variant->product->brand;
            $quantityInBag = (int) $item->quantity;
            $isSelected = true;

            // Retrieve static display details and active special price
            $displayDetails = ProductData::getVariantDisplayDetails($variantId, $locale);
            $promoDetails = ProductData::getActiveSpecialPrice($variantId);

            if (!$displayDetails) {
                return null;
            }

            $imageUrl = $variant->product->default_image;

            // Base price and discount
            $basePrice = (float) $displayDetails['base_price'];
            $finalPrice = $basePrice;
            $discountAmount = 0.0;

            $hasSpecialPrice = !is_null($promoDetails);

            if ($hasSpecialPrice && $promoDetails['type'] === 'absolute_reduction') {
                $finalPrice = (float) $promoDetails['value'];
                $discountAmount = $basePrice - $finalPrice;
            }

            $itemDiscountPercent = null;

            if ($hasSpecialPrice && $basePrice > 0) {
                if (isset($promoDetails['percentage'])) {
                    // Prefer explicit percentage from special_prices table if available
                    $percentValue = (float) $promoDetails['percentage'];
                } elseif ($discountAmount > 0) {
                    // Fallback: derive from discount amount and base price
                    $percentValue = ($discountAmount / $basePrice) * 100;
                } else {
                    $percentValue = 0.0;
                }

                if ($percentValue > 0) {
                    $itemDiscountPercent = PriceFormatter::formatPercentage($percentValue);
                }
            }

            $itemFinalSubtotal = $finalPrice * $quantityInBag;
            $itemBaseSubtotal = $basePrice * $quantityInBag;

            // Live Stock
            $liveStock = (int) $variant->quantity;

            // Availability + stock warning logic
            $isAvailable = true;
            $warningMessage = null;

            if ($liveStock === 0) {
                $isAvailable = false;
                $warningMessage = 'Product is out of stock. Please remove this item.';
            } elseif ($quantityInBag > $liveStock) {
                $isAvailable = false;
                $warningMessage = "Requested quantity ({$quantityInBag}) exceeds available stock ({$liveStock}). Maximum quantity is {$liveStock}.";
            } elseif ($liveStock <= 10) {
                $warningMessage = "Only {$liveStock} item(s) left in stock.";
            }

            /**
             * Enforce invariant:
             * Items that are not available for checkout must not be selected.
             * If we detect an inconsistent state (selected but not available),
             * we unselect it in the DB and in the payload.
             */
            if (!$isAvailable && $isSelected) {
                if ($item->is_selected) {
                    $item->is_selected = false;
                    $item->save();
                }

                $isSelected = false;
            }

            return [
                'id' => $item->id,
                'product_slug' => $displayDetails['product_slug'],
                'product_variant_id' => $variantId,
                'brand_id' => $brand->id,
                'brand_name' => $brand->name,
                'product_name' => $displayDetails['product_name'],
                'variant_details' => $displayDetails['variant_names'],
                'image_url' => $imageUrl,
                'quantity_in_bag' => $quantityInBag,

                // Raw values used for numeric calculations
                'item_subtotal_raw' => $itemFinalSubtotal, // final (after discount)
                'item_base_subtotal_raw' => $itemBaseSubtotal, // base (before discount)

                // Formatted pricing for display
                'base_unit_price' => number_format($basePrice, 0, ',', '.'),
                'final_unit_price' => number_format($finalPrice, 0, ',', '.'),
                'item_subtotal' => number_format($itemFinalSubtotal, 0, ',', '.'),

                'has_special_price' => $hasSpecialPrice,
                'discount_amount' => $hasSpecialPrice ? number_format($discountAmount, 0, ',', '.') : '0',
                'discount_percent' => $itemDiscountPercent,

                'is_selected' => $isSelected,
                'is_available_for_checkout' => $isAvailable,
                'max_available_quantity' => $liveStock,
                'stock_warning_message' => $warningMessage,
            ];
        })->filter();

        // ---------- NEW: select-all meta calculation ----------
        $totalItems = $mappedItems->count();

        // Items that are eligible to be part of checkout
        $selectableItems = $mappedItems->filter(
            fn ($item) => $item['is_available_for_checkout'] === true
        );

        $totalSelectableItems = $selectableItems->count();

        // Among selectable items, how many are currently selected
        $selectedSelectableItems = $selectableItems
            ->filter(fn ($item) => $item['is_selected'] === true)
            ->count();

        // Determine select-all state
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
        // ------------------------------------------------------

        // STEP 2: Global summary for selected items (for order summary box)
        $selectedItems = $mappedItems->filter(fn ($item) => $item['is_selected']);

        $subtotalBaseRaw = $selectedItems->sum('item_base_subtotal_raw'); // before discount
        $subtotalFinalRaw = $selectedItems->sum('item_subtotal_raw'); // after discount
        $discountTotalRaw = max(0, $subtotalBaseRaw - $subtotalFinalRaw);

        $summaryDiscountPercent = 0.0;

        if ($subtotalBaseRaw > 0 && $discountTotalRaw > 0) {
            $summaryDiscountPercent = ($discountTotalRaw / $subtotalBaseRaw) * 100;
        }

        $selectedLineItemsCount = $selectedItems->count(); // how many rows selected
        $selectedItemsQuantity = $selectedItems->sum('quantity_in_bag'); // total quantity selected

        // STEP 3: Group by brand and calculate brand_subtotal and grand_total (only selected items)
        $groupedItems = $mappedItems->groupBy('brand_id');

        $groups = $groupedItems->map(function (Collection $brandItems, $brandId) {
            $brandName = $brandItems->first()['brand_name'];

            // Sum only selected items for this brand (final price)
            $brandSelectedSubtotalRaw = $brandItems
                ->filter(fn ($item) => $item['is_selected'])
                ->sum('item_subtotal_raw');

            // Clean up internal keys from item-level payload
            $items = $brandItems->map(function ($item) {
                unset(
                    $item['brand_id'],
                    $item['brand_name'],
                    $item['item_subtotal_raw'],
                    $item['item_base_subtotal_raw']
                );

                return $item;
            })->values()->toArray();

            return [
                'brand_id'       => $brandId,
                'brand_name'     => $brandName,
                'brand_subtotal' => number_format($brandSelectedSubtotalRaw, 0, ',', '.'),
                'items'          => $items,
            ];
        })->values()->toArray();

        // STEP 4: Final response structure
        return [
            'groups' => $groups,
            'summary' => [
                // e.g. "Subtotal (2 items)"
                'selected_items_count' => $selectedLineItemsCount, // line items
                'selected_items_quantity' => $selectedItemsQuantity, // total quantity

                // Number values for box:
                // Subtotal = before discount, selected only
                'subtotal' => number_format($subtotalBaseRaw, 0, ',', '.'),

                // Discount = subtotal - total
                'discount' => number_format($discountTotalRaw, 0, ',', '.'),
                'discount_percent' => $summaryDiscountPercent > 0
                    ? PriceFormatter::formatPercentage($summaryDiscountPercent)
                    : '0%',
                'grand_total' => number_format($subtotalFinalRaw, 0, ',', '.'),
                'has_special_price' => $selectedItems->some(fn ($item) => $item['has_special_price']),

                // Select-all metadata
                'total_items' => $totalItems,
                'total_selectable_items' => $totalSelectableItems,
                'selected_selectable_items' => $selectedSelectableItems,
                'select_all_state' => $selectAllState,
            ],
        ];
    }

    /**
     * Build the shopping bag snapshot for the given user.
     * This is used both in checkout and updateUserAddress to return the same data structure.
     */
    public function buildShoppingBagSnapshotForUser(string $orderId, string $locale)
    {
        // Reuse snapshot from shopping bag
        $bagSnapshot = $this->buildShoppingBagSnapShot($orderId, $locale);

        // Collect all selected bag item IDs from snapshot
        $selectedItemsIds = $this->getSelectedItemsIds($bagSnapshot);

        // Load store + master_location for selected bag items only
        $selectedBagItems = $this->getSelectedBagItems($orderId, $selectedItemsIds);

        // Build groups for checkout: only selected items
        $selectedGroups = $this->getSelectedGroups($bagSnapshot, $selectedBagItems);

        // Build order summary from snapshot summary
        $orderSummary = $this->getOrderSummary($bagSnapshot);

        return [
            'orderSummary' => $orderSummary,
            'selectedGroups' => $selectedGroups,
        ];
    }

    /**
     * Build summary data for order detail based on persisted order fields.
     * This avoids recalculating prices or discounts using live product data.
     */
    public function buildOrderSummaryFromOrder(Order $order): array
    {
        $itemsCount    = $order->orderItems->count();
        $itemsQuantity = (int) $order->orderItems->sum('quantity');

        $subtotal    = (int) ($order->subtotal ?? 0);
        $discount    = (int) ($order->discount ?? 0);
        $deliveryFee = (int) ($order->delivery_cost ?? 0);
        $otherFees   = (int) ($order->other_fees ?? 0);
        $grandTotal  = (int) ($order->grand_total ?? 0);

        $discountPercent = 0.0;
        if ($subtotal > 0 && $discount > 0) {
            $discountPercent = min(100, ($discount / $subtotal) * 100);
        }

        // Detect if order used any special price based on special_price_data snapshot
        $hasSpecialPrice = $order->orderItems->contains(function (OrderItem $item) {
            $data = $item->special_price_data ?? null;

            if (is_string($data)) {
                $decoded = json_decode($data, true);
                return ! empty($decoded);
            }

            return ! empty($data);
        });

        $deliveryLabel = null;
        if ($itemsCount > 0 && $deliveryFee === 0) {
            $deliveryLabel = 'Free';
        }

        return [
            'subtotal_items'    => $itemsCount,
            'subtotal_quantity' => $itemsQuantity,
            'subtotal'          => PriceFormatter::formatMoney($subtotal),
            'subtotal_raw'      => $subtotal,
            'discount'          => PriceFormatter::formatMoney($discount),
            'discount_raw'      => $discount,
            'discount_percent'  => $discountPercent > 0
                ? PriceFormatter::formatPercentage($discountPercent)
                : '0%',
            'delivery_fee'      => PriceFormatter::formatMoney($deliveryFee),
            'delivery_fee_raw'  => $deliveryFee,
            'delivery_label'    => $deliveryLabel,
            'other_fees'        => PriceFormatter::formatMoney($otherFees),
            'other_fees_raw'        => $otherFees,
            'total'             => PriceFormatter::formatMoney($grandTotal),
            'total_raw'         => $grandTotal,
            'has_special_price' => $hasSpecialPrice,
        ];
    }

    private function getOrderSummary(array $bagSnapshot)
    {
        $summary = $bagSnapshot['summary'] ?? [
            'selected_items_count'     => 0,
            'selected_items_quantity'  => 0,
            'subtotal'                 => 0,
            'discount'                 => 0,
            'grand_total'              => 0,
        ];

        $subtotalRaw = PriceFormatter::parseMoneyStringToInt($summary['subtotal']);  // before discount
        $discountRaw = PriceFormatter::parseMoneyStringToInt($summary['discount']);  // total discount

        $hasSummaryItems = ($summary['selected_items_count'] ?? 0) > 0;

        // Hard-coded delivery and other fees
        $deliveryFeeRaw = 0;
        $deliveryLabel  = $hasSummaryItems ? 'Free' : null;
        $otherFeesRaw = $hasSummaryItems ? 0 : 0;

        $totalRaw = max(0, $subtotalRaw - $discountRaw + $deliveryFeeRaw + $otherFeesRaw);

        $summaryDiscountPercent = 0.0;
        if ($subtotalRaw > 0 && $discountRaw > 0) {
            $summaryDiscountPercent = ($discountRaw / $subtotalRaw) * 100;
        }

        $hasSpecialPrice = isset($bagSnapshot['groups']) 
            ? collect($bagSnapshot['groups'])->flatMap(fn ($group) => isset($group['items']) ? $group['items'] : [])
                ->contains(fn ($item) => $item['has_special_price'] === true)
            : false;

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
            'total'             => number_format($totalRaw, 0, ',', '.'),
            'has_special_price' => $hasSpecialPrice,
        ];
    }

    /**
     * Get selected items IDs from snapshot
     */
    private function getSelectedItemsIds(array $bagSnapshot): array
    {
        $selectedItemsIds = [];

        foreach ($bagSnapshot['groups'] as $group) {
            foreach ($group['items'] as $item) {
                $selectedItemsIds[] = $item['id'];
            }
        }

        return $selectedItemsIds;
    }

    /**
     * Get selected bag items by user and item IDs.
     */
    private function getSelectedBagItems(string $orderId, array $selectedItemsIds)
    {
        return OrderItem::where('order_id', $orderId)
            ->whereIn('id', $selectedItemsIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * Get selected groups of items based on the snapshot data
     */
    private function getSelectedGroups(array $bagSnapshot, $selectedBagItems): array
    {
        $selectedGroups = [];

        foreach ($bagSnapshot['groups'] as $group) {
            $selectedItems = [];

            foreach ($group['items'] as $item) {
                if (empty($item['is_selected'])) {
                    continue;
                }

                // Keep item payload as-is
                $selectedItems[] = $item;
            }

            if (!empty($selectedItems)) {
                $selectedGroups[] = [
                    'brand_id'       => $group['brand_id'],
                    'brand_name'     => $group['brand_name'],
                    'brand_subtotal' => $group['brand_subtotal'],
                    'items'          => $selectedItems,
                ];
            }
        }

        return $selectedGroups;
    }

    public function getPaymentDueDate(array $paymentDetails)
    {
        // Untuk Virtual Account, ambil dari expiration_date dan konversi ke waktu Jakarta
        if (isset($paymentDetails['expiration_date'])) {
            return Carbon::parse($paymentDetails['expiration_date'])
                ->setTimezone('Asia/Jakarta')
                ->format('Y-m-d H:i:s');
        }

        // Untuk QRIS, ambil dari expires_at dan konversi ke waktu Jakarta
        if (isset($paymentDetails['expires_at'])) {
            return Carbon::parse($paymentDetails['expires_at'])
                ->setTimezone('Asia/Jakarta')
                ->format('Y-m-d H:i:s');
        }

        return null;
    }

    public function getPaymentDueDateSecond(array $paymentDetails)
    {
        // Untuk Virtual Account, ambil dari expiration_date dan konversi ke waktu Jakarta
        if (isset($paymentDetails['expiration_date'])) {
            $dueDate = Carbon::parse($paymentDetails['expiration_date'])
                ->setTimezone('Asia/Jakarta')
                ->timestamp;
            
            $currentTime = Carbon::now('Asia/Jakarta')->timestamp;
            return max($dueDate - $currentTime, 0);
        }

        // Untuk QRIS, ambil dari expires_at dan konversi ke waktu Jakarta
        if (isset($paymentDetails['expires_at'])) {
            $dueDate = Carbon::parse($paymentDetails['expires_at'])
                ->setTimezone('Asia/Jakarta')
                ->timestamp;

            $currentTime = Carbon::now('Asia/Jakarta')->timestamp;
            return max($dueDate - $currentTime, 0);
        }

        return null;
    }

    public function prepareManualPaymentDetails($user, $order, $channel, $newGrandTotal)
    {
        $expiresInHours = (int) ($channel->expires_in_hours ?? 24);
        $expirationDate = now('Asia/Jakarta')
            ->addHours($expiresInHours)
            ->utc()
            ->format('Y-m-d\TH:i:s.v\Z');

        return [
            'id' => Str::uuid(),
            'name' => $user->name,
            'status' => 'PENDING',
            'country' => 'ID',
            'currency' => 'IDR',
            'bank_code' => $channel->code,
            'external_id' => (string) $order->id,
            'reference_id' => (string) $order->id,
            'expected_amount' => (int) $newGrandTotal,
            'account_number' => (string) $channel->account_number,
            'account_name' => (string) $channel->account_name,
            'expiration_date' => $expirationDate,
            'type' => 'MANUAL_BANK_TRANSFER',
        ];
    }

    public function processPaymentGateway($order, $channel, $user, $newGrandTotal)
    {
        $amountXendit = $newGrandTotal - $channel->cost;

        $params = [
            'name' => $user->name,
            'reference' => $order->id,
            'code' => $channel->code,
            'external_id' => $order->id,
            'amount' => $amountXendit,
        ];

        try {
            return $channel->process($params);
        } catch (\Throwable $e) {
            Log::error('Payment channel process failed', [
                'order_id' => $order->id,
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function cancelOrder($orderId, $userId, $reason)
    {
        $reason = trim((string) $reason);

        if ($reason === '') {
            throw new Exception(trans('api.order.cancel_reason'));
        }

        return DB::transaction(function () use ($orderId, $userId, $reason) {
            // Fetch order dengan lock untuk keamanan data
            $order = Order::with(['orderItems', 'payment.paymentConfirmations'])
                ->where('id', $orderId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new Exception(trans('api.order.not_found'));
            }

            // Validasi Status (Semua lempar exception yang ditangkap controller sebagai 400)
            if ($order->status == 99) throw new Exception(trans('api.order.already_cancel'));
            if ($order->status == 5) throw new Exception(trans('api.order.already_completed'));
            if (in_array($order->status, [1, 2, 3, 4])) throw new Exception(trans('api.order.cancel_current_status'));

            $latestPaymentConfirmation = $order->payment?->paymentConfirmations()
                ->latest('created_at')
                ->first();

            if ($latestPaymentConfirmation && in_array($latestPaymentConfirmation->status, ['SUBMITTED', 'REJECTED'])) {
                throw new Exception(trans('api.order.cancel_current_status'));
            }

            // 1. Refund Stock
            foreach ($order->orderItems as $item) {
                ProductVariant::where('id', $item->product_variant_id)
                    ->increment('quantity', $item->quantity);
            }

            // 2. Update Status Order
            $order->update([
                'status' => 99,
                'reason' => $reason
            ]);

            // 3. Update Status Payment
            $payment = $order->payment;
            if ($payment) {
                $payment->update([
                    'status' => 'CANCELLED',
                    'canceled_at' => now()
                ]);
            }

            // 4. Revert Voucher
            if ($order->voucher_id) {
                $voucher = Voucher::where('id', $order->voucher_id)->lockForUpdate()->first();
                if ($voucher && $voucher->used_count > 0) {
                    $voucher->decrement('used_count');
                }
            }

            // 5. Log Activity
            $this->activityService->orderLogActivity($order, 'order_canceled', 'Order canceled by user', [
                'user_id' => $userId,
                'order_id' => $order->id,
                'status' => 'CANCELED',
                'reason' => $reason,
            ]);

            // 6. External Services (Notification Firebase, Email & Xendit)
            try {
                // Invalidate Xendit
                if ($payment) {
                    $this->xenditPaymentService->invalidateOnCancelOrChange($payment);
                }

                // Kirim Notifikasi Firebase
                $this->notificationService->send($order->user_id, 'order_cancel', [
                    'order_number' => $order->order_number
                ], "/order-detail/{$order->id}");

                // Kirim Notifikasi Email
                $payload = $this->prepareEmailPayload($order, 'order_canceled');

                $this->emailService->send(
                    'cancel',
                    $order,
                    $payload,
                    $order->user->email
                );
            } catch (\Exception $e) {
                Log::error("External services failed for Order #{$order->order_number}: " . $e->getMessage());
            }

            return $order;
        });
    }


    public function prepareEmailPayload($order, string $templateKey)
    {
        $order->load([
            'user', 
            'orderItems.productVariant.product.brand',
            'store.masterLocation.translations', 
            'courier',
            'channel',
        ]);

        $isPickup = $order->courier?->is_pickup == 1;

        $viewMappingAdmin = [
            'waiting_payment' => [
                'subject' => "Update Status Pesanan - Pesanan #{$order->order_number}",
                'title' => 'Notifikasi Pesanan Baru',
                'body' => 'Ada pesanan baru yang memerlukan verifikasi pembayaran.',
                'path' => '/admin/orders/' . $order->id,
                'view' => 'emails.admins.general_template',
            ],
            'order_confirmed' => [
                'subject' => "Update Status Pesanan - Pesanan #{$order->order_number}",
                'title' => 'Notifikasi Pesanan Baru',
                'body' => '',
                'path' => '/admin/orders/' . $order->id,
                'view' => 'emails.admins.general_template',
            ],
            // 'order_canceled' => [
            //     'subject' => "Pembatalan Pesanan - Pesanan #{$order->order_number}",
            //     'title' => 'Notifikasi Pesanan Baru',
            //     'body' => 'Ada pesanan yang di batalkan.',
            //     'path' => '/admin/orders/' . $order->id,
            //     'view' => 'emails.admins.cancel_template',
            // ],
            'order_packed' => [
                'subject' => "Update Status Pesanan - Pesanan #{$order->order_number}",
                'title' => 'Notifikasi Pesanan Baru',
                'body' => '',
                'path' => '/admin/orders/' . $order->id,
                'view' => 'emails.admins.general_template',
            ],
            'order_delivery' => [
                'subject' => "Update Status Pesanan - Pesanan #{$order->order_number}",
                'title' => 'Notifikasi Pesanan Baru',
                'body' => '',
                'path' => '/admin/orders/' . $order->id,
                'view' => 'emails.admins.general_template',
            ],
            'order_ready_pick_up' => [
                'subject' => "Update Status Pesanan - Pesanan #{$order->order_number}",
                'title' => 'Notifikasi Pesanan Baru',
                'body' => '',
                'path' => '/admin/orders/' . $order->id,
                'view' => 'emails.admins.general_template',
            ],
            'order_pickup_arrived' => [
                'subject' => "Update Status Pesanan - Pesanan #{$order->order_number}",
                'title' => 'Notifikasi Pesanan Baru',
                'body' => '',
                'path' => '/admin/orders/' . $order->id,
                'view' => 'emails.admins.general_template',
            ],
            'order_delivery_arrived' => [
                'subject' => "Update Status Pesanan - Pesanan #{$order->order_number}",
                'title' => 'Notifikasi Pesanan Baru',
                'body' => '',
                'path' => '/admin/orders/' . $order->id,
                'view' => 'emails.admins.general_template',
            ],
        ];

        $viewMappingUser = [
            'waiting_payment' => [
                'subject' => "Pesanan Kamu Telah Dibuat - Pesanan #{$order->order_number}",
                'title' => 'Notifikasi Pesanan Baru',
                'body' => 'Pesanan kamu berhasil dibuat. Silahkan selesaikan pembayaran.',
                'path' => '/order-detail/' . $order->id,
                'view' => $isPickup ? 'emails.users.waiting_pickup' : 'emails.users.waiting_delivery',
            ],
            'order_confirmed' => [
                'subject' => "Pembayaran dikonfirmasi - Pesanan #{$order->order_number}",
                'title' => 'Pembayaran dikonfirmasi',
                'body' => 'Terima kasih! Pembayaran kamu telah kami terima dan pesanan akan segera diproses.',
                'path' => '/order-detail/' . $order->id,
                'view' => $isPickup ? 'emails.users.confirm_pickup' : 'emails.users.confirm_delivery',
            ],
            'order_canceled' => [
                'subject' => "Pembayaran dibatalkan - Pesanan #{$order->order_number}",
                'title' => 'Pembayaran dibatalkan',
                'body' => 'Terima kasih telah berbelanja di Sarinah.',
                'path' => '/order-detail/' . $order->id,
                'view' => $isPickup ? 'emails.users.cancel_pickup' : 'emails.users.cancel_delivery',
            ],
            'order_delivery' => [
                'subject' => "Pesanan Anda sedang dalam perjalanan - Pesanan #{$order->order_number}",
                'title' => 'Pesanan dalam perjalanan',
                'body' => 'Terima kasih telah berbelanja di Sarinah.',
                'path' => '/order-detail/' . $order->id,
                'view' => 'emails.users.ontheway_delivery',
            ],
            'order_ready_pick_up' => [
                'subject' => "Pesanan Anda siap untuk diambil - Pesanan #{$order->order_number}",
                'title' => 'Pesanan siap untuk diambil',
                'body' => 'Terima kasih telah berbelanja di Sarinah.',
                'path' => '/order-detail/' . $order->id,
                'view' => 'emails.users.ready_pickup',
            ],
            'order_pickup_arrived' => [
                'subject' => "Pesanan Anda telah diambil - Pesanan #{$order->order_number}",
                'title' => 'Pesanan telah diambil',
                'body' => 'Terima kasih telah berbelanja di Sarinah.',
                'path' => '/order-detail/' . $order->id,
                'view' => 'emails.users.arrived_pickup',
            ],
            'order_delivery_arrived' => [
                'subject' => "Pesanan Anda telah tiba - Pesanan #{$order->order_number}",
                'title' => 'Pesanan telah tiba',
                'body' => 'Terima kasih telah berbelanja di Sarinah.',
                'path' => '/order-detail/' . $order->id,
                'view' => 'emails.users.arrived_delivery',
            ],
        ];

        $configAdmin = $viewMappingAdmin[$templateKey] ?? null;
        $configUser = $viewMappingUser[$templateKey] ?? null;

        return [
            'admin' => $configAdmin,
            'user' => $configUser
        ];
    }
}