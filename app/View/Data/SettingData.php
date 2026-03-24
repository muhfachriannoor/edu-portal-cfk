<?php

namespace App\View\Data;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CategoryData
{
    public const LOCALE_SEPARATOR = "_";
    public const FORM_CACHE_KEY = "setting_form_data";
    public const FORM_CONTENT_CACHE_KEY = "setting_content_form_data";

    private const CACHE_TTL = 3600; // 60 minutes

    // --- METHOD: LISTS FOR BACKEND FORM (pluck: 'id' => 'name') ---
    public static function lists(): array
    {
        return Cache::remember(self::FORM_CACHE_KEY, now()->addMinutes(10), function () {
            return Setting::all()
                ->map(function($setting){
                    return [
                        'id' => $setting->id,
                        'name' => $setting->name,
                        'key' => $setting->key,
                        'data' => $setting->data,
                    ]; 
                });
        });
    }

    // --- METHOD: LISTS FOR BACKEND FORM (pluck: 'id' => 'name') ---
    public static function content($key, $locale): array
    {
        $key = self::FORM_CONTENT_CACHE_KEY . ":{$key}-{$locale}";
        return Cache::tags(self::FORM_CONTENT_CACHE_KEY)->remember($key, now()->addMinutes(60), function () use($key){
            return Setting::all()
                ->map(function($setting){
                    return [
                        'id' => $setting->id,
                        'name' => $setting->name,
                        'key' => $setting->key,
                        'data' => $setting->data,
                    ]; 
                });
        });
    }

    // -- METHOD: FLUSH CACHE ---
    public static function flush(): void
    {
        $locales = ['en', 'id'];

        // Flush form data cache
        Cache::forget(self::FORM_CACHE_KEY);

        // Flush content cache
        Cache::tags(self::FORM_CONTENT_CACHE_KEY)->flush();
    }
}
