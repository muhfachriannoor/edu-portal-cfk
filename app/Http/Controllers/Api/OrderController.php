<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Channel;
use App\Models\Payment;
use App\Models\Voucher;
use App\Services\LazadaApi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ProductVariant;
use App\Services\OrderService;
use App\View\Data\CourierData;
use App\View\Data\ProductData;
use App\Models\ShoppingBagItem;
use App\Services\VoucherService;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\OrderRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\ShoppingBagService;
use App\Services\XenditPaymentService;
use App\Http\Requests\ConfirmTransferRequest;
use App\Models\PaymentConfirmation;
use App\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @OA\Tag(
 *      name="Order",
 *      description="Endpoints for managing orders and checkout process."
 * )
 *
 * @OA\Schema(
 *      schema="OrderItemRequest",
 *      title="OrderItemRequest",
 *      description="Item data for order creation.",
 *      required={"product_variant_id", "quantity"},
 *
 *      @OA\Property(
 *          property="product_variant_id",
 *          type="integer",
 *          example=101,
 *          description="ID of the product variant to order."
 *      ),
 *      @OA\Property(
 *          property="quantity",
 *          type="integer",
 *          example=2,
 *          description="Quantity of the product variant."
 *      ),
 *      @OA\Property(
 *          property="notes",
 *          type="string",
 *          example="Please handle with care",
 *          nullable=true,
 *          description="Optional notes for this item."
 *      )
 * )
 *
 * @OA\Schema(
 *      schema="OrderRequest",
 *      title="OrderRequest",
 *      description="Payload for creating a new order (checkout).",
 *      required={"channel_id", "courier_id"},
 *
 *      @OA\Property(
 *          property="channel_id",
 *          type="string",
 *          format="uuid",
 *          example="550e8400-e29b-41d4-a716-446655440000",
 *          description="ID of the payment channel (from /generals/channels)."
 *      ),
 *      @OA\Property(
 *          property="courier_id",
 *          type="string",
 *          format="uuid",
 *          example="550e8400-e29b-41d4-a716-446655440000",
 *          description="ID of the courier service for this order (from /master-data/couriers)."
 *      )
 * )
 *
 * @OA\Schema(
 *      schema="OrderResponse",
 *      title="OrderResponse",
 *      description="Order creation response.",
 *
 *      @OA\Property(property="success", type="boolean", example=true),
 *      @OA\Property(property="message", type="string", example="Order created successfully."),
 *      @OA\Property(
 *          property="data",
 *          type="object",
 *          @OA\Property(
 *              property="order_id",
 *              type="string",
 *              format="uuid",
 *              example="550e8400-e29b-41d4-a716-446655440000"
 *          ),
 *          @OA\Property(
 *              property="subtotal",
 *              type="integer",
 *              example=768000
 *          ),
 *          @OA\Property(
 *              property="delivery_cost",
 *              type="integer",
 *              example=18000
 *          ),
 *          @OA\Property(
 *              property="discount",
 *              type="integer",
 *              example=334000
 *          ),
 *          @OA\Property(
 *              property="other_fees",
 *              type="integer",
 *              example=0,
 *              description="Additional fees (internally calculated, not from request)."
 *          ),
 *          @OA\Property(
 *              property="grand_total",
 *              type="integer",
 *              example=434000
 *          ),
 *          @OA\Property(
 *              property="items_count",
 *              type="integer",
 *              example=2
 *          ),
 *          @OA\Property(
 *              property="status",
 *              type="integer",
 *              example=0,
 *              description="Order status: 0=pending,1=processing,2=waiting_for_courier,3=in_transit,4=completed,99=cancelled"
 *          ),
 *          @OA\Property(
 *              property="payment",
 *              type="object",
 *              description="Raw payment payload returned by the payment channel.",
 *              example={
 *                  "status": "PENDING",
 *                  "invoice_url": "https://payments.example.com/invoice/INV-001",
 *                  "external_id": "550e8400-e29b-41d4-a716-446655440000",
 *                  "amount": 434000,
 *                  "expired_at": "2025-11-30T23:59:59Z"
 *              }
 *          )
 *      )
 * )
 * 
 * @OA\Schema(
 *      schema="CancelOrderRequest",
 *      title="CancelOrderRequest",
 *      description="Payload to cancel an order.",
 *      required={"order_id"},
 *      @OA\Property(
 *          property="order_id",
 *          type="string",
 *          format="uuid",
 *          example="550e8400-e29b-41d4-a716-446655440000",
 *          description="ID of the order to be canceled."
 *      ),
 * )
 *
 * @OA\Schema(
 *      schema="CancelOrderResponse",
 *      title="CancelOrderResponse",
 *      description="Response for canceling an order.",
 *      @OA\Property(
 *          property="success",
 *          type="boolean",
 *          example=true
 *      ),
 *      @OA\Property(
 *          property="message",
 *          type="string",
 *          example="Order canceled successfully."
 *      )
 * )
 */
class OrderController extends Controller
{
    use LazadaApi;

    private $activityService;
    private $voucherService;
    private $shoppingBagService;
    private $orderService;
    private $xenditPaymentService;
    private $notificationService;

    public function __construct(
        ActivityService $activityService, VoucherService $voucherService,
        ShoppingBagService $shoppingBagService, OrderService $orderService,
        XenditPaymentService $xenditPaymentService, NotificationService $notificationService
    )
    {
        $this->activityService = $activityService;
        $this->voucherService = $voucherService;
        $this->shoppingBagService = $shoppingBagService;
        $this->orderService = $orderService;
        $this->xenditPaymentService = $xenditPaymentService;
        $this->notificationService = $notificationService;
    }

    /**
     * @OA\Post(
     *      path="/orders",
     *      operationId="createOrder",
     *      tags={"Order"},
     *      summary="Create a new order (checkout process).",
     *      description="Processes checkout by creating an order from shopping bag items. Validates stock, calculates prices, creates order and order items, then initializes payment via selected channel. It also removes selected items from the shopping bag and clears the relevant checkout session.",
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/OrderRequest")
     *      ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="Order created successfully.",
     *          @OA\JsonContent(ref="#/components/schemas/OrderResponse")
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Bad request (e.g., stock insufficient, invalid data).",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Validation failed for some items."
     *              ),
     *              @OA\Property(
     *                  property="errors",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string",
     *                      example="Stock insufficient for Meateria Wagyu Slice 500gr 500g. Available: 1, Requested: 3."
     *                  )
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Resource not found (e.g., channel or courier not found).",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="User address not found or does not belong to you."
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized"
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Validation failed",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Validation failed for order request."),
     *              @OA\Property(
     *                  property="errors",
     *                  type="object",
     *                  @OA\AdditionalProperties(
     *                      type="array",
     *                      @OA\Items(type="string", example="The channel id field is required.")
     *                  )
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Failed to create order. Please try again later."
     *              )
     *          )
     *      )
     * )
     */
    public function store(OrderRequest $request): JsonResponse
    {
        $user   = auth()->user();
        $locale = app()->getLocale();

        $result = $this->orderService->createOrder($user, $locale, $request->channel_id, $request->courier_type);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data'    => $result['data'],
            ], $result['http_code']);
        }

        $response = [
            'success' => false,
            'message' => $result['message'],
        ];

        if (isset($result['errors']) && !empty($result['errors'])) {
            $response['errors'] = $result['errors'];
        }

        return response()->json($response, $result['http_code']);
    }

    /**
     * @OA\Get(
     *     path="/orders/list",
     *     summary="Get order list of authenticated user",
     *     description="Returns a paginated list of orders for the authenticated user. Supports optional filtering by status and search by order number or product name.",
     *     tags={"Order"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter orders by status (pending, processing, waiting_for_courier, in_transit, completed, cancelled).",
     *         @OA\Schema(type="string", example="pending")
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search for orders by order number or product name.",
     *         @OA\Schema(type="string", example="SRN-PU-2601-83971997")
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page.",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order lists successfully retrieved.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order lists successfully retrieved."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="order_id", type="string", example="019ae840-45c9-70be-a643-cb00d52a1e9c"),
     *                         @OA\Property(property="order_code", type="string", example="SRN-PU-2601-83971997"),
     *                         @OA\Property(property="pickup_number", type="string", example="36415557"),
     *                         @OA\Property(property="total_item", type="integer", example=1),
     *                         @OA\Property(property="total_order", type="integer", example=10000),
     *                         @OA\Property(property="file_url", type="string", example="http://127.0.0.1:8000/storage/product/image.jpg"),
     *                         @OA\Property(property="product_name", type="string", example="Test Diskon"),
     *                         @OA\Property(property="transaction_date", type="string", example="2026-01-08 16:56:23"),
     *                         @OA\Property(property="status_name", type="string", example="Preparing"),
     *                         @OA\Property(property="status_key", type="string", example="preparing"),
     *                         @OA\Property(property="channel_code", type="string", example="BANK_TRANSFER"),
     *                         @OA\Property(property="status_alert", type="string", example="null")
     *                     )
     *                 ),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=2),
     *                 @OA\Property(property="last_page", type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */

    public function list()
    {
        $user = auth()->user();

        // Validate search parameters
        $searchTerm = request()->get('search'); // Get search term from query

        // Process orders and filter invalid ones (null)
        $collection = $user->orders
            ->sortByDesc('created_at')
            ->values()
            ->map(function ($order) use ($searchTerm) {
                $payment = $order->payment;

                if (!$payment) {
                    return null; // or throw exception if payment is missing
                }

                $paymentConfirmation = $payment->paymentConfirmations()->latest()->first();
                $status_name = $order->status_name;
                $statusConfirmation = $paymentConfirmation ? strtolower($paymentConfirmation->status) : null;

                // 1. Logika Custom Status Name
                if ($paymentConfirmation) {
                    if ($paymentConfirmation->status === 'SUBMITTED') {
                        $status_name = 'Under Review';
                    } elseif ($paymentConfirmation->status === 'REJECTED') {
                        $status_name = 'Waiting Payment';
                    }
                }

                // 2. Logika Cek cancel atau expired status_key
                if (in_array($order->status_key, ['cancel', 'expired'])) {
                    $status_name = ucfirst($order->status_key); // "Cancel" or "Expired"
                }

                // 3. Logika Pickup Number (Filter Ganda)
                $allowedPickupStatuses = [2, 4]; // Preparing, Ready
                $pickupNumber = (!is_null($statusConfirmation) && in_array($order->status, $allowedPickupStatuses)) 
                    ? $order->pickup_number 
                    : null;

                // Apply search filter on order_number and product_name
                $matchesSearch = false;
                if ($searchTerm) {
                    $searchTerm = strtolower($searchTerm); // Make search term lowercase
                    $matchesSearch = stripos(strtolower($order->order_number), $searchTerm) !== false 
                                    || stripos(strtolower($order->firstProductName()), $searchTerm) !== false; // Also make comparison lowercase
                } else {
                    // If no search term, just return the order
                    $matchesSearch = true;
                }

                if (!$matchesSearch) {
                    return null; // Skip if no match found
                }

                return [
                    'order_id' => $order->id,
                    'order_code' => $order->order_number ?? '#UT378921224',
                    'pickup_number' => $pickupNumber,
                    'total_item' => $order->orderItems->count(),
                    'total_order' => number_format($order->grand_total, 0, ",", "."), 
                    'file_url' => $order->firstProductUrl(),
                    'product_name' => $order->firstProductName(),
                    'product_variant' => $order->firstProductVariant(),
                    'transaction_date' => $order->created_at->format('Y-m-d H:i:s'),
                    'courier_type' => $order->courier?->is_pickup ? 'pickup' : 'delivery',
                    'status_name' => $status_name,
                    'status_key' => $order->status_key,
                    'channel_code' => $order->channel?->channelCategory?->code,
                    'status_alert' => $paymentConfirmation->reject_reason ?? null,
                    'status_confirmation' => $statusConfirmation,
                ];
            })
            ->filter() // Remove any null results after map() (important step to clean data)
            ->when(request()->get('status'), function ($query) {
                return $query->where('status_key', request()->get('status'));
            });

        // Manual pagination using LengthAwarePaginator.
        $perPage = max((int) request()->query('per_page', 10), 1);
        $page    = max((int) request()->query('page', 1), 1);

        $total = $collection->count();
        $items = $collection->forPage($page, $perPage)->values();

        // Remove "page" from query so Laravel can build proper pagination URLs.
        $query = request()->query();
        unset($query['page']);

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
            'pageName' => 'page',
            'query' => $query,
        ]);

        $paginatorArray = $paginator->toArray();
        $meta = $paginatorArray;
        unset($meta['data']);

        return response()->json([
            'success' => true,
            'message' => trans('api.order.retrieve_success'),
            'data'    => $paginatorArray['data'],
            'meta'    => $meta
        ], Response::HTTP_OK);
    }


    /**
     * @OA\Get(
     *     path="/orders/detail/{order_id}",
     *     summary="Get detailed information for a specific order",
     *     description="Returns full order detail including items, payment info, courier, and summary.",
     *     tags={"Order"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="UUID or numeric ID of the order",
     *         @OA\Schema(type="string", example="019ae840-45c9-70be-a643-cb00d52a1e9c")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order detail successfully retrieved.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Order detail successfully retrieved."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status_name", type="string", example="Cancel"),
     *                 @OA\Property(property="transaction_date", type="string", example="Dec 04, 2025"),
     *                 @OA\Property(property="order_id", type="string", example="#UT378921224"),
     *                 @OA\Property(property="courier_code", type="string", example="pick_up"),
     *                 @OA\Property(property="address", type="string", example="Jl. M.H. Thamrin No.11, RT.8/RW.4, Gondangdia, Jakarta Pusat, 10350"),
     *
     *                 @OA\Property(property="payment_method", type="string", example="BCA virtual account"),
     *                 @OA\Property(property="payment_file_url", type="string", example="http://domain.com/storage/channels/image.png"),
     *                 @OA\Property(property="payment_account_number", type="string", example="12345678901234567890"),
     *                 @OA\Property(
     *                     property="payment_procedure",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="Open Mobile Banking"),
     *                         @OA\Property(property="description", type="string", example="Login to your mobile banking app.")
     *                     )
     *                 ),
     *
     *                 @OA\Property(property="subtotal", type="number", example=1600000),
     *                 @OA\Property(property="shipping_total", type="number", example=0),
     *                 @OA\Property(property="grand_total", type="number", example=1602000),
     *
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="item_name", type="string", example="Batik"),
     *                         @OA\Property(property="base_price", type="string", example="750.000"),
     *                         @OA\Property(property="selling_price", type="string", example="700.000"),
     *                         @OA\Property(property="quantity", type="integer", example=1),
     *                         @OA\Property(property="file_url", type="string", example="http://domain.com/storage/product/image.jpg"),
     *
     *                         @OA\Property(
     *                             property="variant",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="key", type="string", example="Size"),
     *                                 @OA\Property(property="value", type="string", example="XL")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */

    public function detail($orderId)
    {
        $user   = auth()->user();
        $locale = app()->getLocale();

        $order = Order::with('orderItems', 'payment')
            ->where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return $this->errorJson(trans('api.order.not_found'), Response::HTTP_NOT_FOUND);
        }

        $shoppingBagSnapshot = $this->orderService->buildShoppingBagSnapshotForUser($order->id, $locale);
        $orderSummary = $this->orderService->buildOrderSummaryFromOrder($order);
        $paymentDetails = $order->payment->payment_details ?? [];

        $paymentConfirmation = PaymentConfirmation::where('payment_id', $order->payment->id)
            ->latest('attempt_no')
            ->first();
    
        $status_name = $order->status_name;
        $statusConfirmation = $paymentConfirmation ? strtolower($paymentConfirmation->status) : null;

        // 1. Logika Custom Status Name berdasarkan Konfirmasi Pembayaran
        if ($paymentConfirmation) {
            if ($paymentConfirmation->status === 'SUBMITTED') {
                $status_name = 'Under Review';
            } elseif ($paymentConfirmation->status === 'REJECTED') {
                $status_name = 'Waiting Payment';
            }
        }

        // 2. Cek status_key cancel atau expired
        if (in_array($order->status_key, ['cancel', 'expired'])) {
            // Jika status cancel atau expired, ganti status_name dengan cancel atau expired
            $status_name = ucfirst($order->status_key); // "Cancel" or "Expired"
        }

        // 3. Logika Pickup Number (Dua Pengecekan)
        $allowedPickupStatuses = [2, 4]; // Preparing, Ready
        $pickupNumber = null;
        if (!is_null($statusConfirmation) && in_array($order->status, $allowedPickupStatuses)) {
            $pickupNumber = $order->pickup_number;
        }

        // 4. Logika Custom Nama Kurir
        $courierName = $order->courier?->name ?? 'No Courier';

        if ($order->courier?->key === 'delivery-sarinah' && $order->external_courier_name) {
            $prefix = ($locale === 'en') ? 'Delivery' : 'Pengiriman';
            
            // Gabungkan menjadi: "Delivery - Nama Kurir" atau "Pengiriman - Nama Kurir"
            $courierName = "{$prefix} - {$order->external_courier_name}";
        }

        $addressLine = $order->recipient_data['address_line'] ?? '';
        $province = $order->recipient_data['province'] ?? '';
        $city = $order->recipient_data['city'] ?? '';
        $district = $order->recipient_data['district'] ?? '';
        $subdistrict = $order->recipient_data['subdistrict'] ?? '';
        $postalCode = $order->recipient_data['postal_code'] ?? '';

        $deliveryAddress = "$addressLine, $province, $city, $district, $subdistrict $postalCode";

        $data = [
            'status_name' => $status_name,
            'status_key'  => $order->status_key,

            'transaction' => [
                'transaction_date'    => $order->created_at->format('M d, Y'),
                'order_id'            => $order->id ?? '',
                'order_number'        => $order->order_number ?? '#UT378921224',
                'pickup_number'       => $pickupNumber,
                'status_confirmation' => $statusConfirmation,
            ],

            // Courier
            'couriers' => [
                // 'id'        => $order->id,
                'name'      => $courierName,
                'is_active' => $order->courier?->is_active,
                'type'      => $order->courier?->is_pickup ? 'pickup' : 'delivery',
                'shipping'  => [
                    'receiver_name' => $order->recipient_name,
                    'phone_number'  => $order->phone_number,
                    'label'         => $order->userAddress?->label,
                    // 'address_line'  => $order->userAddress?->address_line,
                    'address_line' => $deliveryAddress,
                    'city'          => $order->userAddress?->masterAddress?->city_name,
                    'district'      => $order->userAddress?->masterAddress?->district_name,
                    'province'      => $order->userAddress?->masterAddress?->province_name,
                    'postal_code'   => $order->userAddress?->postal_code
                ],
                'shipment' => $order->delivery_payload
                    ? array_merge(
                        $order->delivery_payload,
                        ['tracking_number' => $order->tracking_number]
                    )
                    : null,
                'pickup_location'   => ($order->courier?->is_pickup) ? [
                    'store_id'       => $order->store?->id,
                    'store_slug'     => $order->store?->slug,
                    'location_name'  => $order->store?->masterLocation?->location,
                    'address'        => $order->store?->masterLocation?->address,
                    'city'           => $order->store?->masterLocation?->city,
                    'type_label'     => $order->store?->masterLocation?->type_label,
                    'phone'          => $order->store?->phone,
                    'email'          => $order->store?->email,
                ] : null,
            ],

            // Customer / User
            'recipient' => [
                'receiver_name' => $order->userAddress?->receiver_name,
                'phone_number'  => $order->userAddress?->phone_number,
                'label'         => $order->userAddress?->label,
                'address_line'  => $order->userAddress?->address_line,
                'city'          => $order->userAddress?->masterAddress?->city_name,
                'district'      => $order->userAddress?->masterAddress?->district_name,
                'province'      => $order->userAddress?->masterAddress?->province_name,
                'postal_code'   => $order->userAddress?->postal_code
            ],

            // Payment
            'payment' => [
                'channel_id'        => $order->channel?->id,
                'channel_code'      => $order->channel?->channelCategory?->code,
                'channel_name'      => $order->channel?->name,
                'channel_file_url'  => $order->channel?->file_url,

                'bank_code'         => $paymentDetails['bank_code'] ?? null,
                'account_name'      => $paymentDetails['account_name'] ?? null,
                'account_number'    => $paymentDetails['account_number'] ?? null,
                'due_date'          => $this->orderService->getPaymentDueDate($paymentDetails),
                'second_due_date'   => $this->orderService->getPaymentDueDateSecond($paymentDetails),
                'qr_url'            => $paymentDetails['qr_image'] ?? null,
                'external_id'       => $paymentDetails['reference_id'] ?? null,

                'cc_number'         => null,
                'procedure'         => $order->channel?->procedures->map(function ($procedure) {
                    return [
                        'name'  => $procedure->name,
                        'steps' => $procedure->steps->first()?->description ?? '',
                    ];
                }),
            ],
            // Summary -> now based on `orders` table
            'summary' => $orderSummary,
            // Grouped items -> keep existing structure
            'groups'  => $shoppingBagSnapshot['selectedGroups'],
        ];

        return response()->json([
            'success' => true,
            'message' => trans('api.order.detail_retrieve_success'),
            'data'    => $data,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/orders/{order_id}/cancel",
     *     operationId="cancelOrder",
     *     tags={"Order"},
     *     summary="Cancel an existing order.",
     *     description="Cancels the order, updates stock, and marks payment as canceled.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="ID of the order to be canceled.",
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(
     *                 property="reason",
     *                 type="string",
     *                 example="I want to change my order items.",
     *                 description="Reason for canceling the order."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order canceled successfully.",
     *         @OA\JsonContent(ref="#/components/schemas/CancelOrderResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request (e.g., order is already completed or canceled).",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order is already completed, cannot cancel.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to cancel order. Please try again later.")
     *         )
     *     )
     * )
     */
    public function cancelOrder($orderId, Request $request): JsonResponse
    {
        try {
            $this->orderService->cancelOrder(
                $orderId, 
                auth()->id(), 
                $request->input('reason', '')
            );

            return response()->json([
                'success' => true,
                'message' => trans('api.order.cancel_success'),
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            // Cek apakah ini error sistem (500) atau error bisnis (400)
            $isSystemError = str_contains($e->getMessage(), 'SQLSTATE') || $e instanceof \Error;

            if ($isSystemError) {
                Log::error('Cancel Order System Error: ' . $e->getMessage(), [
                    'order_id' => $orderId,
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => trans('api.order.failed_cancel_order'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Return 400 untuk semua kegagalan validasi bisnis/not found
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @OA\Post(
     *     path="/orders/{order_id}/change-payment",
     *     operationId="changePaymentMethod",
     *     tags={"Order"},
     *     summary="Change the payment method or payment channel for an existing order.",
     *     description="This endpoint updates the payment method or channel for an existing order. A log is created for this change. Maximum 3 changes are allowed per order.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="ID of the order to change the payment for.",
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"channel_id"},
     *             @OA\Property(
     *                 property="channel_id",
     *                 type="string",
     *                 format="uuid",
     *                 example="550e8400-e29b-41d4-a716-446655440000",
     *                 description="The new payment channel ID."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method/channel updated successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment method successfully updated."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request (e.g., order not found or invalid payment data).",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid payment data or order.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update payment method.")
     *         )
     *     )
     * )
     */
    public function changePaymentMethod($orderId, Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate(['channel_id' => ['required', 'string']]);
        $newChannelId = $validated['channel_id'];

        // 1. Pre-validation
        $order = Order::find($orderId);
        // Validate Data orders
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => trans('api.order.not_found'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Only allow change for pending orders (status = 0)
        if ($order->status != 0) {
            return response()->json([
                'success' => false,
                'message' => trans('api.order.payment_cannot_change'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Enforce max 3 payment changes
        if ($order->payment_change_count >= 3) {
            return response()->json([
                'success' => false,
                'message' => trans('api.order.payment_change_limit'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $payment = Payment::where('order_id', $order->id)->first();
        // Validate Data payments
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => trans('api.order.payment_not_found'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $oldChannelId = $payment->channel_id;
        $oldChannelName = optional($payment->channel)->name;
        $oldDetails = $this->xenditPaymentService->normalizePaymentDetails($payment->payment_details);

        if ((string) $oldChannelId === (string) $newChannelId) {
            return response()->json([
                'success' => false,
                'message' => trans('api.order.payment_must_different')
            ], Response::HTTP_BAD_REQUEST);
        }

        $channel = Channel::find($newChannelId);
        // Validate Data channels
        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => trans('api.order.channel_not_found'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // 2. Start Transaction
        DB::beginTransaction();
        try {
            // Lock order for race-condition
            $order = Order::where('id', $orderId)->lockForUpdate()->first();

            // Calculate New Grand Total
            $newGrandTotal = ($order->subtotal - $order->discount) + $order->delivery_cost + $channel->cost;

            // 3. API Call (Xendit/Manual)
            if ($channel->is_manual) {
                $response = $this->orderService->prepareManualPaymentDetails($user, $order, $channel, $newGrandTotal);
            } else {
                $response = $this->orderService->processPaymentGateway($order, $channel, $user, $newGrandTotal);
            }

            if (!$response) {
                throw new \Exception("Failed to process with payment provider.");
            }

            // 4. Database Persistance
            $order->update([
                'channel_id' => $channel->id,
                'other_fees' => $channel->cost,
                'grand_total' => $newGrandTotal,
                'payment_change_count' => ($order->payment_change_count ?? 0) + 1,
            ]);

            $payment->update([
                'channel_id' => $channel->id,
                'payment_details' => $response,
                'amount' => $newGrandTotal,
            ]);

            $this->activityService->orderLogActivity(
                $order,
                'payment_method_changed',
                'Payment method/channel updated.',
                [
                    'user_id' => $user->id,
                    'old_channel_id' => $oldChannelId,
                    'old_channel_name' => $oldChannelName,
                    'new_channel_id' => $channel->id,
                    'new_channel_name' => $channel->name,
                ]
            );

            DB::commit();

            // 5. Post-Commit: Invalidate Old Virtual Account
            $this->xenditPaymentService->invalidateFromOldDetails($oldDetails);

            return response()->json([
                'success' => true,
                'message' => trans('api.order.payment_change_success'),
                'data' => [
                    'order_id' => $order->id,
                    'payment_change_count' => $order->payment_change_count,
                    'payment' => $response,
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Change payment failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment method.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function createPackageSample(){
    //     $orderId = request()->get('order_id');

    //     return $lazadaOp = $this->createPackage($orderId);

    //     if($lazadaOp->success){
    //         return response()->json([
    //             'success' => true,
    //             'message' => "Shipment has been retrieved successfully",
    //             'data' => [
    //                 'order_id' => $orderId,
    //                 'response' => $lazadaOp
    //             ]
    //         ]);
    //     }
        
    //     Log::error($lazadaOp->errorMessage ?? '');
    //     return response()->json([
    //         'success' => false,
    //         'message' => "Something went wrong",
    //         'lazada_message' => $lazadaOp->errorMessage ?? ''
    //     ]);
    // }

    /**
     * @OA\Post(
     *     path="/orders/delivery-list",
     *     operationId="getDeliveryList",
     *     tags={"Order"},
     *     summary="Get delivery list and shipping fee",
     *     description="Retrieve available delivery methods, calculated shipping fee, and estimated delivery date based on the user's shopping bag.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Delivery list retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Delivery list has been retrieved successfully"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Standard"),
     *                     @OA\Property(property="fee", type="integer", example=10000),
     *                     @OA\Property(property="is_discount", type="boolean", example=false),
     *                     @OA\Property(
     *                         property="estimated",
     *                         type="string",
     *                         format="date",
     *                         example="2025-12-25"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Data not found or external service error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lazada API not connected"),
     *             @OA\Property(
     *                 property="lazada_message",
     *                 type="string",
     *                 example="Upstream service error"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="No item selected",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No item has been selected")
     *         )
     *     )
     * )
     */
    public function deliveryList(Request $request){
        
        $userId = auth()->id();
        $locale = app()->getLocale();
        
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($userId, $locale);

        $from = $snapshot['couriers']['pickup_location']['subdistrict_id'] ?? null;
        $to = $snapshot['user_address']['subdistrict_id'] ?? null;
        
        if( count($snapshot['groups']) > 0 ){ // Hanya menghitung apabila ada item di shopping bag
            $lazadaShipping  = $this->getLazadaShipping($snapshot);
            $sarinahShipping = $this->getSarinahShipping();

            $lists = collect([
                $lazadaShipping,
                $sarinahShipping,
            ])
            ->filter()
            ->values()
            ->toArray();

            return response()->json([
                'success' => true,
                'message' => trans('api.order.delivery_retrieve_success'),
                'data'    => $lists,
            ], Response::HTTP_OK);
        }

        \Log::error('No item has been selected for user: ' . $userId);
        return response()->json([
            'success' => false,
            'message' => "No item has been selected"
        ]);
    }

    /**
     * Get Sarinah Shipping Fee 
     */ 
    private function getSarinahShipping(){
        $couriers = collect(CourierData::listsForApi());
        $name = "Sarinah";
        $key = "delivery-sarinah";
        $fee = $couriers->where('key', $key)->first()['fee'];

        return [
            'name' => $name,
            'key' => $key,
            'fee_raw' => $fee,
            'fee' => number_format($fee, 0, ',', '.'),
            'is_discount' => false,
            'estimated' => Carbon::now()->addDays(3)->format('Y-m-d')
        ];
    }

    /**
     * Get Lazada Shipping Fee 
     * @return null if lazada not connected OR calculation failed
     */ 
    private function getLazadaShipping($snapshot){
        $lazadaOp = $this->getShippingFee($snapshot);

        if(!$lazadaOp)
            return null;

        if ($lazadaOp?->success === true) {
            $dataShippingFee = $this->calculateShippingFee($lazadaOp);

            return $dataShippingFee;
        }
        
        \Log::error($lazadaOp->errorMessage ?? '');
        return null;
    }

    /**
     * Determine whether the order is eligible for Buy Again.
     * Adjust the status logic based on your own status implementation.
     */
    private function isOrderEligibleForBuyAgain(Order $order): bool
    {
        // Example: allow only completed orders
        // If your status is numeric (e.g. 5 = completed), adjust this array
        $allowedStatuses = [5, 'completed', 'COMPLETED'];

        return in_array($order->status, $allowedStatuses, false);
    }

    /**
     * Format a list of product names for error messages.
     * Example output:
     * - "Product A"
     * - "Product A, Product B"
     * - "Product A, Product B, Product C"
     * - "Product A, Product B, Product C, and 4 more item(s)"
     */
    private function formatSkippedProductList(array $names, int $maxNamesToShow = 3): string
    {
        // Normalize, trim, and make names unique
        $names = array_values(array_filter(array_unique(array_map('trim', $names))));
        $total = count($names);

        if ($total === 0) {
            return '';
        }

        if ($total <= $maxNamesToShow) {
            return implode(', ', $names);
        }

        $displayed = array_slice($names, 0, $maxNamesToShow);
        $remaining = $total - $maxNamesToShow;

        return implode(', ', $displayed) . ', ' . trans('api.order.and_more', ['count' => $remaining]);
    }

    /**
     * Build the final Buy Again response message and meta.
     * 
     * Returns array: [bool $success, int $httpCode, string $message]
     */
    private function buildBuyAgainResponseMessage(int $addedCount, int $skippedCount, array $skippedProductNames): array
    {
        // All failed: no item added
        if ($addedCount === 0 && $skippedCount > 0) {
            $productsLabel = $this->formatSkippedProductList($skippedProductNames);
            $message = trans('api.order.failed_buy_again');
            
            if ($productsLabel !== '') {
                $message .= ': ' . $productsLabel . '.';
            }

            return [false, Response::HTTP_BAD_REQUEST, $message];
        }

        // Full success: all items added
        if ($addedCount > 0 && $skippedCount === 0) {
            $message = trans('api.order.buy_again_success', [
                'added_count' => $addedCount,
                'item_word' => $addedCount > 1 ? 'items' : 'item' // English only plural logic
            ]);

            return [true, Response::HTTP_OK, $message];
        }

        // Partial success: some items added, some skipped
        $productsLabel = $this->formatSkippedProductList($skippedProductNames);
        $message = trans('api.order.buy_again_partial', [
            'added_count'   => $addedCount,
            'item_word'     => $addedCount > 1 ? 'items' : 'item',
            'skipped_count' => $skippedCount,
            'skipped_word'  => $skippedCount > 1 ? 'items' : 'item',
            'products'      => $productsLabel
        ]);

        return [true, Response::HTTP_OK, $message];
    }

    /**
     * Build readable label for product in user-facing messages.
     * Example: "Test Product Variant New 1 (White / S)"
     */
    private function buildItemLabelForMessage(ProductVariant $variant, ?string $locale = null): string
    {
        // Fallback to current app locale if not provided
        $locale = $locale ?: app()->getLocale();

        // Try to get display details (product_name + variant_names)
        $displayDetails = ProductData::getVariantDisplayDetails($variant->id, $locale);

        // Resolve product name
        if ($displayDetails && !empty($displayDetails['product_name'])) {
            $productName = $displayDetails['product_name'];
        } elseif ($variant->product && !empty($variant->product->name)) {
            $productName = $variant->product->name;
        } else {
            // Safe fallback to avoid empty string in message
            $productName = trans('api.order.unknown_product');
        }

        // Resolve variant details (e.g. "White / S")
        $variantDetails = null;
        if ($displayDetails && !empty($displayDetails['variant_names'])) {
            $variantDetails = $displayDetails['variant_names'];
        }

        // If there are variant details, append them in parentheses
        if ($variantDetails && trim($variantDetails) !== '') {
            return sprintf('%s (%s)', $productName, $variantDetails);
        }

        // Non-variant product or no variant information
        return $productName;
    }

    /**
     * @OA\Post(
     *     path="/orders/{order_id}/buy-again",
     *     operationId="buyAgainFromOrder",
     *     tags={"Order"},
     *     summary="Re-add items from a completed order into the shopping bag.",
     *     description="For each item in the order, tries to add it back into the shopping bag using the original quantities. 
     *                  If some items are out of stock, they will be skipped and reported in the message. 
     *                  Response only contains success flag and a descriptive message (no data payload).",
     *     security={{"bearerAuth": {}}}, 
     *
     *     @OA\Parameter(
     *          name="order_id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer", example=123),
     *          description="The ID of the order to be used for Buy Again."
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Buy Again processed successfully (all or some items added).",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="2 items added to your shopping bag. 1 item could not be added because it is out of stock: Product A."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Order is not eligible for Buy Again or all items are out of stock.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="This order is not eligible for Buy Again. Only completed orders can be used.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Order does not belong to the current user.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You are not allowed to access this order.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Order not found.")
     *         )
     *     ),
     *     @OA\Response(
     *          response=500,
     *          description="Internal error while processing Buy Again.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Failed to process Buy Again due to an internal error. Please try again later.")
     *          )
     *     )
     * )
     */
    public function buyAgain(string $order_id): JsonResponse
    {
        $userId = auth()->id();
        $locale = app()->getLocale();

        $order = Order::with(['orderItems.productVariant.product.store'])->find($order_id);

        if (!$order) {
            return $this->errorJson(trans('api.order.not_found'), Response::HTTP_BAD_REQUEST);
        }
        if ((int) $order->user_id !== (int) $userId) {
            return $this->errorJson(trans('api.order.not_allowed_access'), Response::HTTP_FORBIDDEN);
        }
        if (!$this->isOrderEligibleForBuyAgain($order)) {
            return $this->errorJson(trans('api.order.not_eligible_buy_again'), Response::HTTP_BAD_REQUEST);
        }

        $orderItems = $order->orderItems ?? collect();
        if ($orderItems->isEmpty()) {
            return $this->errorJson(trans('api.order.not_contain_buy_again'), Response::HTTP_BAD_REQUEST);
        }

        $firstItem = $orderItems->first();
        $store = $firstItem->productVariant->product->store ?? null;
        $storeId = $store->id ?? null;

        if (!$storeId) {
            return $this->errorJson(trans('api.shopping_bag.product_not_have_store_validation'), Response::HTTP_BAD_REQUEST);
        }

        $this->shoppingBagService->unselectItemsFromOtherStores($userId, $storeId);
        $this->shoppingBagService->syncFulfillmentSession($userId, $store);

        $addedCount = 0;
        $skippedCount = 0;
        $skippedProductNames = [];

        DB::beginTransaction();
        try {
            foreach ($orderItems as $orderItem) {
                $variant = $orderItem->productVariant;
                $itemLabel = $variant
                    ? $this->buildItemLabelForMessage($variant, $locale)
                    : ($orderItem->product_name ?? 'Unknown product');
                
                if (!$variant || $variant->is_active === false) {
                    $skippedCount++; $skippedProductNames[] = $itemLabel; continue;
                }

                $liveStock = (int) $variant->quantity;
                $orderQty = (int) $orderItem->quantity;

                if ($orderQty <= 0 || $liveStock <= 0) {
                    $skippedCount++; $skippedProductNames[] = $itemLabel; continue;
                }

                $bagItem = ShoppingBagItem::where('user_id', $userId)
                    ->where('store_id', $storeId)
                    ->where('product_variant_id', $variant->id)
                    ->first();
                
                $oldQuantity = $bagItem ? (int) $bagItem->quantity : 0;
                $totalRequestedQty = $oldQuantity + $orderQty;

                if ($totalRequestedQty > $liveStock) {
                    $skippedCount++; $skippedProductNames[] = $itemLabel; continue;
                }

                if ($bagItem) {
                    $bagItem->quantity = $totalRequestedQty;
                    $bagItem->is_selected = true;
                    $bagItem->save();
                } else {
                    ShoppingBagItem::create([
                        'user_id' => $userId,
                        'store_id' => $storeId,
                        'brand_id' => $variant->product->brand_id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $totalRequestedQty,
                        'is_selected' => true,
                    ]);
                }
                $addedCount++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Buy Again failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorJson(trans('api.order.failed_buy_again'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        [$success, $httpCode, $message] = $this->buildBuyAgainResponseMessage($addedCount, $skippedCount, $skippedProductNames);

        return response()->json([
            'success' => $success,
            'message' => $message,
        ], $httpCode);
    }

    /**
     * @OA\Get(
     *     path="/orders/{order_id}/invoice",
     *     summary="Get invoice as HTML or PDF",
     *     description="Get the invoice for a specific order as HTML (for print) or PDF (for download). Use ?format=pdf for PDF, default is HTML. Only the order owner can access this endpoint.",
     *     tags={"Order"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="UUID or numeric ID of the order",
     *         @OA\Schema(type="string", example="019ae840-45c9-70be-a643-cb00d52a1e9c")
     *     ),
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         required=false,
     *         description="Format: pdf (download) or html (print)",
     *         @OA\Schema(type="string", enum={"pdf","html"}, default="html")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="HTML or PDF content returned.",
     *         @OA\MediaType(mediaType="application/pdf"),
     *         @OA\MediaType(mediaType="text/html")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found or not owned by user."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function invoice(Request $request, $orderId)
    {
        $order = Order::find($orderId);
    
        if (!$order) {
            return $this->errorJson(trans('api.order.not_found'), Response::HTTP_BAD_REQUEST);
        }

        if ($request->hasValidSignature()) {
           
        } else {
            $user = auth()->user();
            if (!$user || $order->user_id !== $user->id) {
                return $this->errorJson(trans('api.order.not_found'), Response::HTTP_UNAUTHORIZED);
            }
        }

        $format = request()->query('format', 'html');
        if ($format === 'pdf') {
            return $this->orderService->generateInvoicePdfApi($orderId);
        }
        return $this->orderService->generateInvoiceHtml($orderId);
    }

    /**
     * @OA\Post(
     *     path="/orders/{order_id}/confirm-transfer",
     *     summary="Submit manual transfer confirmation (upload receipt)",
     *     description="Submit proof of payment for manual bank transfer orders. Only order owner can access. Max 3 attempts. Cannot submit if payment expired or previous submission still waiting review.",
     *     tags={"Order"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="UUID or numeric ID of the order",
     *         @OA\Schema(type="string", example="019ae840-45c9-70be-a643-cb00d52a1e9c")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"sender_bank_name","sender_account_name","transfer_amount","transfer_date","receipt"},
     *                 @OA\Property(
     *                     property="sender_bank_name",
     *                     type="string",
     *                     example="BCA"
     *                 ),
     *                 @OA\Property(
     *                     property="sender_account_name",
     *                     type="string",
     *                     example="Budi Santoso"
     *                 ),
     *                 @OA\Property(
     *                     property="transfer_amount",
     *                     type="integer",
     *                     format="int64",
     *                     example=10000,
     *                     description="Amount transferred by user (can be different from expected_amount)"
     *                 ),
     *                 @OA\Property(
     *                     property="transfer_date",
     *                     type="string",
     *                     format="date",
     *                     example="2025-12-30",
     *                     description="Transfer date (date only)"
     *                 ),
     *                 @OA\Property(
     *                     property="receipt",
     *                     type="string",
     *                     format="binary",
     *                     description="Receipt file (JPG/PNG/PDF), max 5MB"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Payment confirmation submitted.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="confirmation_id", type="string", example="3b8e4a4a-1a2b-4b5d-9c3e-6a7a1b2c3d4e"),
     *                 @OA\Property(property="payment_id", type="string", example="019b6946-669b-729c-9d24-4aa933fd5f6e"),
     *                 @OA\Property(property="attempt_no", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="SUBMITTED"),
     *                 @OA\Property(property="file_confirm", type="string", example="https://your-cdn.com/storage/payment_confirmations/.../receipt.pdf")
     *             ),
     *             @OA\Property(property="message", type="string", example="Payment confirmation submitted."),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request (not manual channel / payment expired / max attempts reached / invalid state).",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="data", nullable=true, example=null),
     *             @OA\Property(property="message", type="string", example="Payment is expired. You cannot submit confirmation."),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden (not owner of order).",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="data", nullable=true, example=null),
     *             @OA\Property(property="message", type="string", example="You are not allowed to access this order."),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="data", nullable=true, example=null),
     *             @OA\Property(property="message", type="string", example="Order not found."),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=409,
     *         description="Conflict (already submitted waiting for review / already approved).",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="data", nullable=true, example=null),
     *             @OA\Property(property="message", type="string", example="Your confirmation is already submitted and waiting for review."),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function confirmTransfer(ConfirmTransferRequest $request, string $orderId)
    {
        $result = $this->orderService->confirmTransfer(
            auth()->user(),
            $orderId,
            $request->validated(),
            $request->file('receipt')
        );

        if ($result['success']) {
            $order = Order::with('orderItems', 'payment')
                ->where('id', $orderId)
                ->where('user_id', auth()->id())
                ->first();

            if ($order) {
                $paymentConfirmation = PaymentConfirmation::where('payment_id', $order->payment->id)
                    ->latest('attempt_no')
                    ->first();
                $statusConfirmation = $paymentConfirmation ? strtolower($paymentConfirmation->status) : '';

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'status_name' => $order->status_name,
                        'status_key' => $order->status_key,
                        'transaction' => [
                            'transaction_date' => $order->created_at->format('M d, Y'),
                            'order_id' => $orderId ?? '',
                            'order_number' => $order->order_number ?? '#UT378921224',
                            'status_confirmation' => $statusConfirmation,
                        ],
                        'couriers' => [
                            // 'id'        => $order->id,
                            'name'      => $order->courier?->name,
                            'is_active' => $order->courier?->is_active,
                            'type'      => $order->courier?->is_pickup ? 'pickup' : 'delivery',
                            'shipping'  => null,
                            'pickup_location' => ($order->courier?->is_pickup) ? [
                                'store_id' => $order->store?->id,
                                'store_slug' => $order->store?->slug,
                                'location_name' => $order->store?->masterLocation?->location,
                                'address' => $order->store?->masterLocation?->address,
                                'city' => $order->store?->masterLocation?->city,
                                'type_label' => $order->store?->masterLocation?->type_label,
                                'phone' => $order->store?->phone,
                                'email' => $order->store?->email,
                            ] : null,
                        ],
                        'recipient' => $order->userAddress?->only([
                            'receiver_name',
                            'phone_number',
                            'label',
                            'address_line',
                            'city',
                            'district',
                            'province',
                            'postal_code',
                        ]),
                        'payment' => [
                            'channel_id' => $order->channel?->id,
                            'channel_code' => $order->channel?->channelCategory?->code,
                            'channel_name' => $order->channel?->name,
                            'channel_file_url' => $order->channel?->file_url,

                            'bank_code' => $paymentDetails['bank_code'] ?? null,
                            'account_name' => $paymentDetails['account_name'] ?? null,
                            'account_number' => $paymentDetails['account_number'] ?? null,
                            'due_date' => $this->orderService->getPaymentDueDate($order->payment->payment_details),
                            'second_due_date' => $this->orderService->getPaymentDueDateSecond($order->payment->payment_details),
                            'qr_url' => $paymentDetails['qr_image'] ?? null,
                            'external_id' => $paymentDetails['reference_id'] ?? null,

                            'cc_number' => null,
                            'procedure' => $order->channel?->procedures->map(function ($procedure) {
                                return [
                                    'name' => $procedure->name,
                                    'steps' => $procedure->steps->first()?->description ?? '',
                                ];
                            }),
                        ],
                        'summary' => $this->orderService->buildOrderSummaryFromOrder($order),
                        'groups' => $this->orderService->buildShoppingBagSnapshotForUser($order->id, app()->getLocale())['selectedGroups'],
                    ]
                ], Response::HTTP_OK);
            }
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['http_code']);
    }

    /**
     * Helper for error response.
     */
    private function errorJson($message, $httpCode, $errors = null)
    {
        $resp = ['success' => false, 'message' => $message];
        if ($errors) $resp['errors'] = $errors;
        return response()->json($resp, $httpCode);
    }
}