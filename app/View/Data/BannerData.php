<?php

namespace App\View\Data;

use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

class BannerData
{
    public const API_CACHE_KEY_PREFIX = "banner_api_list_";

    public static function listsForApi(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = self::API_CACHE_KEY_PREFIX . $locale;

        return Cache::rememberForever($cacheKey, function () use ($locale) {
            $banners = Banner::query()
                ->with([
                    'translations',
                    'files' => function ($q) {
                        $q->where('field', 'image');
                    }
                ])
                ->where('is_active', true)
                ->orderBy('sequence', 'asc')
                ->get();

            return $banners->map(function (Banner $banner) use ($locale) {
                $t = $banner->translation($locale);

                return [
                    'id' => $banner->id,
                    'title' => $t->description ?? '',
                    'subtitle' => $t->name ?? '',
                    'image' => $banner->image,
                    'sequence' => $banner->sequence,
                ];
            })->values()->toArray();
        });
    }

    public static function listsForApiByCategory(string $category, ?string $locale = null, int $limit = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = self::API_CACHE_KEY_PREFIX . $category . '_' . $locale;

        return Cache::rememberForever($cacheKey, function () use ($category, $locale, $limit) {
            $query = Banner::query()
                ->with([
                    'translations',
                    'files' => function ($q) {
                        $q->where('field', 'image');
                    }
                ])
                ->where('is_active', true)
                ->where('category', $category)
                ->orderBy('sequence', 'asc');

            // Apply limit if provided, else no limit for categories
            if ($limit) {
                $query->limit($limit);
            }

            $banners = $query->get();

            return $banners->map(function (Banner $banner) use ($locale) {
                $t = $banner->translation($locale);

                return [
                    'id' => $banner->id,
                    'title' => $t->description ?? '',
                    'subtitle' => $t->name ?? '',
                    'image' => $banner->image,
                    'sequence' => $banner->sequence,
                ];
            })->values()->toArray();
        });
    }

    public static function flush(): void
    {
        $locales = ['id', 'en'];

        foreach ($locales as $locale) {
            Cache::forget(self::API_CACHE_KEY_PREFIX . $locale);
        }

        // Clear cache for specific categories and locales
        $categories = ['HOME_NOT_LOGGED', 'PROMOTION'];

        foreach ($categories as $category) {
            foreach ($locales as $locale) {
                Cache::forget(self::API_CACHE_KEY_PREFIX . $category . '_' . $locale);
            }
        }
    }
}
