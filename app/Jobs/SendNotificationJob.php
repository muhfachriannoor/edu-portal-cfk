<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Notification;
use App\Models\UserNotification;
use App\Services\FirebaseService;
use App\View\Data\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notification;
    protected $content;

    /**
     * Create a new job instance.
     */
    public function __construct(Notification $notification, array $content)
    {
        $this->notification = $notification;
        $this->content = $content;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService): void
    {
        $firebaseEnv = config('services.firebase.env');

        User::verified()->select('id')
            ->chunk(200, function ($users) use ($firebaseService, $firebaseEnv) {
                $batchData = [];
                $userIds = [];

                foreach ($users as $user) {
                    $title = $this->content['title_en']; 
                    $message = $this->content['message_en'];

                    $batchData[] = [
                        'id' => (string) Str::orderedUuid(),
                        'type' => 'general',
                        'user_id' => $user->id,
                        'notification_id' => $this->notification->id,
                        'title' => $title,
                        'message' => $message,
                        'created_timestamp' => now()->valueOf(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $userIds[] = $user->id;
                    $firebaseService->unreadCount($user, $firebaseEnv);
                }

                UserNotification::insert($batchData);

                foreach ($userIds as $userId) {
                    NotificationData::flush($userId); 
                }
            });
    }
}
