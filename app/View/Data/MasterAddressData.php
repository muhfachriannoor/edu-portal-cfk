<?php

namespace App\View\Data;

use App\Models\MasterAddress;
use Illuminate\Support\Facades\Cache;

class MasterAddressData
{
    public const FORM_CACHE_KEY = "master_address_form_data";
    private const CACHE_TTL = 3600; // 60 minutes

    public static function lists(): array
    {
        return Cache::remember(self::FORM_CACHE_KEY, self::CACHE_TTL, function () {
            $result = [];

            MasterAddress::select([
                'subdistrict_id',
                'country_name',
                'province_name',
                'city_name',
                'district_name',
                'subdistrict_name',
            ])
            ->chunk(1000, function ($rows) use (&$result) {
                foreach ($rows as $row) {
                    $key = implode('|', [
                        $row->subdistrict_id,
                        $row->country_name,
                        $row->province_name,
                        $row->city_name,
                        $row->district_name,
                        $row->subdistrict_name,
                    ]);

                    $result[$key] = [
                        'subdistrict_id' => $row->subdistrict_id,
                        'country_name' => $row->country_name,
                        'province_name' => $row->province_name,
                        'city_name' => $row->city_name,
                        'district_name' => $row->district_name,
                        'subdistrict_name' => $row->subdistrict_name,
                    ];
                }
            });

            return array_values($result);
        });
    }

    
    // -- METHOD: FLUSH CACHE ---
    public static function flush(): void
    {
        // 1. Flush Cache
        Cache::forget(self::FORM_CACHE_KEY);        
    }
}