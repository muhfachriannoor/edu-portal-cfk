<?php

namespace App\View\Data;

use App\Models\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class BrandData
{

    public const FORM_CACHE_KEY = "brand_form_data";
    public const API_CACHE_KEY = "brand_api_list";
    public const KEYWORD_API_CACHE_KEY = "keyword_brand_api_list";

    private const CACHE_TTL = 3600; // 60 minutes

    // --- METHOD: LISTS FOR BACKEND FORM (pluck: 'id' => 'name') ---
    public static function lists(): array
    {
        return Cache::remember(self::FORM_CACHE_KEY, now()->addMinutes(10), function () {
            // Format: ['id' => 'name']
            return Brand::where('is_active', 1)->orderBy('name')->pluck('name', 'id')->toArray();
        });
    }

    // --- METHOD: LISTS FOR API ENDPOINT (Categories only) ---
    public static function listsForApi(): array
    {
        return Cache::remember(self::API_CACHE_KEY, self::CACHE_TTL, function () {
            $brands = Brand::where('is_active', true)
                                ->get();

            // Format: Array of Objects for API
            return $brands->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'image_url' => $brand->file_url,
                ];
            })->toArray();
        });
    }

    // 
    public static function listsForApiKeyword($keyword = "")
    {
        $key = self::KEYWORD_API_CACHE_KEY . ":{$keyword}";
        return Cache::tags(self::KEYWORD_API_CACHE_KEY)->remember($key, self::CACHE_TTL, function () use($keyword) {
            return Brand::where('is_active', true)
                ->when($keyword, function($query) use($keyword) {
                    return $query->where('name', 'like', "%$keyword%");
                })
                ->orderBy('name')
                ->get()
                ->map(function($brand){
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'image_url' => $brand->file_url,
                    ];
                });
        });
    }

    // -- METHOD: FLUSH CACHE ---
    public static function flush(): void
    {
        // 1. Flush Cache Form Data
        Cache::forget(self::FORM_CACHE_KEY);

        // 2. Flush Cache API Brand only
        Cache::forget(self::API_CACHE_KEY);

        // 3. Flush Cache API Keyword Product
        Cache::tags(self::KEYWORD_API_CACHE_KEY)->flush();
    }
}
