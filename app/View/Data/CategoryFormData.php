<?php

namespace App\View\Data;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryFormData
{
    public static function lists(): array
    {
        $cacheKey = "category_form_data";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Category::where('is_active', 1)->orderBy('name')->pluck('name', 'id')->toArray();
        });
    }

    public static function flush(): void
    {
        Cache::forget("category_form_data");
    }
}
