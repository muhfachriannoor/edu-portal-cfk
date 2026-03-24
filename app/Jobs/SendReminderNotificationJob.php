<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReminderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payment;
    protected $type;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Payment $payment,
        string $type
    ) {
        $this->payment = $payment;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $payment = $this->payment->fresh();

        // Payment Reminder
        if ($this->type === 'payment_reminder') {
            if (!in_array(strtolower($payment->status), ['waiting', 'pending'])) {
                return;
            }
        }

        if ($this->type === 'order_pickup_reminder') {
            $order = $payment->order()->first();
            if (!$order || $order->status != Order::STATUS['ready_pick_up']) {
                return;
            }
        }

        try {
            $notificationService->send(
                $payment->order->user_id,
                $this->type,
                ['order_number' => $payment->order->order_number],
                "/order-detail/{$payment->order->id}"
            );
        } catch (\Exception $e) {
            Log::error("Firebase Notification failed ({$this->type}) for Order {$payment->order->order_number}: " . $e->getMessage());
        }
    }
}
