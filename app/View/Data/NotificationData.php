<?php

namespace App\View\Data;

use App\Models\UserNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class NotificationData
{
    public const API_CACHE_KEY_PREFIX = "user_notification_api_";

    /**
     * Get paginated notification for API with caching.
     */
    public static function listsForApi($user, ?string $type = 'all', int $perPage = 10, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $page = request()->get('page', 1);
        
        $cacheKey = self::API_CACHE_KEY_PREFIX . "{$user->id}_{$type}_{$locale}_p{$page}_limit{$perPage}";

        // TTL 1 jam
        return Cache::remember($cacheKey, 3600, function () use ($user, $type, $perPage, $locale) {
            $notifications = UserNotification::with(['user', 'notification.translations'])
                ->where('user_id', $user->id)
                ->when($type && $type !== 'all', function ($query) use ($type) {
                    return $query->where('type', $type);
                })
                ->orderBy('created_timestamp', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $formattedData = collect($notifications->items())->map(function ($notif) use ($locale) {
                $translation = $notif->notification?->translations->firstWhere('locale', $locale);

                $title = $notif->title;
                $message = $notif->message;

                if ($translation) {
                    preg_match('/#([^#]+)$|#([^\s]+)/', $notif->title, $matches);
                    
                    $rawDynamicValue = !empty($matches[1]) ? trim($matches[1]) : (!empty($matches[2]) ? trim($matches[2]) : '');

                    if ($rawDynamicValue) {
                        $cleanValue = ltrim($rawDynamicValue, ':');
                        $formattedValue = "#" . $cleanValue;

                        $templateName = $translation->name ?? $notif->title;
                        $templateDesc = $translation->description ?? $notif->message;

                        $search = [
                            '#:product_name', ':product_name', 
                            '#:order_number', ':order_number'
                        ];

                        $title = str_replace($search, $formattedValue, $templateName);
                        $message = str_replace($search, $formattedValue, $templateDesc);
                    } else {
                        $title = $translation->name ?? $notif->title;
                        $message = $translation->description ?? $notif->message;
                    }
                }

                return [
                    'uuid' => $notif->id,
                    'user_id' => $notif->user?->id,
                    'user_email' => $notif->user?->email,
                    'user_name' => $notif->user?->name,
                    'type' => $notif->type,
                    'title' => $title,
                    'message' => $message,
                    'target' => $notif->target,
                    'is_read' => !is_null($notif->read_at),
                    'created_at' => $notif->created_timestamp
                ];
            });

            return [
                'items' => $formattedData->values()->toArray(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage()
                ]
            ];
        });
    }

    /**
     * Flush/Clear cache for a specific user.
     */
    public static function flush($userId): void
    {
        $locales = ['id', 'en'];
        $types = ['all', 'order', 'general'];
        $perPages = [10, 25, 50];

        foreach ($locales as $locale) {
            foreach ($types as $type) {
                foreach ($perPages as $perPage) {
                    for ($page = 1; $page <= 5; $page++) {
                        $key = self::API_CACHE_KEY_PREFIX . "{$userId}_{$type}_{$locale}_p{$page}_limit{$perPage}";
                        Cache::forget($key);
                    }
                }
            }
        }
    }
}
