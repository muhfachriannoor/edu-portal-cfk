<?php

namespace App\View\Data;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SubCategoryData
{
    public const LOCALE_SEPARATOR = "_";
    public const FORM_CACHE_KEY = "subcategory_form_data";
    public const ALL_API_CACHE_KEY = "subcategory_api_list_all";
    public const FILTERED_CACHE_KEY_PREFIX = "subcategory_api_list_category_";
    public const HOMEPAGE_CACHE_KEY = "subcategory_api_homepage_featured_6"; // Cache key for homepage (6 item, random)

    private const CACHE_TTL = 3600; // 60 minutes
    private const SUPPORTED_LOCALES = ['en', 'id'];

    // --- METHOD: LISTS FOR BACKEND FORM (pluck: 'id' => 'name') ---
    public static function lists($category_id = null): array
    {
        $cacheKey = self::FORM_CACHE_KEY . ($category_id ? ('_' . $category_id) : '');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($category_id) {
            if (!$category_id) {
                return Category::whereNotNull('parent_id')->get()->pluck('name', 'id')->toArray();
            }

            $category = Category::with('children.children')->find($category_id);

            if (!$category) {
                return [];
            }

            return $category->all_descendants->pluck('name', 'id')->toArray();

            // $query = Category::query()
            //             ->with('translations')
            //             ->where('parent_id', $category_id)
            //             ->where('is_active', 1)
            //             ->orderBy('name')
            //             ->get();
            
            // return $query->mapWithKeys(function ($subCategory) {
            //     return [$subCategory->id => $subCategory->name];
            // })->toArray();
        });
    }

    // --- METHOD: LISTS FOR API ENDPOINT (Array of Objects, with optional filter) ---
    public static function listsForApi(?int $categoryId = null, string $locale = 'en'): array
    {
        $baseCacheKey = $categoryId ? self::FILTERED_CACHE_KEY_PREFIX . $categoryId
                                    : self::ALL_API_CACHE_KEY;

        $cacheKey = $baseCacheKey . self::LOCALE_SEPARATOR . $locale;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categoryId, $locale) {
            $category = Category::with('children.children')->find($categoryId);

            if ($category) {
                return $category->allDescendants->map(function ($subCategory) use ($locale) {
                    $translation = $subCategory->translation($locale);

                    return [
                        'id' => $subCategory->id,
                        'category_id' => $subCategory->parent_id,
                        'name' => optional($translation)->name ?? $subCategory->name,
                        'slug' => $subCategory->slug,
                        'description' => optional($translation)->description ?? $subCategory->description,
                        'order' => $subCategory->order,
                        'image_url' => $subCategory->image ? Storage::url($subCategory->image) : null,
                        // 'display_name' => $subCategory->categoryDisplayName
                    ];
                })->toArray();
            }

            $subCategories = Category::where('is_active', true)
                                    ->whereNotNull('parent_id')
                                    ->with('translations')
                                    ->orderBy('order')
                                    ->get();

                // Format: Array of Objects for API
                return $subCategories->map(function ($subCategory) use ($locale) {
                    $translation = $subCategory->translation($locale);

                    return [
                        'id' => $subCategory->id,
                        'category_id' => $subCategory->parent_id,
                        'name' => optional($translation)->name ?? $subCategory->name,
                        'slug' => $subCategory->slug,
                        'description' => optional($translation)->description ?? $subCategory->description,
                        'order' => $subCategory->order,
                        'image_url' => $subCategory->image ? Storage::url($subCategory->image) : null,
                        // 'display_name' => $subCategory->categoryDisplayName
                    ];
                })->toArray();
        });
    }

    // --- METHOD FOR HOMEPAGE FEATURED (6 Random items with image) ---
    public static function listsForHomePage(string $locale = 'en'): array
    {
        $cacheKey = self::HOMEPAGE_CACHE_KEY . self::LOCALE_SEPARATOR . $locale;

        return Cache::remember($cacheKey, 300, function () use ($locale) {
            $subCategories = Category::query()
                            ->select(['id', 'name', 'slug', 'image'])
                            ->where('is_active', true)
                            ->whereNotNull(['image', 'parent_id'])
                            ->with('translations')
                            ->inRandomOrder()
                            ->take(6)
                            ->get();
            
            // Format: Array of Objects for API
            return $subCategories->map(function ($subCategory) use ($locale) {
                $translation = $subCategory->translation($locale);

                return [
                    'id' => $subCategory->id,
                    'name' => optional($translation)->name ?? $subCategory->name,
                    'slug' => $subCategory->slug,
                    'image_url' => $subCategory->image ? Storage::url($subCategory->image) : null,
                ];
            })->toArray();
        });
    }

    // -- METHOD: FLUSH CACHE ---
    public static function flush(?int $categoryId = null): void
    {
        // 1. Flush Cache Form Data
        Cache::forget(self::FORM_CACHE_KEY);

        // 2. Flush Cache API FILTER AND ALL API
        Cache::forget(self::ALL_API_CACHE_KEY);

        foreach (self::SUPPORTED_LOCALES as $locale) {
            Cache::forget(self::ALL_API_CACHE_KEY . self::LOCALE_SEPARATOR . $locale);
            Cache::forget(self::HOMEPAGE_CACHE_KEY . self::LOCALE_SEPARATOR . $locale);

            if ($categoryId) {
                Cache::forget(self::FILTERED_CACHE_KEY_PREFIX . $categoryId . self::LOCALE_SEPARATOR . $locale);
            } else {
                $categoryIds = DB::table('categories')->pluck('id');
                foreach ($categoryIds as $id) {
                    Cache::forget(self::FILTERED_CACHE_KEY_PREFIX . $id . self::LOCALE_SEPARATOR . $locale);
                }
            }
        }
    }
}
