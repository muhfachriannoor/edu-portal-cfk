<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Jobs\SendReminderNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPickupReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:send-pickup-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder to customer 1 hour after order is ready for pickup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalDispatched = 0;

        $query = Order::query()
            ->with(['user', 'payment'])
            ->where('status', Order::STATUS['ready_pick_up'])
            ->whereNull('pickup_reminder_sent_at')
            ->where('updated_at', '<=', now()->subHour());

        $query->chunk(100, function ($orders) use (&$totalDispatched) {
            foreach ($orders as $order) {
                try {
                    $order->update(['pickup_reminder_sent_at' => now()]);

                    if ($order->payment) {
                        dispatch(new SendReminderNotificationJob($order->payment, 'order_pickup_reminder'));
                        $totalDispatched++;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to process pickup reminder for Order {$order->order_number}: " . $e->getMessage());
                }
            }
        });

        if ($totalDispatched > 0) {
            Log::info("Pickup Reminder: Sent {$totalDispatched} notifications.");
        }

        return Command::SUCCESS;
    }
}
