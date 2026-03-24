<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell;
use Illuminate\Support\Carbon;

class SetTimezoneBinder extends Cell\DefaultValueBinder implements Cell\IValueBinder
{
    /**
     * @param Cell\Cell $cell
     * @param mixed $value
     * @return bool
     */
    public function bindValue(Cell\Cell $cell, $value): bool
    {
        if ($value instanceof Carbon) {
            $cell->setValueExplicit($value->setTimezone(config('app.timezone'))
                ->toDateTimeString(), Cell\DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}