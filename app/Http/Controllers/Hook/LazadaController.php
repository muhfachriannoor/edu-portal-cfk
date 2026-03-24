<?php

namespace App\Http\Controllers\Hook;
use App\Models\Order;
use App\Models\LexWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\MasterDeliveryStatus;

class LazadaController extends Controller
{
    /**
     * @OA\Post(
     *      path="/hook/lazada",
     *      operationId="lazadaShippingCallback",
     *      tags={"Webhook"},
     *      summary="Receive Lazada shipping updates",
     *      description="Webhook endpoint for Lazada to send shipping status updates.",
     *      
     *      @OA\Parameter(
     *          name="X-WEBHOOK-SECRET",
     *          in="header",
     *          description="Secret key to authorize the webhook request",
     *          required=true,
     *          @OA\Schema(type="string", example="your_webhook_secret_here")
     *      ),
     *
     *      @OA\RequestBody(
     *          required=true,
     *          description="Payload sent by Lazada webhook",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="externalOrderId", type="string", example="ORD123456"),
     *              @OA\Property(property="packageCode", type="string", example="PKG123"),
     *              @OA\Property(property="properties", type="object",
     *                  @OA\Property(property="originalTrackingNumber", type="string", example="TRACK123")
     *              ),
     *              @OA\Property(property="status", type="string", example="DELIVERED")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Webhook processed successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="Webhook processed")
     *          )
     *      ),
     *      
     *      @OA\Response(
     *          response=400,
     *          description="Order not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Order not found")
     *          )
     *      ),
     * 
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized or status code not registered",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthorized")
     *          )
     *      )
     * )
     */
    public function callbackLazada(Request $request)
    {
        if ($request->header('X-WEBHOOK-SECRET') !== config('services.shipping.webhook_secret')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        // 1. Log raw payload (VERY useful for debugging)
        Log::info('Shipping webhook received', $request->all());

        // 2. Example payload (depends on provider)
        $externalOrderId = $request->input('externalOrderId');
        $packageCode = $request->input('packageCode');
        $trackingNumber = $request->input('properties.originalTrackingNumber');
        $status = $request->input('status');

        // 3. Find your order
        $order = Order::where([
            'order_number'       => $externalOrderId,
            'package_code'       => $packageCode,
            'tracking_number'    => $trackingNumber,
        ])->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 400);
        }

        // 4️. Update order status
        $masterStatus = MasterDeliveryStatus::where('status', $status)->first();
        if(!$masterStatus){
            return response()->json(['message' => 'Status code is not registered'], 401);
        }
        $order->update([ 'status' => $masterStatus->order_status ]);

        // 5. Save into database
        LexWebhook::create([
            'order_id' => $order->id,
            'callback' => $request->all()
        ]);

        // 6. Always respond 200 OK
        return response()->json(['message' => 'Webhook processed']);
    }
}