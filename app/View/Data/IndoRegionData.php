<?php

namespace App\View\Data;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class IndoRegionData
{
    // File and cache configuration
    private const FILE_PATH = 'public/assets/indo-region/province_with_city.json';
    private const CACHE_PREFIX = 'indo_region_api_';
    private const CACHE_TTL = 60 * 24 * 7; // 1 minggu

    /**
     * Baca file JSON + format, lalu cache per locale.
     * Output:
     *  - provinces: [ ['id' => 1, 'name' => 'East Kalimantan'], ... ]
     *  - cities:    [ ['id' => 1, 'province_id' => 23, 'name' => 'Samarinda City'], ... ]
     */
    private static function getFormattedData(string $locale): array
    {
        $cacheKey = self::CACHE_PREFIX . $locale;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {

            if (!File::exists(base_path(self::FILE_PATH))) {
                return ['provinces' => [], 'cities' => []];
            }

            $jsonContent = File::get(base_path(self::FILE_PATH));
            $data = json_decode($jsonContent, true);

            if (!is_array($data)) {
                return ['provinces' => [], 'cities' => []];
            }

            $allProvinces = [];
            $allCities    = [];

            $provinceNameKey = 'provinsi_' . $locale; // provinsi_id / provinsi_en
            $cityNameKey     = 'nama_' . $locale;     // nama_id / nama_en

            foreach ($data as $province) {
                $provinceIdKey = $province['provinsi_id'] ?? null;

                // 1. Province ID: pakai id numeric dari JSON
                $uniqueProvinceId = (int) $province['id'];

                // 2. Province Name: lokalized
                $provinceDisplayName = $province[$provinceNameKey] ?? $provinceIdKey ?? 'Unknown Province';

                $allProvinces[] = [
                    'id'   => $uniqueProvinceId,
                    'name' => $provinceDisplayName,
                ];

                if (isset($province['kota']) && is_array($province['kota'])) {
                    foreach ($province['kota'] as $city) {
                        $cityIdKey = $city['nama_id'] ?? null;

                        $uniqueCityId   = (int) $city['id']; // id lokal per-provinsi
                        $cityDisplayName = $city[$cityNameKey] ?? $cityIdKey ?? 'Unknown City';

                        $allCities[] = [
                            'id'          => $uniqueCityId,
                            'province_id' => $uniqueProvinceId,
                            'name'        => $cityDisplayName,
                        ];
                    }
                }
            }

            return [
                'provinces' => $allProvinces,
                'cities'    => $allCities,
            ];
        });
    }

    /** List semua provinsi: id + name */
    public static function getProvinces(string $locale): array
    {
        $data = self::getFormattedData($locale);

        return collect($data['provinces'] ?? [])
            ->map(fn ($province) => [
                'id'   => (int) $province['id'],
                'name' => $province['name'],
            ])
            ->toArray();
    }

    /**
     * List kota, optional filter by province_id
     */
    public static function getCities(string $locale, $provinceId = null): array
    {
        $data   = self::getFormattedData($locale);
        $cities = collect($data['cities'] ?? []);

        if ($provinceId) {
            $cities = $cities
                ->where('province_id', (int) $provinceId)
                ->values();
        }

        return $cities
            ->map(fn ($city) => [
                'id'   => (int) $city['id'],
                'name' => $city['name'],
            ])
            ->toArray();
    }

    /**
     * Ambil 1 provinsi by ID.
     */
    public static function getProvinceById(int $provinceId, string $locale = 'en'): ?array
    {
        $data = self::getFormattedData($locale);

        return collect($data['provinces'] ?? [])
            ->first(fn ($province) => (int) $province['id'] === $provinceId);
    }

    public static function getProvinceNameById(int $provinceId, string $locale = 'en'): ?string
    {
        $province = self::getProvinceById($provinceId, $locale);

        return $province['name'] ?? null;
    }

    /**
     * Ambil 1 kota berdasarkan (province_id, city_id).
     * City id TIDAK global unique, jadi wajib pakai pair.
     */
    public static function getCityByProvinceAndId(
        int $provinceId,
        int $cityId,
        string $locale = 'en'
    ): ?array {
        $data = self::getFormattedData($locale);

        return collect($data['cities'] ?? [])
            ->first(
                fn ($city) =>
                    (int) $city['province_id'] === $provinceId &&
                    (int) $city['id'] === $cityId
            );
    }

    public static function getCityNameByProvinceAndId(
        int $provinceId,
        int $cityId,
        string $locale = 'en'
    ): ?string {
        $city = self::getCityByProvinceAndId($provinceId, $cityId, $locale);

        return $city['name'] ?? null;
    }

    public static function flushAll(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'id');
        Cache::forget(self::CACHE_PREFIX . 'en');
    }
}
