<?php

namespace App\View\Data;

use App\Models\Newsroom;
use Illuminate\Support\Facades\Cache;

class NewsroomData
{
    public const LOCALE_SEPARATOR = "_";
    public const API_CACHE_KEY = "newsroom_api_list";

    /**
     * Retrieves the list of active newsroom items from the cache forever.
     * The returned data is in an array format ready to be sent via the API.
     * * @return array
     */
    public static function listsForApi(string $locale = 'en'): array
    {
        $cacheKey = self::API_CACHE_KEY . self::LOCALE_SEPARATOR . $locale;
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($locale) {
            $data = Newsroom::where('is_active', true)
                            ->with(['translations', 'admin', 'seos'])
                            ->orderBy('published_at', 'desc')
                            ->get();
            
            return $data->map(function (Newsroom $item) use ($locale) {
                $translation = $item->translations->where('locale', $locale)->first();
                $seo = $item->seos->where('locale', $locale)->first();

                return [
                    'slug' => $item->slug,
                    'author_name' => $item->admin->name ?? 'Admin',
                    'title' => $translation->name ?? $item->title,
                    'content' => $translation->description ?? $item->content,
                    'image_url' => $item->image, 
                    'published_at' => $item->published_at
                        ? $item->published_at->toDateString()
                        : null,
                    'view_count' => $item->view_count,
                    
                    // --- SEO ---
                    'meta_title'       => $seo->meta_title ?? null,
                    'meta_description' => $seo->meta_description ?? null,
                    'meta_keywords'    => $seo->meta_keywords ?? null,
                ];
            })->toArray();
        });
    }

    /**
     * Flushes the newsroom API list cache.
     * * @return void
     */
    public static function flush(): void
    {
        $locales = ['en', 'id'];

        // 1. Flush API Cache
        foreach ($locales as $locale) {
            Cache::forget(self::API_CACHE_KEY . self::LOCALE_SEPARATOR . $locale);
        }
    }
}