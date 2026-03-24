<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Payment;
use App\Jobs\SendReminderNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:send-payment-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification reminder 1 hour before payment expires';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $keys = ['expiration_date', 'expires_at'];
        $rangeStart = Carbon::now('UTC')->addMinutes(50);
        $rangeEnd = Carbon::now('UTC')->addMinutes(60);

        // Inisialisasi counter untuk log di akhir
        $totalDispatched = 0;

        $query = Payment::query()
            ->with(['order.user'])
            ->whereIn('status', ['waiting', 'pending', 'WAITING', 'PENDING'])
            ->whereNull('reminder_sent_at')
            ->where(function ($q) use ($keys, $rangeStart, $rangeEnd) {
                foreach ($keys as $key) {
                    $q->orWhereRaw("STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(payment_details, '$.{$key}')), '%Y-%m-%dT%H:%i:%s') BETWEEN ? AND ?", [
                        $rangeStart->toDateTimeString(),
                        $rangeEnd->toDateTimeString()
                    ]);
                }
            });

        // Gunakan chunk untuk memproses data secara efisien
        $query->chunk(100, function ($payments) use (&$totalDispatched) {
            foreach ($payments as $payment) {
                try {
                    // Update penanda agar tidak diproses ulang menit berikutnya
                    $payment->update(['reminder_sent_at' => now()]);

                    // Kirim ke Queue
                    dispatch(new SendReminderNotificationJob($payment, 'payment_reminder'))->onQueue('high');

                    $totalDispatched++;
                } catch (\Exception $e) {
                    Log::error("Failed to process payment reminder for ID {$payment->id}: " . $e->getMessage());
                }
            }
        });

        // Hanya tulis log jika ada data yang diproses agar log tidak penuh dengan angka 0
        if ($totalDispatched > 0) {
            Log::info("Payment Reminder: Successfully dispatched {$totalDispatched} notification jobs.");
        }

        return Command::SUCCESS;
    }
}
