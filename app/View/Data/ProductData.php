<?php

namespace App\View\Data;

use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\SpecialPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductData
{

    public const FORM_CACHE_KEY = "product_form_data";
    public const API_CACHE_KEY = "product_api_list";
    public const RECOMMENDATION_API_CACHE_KEY = "recommendation_product_api_list";
    public const BESTSELLER_API_CACHE_KEY = "bestseller_product_api_list";
    public const KEYWORD_API_CACHE_KEY = "keyword_product_api_list";
    public const SEARCH_RESULT_API_CACHE_KEY = "search_result_product_api_list";
    public const CATEGORY_API_CACHE_KEY = "product_api_list_from_category";
    public const FULL_LIST_CACHE_KEY = "master_data_products_with_subs";

    // For Shopping Bag
    public const LOCALE_SEPARATOR = "_";
    public const DETAIL_CACHE_TTL = 3600; // 60 minutes
    public const PRICE_CACHE_TTL = 600; // 10 minutes
    public const VARIANT_DETAIL_KEY = "product_variant_details";
    public const SPECIAL_PRICE_KEY = "product_variant_special_price";

    private const CACHE_TTL = 3600; // 60 minutes

    // --- METHOD: LISTS FOR BACKEND FORM (pluck: 'id' => 'name') ---
    public static function lists(): array
    {
        return Cache::remember(self::FORM_CACHE_KEY, now()->addMinutes(10), function () {
            // Format: ['id' => 'name']
            return Product::where('is_active', 1)->orderBy('name')->pluck('name', 'id')->toArray();
        });
    }

    // --- METHOD: LISTS FOR API ENDPOINT (Categories only) ---
    public static function listsForApi()
    {
        return Cache::remember(self::API_CACHE_KEY, self::CACHE_TTL, function () {
            $products = Product::with([
                    'category', 'brand',
                    'variants.special_price', 'store.masterLocation',
                ])
                ->where('is_active', true)
                ->orderBy('created_at')
                ->get();

            $today = Carbon::today();

            $mappedProducts = $products->map(function ($product) use ($today) {
                $variant = $product->variants
                    ->where('is_active', 1)
                    ->sortBy('price')
                    ->first();
                
                $basePriceInt = (int) ($variant->price ?? 0);

                $activeSpecialPrice = null;
                
                if ($variant) {
                    $activeSpecialPrice = $variant->special_price
                        ->filter(function ($sp) use ($today) {
                            if (!$sp || (int) $sp->is_active !== 1) return false;
                            return Carbon::parse($sp->start_at)->lte($today)
                                && Carbon::parse($sp->end_at)->gte($today);
                        })
                        ->sortByDesc('discount')
                        ->first();
                }

                $hasDiscount = $activeSpecialPrice !== null;
                $discountAmount = (int) ($activeSpecialPrice->discount ?? 0);

                // Format harga
                $priceFormatted = number_format($basePriceInt, 0, ",", ".");
                $newPriceFormatted = number_format($discountAmount, 0, ",", ".");

                return [
                    'id' => $product->id,
                    'category' => $product->category?->slug,
                    'subcategory' => $product->subcategory?->slug,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'store_id' => $product->store?->id,
                    'store_name' => $product->store?->name,
                    'location_path' => $product->store?->masterLocation?->location_path_api,
                    'brand_id' => $product->brand?->id,
                    'brand_name' => $product->brand?->name,
                    'image_url' => $product->default_image,
                    'price' => $priceFormatted,
                    'is_pickup' => $product->store?->is_pickup,
                    'is_delivery' => $product->store?->is_delivery,
                    'rating' => "4.8",
                    'total_sold' => 100,
                    'has_discount' => $hasDiscount,
                    'discount_percentage' => $hasDiscount ? ((string)((int)floor($activeSpecialPrice->percentage ?? 0)) . '%') : '0%',
                    'new_price' => ($discountAmount > 0) ? $newPriceFormatted : $priceFormatted,
                    'is_wishlist' => (bool) rand(0, 1),
                    'is_bestseller' => $product->is_bestseller,
                    'is_truly_indonesian' => $product->is_truly_indonesian,
                    'is_limited_edition' => $product->is_limited_edition,
                    'created_at' => Carbon::parse($product->created_at)->format('Y-m-d H:i:s'),
                ];
            });

            return $mappedProducts->groupBy('product_slug')->map(function ($group) {
                $priority = $group->first(function ($item) {
                    return $item['location_path'] === 'THA/Stock/Thamrin';
                });

                return $priority ?: $group->first();
            })->values();
        });
    }

    // --- METHOD: LISTS FOR API ENDPOINT WITH STORE SLUG (Categories only) ---
    public static function listsFromCategoryApi($category)
    {
        $key = self::CATEGORY_API_CACHE_KEY . $category;

        return Cache::tags(self::CATEGORY_API_CACHE_KEY)->remember($key, self::CACHE_TTL, function () use ($category) {
            $products = Product::with([
                'category', 'subcategory', 'store.masterLocation',
                'brand', 'variants.special_price'
            ])
                ->where('is_active', true)
                ->where('category_id', $category)
                ->orderBy('created_at')
                ->get();
            
            return $products->map(function ($product) {
                $variant = $product->variants->sortBy('price')->first();
                $specialPrice = ($variant) ? ($variant->special_price->first() ?? []) : [];
                $percentage = (string) ((int) ($specialPrice->percentage ?? 0)).'%';
                $price = number_format( ($variant->price ?? 0), 0, ",", ".");
                $discountValue = $specialPrice->discount ?? 0;
                $newPriceFormatted = number_format($discountValue, 0, ",", ".");
                $priceFormatted = number_format(($variant->price ?? 0), 0, ",", ".");

                return [
                    'id' => $product->id,
                    'category' => $product->category?->slug,
                    'subcategory' => $product->subcategory?->slug,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'store_id' => $product->store?->id,
                    'store_name' => $product->store?->name,
                    'location_path' => $product->store?->masterLocation?->location_path_api,
                    'brand_id' => $product->brand?->id,
                    'brand_name' => $product->brand?->name,
                    'image_url' => $product->default_image,
                    'price' => $price,

                    'is_pickup'     => $product->store?->is_pickup,
                    'is_delivery'   => $product->store?->is_delivery,

                    // disiapkan dulu, belum integrasi
                    'rating' => "4.8",
                    'total_sold' => 100,
                    'has_discount' => ($specialPrice) ? true : false,
                    'discount_percentage' => $percentage,
                    'new_price' => ($discountValue > 0) ? $newPriceFormatted : $priceFormatted,
                    'is_wishlist' => (bool) rand(0, 1),
                    'is_bestseller' => $product->is_bestseller,
                    'is_truly_indonesian' => $product->is_truly_indonesian,
                    'is_limited_edition' => $product->is_limited_edition,
                    'created_at' => Carbon::parse($product->created_at)->format('Y-m-d H:i:s')
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

    public static function listsForApiRecommendation($user, $interests = [])
    {
        $key = self::RECOMMENDATION_API_CACHE_KEY . ":{$user->id}";

        return Cache::tags(self::RECOMMENDATION_API_CACHE_KEY)->remember($key, self::CACHE_TTL, function () use ($interests) {
            $products = Product::with([
                    'store.masterLocation', 
                    'brand', 
                    'variants.special_price'
                ])
                ->where('is_active', true)
                ->when(count($interests) > 0, function($query) use($interests) {
                    return $query->whereIn('sub_category_id', $interests);
                })
                ->orderBy('created_at')
                ->get();

            $mapped = $products->map(function ($product) {
                $variant = $product->variants->sortBy('price')->first();
                $specialPrice = ($variant) ? ($variant->special_price->first() ?? []) : [];
                
                $percentage = (string)((int)($specialPrice->percentage ?? 0)).'%';
                $newPriceVal = $specialPrice->discount ?? 0;
                $newPriceFormatted = number_format($newPriceVal, 0, ",", ".");
                $priceFormatted = number_format(($variant->price ?? 0), 0, ",", ".");

                return [
                    'id' => $product->id,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'store_id' => $product->store?->id,
                    'store_name' => $product->store?->name,
                    'location_path' => $product->store?->masterLocation?->location_path_api,
                    'brand_id' => $product->brand?->id,
                    'brand_name' => $product->brand?->name,
                    'image_url' => $product->default_image,
                    'price' => $priceFormatted,
                    'is_pickup' => $product->store?->is_pickup,
                    'is_delivery' => $product->store?->is_delivery,
                    'rating' => "4.8",
                    'total_sold' => 100,
                    'has_discount' => !empty($specialPrice),
                    'discount_percentage' => $percentage,
                    'new_price' => ($newPriceVal > 0) ? $newPriceFormatted : $priceFormatted,
                    'is_wishlist' => (bool) rand(0, 1),
                    'is_bestseller' => $product->is_bestseller,
                ];
            });

            return $mapped->groupBy('product_slug')->map(function ($group) {
                $priority = $group->first(function ($item) {
                    return $item['location_path'] === 'THA/Stock/Thamrin';
                });
                return $priority ?: $group->first();
            })->values();
        });
    }

    public static function listsForApiBestSeller()
    {
        $key = self::BESTSELLER_API_CACHE_KEY;
        return Cache::tags(self::BESTSELLER_API_CACHE_KEY)->remember($key, self::CACHE_TTL, function () {
            $products = Product::with([
                    'store.masterLocation', 
                    'brand', 
                    'variants.special_price'
                ])
                ->where('is_bestseller', true)
                ->where('is_active', true)
                ->orderBy('created_at')
                ->get();

            $mapped = $products->map(function ($product) {
                $variant = $product->variants->sortBy('price')->first();
                $specialPrice = ($variant) ? ($variant->special_price->first() ?? []) : [];
                
                $percentage = (string)((int)($specialPrice->percentage ?? 0)).'%';
                $discountVal = $specialPrice->discount ?? 0;
                $newPriceFormatted = number_format($discountVal, 0, ",", ".");
                $priceFormatted = number_format(($variant->price ?? 0), 0, ",", ".");

                return [
                    'id' => $product->id,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'store_id' => $product->store?->id,
                    'store_name' => $product->store?->name,
                    'location_path' => $product->store?->masterLocation?->location_path_api,
                    'brand_id' => $product->brand?->id,
                    'brand_name' => $product->brand?->name,
                    'image_url' => $product->default_image,
                    'price' => $priceFormatted,
                    'is_pickup' => $product->store?->is_pickup,
                    'is_delivery' => $product->store?->is_delivery,

                    'rating'        => "4.8",
                    'total_sold' => 100,
                    'has_discount' => !empty($specialPrice),
                    'discount_percentage' => $percentage,
                    'new_price' => ($discountVal > 0) ? $newPriceFormatted : $priceFormatted,
                    'is_wishlist' => (bool) rand(0, 1),
                    'is_bestseller' => (bool) $product->is_bestseller
                ];
            });
            return $mapped->groupBy('product_slug')->map(function ($group) {
                $priority = $group->first(function ($item) {
                    return $item['location_path'] === 'THA/Stock/Thamrin';
                });
                return $priority ?: $group->first();
            })->values();
        });
    }

    public static function listsForApiKeyword($keyword = "")
    {
        $key = self::KEYWORD_API_CACHE_KEY . ":{$keyword}";
        return Cache::tags(self::KEYWORD_API_CACHE_KEY)->remember($key, self::CACHE_TTL, function () use($keyword) {
            $products = Product::with(['store.masterLocation', 'variants.special_price'])
                ->where('is_active', true)
                ->when($keyword, function($query) use($keyword) {
                    $query->where('name', 'like', "%$keyword%");
                })
                ->orderBy('created_at')
                ->get();

            $mapped = $products->map(function ($product) {
                $variant = $product->variants->sortBy('price')->first();
                $specialPrice = ($variant) ? ($variant->special_price->first() ?? []) : [];
                
                $percentage = (string)((int)($specialPrice->percentage ?? 0)).'%';
                $discountVal = $specialPrice->discount ?? 0;
                $newPriceFormatted = number_format($discountVal, 0, ",", ".");
                $priceFormatted = number_format(($variant->price ?? 0), 0, ",", ".");

                return [
                    'product_name' => $product->name,
                    'price' => $priceFormatted,
                    'new_price' => ($discountVal > 0) ? $newPriceFormatted : $priceFormatted,
                    'discount_percentage' => $percentage,
                    'has_discount' => !empty($specialPrice),
                    'slug' => $product->slug,
                    'location_path' => $product->store?->masterLocation?->location_path_api,
                    'image_url'     => $product->default_image,
                    'is_bestseller' => (bool) $product->is_bestseller,
                ];
            });

            return $mapped->groupBy('slug')->map(function ($group) {
                $priority = $group->first(function ($item) {
                    return $item['location_path'] === 'THA/Stock/Thamrin';
                });
                return $priority ?: $group->first();
            })->values();
        });
    }

    public static function listsForApiSearchResult($keyword = "", $filters = [])
    {
        $keyword = trim($keyword);
        $key = self::SEARCH_RESULT_API_CACHE_KEY . ":{$keyword}:" . md5(serialize($filters));

        return Cache::tags(self::SEARCH_RESULT_API_CACHE_KEY)->remember($key, self::CACHE_TTL, function () use ($keyword, $filters) {
            $today = Carbon::today();
            $keywordLower = strtolower(trim($keyword));

            $query = Product::with([
                'category', 'subcategory', 'brand',
                'variants.special_price', 'store.masterLocation',
            ])
            ->where('is_active', true);

            if (!empty($keyword)) {
                $query->where('name', 'like', "%$keyword%");
            }

            // --- FILTER DATABASE (Query Level) ---
            if (!empty($filters['brand_ids'])) {
                $query->whereIn('brand_id', explode(',', $filters['brand_ids']));
            }
            if (!empty($filters['store_ids'])) {
                $query->whereIn('store_id', explode(',', $filters['store_ids']));
            }
            if (!empty($filters['subcategory'])) {
                $query->whereHas('subcategory', fn($q) => $q->where('slug', $filters['subcategory']));
            }
            if (isset($filters['is_bestseller']) && $filters['is_bestseller'] == 1) {
                $query->where('is_bestseller', true);
            }
            if (isset($filters['is_truly_indonesian']) && $filters['is_truly_indonesian'] == 1) {
                $query->where('is_truly_indonesian', true);
            }
            if (isset($filters['is_limited_edition']) && $filters['is_limited_edition'] == 1) {
                $query->where('is_limited_edition', true);
            }
            if (isset($filters['new_this_month']) && $filters['new_this_month'] == 1) {
                $query->whereMonth('created_at', $today->month)->whereYear('created_at', $today->year);
            }
            // Filter Store Delivery/Pickup
            if (isset($filters['is_pickup']) && $filters['is_pickup'] == 1) {
                $query->whereHas('store', fn($q) => $q->where('is_pickup', true));
            }
            if (isset($filters['is_delivery']) && $filters['is_delivery'] == 1) {
                $query->whereHas('store', fn($q) => $q->where('is_delivery', true));
            }

            $products = $query->get();

            $mapped = $products->map(function ($product) use ($today, $keywordLower) {
                $variant = $product->variants->where('is_active', 1)->sortBy('price')->first();
                $basePriceInt = (int) ($variant->price ?? 0);

                $activeSpecialPrice = $variant ? $variant->special_price
                    ->filter(fn($sp) => (int)$sp->is_active === 1 && Carbon::parse($sp->start_at)->lte($today) && Carbon::parse($sp->end_at)->gte($today))
                    ->sortByDesc('discount')->first() : null;

                $hasDiscount = $activeSpecialPrice !== null;
                $discountAmount = (int) ($activeSpecialPrice->discount ?? 0);
                
                $productNameLower = strtolower($product->name);
                $relevance = ($productNameLower === $keywordLower) ? 3 : (str_starts_with($productNameLower, $keywordLower) ? 2 : 1);

                return [
                    'id' => $product->id,
                    'category' => $product->category?->slug,
                    'subcategory' => $product->subcategory?->slug,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'store_id' => $product->store?->id,
                    'store_name' => $product->store?->name,
                    'location_path' => $product->store?->masterLocation?->location_path_api,
                    'brand_id' => $product->brand?->id,
                    'brand_name' => $product->brand?->name,
                    'image_url' => $product->default_image,
                    'price' => number_format($basePriceInt, 0, ",", "."),
                    'is_pickup' => (bool) $product->store?->is_pickup,
                    'is_delivery' => (bool) $product->store?->is_delivery,
                    'rating' => "4.8",
                    'total_sold' => 100,
                    'has_discount' => $hasDiscount,
                    'discount_percentage' => $hasDiscount ? ((string)((int)floor($activeSpecialPrice->percentage ?? 0)) . '%') : '0%',
                    'new_price' => number_format(($discountAmount > 0 ? $discountAmount : $basePriceInt), 0, ",", "."),
                    'is_wishlist' => false,
                    'is_bestseller' => (bool) $product->is_bestseller,
                    'is_truly_indonesian' => (bool) $product->is_truly_indonesian,
                    'is_limited_edition' => (bool) $product->is_limited_edition,
                    'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                    '_relevance' => $relevance,
                ];
            });

            // --- FILTER COLLECTION (Logic Level) ---
            if (isset($filters['is_discount']) && $filters['is_discount'] == 1) {
                $mapped = $mapped->where('has_discount', true);
            }

            // Deduplikasi & Priority Store
            $uniqueProducts = $mapped->groupBy('product_slug')->map(function ($group) {
                return $group->first(fn($item) => $item['location_path'] === 'THA/Stock/Thamrin') ?: $group->first();
            })->values();

            // Sorting
            $sortBy = $filters['sort_by'] ?? null;
            $sorted = $uniqueProducts->sort(function ($a, $b) use ($sortBy) {
                if ($a['_relevance'] !== $b['_relevance']) return $b['_relevance'] <=> $a['_relevance'];
                
                if ($sortBy === 'newest') return strtotime($b['created_at']) <=> strtotime($a['created_at']);
                if ($sortBy === 'price_low_to_high') return (int)str_replace('.', '', $a['new_price']) <=> (int)str_replace('.', '', $b['new_price']);
                if ($sortBy === 'price_high_to_low') return (int)str_replace('.', '', $b['new_price']) <=> (int)str_replace('.', '', $a['new_price']);
                
                return strtotime($b['created_at']) <=> strtotime($a['created_at']);
            })->values();

            return $sorted->map(function ($item) {
                unset($item['_relevance']);
                return $item;
            });
        });
    }

    /**
     * Retrieves static product variant display details from cache.
     * This method EXCLUDES LIVE STOCK (quantity).
     */
    public static function getVariantDisplayDetails(int $variantId, string $locale = 'en'): ?array
    {
        $cacheKey = self::VARIANT_DETAIL_KEY . self::LOCALE_SEPARATOR . $variantId . self::LOCALE_SEPARATOR . $locale;

        return Cache::remember($cacheKey, self::DETAIL_CACHE_TTL, function () use ($variantId, $locale) {
            $variant = ProductVariant::with([
                'product.translations',
            ])->where('id', $variantId)->first();

            if (!$variant) {
                return null;
            }

            $productName = $variant->product->name;
            
            // 1. Deconstruct and translate option combination (e.g., "1,5" -> "Black / L (Large)")
            $combination = $variant->combination;

            if (is_string($combination)) {
                $optionValueIds = explode(',', $combination);
            } elseif (is_array($combination)) {
                $optionValueIds = $combination;
            } else {
                $optionValueIds = [];
            }

            $optionValueIds = array_filter($optionValueIds, function($id) {
                return !empty($id);
            });

            $optionsData = ProductOptionValue::query()
                ->whereIn('id', $optionValueIds)
                ->get();

            $variantNames = $optionsData->map(function ($option) use ($locale) {
                $translation = $option->translation('en');

                return optional($translation)->name ?? $option->name;
            })->implode(' / ');

            return [
                'product_name' => $productName,
                'product_slug' => $variant->product->slug,
                'variant_names' => $variantNames,
                'base_price' => $variant->price,
                'main_image_url' => null,
            ];
        });
    }

    /**
     * Retrieves the currently active special price for a given variant ID.
     */
    public static function getActiveSpecialPrice(int $variantId): ?array
    {
        $cacheKey = self::SPECIAL_PRICE_KEY . self::LOCALE_SEPARATOR . $variantId;

        return Cache::remember($cacheKey, self::PRICE_CACHE_TTL, function () use ($variantId) {
            $now = Carbon::now();

            $specialPrice = SpecialPrice::where('product_variant_id', $variantId)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->orderBy('discount', 'desc') // Ambil diskon tertinggi jika ada lebih dari satu
                ->first(['discount', 'type', 'percentage']); 
            
            if (!$specialPrice) {
                return null;
            }
            
            return [
                'type' => 'absolute_reduction', 
                'value' => (float) $specialPrice->discount,
                'percentage' => $specialPrice->percentage,
            ];
        });
    }
    

    // -- METHOD: FLUSH CACHE ---
    public static function flush(): void
    {
        // 1. Flush Cache Form Data
        Cache::forget(self::FORM_CACHE_KEY);

        // 2. Flush Cache API Product only
        Cache::forget(self::API_CACHE_KEY);

        // 3. Flush Cache API Product with SubProduct
        Cache::forget(self::FULL_LIST_CACHE_KEY);

        // 4. Flush Cache API Recommendation Product
        Cache::tags(self::RECOMMENDATION_API_CACHE_KEY)->flush();

        // 5. Flush Cache API Recommendation Product
        Cache::tags(self::BESTSELLER_API_CACHE_KEY)->flush();

        // 6. Flush Cache API Keyword Product
        Cache::tags(self::KEYWORD_API_CACHE_KEY)->flush();

        // 7. Flush Cache API Search Result Product
        Cache::tags(self::SEARCH_RESULT_API_CACHE_KEY)->flush();

        // 8. Flush Cache API Category only
        Cache::tags(self::CATEGORY_API_CACHE_KEY)->flush();
        
    }

    /**
     * Flushes cache entries related to a specific product variant when data changes.
     */
    public static function flushVariantCache(int $variantId): void
    {
        $locales = ['en', 'id'];

        foreach ($locales as $locale) {
            $key = self::VARIANT_DETAIL_KEY . self::LOCALE_SEPARATOR . $variantId . self::LOCALE_SEPARATOR . $locale;
            Cache::forget($key);
        }

        Cache::forget(self::SPECIAL_PRICE_KEY . self::LOCALE_SEPARATOR . $variantId);
    }
}
