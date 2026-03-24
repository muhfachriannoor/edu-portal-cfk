<?php

namespace App\View\Data;

use App\Models\ChannelCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PaymentCategoryData
{

    public const FORM_CACHE_KEY = "payment_category_form_data";

    private const CACHE_TTL = 3600; // 60 minutes

    // --- METHOD: LISTS FOR BACKEND FORM (pluck: 'id' => 'name') ---
    public static function lists(): array
    {
        return Cache::remember(self::FORM_CACHE_KEY, self::CACHE_TTL, function () {
            // Format: ['id' => 'name']
            return ChannelCategory::where('is_enabled', 1)->orderBy('name')->pluck('name', 'id')->toArray();
        });
    }


    // -- METHOD: FLUSH CACHE ---
    public static function flush(): void
    {
        // 1. Flush Cache Form Data
        Cache::forget(self::FORM_CACHE_KEY);
    }
}
