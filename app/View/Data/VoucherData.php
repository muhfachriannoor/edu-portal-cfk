<?php

namespace App\View\Data;

use App\Models\Voucher;
use Illuminate\Support\Facades\Cache;

class VoucherData
{
    public const API_CACHE_KEY = "voucher_api_list";
    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Retrieve a list of couriers for API, with caching.
     * 
     * Rules:
     * - Only active vouchers.
     * - start_date <= now and end_date >= now.
     * - usage_limit is null/0 (unlimited) OR used_count < usage_limit.
     */
    public static function listsForApi(string $locale = 'en'): array
    {
        $cacheKey = self::API_CACHE_KEY . "_{$locale}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            $now = now();

            $vouchers = Voucher::query()
                ->with(['translations', 'files'])
                ->where('is_active', true)
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->where(function ($q) {
                    $q->whereNull('usage_limit')
                        ->orWhere('usage_limit', 0)
                        ->orWhereColumn('used_count', '<', 'usage_limit');
                })
                ->orderBy('end_date') // soonest to expire first
                ->orderBy('id')
                ->get();

            return $vouchers->map(function (Voucher $voucher) use ($locale) {
                // Localized name
                $translation = $voucher->translation($locale);
                $title = optional($translation)->name ?: $voucher->voucher_name;

                // Display amount (Rp 50.000 or 10%)
                $amountDisplay = $voucher->type === 'percentage'
                    ? $voucher->amount . '%'
                    : number_format($voucher->amount, 0, ',', '.');

                $minTxnRaw = (int) ($voucher->min_transaction_amount ?? 0);
                $maxDiscountRaw = (int) ($voucher->max_discount_amount  ?? 0);

                $minTxnDisplay       = $minTxnRaw > 0
                    ? number_format($minTxnRaw, 0, ',', '.')
                    : null;

                $maxDiscountDisplay  = $maxDiscountRaw > 0
                    ? number_format($maxDiscountRaw, 0, ',', '.')
                    : null;

                return [
                    'id'   => $voucher->id,
                    'code' => $voucher->voucher_code,

                    // Title / headline shown in the card
                    'title' => $title,

                    // Type & amount
                    'type'        => $voucher->type,           // "percentage" | "fixed_amount"
                    'amount'      => $amountDisplay,           // "Rp 50.000" or "10%"
                    'amount_raw'  => (int) $voucher->amount,   // 50000 or 10

                    // Min / max transaction (used later when applying)
                    'min_transaction_amount'         => $minTxnRaw ?: null,
                    'min_transaction_amount_label'   => $minTxnDisplay,
                    'max_discount_amount'            => $maxDiscountRaw ?: null,
                    'max_discount_amount_label'      => $maxDiscountDisplay,

                    // Date info
                    'start_date'            => optional($voucher->start_date)->toDateTimeString(),
                    'end_date'              => optional($voucher->end_date)->toDateTimeString(),
                    'expiration_date_label' => optional($voucher->end_date)->format('d/m/Y'),

                    'image_url' => $voucher->image,
                ];
            })->toArray();
        });
    }

    /**
     * FLush all cached voucher lists for all supported locales.
     */
    public static function flush(): void
    {
        $locales = ['en', 'id'];

        foreach ($locales as $locale) {
            $cacheKey = self::API_CACHE_KEY . "_{$locale}";
            
            Cache::forget($cacheKey);
        }
    }
}