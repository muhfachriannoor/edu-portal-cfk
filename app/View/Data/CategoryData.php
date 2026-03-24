<?php

namespace App\View\Data;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CategoryData
{
    public const LOCALE_SEPARATOR = "_";
    public const FORM_CACHE_KEY = "category_form_data";
    public const API_CACHE_KEY = "category_api_list";
    public const FULL_LIST_CACHE_KEY = "master_data_categories_with_subs";
    public const DETAIL_CACHE_KEY = "category_detail_by_slug";
    public const OTHERS_CACHE_KEY = "category_others_for_details";

    private const CACHE_TTL = 3600; // 60 minutes

    // --- METHOD: LISTS FOR BACKEND FORM (pluck: 'id' => 'name') ---
    public static function lists(): array
    {
        return Cache::remember(self::FORM_CACHE_KEY, now()->addMinutes(10), function () {
            $categories = Category::where('is_active', true)
                                // ->whereNull('parent_id')
                                ->with('translations')
                                ->orderBy('name')
                                ->get();
            return $categories->mapWithKeys(function ($category) {
                return [$category->id => $category->name];
            })->toArray();
        });
    }

    // --- METHOD: LISTS FOR API ENDPOINT (Categories only) ---
    public static function listsForApi(string $locale = 'en'): array
    {
        $cacheKey = self::API_CACHE_KEY . self::LOCALE_SEPARATOR . $locale;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            $categories = Category::where('is_active', true)
                                ->whereNull('parent_id')
                                ->with('translations')
                                ->orderBy('order')
                                ->get();

            return $categories->map(function ($category) use ($locale) {
                $translation = $category->translation($locale);

                return [
                    'id' => $category->id,
                    'name' => optional($translation)->name ?? $category->name,
                    'slug' => $category->slug,
                    'description' => optional($translation)->description ?? $category->description, 
                    'order' => $category->order,
                    'image_url' => $category->image ? Storage::url($category->image) : null,
                    'icon_url' => $category->icon_image ? Storage::url($category->icon_image) : null,
                    'is_navbar' => $category->is_navbar,
                ];
            })->toArray();
        });
    }

    // -- METHOD: LISTS WITH SUB-CATEGORIES FOR API (Master Data) ---
    public static function listsWithSubCategories(string $locale = 'en'): array
    {
        $cacheKey = self::FULL_LIST_CACHE_KEY . self::LOCALE_SEPARATOR . $locale;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            
            $allData = Category::where('is_active', true)
                            ->with('translations')
                            ->orderBy('order')
                            ->get();
            
            $mainCategories = $allData->filter(fn ($item) => is_null($item->parent_id));
            $subCategories = $allData->filter(fn ($item) => !is_null($item->parent_id));
            $groupedSubCategories = $subCategories->groupBy('parent_id');

            $formatItem = function ($item, $locale) {
                $translation = $item->translation($locale);

                return [
                    'id' => $item->id,
                    'name' => optional($translation)->name ?? $item->name,
                    'slug' => $item->slug,
                    'description' => optional($translation)->description ?? $item->description,
                    'order' => $item->order,
                    'image_url' => $item->image ? Storage::url($item->image) : null,
                    'icon_url' => $item->icon_image ? Storage::url($item->icon_image) : null,
                    'is_navbar' => $item->is_navbar,
                ];
            };

            return $mainCategories->map(function ($category) use ($groupedSubCategories, $locale, $formatItem) {
                $categoryId = $category->id;

                // Format Categories Main (parent)
                $formattedCategory = $formatItem($category, $locale);

                // Take SubCategory grouped
                $subs = $groupedSubCategories->get($categoryId, collect());

                // Format SubCategories (child)
                // $formattedSubs = $subs->map(fn ($sub) => $formatItem($sub, $locale))->toArray();
                $formattedSubs = $category->allDescendants->map(fn ($sub) => $formatItem($sub, $locale))->toArray();

                // Add SubCategories to Categories Main
                $formattedCategory['sub_categories'] = $formattedSubs;

                return $formattedCategory;
                
            })->values()->toArray();
        });
    }

    /**
     * Retrieve a single root category (where parent_id is null) by slug.
     * Return an array ready for API responses, or null if not found.
     * 
     * The returned array also includes "sub_categories" containing
     * all active child categories (where parent_id = category id).
     */
    public static function detailBySlug(string $slug, string $locale = 'en'): ?array
    {
        $cacheKey = self::DETAIL_CACHE_KEY . self::LOCALE_SEPARATOR . $locale . '_' . $slug;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug, $locale) {
            // Load root category (parent_id is null)
            $category = Category::where('is_active', true)
                ->whereNull('parent_id') // Only root/main categories
                ->where('slug', $slug)
                ->with(['translations', 'seos'])
                ->first();

            if (!$category) {
                return null;
            }

            $translation = $category->translation($locale);
            $seo = $category->seos->where('locale', $locale)->first();

            // Load all active sub-categories for this category
            // $subCategories = Category::where('is_active', true)
            //      ->where('parent_id', $category->id)
            //      ->with('translations')
            //      ->orderBy('order')
            //      ->get()
            
            $subCategories = $category->allDescendants
                ->map(function (Category $sub) use ($locale, $category) {
                    $subTranslation = $sub->translation($locale);

                    return [
                        'id' => $sub->id,
                        'category_id' => $category->id,
                        'name' => optional($subTranslation)->name ?? $sub->name,
                        'slug' => $sub->slug,
                        'order' => $sub->order,
                        'image_url' => $sub->image ? Storage::url($sub->image) : null,
                        'icon_url' => $sub->icon_image ? Storage::url($sub->icon_image) : null,
                    ];
                })->toArray();

            return [
                'id' => $category->id,
                'name' => optional($translation)->name ?? $category->name,
                'slug' => $category->slug,
                'description' => optional($translation)->description ?? $category->description,
                'order' => $category->order,
                'image_url' => $category->image ? Storage::url($category->image) : null,
                'icon_url' => $category->icon_image ? Storage::url($category->icon_image) : null,
                'is_navbar' => $category->is_navbar,
                'meta_title'       => $seo->meta_title ?? null,
                'meta_description' => $seo->meta_description ?? null,
                'meta_keywords'    => $seo->meta_keywords ?? null,
                'sub_categories'   => $subCategories, // always an array (can be empty)
            ];
        });
    }

    /**
     * Retrieve other root categories for the "keep exploring" section.
     * 
     * @param int $currentCategoryId ID of the currently opened category.
     * @param string $locale Locale used for translations.
     * 
     * @return array<int, array<string, mixed>>
     */
    public static function othersForDetail(int $currentCategoryId, string $locale = 'en'): array
    {
        $cacheKey = self::OTHERS_CACHE_KEY . self::LOCALE_SEPARATOR . $locale . '_' . $currentCategoryId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($currentCategoryId, $locale) {
            $query = Category::where('is_active', true)
                ->whereNull('parent_id') // Only root categories main
                ->where('id', '!=', $currentCategoryId)
                ->with('translations')
                ->orderBy('order');
                // ->inRandomOrder(); // for random data

            $categories = $query->get();

            return $categories->map(function ($category) use ($locale) {
                $translation = $category->translation($locale);

                return [
                    'id' => $category->id,
                    'name' => optional($translation)->name ?? $category->name,
                    'slug' => $category->slug,
                    'image_url' => $category->image ? Storage::url($category->image) : null,
                ];
            })->toArray();
        });
    }

    // -- METHOD: FLUSH CACHE ---
    public static function flush(): void
    {
        $locales = ['en', 'id'];

        // Flush form data cache
        Cache::forget(self::FORM_CACHE_KEY);

        // Get all root categories so we can build detail/others cache keys
        $rootCategories = Category::whereNull('parent_id')->get(['id', 'slug']);

        foreach ($locales as $locale) {
            // 1. Flush simple lists
            Cache::forget(self::API_CACHE_KEY . self::LOCALE_SEPARATOR . $locale);
            Cache::forget(self::FULL_LIST_CACHE_KEY . self::LOCALE_SEPARATOR . $locale);

            // 2. Flush detail & "keep exploring" caches per category
            foreach ($rootCategories as $category) {
                // Detail cache key
                $detailKey = self::DETAIL_CACHE_KEY . self::LOCALE_SEPARATOR . $locale . '_' . $category->slug;

                Cache::forget($detailKey);

                // Others cache keys (for limit 4 and "all")
                $othersPrefix = self::OTHERS_CACHE_KEY . self::LOCALE_SEPARATOR . $locale . '_' . $category->id . '_';

                Cache::forget($othersPrefix . 'limit_4');
                Cache::forget($othersPrefix . 'limit_all');
            }
        }
    }
}
