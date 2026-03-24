<?php

namespace App\Jobs;

use App\Services\KoalaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendKoalaBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user_id;
    protected $phone;
    protected $params;

    /**
     * Create a new job instance.
     */
    public function __construct($user_id, $phone, $params)
    {
        $this->user_id = $user_id;
        $this->phone = $phone;
        $this->params = $params;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $notifications = [[
                'phoneNumber' => $this->phone,
                'paramData' => $this->params,
            ]];

            $result = '';

            if(config('app.env') != 'local'){
                $result = KoalaService::sendBroadcast(
                    $notifications
                );
            }

            Log::info('✅ Koala broadcast sent successfully', [
                'phone' => $this->phone,
                'result' => $result,
                'paramData' => $this->params,
            ]);

            \DB::table('user_blasts')->insert([
                'user_id' => $this->user_id,
                'is_send'  => 1,
                'response' => json_encode($result),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Koala broadcast failed', [
                'phone' => $this->phone,
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
        }
    }
}
