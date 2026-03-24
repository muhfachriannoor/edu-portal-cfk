<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;

class NotificationService
{
    protected $firebaseService;
    protected $firebaseEnv;

    const TEMPLATES = [
        'order_created' => 'order-created-template',
        'payment_reminder' => 'payment-reminder-template',
        'order_rejected' => 'order-rejected-template',
        'order_cancel' => 'order-cancel-template',
        'payment_confirmation_accept' => 'payment-confirmation-accept-template',
        'payment_expired' => 'payment-expired-template',
        'order_confirmed' => 'order-confirmed-template',
        'order_packed' => 'order-packed-template',
        'order_shipped' => 'order-shipped-template',
        'order_delivered' => 'order-delivered-template',
        'order_pickup_delivered' => 'order-pickup-delivered-template',
        'order_ready_for_pickup' => 'order-ready-for-pickup-template',
        'order_pickup_reminder' => 'order-pickup-reminder-template',
        'product_out_of_stock_ready' => 'product-out-of-stock-ready-template',
    ];

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
        $this->firebaseEnv = config('services.firebase.env');
    }

    /**
     * Fungsi single entry point untuk mengirim notifikasi
     */
    public function send(int $userId, string $event, array $replacements = [], string $target = null, $customTimestamp = null)
    {
        $user = User::find($userId);
        $templateUuid = self::TEMPLATES[$event] ?? null;

        if (!$user || !$templateUuid) {
            return null;
        }

        $master = Notification::with(['translations' => function ($query) {
            $query->where('locale', App::getLocale());
        }])->where('uuid', $templateUuid)->first();

        if (!$master) return null;

        $translation = $master->translations->first();

        $title = $translation ? $translation->name : $master->title;
        $message = $translation ? $translation->description : $master->message;

        if (!empty($replacements)) {
            $keys = array_map(fn ($k) => ":$k", array_keys($replacements));
            $values = array_values($replacements);

            $title = str_replace($keys, $values, $title);
            $message = str_replace($keys, $values, $message);
        }

        $userNotification = UserNotification::create([
            'user_id' => $user->id,
            'notification_id' => $master->id,
            'type' => 'order',
            'title' => $title,
            'message' => $message,
            'target' => $target,
            'read_at' => null,
            'created_timestamp' => $customTimestamp ?? now()->valueOf(),
        ]);

        $this->firebaseService->unreadCount($user, $this->firebaseEnv);

        return $userNotification;
    }
}