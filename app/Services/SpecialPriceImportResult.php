<?php

namespace App\Services;

use App\Models\SpecialPrice;
use App\Models\ProductVariant;
use App\Models\Store;
use App\View\Data\ProductVariantData;
use Carbon\Carbon;
use Throwable;

class SpecialPriceImportResult
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $validRows = [];

    /**
     * @var array<int, array{row:int|null, field:string|null, message:string}
     */
    public array $errors = [];
    
    public function addError(?int $row, ?string $field, string $message): void
    {
        $this->errors[] = [
            'row' => $row,
            'field' => $field,
            'message' => $message,
        ];
    }

    public function addValidRow(array $row): void
    {
        $this->validRows[] = $row;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}