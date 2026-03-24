<?php

namespace App\Http\Controllers\Cms;

use App\Models\Order;
use App\Models\Setting;
use App\Models\PaymentConfirmation;
use App\Services\OrderService;
use App\Http\Controllers\Cms\CmsController;
use App\Services\EmailNotificationService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'order';
    private $orderService;
    private $notificationService;
    private $emailService;

    // Status mapping for pickup
    private const PICKUP_STATUS_MAP = [
        'ready_pick_up' => 4,
        'completed'     => 5,
    ];

    /**
     * Constructor: Authorize resource wildcard.
     */
    public function __construct(
        OrderService $orderService,
        NotificationService $notificationService,
        EmailNotificationService $emailService,
    ) {
        $this->authorizeResourceWildcard($this->resourceName);
        $this->orderService = $orderService;
        $this->notificationService = $notificationService;
        $this->emailService = $emailService;
    }


    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        $orderStatusRoles = Setting::where('key', 'ORDER_STATUS_ROLES')->first();
        $userRole = auth()->user()->role;
        $rolesData = collect($orderStatusRoles->data ?? []);

        $courierStatusKeys = ['pending', 'sent_to_courier', 'preparing', 'on_delivery', 'completed'];
        $pickupStatusKeys = ['pending', 'preparing', 'ready_pick_up', 'completed'];

        $formatStatus = function ($keys) use ($rolesData, $userRole) {
            return collect($keys)->map(function ($key) use($rolesData) {
                $statusId = Order::STATUS[$key] ?? null;

                $label = Order::getStatusMapping()[$statusId] ?? ucfirst($key);

                // Override: Jika key-nya 'pending', ganti labelnya jadi 'Pending'
                if ($key === 'pending') {
                    $label = 'Pending';
                }

                return [
                    'value' => $key,
                    'label' => $label,
                    'roles' => collect($rolesData->get($key, []))->pluck('name')->toArray()
                ];
            })->filter(function ($item) use ($userRole) {
                return $userRole === 'Superadmin' || in_array($userRole, $item['roles']);
            })->values();
        };

        $courierStatuses = $formatStatus($courierStatusKeys);
        $pickupStatuses = $formatStatus($pickupStatusKeys);

        $defaultStatus = $courierStatuses->pluck('value')->first() ?? $pickupStatuses->pluck('value')->first();

        return view("cms.{$this->resourceName}.index", [
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'Order List'
            ],
            'courierStatuses' => $courierStatuses,
            'pickupStatuses' => $pickupStatuses,
            'defaultStatus' => $defaultStatus,
        ]);
    }

    /**
     * API: Get Pick Up in Store orders (for CMS data)
     */
    public function pickupOrdersApi()
    {
        $status = request('status');
        $search = request('search');

        $meta = [];
        $orders = $this->orderService->getPickupOrdersForCms($status, $search, $meta);

        return response()->json([
            'success' => true,
            'data' => $orders,
            'meta' => $meta,
        ]);
    }

    /**
     * API: Get Ship by Courier orders (for CMS data)
     */
    public function courierOrdersApi()
    {
        $status = request('status');
        $search = request('search');

        $meta = [];
        $orders = $this->orderService->getCourierOrdersForCms($status, $search, $meta);

        return response()->json([
            'success' => true,
            'data' => $orders,
            'meta' => $meta,
        ]);
    }

    /**
     * Update order status based on the mapping in Order Model.
     * This method handles transitions for both Pickup (2, 4, 5)
     * and Courier (2, 3, 5) delivery methods.
     * 
     */
    public function setChangeOrderStatus(Order $order)
    {
        $statusKey = request('status');

        /**
         * Validate if the provided status key exists in the Order model's constants.
         * This ensures only defined statuses (e.g., preparing, on_delivery, etc.) can be processed.
         */
        if (!isset(Order::STATUS[$statusKey])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition requested.',
            ], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            DB::beginTransaction();

            // Update the order status using the integer value from the Model mapping
            $order->status = Order::STATUS[$statusKey];
            $order->save();

            /**
             * Log the action to the order_logs table for audit purposes.
             * Capture the action type, a human-readable description, and admin details.
             */
            if (method_exists($order, 'orderLog')) {
                $statusLabel = ucfirst(str_replace('_', ' ', $statusKey));

                $order->orderLog()->create([
                    'action' => 'set_' . $statusKey,
                    'description' => "Order set to {$statusLabel} by admin",
                    'additional_data' => [
                        'admin_id' => auth()->id(),
                        'admin_name' => auth()->user()?->name,
                        'ip_address' => request()->ip(),
                    ],
                ]);
            }

            DB::commit();
            
            $statusNotificationMap = [
                'preparing' => 'order_packed',
                'completed' => 'order_delivered',
                'ready_pick_up' => 'order_ready_for_pickup',
            ];

            // Send Notification to firebase (user only)
            if (isset($statusNotificationMap[$statusKey])) {
                $event = $statusNotificationMap[$statusKey];

                try {
                    $this->notificationService->send(
                        $order->user_id,
                        $event,
                        ['order_number' => $order->order_number],
                        "/order-detail/{$order->id}"
                    );
                } catch (\Exception $e) {
                    Log::error("Notification {$event} failed for order {$order->order_number}: {$e->getMessage()}");
                }
            }

            $emailConfigMap = [
                'preparing'     => 'order_packed', // Tidak kirim ke user (admin only)
                'ready_pick_up' => 'order_ready_pick_up',
                'completed'     => 'order_delivery_arrived',
            ];

            // Send Notification to email for Pickup Method
            if (isset($emailConfigMap[$statusKey])) {
                try {
                    $order->loadMissing('user');
                    
                    // Tentukan payload berdasarkan mapping
                    $emailType = $emailConfigMap[$statusKey];
                    $payload = $this->orderService->prepareEmailPayload($order, $emailType);

                    $recipientEmail = ($statusKey === 'preparing') ? null : $order->user->email;

                    $this->emailService->send(
                        $statusKey,
                        $order,
                        $payload,
                        $recipientEmail
                    );
                } catch (\Exception $e) {
                    Log::error("Email Notification failed for status {$statusKey}: {$e->getMessage()}");
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Unified invoice endpoint for CMS (HTML or PDF)
     * GET /order/{order}/invoice?format=pdf|html
     */
    public function invoice($orderId)
    {
        $user = auth()->user();
        $order = Order::findOrFail($orderId);
        // CMS: admin can access all, else only own order
        
        $format = request()->query('format', 'html');

        if ($format === 'pdf') {
            return $this->orderService->generateInvoicePdf($orderId);
        }
        
        return $this->orderService->generateInvoiceHtml($orderId);
    }

    public function reviewPaymentConfirmation(Request $request, PaymentConfirmation $confirmation)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'reject_reason' => 'nullable|string|max:2000',
            'ack_mismatch' => 'nullable|boolean',
        ]);

        // Reject must have reason
        if ($validated['action'] === 'reject') {
            $reason = trim((string) ($validated['reject_reason'] ?? ''));
            if ($reason === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Reject reason is required.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $result = $this->orderService->reviewManualTransferConfirmation(
            auth()->user(),
            $confirmation->id,
            $validated['action'],
            $validated['reject_reason'] ?? null,
            (bool) ($validated['ack_mismatch'] ?? false)
        );

        return response()->json([
            'success' => $result['success'],
            'data'    => $result['data'],
            'message' => $result['message'],
            'errors'  => $result['errors'],
        ], $result['http_code']);
    }

    public function verifyPickupNumber(Order $order, Request $request)
    {
        $pickupNumber = $request->input('pickup_number');

        if ($order->pickup_number !== $pickupNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid pickup number.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // If pickup number is correct, update the order status
        $order->status = self::PICKUP_STATUS_MAP['completed'];
        $order->save();

        // Log ke order_logs
        if (method_exists($order, 'orderLog')) {
            $order->orderLog()->create([
                'action' => 'set_completed',
                'description' => 'Order set to completed by admin',
                'additional_data' => [
                    'admin_id' => auth()->id(),
                    'admin_name' => auth()->user()?->name,
                ],
            ]);
        }

        try {
            // Send Notification to firebase (user only)
            $this->notificationService->send($order->user_id, 'order_pickup_delivered', [
                'order_number' => $order->order_number
            ], "/order-detail/{$order->id}");

            // Send Notification to email
            $payload = $this->orderService->prepareEmailPayload($order, 'order_pickup_arrived');

            $this->emailService->send(
                'completed',
                $order,
                $payload,
                $order->user->email
            );
        } catch (\Exception $e) {
            Log::error('Notification order_pickup_delivered failed for order ' . $order->order_number . ': ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated to completed.',
        ]);
    }

    /**
     * Set order status to 'on_delivery' and save courier information.
     * Required for manual delivery methods (e.g., Delivery - Sarinah).
     * 
     * @param Order $order
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setOrderOnDelivery(Order $order, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'external_courier_name' => 'required|string|min:3|max:255',
            'tracking_number' => 'required|string|min:5|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Courier name and tracking number are required.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();
            $estimatedDate = now()->addDays(3)->format('Y-m-d');
            $courierFee = $order->courier?->fee ?? 0;
        
            $deliveryPayload = [
                'fee' => number_format($courierFee, 0, ',', '.'),
                'key' => $order->courier?->key,
                'name' => "Delivery - " . $request->external_courier_name,
                'fee_raw' => (int) $courierFee,
                'estimated' => $estimatedDate,
                'is_discount' => false
            ];

            $statusKey = 'on_delivery';
            $order->status = Order::STATUS[$statusKey];
            $order->external_courier_name = $request->external_courier_name;
            $order->tracking_number = $request->tracking_number;
            $order->delivery_payload = $deliveryPayload;
            $order->save();

            // 3. Log to order_logs
            if (method_exists($order, 'orderLog')) {
                $statusLabel = ucfirst(str_replace('_', ' ', $statusKey));

                $order->orderLog()->create([
                    'action' => 'set_' . $statusKey,
                    'description' => "Order set to {$statusLabel} by admin",
                    'additional_data' => [
                        'admin_id' => auth()->id(),
                        'admin_name' => auth()->user()?->name,
                        'ip_address' => request()->ip(),
                        'external_courier_name' => $order->external_courier_name,
                        'tracking_number' => $order->tracking_number,
                        'delivery_payload' => $deliveryPayload,
                    ],
                ]);
            }

            DB::commit();

            try {
                // Send Notification to firebase (user only)
                $this->notificationService->send($order->user_id, 'order_shipped', [
                    'order_number' => $order->order_number
                ], "/order-detail/{$order->id}");

                // Send Notification to email
                $payload = $this->orderService->prepareEmailPayload($order, 'order_delivery');

                $this->emailService->send(
                    'on_delivery',
                    $order,
                    $payload,
                    $order->user->email
                );
            } catch (\Exception $e) {
                Log::error('Notification order_shipped failed for order ' . $order->order_number . ': ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Order is now on delivery with ' . $order->external_courier_name,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to set delivery: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}