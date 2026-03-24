<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AutoCompleteDeliveryOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-complete-delivery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically complete orders using delivery-sarinah courier after 5 days of estimated arrival';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting auto-complete for delivery-sarinah orders...');

        // 1. Fetch orders with status 'on_delivery' using 'delivery-sarinah' courier
        $query = Order::where('status', Order::STATUS['on_delivery'])
            ->whereHas('courier', function ($q) {
                $q->where('key', 'delivery-sarinah');
            });

        $totalProcessed = 0;
        
        // 2. Use chunking to handle large datasets efficiently
        $query->chunk(100, function ($orders) use (&$totalProcessed) {
            foreach ($orders as $order) {
                $payload = $order->delivery_payload;

                // 3. Validate if 'estimated' field exists in the JSON payload
                if (isset($payload['estimated'])) {
                    try {
                        $estimatedDate = Carbon::parse($payload['estimated']);

                        // Rule: Current date >= (Estimated Date + 5 days)
                        $completionThreshold = $estimatedDate->addDays(5);

                        if (now()->startOfDay()->greaterThanOrEqualTo($completionThreshold->startOfDay())) {

                            DB::transaction(function () use ($order, $payload) {
                                // 4. Update Order status to 'completed'
                                $order->update([
                                    'status' => Order::STATUS['completed']
                                ]);

                                // 5. Create audit trail in OrderLog
                                $order->orderLog()->create([
                                    'action' => 'order_auto_completed',
                                    'description' => 'System automatically completed the order. Logic 5 days after estimated arrival date (' . $payload['estimated'] . ').',
                                    'additional_data' => [
                                        'auto_completed_at' => now()->toDateTimeString(),
                                        'original_estimated' => $payload['estimated'],
                                        'courier_key' => $payload['key'] ?? 'delivery-sarinah'
                                    ]
                                ]);
                            });

                            $totalProcessed++;
                            $this->line("Order #{$order->order_number} marked as completed.");
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to parse estimated date for Order ID {$order->id}: " . $e->getMessage());
                        continue;
                    }
                }
            }
        });

        $this->info("Process completed. Total orders updated: {$totalProcessed}");
        Log::info("AutoCompleteDeliveryOrder: Successfully processed {$totalProcessed} orders.");

        return Command::SUCCESS;
    }
}
