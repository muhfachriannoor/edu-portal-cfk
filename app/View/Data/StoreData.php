<?php

namespace App\View\Data;

use App\Models\Store;
use App\Models\StoreOptionValue;
use App\Models\StoreVariant;
use App\Models\SpecialPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreData
{

    public const FORM_CACHE_KEY = "store_form_data";
    public const API_CACHE_KEY = "store_api_list";
    public const KEYWORD_API_CACHE_KEY = "keyword_store_api_list";

    // For Shopping Bag
    public const LOCALE_SEPARATOR = "_";

    private const CACHE_TTL = 3600; // 60 minutes

    // --- METHOD: LISTS FOR BACKEND FORM (pluck: 'id' => 'name') ---
    public static function lists(): array
    {
        return Cache::remember(self::FORM_CACHE_KEY, now()->addMinutes(10), function () {
            // Format: ['id' => 'name']
            return Store::where('is_active', 1)->get()->sortBy('name')->pluck('name', 'id')->toArray();
        });
    }

    // --- METHOD: LISTS FOR API ENDPOINT (Categories only) ---
    public static function listsForApi(): array
    {
        return Cache::remember(self::API_CACHE_KEY, self::CACHE_TTL, function () {
            $stores = Store::where('is_active', true)
                                ->get();

            // Format: Array of Objects for API
            return $stores->map(function ($store) {
                return [
                    'id' => $store->id,
                    // 'location_id' => $store->location_id,
                    'name' => $store->name,
                    'slug' => $store->slug,
                    'description' => $store->description,
                    'image_url' => $store->file_url,
                ];
            })->toArray();
        });
    }

    public static function listsForApiKeyword($keyword = "")
    {
        $key = self::KEYWORD_API_CACHE_KEY . ":{$keyword}";
        return Cache::tags(self::KEYWORD_API_CACHE_KEY)->remember($key, self::CACHE_TTL, function () use($keyword) {
            return Store::where('is_active', true)
                ->when($keyword, function($query) use($keyword) {
                    $query->whereHas('translations', function($query) use($keyword){
                        return $query->where('name', 'like', "%$keyword%");
                    });
                })
                ->orderBy('created_at')
                ->get()
                ->map(function($store){
                    return [
                        'name' => $store->name,
                        'slug' => $store->slug,
                        'image_url' => $store->file_url,
                    ];
                });
        });
    }
    

    // -- METHOD: FLUSH CACHE ---
    public static function flush(): void
    {
        // 1. Flush Cache Form Data
        Cache::forget(self::FORM_CACHE_KEY);

        // 2. Flush Cache API Recommendation Product
        Cache::tags(self::KEYWORD_API_CACHE_KEY)->flush();
    }
}
