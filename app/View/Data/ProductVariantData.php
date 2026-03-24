<?php

namespace App\View\Data;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductVariantData
{

    public const FORM_CACHE_KEY = "product_variant_form_data";
    public const API_CACHE_KEY = "product_api_list";
    public const FULL_LIST_CACHE_KEY = "master_data_products_with_subs";
    public const FORM_STORE_CACHE_KEY_PREFIX = "product_variant_form_data_store_";

    private const CACHE_TTL = 3600; // 60 minutes

    /**
     * Existing global lists (all active variants across all stores).
     * 
     * Format: ['id' => 'Product Name Variant Name']
     */
    public static function lists(): array
    {
        return Cache::remember(self::FORM_CACHE_KEY, now()->addMinutes(10), function () {
            // Format: ['id' => 'name']
            return ProductVariant::where('is_active', 1)
                ->get()
                ->map(function($variant){
                    return [
                        'id' => $variant->id,
                        'name' => trim($variant->product?->name . ' ' . $variant->getVariantName())
                    ];
                })
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Lists of variants for a specific store only.
     * 
     * This is used in Store CMS context (e.g. Special Price form & excel import)
     * to ensure we only show variants that belong to the current store.
     * 
     * Format: ['id' => 'Product Name Variant Name']
     */
    public static function listsForStoreForm(int $storeId): array
    {
        $cacheKey = self::FORM_STORE_CACHE_KEY_PREFIX . $storeId;

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($storeId) {
            return ProductVariant::where('is_active', 1)
                ->where('store_id', $storeId)
                ->get()
                ->map(function ($variant) {
                    return [
                        'id'   => $variant->id,
                        'name' => trim(($variant->product?->name ?? '') . ' ' . $variant->getVariantName()),
                    ];
                })
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    // --- METHOD: LISTS FOR API ENDPOINT (Categories only) ---
    public static function listsForApi()
    {
        return Cache::remember(self::API_CACHE_KEY, self::CACHE_TTL, function () {
            $randomIds = ProductVariant::inRandomOrder()->limit(200)->pluck('id');

            return ProductVariant::query()
                ->withActiveProduct()
                ->whereIn('id', $randomIds->take(100))
                ->get()
                ->map(function($variant){
                    return [
                        'id' => $variant->id,
                        'store_name' => $variant->store->name,
                        'product_name' => $variant->product->name,
                        'image_url' => $variant->product->default_image,
                        'price' => $variant->price,
                        'slug' => $variant->slug
                    ];
                });
        });
    }

    // --- METHOD: LISTS FOR API ENDPOINT WITH STORE SLUG (Categories only) ---
    public static function listsForStore($store): array
    {
        return Cache::remember(self::API_CACHE_KEY, self::CACHE_TTL, function () {
            $products = Product::where('is_active', true)
                                ->orderBy('order')
                                ->get();

            // Format: Array of Objects for API
            return $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'order' => $product->order,
                    'image_url' => $product->image ? Storage::url($product->image) : null,
                ];
            })->toArray();
        });
    }

    

    /**
     * Flush global form cache
     */
    public static function flush(): void
    {
        // 1. Flush Cache Form Data
        Cache::forget(self::FORM_CACHE_KEY);

        // 2. Flush Cache API Product only
        Cache::forget(self::API_CACHE_KEY);

        // 3. Flush Cache API Product with SubProduct
        Cache::forget(self::FULL_LIST_CACHE_KEY);
    }

    /**
     * Flush only the form cache for a specific store.
     * 
     */
    public static function flushForStore(int $storeId): void
    {
        $cacheKey = self::FORM_STORE_CACHE_KEY_PREFIX . $storeId;
        
        Cache::forget($cacheKey);
    }
}
