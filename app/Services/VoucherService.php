<?php

namespace App\Services;

use App\Models\Voucher;
use App\Services\Support\PriceFormatter;

class VoucherService
{
    /**
     * Calculate voucher discount based on the given base amount.
     */
    public function calculateDiscount(Voucher $voucher, int $baseAmount): int
    {
        if ($baseAmount <= 0) return 0;

        if ($voucher->type === 'percentage') {
            $percent = (float) $voucher->amount;
            if ($percent <= 0) return 0;
            $discount = (int) floor($baseAmount * $percent / 100);
            $maxDiscount = (int) ($voucher->max_discount_amount ?? 0);
            if ($maxDiscount > 0 && $discount > $maxDiscount) $discount = $maxDiscount;
            return max(0, $discount);
        }

        $fixedAmount = (int) $voucher->amount;
        if ($fixedAmount <= 0) return 0;
        return min($fixedAmount, $baseAmount);
    }

    /**
     * Build voucher payload for API response when voucher is applied.
     */
    public function buildVoucherPayloadForApply(Voucher $voucher, string $locale, int $voucherDiscountRaw, int $baseAmountRaw): array
    {
        $translation = $voucher->translation($locale);
        $title = optional($translation)->name ?: $voucher->voucher_name;
        $amountDisplay = $voucher->type === 'percentage'
            ? $voucher->amount . '%'
            : PriceFormatter::formatMoney((int) $voucher->amount);

        $minTxnRaw = (int) ($voucher->min_transaction_amount ?? 0);
        $maxDiscountRaw = (int) ($voucher->max_discount_amount ?? 0);

        return [
            'id'   => $voucher->id,
            'code' => $voucher->voucher_code,
            'title' => $title,
            'type'       => $voucher->type,
            'amount'     => $amountDisplay,
            'amount_raw' => (int) $voucher->amount,
            'min_transaction_amount'       => $minTxnRaw ?: null,
            'min_transaction_amount_label' => $minTxnRaw > 0 ? PriceFormatter::formatMoney($minTxnRaw) : null,
            'max_discount_amount'          => $maxDiscountRaw ?: null,
            'max_discount_amount_label'    => $maxDiscountRaw > 0 ? PriceFormatter::formatMoney($maxDiscountRaw) : null,
            'start_date'            => optional($voucher->start_date)->toDateTimeString(),
            'end_date'              => optional($voucher->end_date)->toDateTimeString(),
            'expiration_date_label' => optional($voucher->end_date)->format('d/m/Y'),
            'image_url' => $voucher->image,
            'discount_raw'            => $voucherDiscountRaw,
            'discount'                => PriceFormatter::formatMoney($voucherDiscountRaw),
            'applied_to_amount_raw'   => $baseAmountRaw,
            'applied_to_amount_label' => PriceFormatter::formatMoney($baseAmountRaw),
        ];
    }

    /**
     * DRY helper for validation response.
     */
    private function validationResult(bool $ok, int $discount, ?string $message = null): array
    {
        return ['ok' => $ok, 'discount' => $discount, 'message' => $message];
    }

    /**
     * High-level business validation for voucher against a cart/order amount.
     */
    public function validateForCart(Voucher $voucher, int $baseAmount): array
    {
        $now = now();

        if (! $voucher->is_active)
            return $this->validationResult(false, 0, 'Voucher code is invalid or inactive.');
        if ($voucher->start_date && $voucher->start_date->isFuture())
            return $this->validationResult(false, 0, 'This voucher is not valid yet.');
        if ($voucher->end_date && $voucher->end_date->lt($now))
            return $this->validationResult(false, 0, 'This voucher has expired.');
        if (
            $voucher->usage_limit !== null &&
            (int) $voucher->usage_limit > 0 &&
            (int) $voucher->used_count >= (int) $voucher->usage_limit
        )
            return $this->validationResult(false, 0, 'This voucher is no longer available (usage limit reached).');
        if ($baseAmount <= 0)
            return $this->validationResult(false, 0, 'Your order total is zero. Cannot apply voucher.');

        $minTxn = (int) ($voucher->min_transaction_amount ?? 0);
        if ($minTxn > 0 && $baseAmount < $minTxn)
            return $this->validationResult(
                false,
                0,
                sprintf('Minimum transaction amount to use this voucher is %s.', PriceFormatter::formatMoney($minTxn))
            );

        $discount = $this->calculateDiscount($voucher, $baseAmount);
        if ($discount <= 0)
            return $this->validationResult(false, 0, 'This voucher does not give any discount for the current order.');

        return $this->validationResult(true, $discount);
    }

    /**
     * Same validation as validateForCart(), but locks the row with FOR UPDATE.
     */
    public function lockAndValidateForCheckout(int|string $voucherId, int $baseAmount): array
    {
        $voucher = Voucher::where('id', $voucherId)->lockForUpdate()->first();
        if (! $voucher) {
            return [
                'ok'       => false,
                'discount' => 0,
                'message'  => 'Voucher is no longer available.',
                'voucher'  => null,
            ];
        }
        $result = $this->validateForCart($voucher, $baseAmount);
        $result['voucher'] = $voucher;
        return $result;
    }
}
