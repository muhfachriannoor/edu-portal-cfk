<?php

namespace App\View\Data;

use App\Models\Courier;
use Illuminate\Support\Facades\Cache;

class CourierData
{
    public const API_CACHE_KEY = "courier_api_list";
    private const CACHE_TTL = 3600; // 60 minutes

    /**
     * Retrieve a list of couriers for API, with caching
     */
    public static function listsForApi(string $locale = 'en'): array
    {
        $cacheKey = self::API_CACHE_KEY . "_{$locale}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            $couriers = Courier::where('is_active', true)
                ->with('translations')
                ->orderBy('name')
                ->get();

            return $couriers->map(function ($courier) use ($locale) {
                $translation = $courier->translation($locale);

                return [
                    'id'            => $courier->id,
                    'name'          => optional($translation)->name ?? $courier->name,
                    'key'           => $courier->key,
                    'fee'           => $courier->fee,
                    'description'   => optional($translation)->description ?? $courier->description,
                ];
            })->toArray();
        });
    }

    public static function flush(): void
    {
        $locales = ['en', 'id'];

        foreach ($locales as $locale) {
            Cache::forget(self::API_CACHE_KEY . "_{$locale}");
        }
    }
}