<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\ChannelCategory;
use App\Models\Courier;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\ActivityService;
use App\Services\EmailNotificationService;
use App\Services\LazadaApi;
use App\Services\OrderService;
use App\Services\XenditPaymentService;

class ChannelsController extends Controller
{
    use LazadaApi;

    private $activityService;
    private $xenditPaymentService;
    private $orderService;
    private $emailService;
    
    public function __construct(
        ActivityService $activityService,
        XenditPaymentService $xenditPaymentService,
        OrderService $orderService,
        EmailNotificationService $emailService
    ) {
        $this->activityService = $activityService;
        $this->xenditPaymentService = $xenditPaymentService;
        $this->orderService = $orderService;
        $this->emailService = $emailService;
    }

    /**
     * @OA\Get(
     *      path="/generals/channels",
     *      operationId="channels",
     *      tags={"General"},
     *      summary="",
     *      description="",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="status",
     *                  type="string",
     *                  example="success",
     *                  description="",
     *              ),
     *              @OA\Property(
     *                  property="code",
     *                  type="int",
     *                  example=200,
     *                  description="",
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Berhasil.",
     *                  description="",
     *              ),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *              )
     *          )
     *      ),
     * )
     */
    public function getChannels()
    {
        return $this->sendSuccess(200, 'Success.', Channel::with('procedures', 'procedures.steps')->get());
    }

    /**
     * @OA\Get(
     *      path="/generals/channels/grouped",
     *      operationId="channelsGroupedByCategory",
     *      tags={"General"},
     *      summary="Get payment channels grouped by channel category",
     *      description="Returns channel categories and their active channels, including procedures and steps.",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="code", type="integer", example=200),
     *              @OA\Property(property="message", type="string", example="Success."),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="id", type="string", example="98e9...bfe3d"),
     *                      @OA\Property(property="code", type="string", example="VIRTUAL_ACCOUNT"),
     *                      @OA\Property(property="name", type="string", example="Virtual Account"),
     *                      @OA\Property(property="description", type="string", nullable=true),
     *                      @OA\Property(property="icon_url", type="string", example="http://..."),
     *                      @OA\Property(
     *                          property="channels",
     *                          type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              @OA\Property(property="id", type="string", example="98e9...1759f"),
     *                              @OA\Property(property="code", type="string", example="BNI"),
     *                              @OA\Property(property="name", type="string", example="BNI virtual account"),
     *                              @OA\Property(property="description", type="string", nullable=true),
     *                              @OA\Property(property="file_url", type="string", example="http://..."),
     *                              @OA\Property(property="tax", type="number", format="float", example=0),
     *                              @OA\Property(property="title", type="string", example="BNI virtual account - Virtual Account"),
     *                              @OA\Property(property="is_enabled", type="boolean", example=true),
     *                              @OA\Property(property="is_published", type="boolean", example=true)
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function getChannelsGroupedByCategory()
    {
        $categories = ChannelCategory::query()
            ->with([
                'channels' => function ($q) {
                    $q->where('is_enabled', true)
                    ->where('is_published', true)
                    ->with(['procedures.steps', 'file']);
                },
            ])
            // Only return categories that still have at least 1 active+published channel
            ->whereHas('channels', function ($q) {
                $q->where('is_enabled', true)
                ->where('is_published', true);
            })
            ->where('is_enabled', true)
            ->where('is_published', true)
            ->get();

        $grouped = $categories
            ->map(function (ChannelCategory $category) {
                return [
                    'id'          => $category->id,
                    'code'        => $category->code,
                    'name'        => $category->name,
                    'description' => $category->description,
                    'icon_url'    => $category->icon,
                    'channels'    => $category->channels
                        ->map(function (Channel $channel) {
                            $data = $channel->toArray();

                            // Remove nested category from each channel in the payload (keep response structure unchanged)
                            unset($data['channel_category']);

                            return $data;
                        })
                        ->values(),
                ];
            })
            ->values();

        return $this->sendSuccess(200, 'Success.', $grouped);
    }

    /**
     * @OA\Post(
     *      path="/payments/simulate/va",
     *      operationId="simulate-payment-va",
     *      tags={"Payment"},
     *      summary="",
     *      description="",
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="bank_account_number",
     *                  type="string",
     *                  example="test-0001-payment",
     *                  description="",
     *              ),
     *              @OA\Property(
     *                  property="bank_code",
     *                  type="string",
     *                  example="BCA",
     *                  description="",
     *              ),
     *              @OA\Property(
     *                  property="transfer_amount",
     *                  type="integer",
     *                  example="100000",
     *                  description="",
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="status",
     *                  type="string",
     *                  example="success",
     *                  description="",
     *              ),
     *              @OA\Property(
     *                  property="code",
     *                  type="int",
     *                  example=200,
     *                  description="",
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Berhasil.",
     *                  description="",
     *              ),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(
     *                      property="owner_id",
     *                      type="string",
     *                      example="5e943709950d7621c1d57639",
     *                      description="",
     *                  ),
     *              )
     *          )
     *      ),
     * )
     */
    public function simulateVirtualAccount(Request $request)
    {
        $params = $request->validate([
            'bank_account_number' => 'required|string',
            'bank_code' => 'required|string',
            'transfer_amount' => 'required|numeric',
        ]);

        $key = config('services.default.xendit.key.secret');
        $response = Http::withHeaders(['Authorization' => 'Basic ' . base64_encode("$key:")])
            ->asForm()
            ->post('https://api.xendit.co/pool_virtual_accounts/simulate_payment', $params);

        $data = $response->json();

        if ($response->failed()) {
            return [
                'status' => 'FAILED',
                'http_code' => $response->status(),
                'response' => $data
            ];
        }

        return $data;
    }

    /**
     * @OA\Post(
     *      path="/payments/simulate/qris",
     *      operationId="simulate-payment-qr",
     *      tags={"Payment"},
     *      summary="Simulate QR payment (Xendit)",
     *      description="Call Xendit for QR.",
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="reference_id",
     *                  type="string",
     *                  example="019ac98d-052d-7054-b0e5-0a2b44d4a5c7",
     *                  description="ID from `payment.id` returned by Xendit when creating the QR."
     *              ),
     *              @OA\Property(
     *                  property="amount",
     *                  type="integer",
     *                  example=100000,
     *                  description="Simulated paid amount."
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *          @OA\JsonContent(type="object")
     *      )
     * )
     */
    public function simulateQrPayment(Request $request)
    {
        $external_id = $request->input('reference_id');
        $key = config('services.default.xendit.key.secret');
        $response = Http::withHeaders(['Authorization' => 'Basic ' . base64_encode("$key:")])
            ->asForm()
            ->post('https://api.xendit.co/qr_codes/' . $external_id . '/payments/simulate');

        $data = $response->json();

        if ($response->failed()) {
            return [
                'status' => 'FAILED',
                'http_code' => $response->status(),
                'response' => $data
            ];
        }

        return $data;
    }

    /**
     * Xendit Callback
     */
    public function callbackPayment(Request $request)
    {
        // Cek apakah ada data di request
        if ($request->exists('data')) {
            $request->merge($request->all('data')['data']);
        }

        // Token untuk keamanan callback
        $callbackToken = config('services.default.callback.token');
        if (!empty($callbackToken)) {
            $incomingToken = $request->header('x-callback-token');
            if ($incomingToken !== $callbackToken) {
                Log::channel('daily')->warning('INVALID CALLBACK TOKEN - PAYMENT', [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'token'      => $incomingToken,
                ]);
                return $this->sendError(401, 'Invalid callback token');
            }
        }

        // Log request yang masuk
        Log::channel('daily')->info('incoming request: ');
        Log::channel('daily')->info('XENDIT CALLBACK');
        Log::channel('daily')->info(json_encode($request->all(), JSON_PRETTY_PRINT));
        Log::channel('daily')->info('Response: ');
        Log::channel('daily')->info('Request success 204');
        Log::channel('daily')->info(json_encode($request->header(), JSON_PRETTY_PRINT));

        // Simpan callback ke dalam tabel log
        DB::table('callbacks')->insert([
            'id'            => Str::uuid()->toString(),
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
            'code'          => 200,
            'callback_type' => 'CALLBACK_IN',
            'instance'      => 'PAYMENT',
            'header'        => json_encode($request->header()),
            'body'          => json_encode($request->all()),
            'created_at'    => now()->format('Y-m-d H:i:s'),
        ]);

        // Ambil external_id dari request
        $externalID = null;
        if ($request->exists('external_id')){
            $externalID = $request->input('external_id');
        } elseif ($request->exists('reference_id')){
            $externalID = $request->input('reference_id');
        }

        if (!$externalID) {
            return $this->sendError(400, 'External ID not found');
        }

        // Cari Payment berdasarkan external_id atau reference_id
        $payment = Payment::where('reference', $externalID)->first();

        if (!$payment) {
            return $this->sendError(400, 'Payment not found');
        }

        // Ambil order terkait (jika ada)
        $order = null;
        if (!empty($payment->order_id)) {
            $order = Order::find($payment->order_id);
        }

        // Guard A: Jika payment atau order sudah dibatalkan, ignore callback
        if ($payment->status === 'CANCELLED' || ($order && $order->status === 99)) {
            Log::channel('daily')->info('Ignored callback for cancelled order/payment.', [
                'payment_id' => $payment->id,
                'order_id' => $order ? $order->id : null,
                'external_id' => $externalID,
            ]);
            return $this->sendSuccess(204);  // Respond with 204 to acknowledge callback but do nothing
        }

        // Guard B: Check jika callback berasal dari payment instrument yang valid dan aktif
        [$ignore, $reason] = $this->xenditPaymentService->shouldIgnoreCallback($request, $payment, $order);
        if ($ignore) {
            Log::channel('daily')->info('Ignored stale or invalid callback.', [
                'reason' => $reason,
                'payment_id' => $payment->id,
                'external_id' => $externalID,
            ]);
            return $this->sendSuccess(204);  // Respond with 204 if the callback is stale or invalid
        }

        // Map status dari callback ke status internal
        $mappedStatus = $this->xenditPaymentService->mapCallbackStatus($request);

        // Jika statusnya SUCCEEDED, update payment dan order status
        if ($mappedStatus === 'SUCCEEDED') {
            $payment->update([
                'status'      => 'SUCCEEDED',
                'succeded_at' => now()->format('Y-m-d H:i:s'),
            ]);

            if ($order) {
                $oldStatus = $order->status;

                // Tentukan status baru berdasarkan status pengiriman (misalnya status ke 2 = preparing)
                $isPickup = $order->courier?->is_pickup ?? false;
                $newStatus = $isPickup ? 2 : 1;

                if ($oldStatus !== $newStatus) {
                    $order->update(['status' => $newStatus]);

                    // HIT API Lazada CreatePackage
                    if (!$isPickup) {
                        try {
                            $lazadaPackage = $this->createPackage($order->id);
                        } catch (\Exception $e) {
                            Log::error("Lazada CreatePackage failed for order {$order->order_number}: " . $e->getMessage());
                        }
                    }

                    // Create notification to email (Admin and User)
                    try {
                        $payload = $this->orderService->prepareEmailPayload($order, 'order_confirmed');
                        $statusOrderToEmail = $isPickup ? 'preparing' : 'sent_to_courier';
                        
                        $this->emailService->send(
                            $statusOrderToEmail,
                            $order,
                            $payload,
                            $order->user->email
                        );
                    } catch (\Exception $e) {
                        Log::error('Notification Email order_confirmed failed for order ' . $order->order_number . ': ' . $e->getMessage());
                    }

                    $this->activityService->orderLogActivity(
                        $order,
                        'payment_succeeded',
                        'Order status updated to processing after successful payment callback.',
                        [
                            'from'         => $oldStatus,
                            'to'           => $newStatus,
                            'payment_id'   => $payment->id,
                            'payment_stat' => 'SUCCEEDED',
                            'external_id'  => $externalID,
                        ]
                    );
                }
            }
        } elseif ($mappedStatus === 'FAILED') {
            // Jika statusnya FAILED, update payment dan order status ke cancelled
            $payment->update([
                'status'    => 'FAILED',
                'failed_at' => now()->format('Y-m-d H:i:s'),
            ]);

            if ($order) {
                $oldStatus = $order->status;
                $newStatus = 99; // 99 = cancelled

                if ($oldStatus !== $newStatus) {
                    $order->update(['status' => $newStatus]);

                    $this->activityService->orderLogActivity(
                        $order,
                        'payment_failed',
                        'Order status updated to cancelled after failed payment callback.',
                        [
                            'from'         => $oldStatus,
                            'to'           => $newStatus,
                            'payment_id'   => $payment->id,
                            'payment_stat' => 'FAILED',
                            'external_id'  => $externalID,
                        ]
                    );
                }
            }
        } elseif (in_array($mappedStatus, ['PENDING', 'WAITING', 'ACTIVE'], true)) {
            // Jika statusnya PENDING, update status payment ke PENDING
            $payment->update(['status' => 'PENDING']);
        }

        return $this->sendSuccess(204); // Return 204 to acknowledge callback
    }
}
