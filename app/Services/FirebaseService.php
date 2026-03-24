<?php

namespace App\Services;
use Kreait\Firebase\Factory;
use App\Models\UserNotification;

class FirebaseService {
    protected $database;

    public function __construct(){
        $this->database = $this->connect();
    }

    public function connect(){
       if (!$this->database) {
            $this->database = (new Factory)
                ->withServiceAccount(config('services.firebase.credentials'))
                ->withDatabaseUri(config('services.firebase.url'))
                ->createDatabase();
        }
        return $this->database;
    }

    public function saveValue($channel, $jsonData){
        $this->database
                ->getReference($channel)
                ->set($jsonData);
    }

    public function getValue($channel){
        return $this->database
            ->getReference($channel)
            ->getValue();
    }

    public function unreadCount($user, $firebaseEnv){
        // Get notifications
        $userNotification = $user->notifications()->where('user_id', $user->id)->whereNull('read_at')->get();
        $notifGeneral = $userNotification->where('type', 'general')->count();
        $notifOrder = $userNotification->where('type', 'order')->count();

        // Get orders
        $pendingOrder = $user->orders()->whereIn('status', [0])->count();

        $channel = "{$firebaseEnv}/counts/{$user->id}";
        $this->saveValue(
            $channel,
            [
                'notification_general' => $notifGeneral,
                'notification_order' => $notifOrder,
                'total_order' => $pendingOrder,
                'total_voucher' => 1
            ]
        );
    }
} 