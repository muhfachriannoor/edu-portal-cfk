<?php

namespace App\View\Data;

use App\Models\MasterLocation;
use Illuminate\Support\Facades\Cache;

class MasterLocationData
{

    public const FORM_CACHE_KEY = "location_form_data";
    // public const API_CACHE_KEY = "location_api_list";

    private const CACHE_TTL = 3600; // 60 minutes

    // --- METHOD: LISTS FOR BACKEND FORM (pluck: 'id' => 'name') ---
    public static function lists(): array
    {
        return Cache::remember(self::FORM_CACHE_KEY, now()->addMinutes(10), function () {
            $locations = MasterLocation::where('is_active', 1)
                                    ->orderBy('location')
                                    ->get(['id', 'location', 'city']);
            return $locations->mapWithKeys(function ($item) {
                return [$item->id => "{$item->location} ({$item->city})"];
            })->toArray();
        });
    }

    // --- METHOD: LISTS FOR API ENDPOINT ---
    // public static function listsForApi(): array
    // {
    //     return Cache::remember(self::API_CACHE_KEY, self::CACHE_TTL, function () {
    //         $data = MasterLocation::where('is_active', true)
    //                             ->orderBy('location')
    //                             ->get();
    //         return $data->toArray();
    //     });
    // }

    // -- METHOD: FLUSH CACHE ---
    public static function flush(): void
    {
        // 1. Flush Cache Form Data
        Cache::forget(self::FORM_CACHE_KEY);

        // 2. Flush Cache API
        // Cache::forget(self::API_CACHE_KEY);
    }
}
