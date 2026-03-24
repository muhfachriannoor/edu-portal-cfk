<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Jobs\SendReminderNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateExpiredOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update payment and order status to expired and return stock quantities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $keys = ['expiration_date', 'expires_at'];
        $totalExpired = 0;

        $query = Payment::query()
            ->with(['order.user', 'order.orderItems'])
            ->where(function ($q) use ($keys) {
                // CASE 1: field payment_details NOT NULL
                $q->whereNotNull('payment_details')
                    ->whereIn('status', ['waiting', 'pending', 'WAITING', 'PENDING'])
                    ->where(function ($q2) use ($keys) {
                        foreach ($keys as $key) {
                            $q2->orWhereRaw("STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(payment_details, '$.{$key}')), '%Y-%m-%dT%H:%i:%s') < UTC_TIMESTAMP()");
                        }
                    });
            })
            ->orWhere(function ($q) {
                // CASE 2: field payment_details NULL
                $q->whereNull('payment_details')
                    ->whereIn('status', ['waiting', 'pending', 'WAITING', 'PENDING'])
                    ->where('created_at', '<=', now()->subDay());
            });

        $query->chunk(100, function ($expiredPayments) use (&$totalExpired) {
            foreach ($expiredPayments as $payment) {
                DB::beginTransaction();
                
                try {
                    $payment->update([
                        'status' => 'EXPIRED',
                        'failed_at' => now()
                    ]);

                    $order = $payment->order;

                    if ($order) {
                        $order->update(['status' => Order::STATUS['expired'] ?? 98]);

                        foreach ($order->orderItems as $item) {
                            ProductVariant::where('id', $item->product_variant_id)
                                ->increment('quantity', $item->quantity);
                        }

                        $order = $order->orderLog()->create([
                            'action' => 'order_expired',
                            'description' => 'Order status expired',
                            'additional_data' => $order
                        ]);

                        dispatch(new SendReminderNotificationJob($payment, 'payment_expired'));
                    }

                    DB::commit();
                    $totalExpired++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Failed to expire payment ID {$payment->id}: " . $e->getMessage());
                }
            }
        });

        if ($totalExpired > 0) {
            Log::info("UpdateExpiredOrders: Successfully expired {$totalExpired} transactions.");
        }

        return Command::SUCCESS;
    }
}
