<?php

namespace App\Jobs;

use App\Models\NotifyMe;
use App\Models\ProductVariant;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendStockNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $variant;

    /**
     * Create a new job instance.
     */
    public function __construct(ProductVariant $variant)
    {
        $this->variant = $variant;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $this->variant->load('product');

        NotifyMe::where('variant_id', $this->variant->id)
            ->where('notified', 0)
            ->chunkById(100, function ($subscribers) use ($notificationService) {
                foreach ($subscribers as $subscriber) {
                    $locale = app()->getLocale();
                    $productName = trim($this->variant->product->name . ' ' . $this->variant->getVariantName($locale));

                    $notificationService->send(
                        $subscriber->user_id,
                        'product_out_of_stock_ready',
                        ['product_name' => $productName],
                        "/product/{$this->variant->product->slug}?store_id={$this->variant->product->store_id}"
                    );

                    // Opsi Langsung Hapus
                    $subscriber->delete();

                    // Opsi simpan untuk history (analytic maybe)
                    // $subscriber->update(['notified' => 1]);
                }
            });
    }
}
