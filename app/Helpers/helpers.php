<?php

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if (! function_exists('getExcelColumns')) {
    function getExcelColumns(int $max = 52): array
    {
        $letters = [];
        for ($i = 0; $i < $max; $i++) {
            $letters[] = Coordinate::stringFromColumnIndex($i + 1);
        }
        return $letters;
    }
}

if (! function_exists('generateSlug')) {
    function generateSlug(array $strings)
    {
        $string = implode(" ", $strings);
        return Str::slug($string, '-');
    }
}

if (! function_exists('calculatePercentage')) {
    function calculatePercentage($original, $new)
    {
        $original = (int) str_replace('.', '', $original);
        $new = (int) str_replace('.', '', $new);

        if($original > 0) 
            return round((($original - $new) / $original) * 100);
    }
}