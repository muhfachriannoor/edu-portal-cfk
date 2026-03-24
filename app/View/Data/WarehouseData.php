<?php

namespace App\View\Data;

use App\Models\Warehouse;
use Illuminate\Support\Facades\Cache;

class WarehouseData
{

    public const FORM_CACHE_KEY = "warehouse_form_data";
    private const CACHE_TTL = 3600; // 60 minutes

    // --- METHOD: LISTS FOR BACKEND FORM (pluck: 'id' => 'name') ---
    public static function lists(): array
    {
        return Cache::remember(self::FORM_CACHE_KEY, now()->addMinutes(10), function () {
            $warehouses = Warehouse::where('is_active', 1)
                                    ->get(['id', 'name', 'master_address_id']);
            return $warehouses->mapWithKeys(function ($item) {
                return [$item->id => "{$item->name} ({$item->master_address?->subdistrict_name})"];
            })->toArray();
        });
    }

    // -- METHOD: FLUSH CACHE ---
    public static function flush(): void
    {
        // 1. Flush Cache Form Data
        Cache::forget(self::FORM_CACHE_KEY);
    }
}
