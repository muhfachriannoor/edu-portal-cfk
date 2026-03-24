<?php

namespace App\Services;

use App\Models\SpecialPrice;
use App\Models\ProductVariant;
use App\Models\Store;
use App\View\Data\ProductVariantData;
use Carbon\Carbon;
use Throwable;

class SpecialPriceImportService
{
    /**
     * Prepare import result from raw Excel rows.
     * 
     * @param Store $store
     * @param array<int, array<string, mixed>> $rows
     */
    public function prepareImport(Store $store, array $rows): SpecialPriceImportResult
    {
        $result = new SpecialPriceImportResult();

        // Build label -> variant ID map using the same source as dropdown,
        // But scoped to the current store.
        $labelToVariantId = $this->buildVariantLabelMap($store->id);

        foreach ($rows as $index => $row) {
            // Row 1 is header in the sample template
            if ($index === 1) {
                continue;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $this->processRow($store, $index, $row, $labelToVariantId, $result);
        }

        return $result;
    }

    /**
     * Build mapping from normalized variant label to variant ID.
     * for a specific store. 
     * 
     * @param int $storeId
     * @return array<string, int>
     */
    protected function buildVariantLabelMap(int $storeId): array
    {
        // Use per-store form list so that only variants for this store are included.
        $variantOptions = ProductVariantData::listsForStoreForm($storeId); // [id => label]
        $map = [];

        foreach ($variantOptions as $id => $label) {
            $normalized = mb_strtolower(trim((string) $label));
            $map[$normalized] = (int) $id;
        }

        return $map;
    }

    /**
     * Determine whether the row is completely empty (based on used columns).
     * 
     * @param array<string, mixed> $row
     */
    protected function isEmptyRow(array $row): bool
    {
        $variantLabel = trim((string) ($row['B'] ?? ''));
        $typeRaw = trim((string) ($row['C'] ?? ''));
        $discountRaw = trim((string) ($row['D'] ?? ''));
        $percentRaw = trim((string) ($row['E'] ?? ''));
        $startRaw = trim((string) ($row['F'] ?? ''));
        $endRaw = trim((string) ($row['G'] ?? ''));
        $isActiveRaw = trim((string) ($row['H'] ?? ''));

        return $variantLabel === ''
            && $typeRaw === ''
            && $discountRaw === ''
            && $percentRaw === ''
            && $startRaw === ''
            && $endRaw === ''
            && $isActiveRaw === '';
    }

    /**
     * Process a single row: validate, check overlaps, and collect valid row.
     * 
     * @param Store $store
     * @param int $rowIndex Excel row number (1-based)
     * @param array<string, mixed> $row
     * @param array<string, int> $labelToVariantId
     * @param SpecialPriceImportResult $result
     */
    protected function processRow(Store $store, int $rowIndex, array $row, array $labelToVariantId, SpecialPriceImportResult $result): void {
        // Column mapping based on sample:
        // A: NO (Ignored)
        // B: variant_label (*)
        // C: type (*)
        // D: discount
        // E: percentage
        // F: start_at (*)
        // G: end_at (*)
        // H: is_active (*)

        $variantLabel = trim((string) ($row['B'] ?? ''));
        $typeRaw = trim((string) ($row['C'] ?? ''));
        $discountRaw = trim((string) ($row['D'] ?? ''));
        $percentRaw = trim((string) ($row['E'] ?? ''));
        $startRaw = trim((string) ($row['F'] ?? ''));
        $endRaw = trim((string) ($row['G'] ?? ''));
        $isActiveRaw = trim((string) ($row['H'] ?? ''));

        $hasError = false;

        // --- Variant label -> variant ID ---
        $variantId = null;

        if ($variantLabel === '') {
            $result->addError($rowIndex, 'variant_label', 'Column "variant_label" is required.');
            $hasError = true;
        } else {
            $normalizedLabel = mb_strtolower($variantLabel);

            if (!array_key_exists($normalizedLabel, $labelToVariantId)) {
                $result->addError($rowIndex, 'variant_label', 'Variant not found. Make sure the text matches the dropdown exactly.');
                $hasError = true;
            } else {
                $variantId = $labelToVariantId[$normalizedLabel];

                // Extra safety: ensure the variant belongs to this store
                $variantStoreId = ProductVariant::where('id', $variantId)->value('store_id');

                if ($variantStoreId !== $store->id) {
                    $result->addError($rowIndex, 'variant_label', 'This variant does not belong to the current store.');
                    $hasError = true;
                }
            }
        }

        // --- Type ---
        $type = strtolower($typeRaw);

        if ($type === '') {
            $result->addError($rowIndex, 'type', 'Column "type" is required (discount / percentage).');
            $hasError = true;
        } elseif (!in_array($type, ['discount', 'percentage'], true)) {
            $result->addError($rowIndex, 'type', 'Column "type" must be either "discount" or "percentage".');
            $hasError = true;
        }

        // --- Discount / Percentage ---
        $discount = 0;
        $percentage = 0.0;

        if ($type === 'discount') {
            if ($discountRaw === '') {
                $result->addError($rowIndex, 'discount', 'Column "discount" is required for type "discount".');
                $hasError = true;
            } else {
                $discountNumeric = (int) preg_replace('/[^\d]/', '', $discountRaw);
                
                if ($discountNumeric <= 0) {
                    $result->addError($rowIndex, 'discount', 'Column "discount" must be greater than 0.');
                    $hasError = true;
                } else {
                    $discount = $discountNumeric;
                }
            }
        } elseif ($type === 'percentage') {
            if ($percentRaw === '') {
                $result->addError($rowIndex, 'percentage', 'Column "percentage" is required for type "percentage".');
                $hasError = true;
            } else {
                $percentNumeric = (float) str_replace(',', '.', $percentRaw);

                if ($percentNumeric <= 0 || $percentNumeric > 100) {
                    $result->addError($rowIndex, 'percentage', 'Column "percentage" must be between 0 and 100.');
                    $hasError = true;
                } else {
                    $percentage = $percentNumeric;
                }
            }
        }

        // --- Start At & End At ---
        $startAt = null;
        $endAt = null;

        if ($startRaw === '') {
            $result->addError($rowIndex, 'start_at', 'Column "start_at" is required and must have format YYYY-MM-DD.');
            $hasError = true;
        } else {
            try {
                $startAt = Carbon::createFromFormat('Y-m-d', $startRaw)->startOfDay();
            } catch (Throwable $e) {
                $result->addError($rowIndex, 'start_at', 'Column "start_at" must have format YYYY-MM-DD.');
                $hasError = true;
            }
        }

        if ($endRaw === '') {
            $result->addError($rowIndex, 'end_at', 'Column "end_at" is required and must have format YYYY-MM-DD.');
            $hasError = true;
        } else {
            try {
                $endAt = Carbon::createFromFormat('Y-m-d', $endRaw)->endOfDay();
            } catch (Throwable $e) {
                $result->addError($rowIndex, 'end_at', 'Column "end_at" must have format YYYY-MM-DD.');
                $hasError = true;
            }
        }

        if ($startAt && $endAt && $endAt->lt($startAt)) {
            $result->addError($rowIndex, 'end_at', '"end_at" must not be earlier than "start_at".');
            $hasError = true;
        }

        // --- Is Active ---
        $isActive = null;

        if ($isActiveRaw === '') {
            $result->addError($rowIndex, 'is_active', 'Column "is_active" is required (yes / no).');
            $hasError = true;
        } else {
            $flag = strtolower($isActiveRaw);
            
            if (in_array($flag, ['yes', 'y', '1', 'true'], true)) {
                $isActive = 1;
            } elseif (in_array($flag, ['no', 'n', '0', 'false'], true)) {
                $isActive = 0;
            } else {
                $result->addError($rowIndex, 'is_active', 'Column "is_active" must be either "yes" or "no".');
                $hasError = true;
            }
        }

        // If already has errors at this point, do not continue with overlap checks.
        if ($hasError || $variantId === null || $startAt === null || $endAt === null) {
            return;
        }

        $startDateString = $startAt->toDateString();
        $endDateString = $endAt->toDateString();

        // --- Check overlap with other valid rows in the same file ---
        foreach ($result->validRows as $existing) {
            if ($existing['product_variant_id'] === $variantId && $existing['start_at'] <= $endDateString && $existing['end_at'] >= $startDateString) {
                $result->addError($rowIndex, 'variant_label', 'This variant has another row in the file with an overlapping date range.');

                // Do not add this row to valid rows
                return;
            }
        }

        // --- Check overlap with existing data in the database ---
        $hasExisting = SpecialPrice::query()
            ->where('product_variant_id', $variantId)
            ->whereHas('variant', function ($query) use ($store) {
                $query->where('store_id', $store->id);
            })
            ->whereDate('start_at', '<=', $endDateString)
            ->whereDate('end_at', '>=', $startDateString)
            ->exists();
        
        if ($hasExisting) {
            $result->addError($rowIndex, 'variant_label', 'There is already a special price in the database for this variant with an overlapping date range.');

            return;
        }

        // --- Collect valid row for bulk insert ---
        $result->addValidRow([
            'product_variant_id' => $variantId,
            'type' => $type,
            'discount' => $discount,
            'percentage' => $percentage,
            'start_at' => $startDateString,
            'end_at' => $endDateString,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}