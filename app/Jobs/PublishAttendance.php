<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Events\AttendanceEvent;
use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishAttendance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $attendance;

    public function __construct($attendance)
    {
        $this->attendance = $attendance;
    }

    public function handle()
    {
        \Log::info('PublishAttendance job is running!', [
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->attendance->user_id
        ]);

        $data = [
            'id' => $this->attendance->id,
            'user_id' => $this->attendance->user_id,
            'status' => $this->attendance->status,
        ];

        try {
            // Redis::publish('socket-channel', json_encode($data));
            event(new AttendanceEvent([
                "message" => "Hello World",
                "time"    => now()->toDateTimeString(),
            ]));
            
            \Log::info('Redis publish successful');
        } catch (\Exception $e) {
            \Log::error('Redis publish failed: ' . $e->getMessage());
        }
    }
}