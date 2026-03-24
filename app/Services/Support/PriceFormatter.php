<?php

namespace App\Services\Support;

class PriceFormatter
{
    /**
     * Parse a formatted money string (e.g. "768.000" or "Rp 768.000") into an integer (e.g. 768000).
     */
    public static function parseMoneyStringToInt(string $value): int
    {
        $normalized = preg_replace('/[^\d]/', '', $value);
        return $normalized ? (int) $normalized : 0;
    }

    /**
     * Format a float percentage value into a string like "10%" or "12%" (without decimal).
     */
    public static function formatPercentage(float $value): string
    {
        // Pembulatan ke nilai terdekat tanpa desimal
        $value = round($value);

        return (int)$value . '%';
    }

    /**
     * Format integer amount into label: "10.000".
     * The currency prefix (e.g. "Rp") is handled by the frontend.
     */
    public static function formatMoney(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}

