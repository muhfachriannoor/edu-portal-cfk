<?php

namespace App\Services;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;


trait HasExportExcel
{
    /**
     * @param array $customAttributes
     * @param callable|null $builder
     * @param callable|null $callback
     * @param bool $custom
     * @return void
     */
    public function exportToExcel(array $customAttributes = [], callable $builder = null, callable $callback = null, $custom = false): void
    {
        $className = $this->customExportFilename() ?? Str::plural(Str::afterLast(static::class, '\\'));
        $filename = $className .'.xlsx';
        $collections = $builder
            ? ($custom ? $builder($this->query()) : $builder($this->query())->get())
            : $this->query()->get();

        $attributes = empty($customAttributes)
            ? $this->attributes
            : $customAttributes;

        Cell\Cell::setValueBinder(new SetTimezoneBinder);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $writer = new Xlsx($spreadsheet);

        $alpha = 'A';
        foreach ($attributes as $key => $value) {
            $sheet->setCellValue($alpha++ .'1', $value);
        }

        $alpha = 'A';
        $rowNo = 2;
        foreach ($collections as $index => $collection) {
            foreach ($attributes as $key => $value) {
                if ($callback) {
                    $val = $callback($collection, $key, $value, $index);
                } else {
                    if ($key === 'created_at') {
                        $val = $collection->$key->toDatetimeString();
                    } else {
                        $val = $collection->$key;
                    }
                }

                $sheet->setCellValue($alpha++ . $rowNo, $val);
            }
            $alpha = 'A';
            $rowNo++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
        $writer->save('php://output');
    }

    /**
     * @param array $customAttributes
     * @param callable|null $builder
     * @param callable|null $callback
     */
    public function exportToExcelCustom(array $customAttributes = [], callable $builder = null, callable $callback = null, $custom_filename = null): void
    {
        $className = ($custom_filename) ? $custom_filename : $this->customExportFilename() ?? Str::plural(Str::afterLast(static::class, '\\'));
        $className = str_replace(' ', '_', $className);
        $filename = $className .'.xlsx';
        $collections = $builder
            ? $builder($this->query())->get()
            : $this->query()->get();

        $attributes = empty($customAttributes)
            ? $this->attributes
            : $customAttributes;

        Cell\Cell::setValueBinder(new SetTimezoneBinder);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $writer = new Xlsx($spreadsheet);

        $callback($sheet, $attributes, $collections);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
        $writer->save('php://output');
    }

    /**
     * @param array $customAttributes
     * @param callable|null $builder
     * @param callable|null $callback
     */
    public function exportToExcelCollectionCustom(array $customAttributes = [], $collections = null, callable $callback = null): void
    {
        $className = $this->customExportFilename() ?? Str::plural(Str::afterLast(static::class, '\\'));
        $filename = $className .'.xlsx';
        // $collections = $builder
        //     ? $builder($this->query())->get()
        //     : $this->query()->get();

        $attributes = empty($customAttributes)
            ? $this->attributes
            : $customAttributes;

        Cell\Cell::setValueBinder(new SetTimezoneBinder);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $writer = new Xlsx($spreadsheet);

        $callback($sheet, $attributes, $collections);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
        $writer->save('php://output');
    }

    /**
     * @return mixed
     */
    public function customExportFilename()
    {
        return null;
    }

    /**
     * @param array $customAttributes
     * @param callable|null $builder
     * @param callable|null $callback
     * @param bool $custom
     * @return void
     */
    public function exportToExcelHasImage(array $customAttributes = [], callable $builder = null, callable $callback = null, $custom = false): void
    {
        $className = $this->customExportFilename() ?? Str::plural(Str::afterLast(static::class, '\\'));
        $filename = $className .'.xlsx';
        $collections = $builder
            ? ($custom ? $builder($this->query()) : $builder($this->query())->get())
            : $this->query()->get();

        $attributes = empty($customAttributes)
            ? $this->attributes
            : $customAttributes;

        Cell\Cell::setValueBinder(new SetTimezoneBinder);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $writer = new Xlsx($spreadsheet);

        $alpha = 'A';
        foreach ($attributes as $key => $value) {
            $sheet->setCellValue($alpha++ .'1', $value);
        }

        $alpha = 'A';
        $rowNo = 2;

        $ext = array('jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG');
        $impExts = implode('|', $ext);

        $pngExt = array('png', 'PNG');
        $impPngExts = implode('|', $pngExt);

        foreach ($collections as $index => $collection) {
            foreach ($attributes as $key => $value) {
                if ($callback) {
                    $val = $callback($collection, $key, $value, $index);
                } else {
                    if ($key === 'created_at') {
                        $val = $collection->$key->toDatetimeString();
                    } else {
                        $val = $collection->$key;
                    }
                }

                if(preg_match('/^.*\.('.$impExts.')$/i', $val)){
                    $imgType = (preg_match('/^.*\.('.$impPngExts.')$/i', $val)) ? 'png' : 'jpg';
                    $imgUrl = ($imgType == 'png') ? $val : env('APP_URL') . 'storage/' . $val;
                    
                    $drawing = new MemoryDrawing();
                    $sheet->getRowDimension($rowNo)->setRowHeight(80);
                    $sheet->getColumnDimension($alpha)->setWidth(80);
                
                    if($this->checkImgExist($imgUrl)){
                        $gdImage = ($imgType == 'png') ? imagecreatefrompng($imgUrl) : imagecreatefromjpeg($imgUrl);
                        imagealphablending( $gdImage, false );
                        imagesavealpha( $gdImage, true );
    
                        $drawing->setName("Img_" . $alpha . $rowNo);
                        $drawing->setResizeProportional(false);
                        $drawing->setImageResource($gdImage);
                        // $drawing->setRenderingFunction(\PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::RENDERING_JPEG);
                        // $drawing->setMimeType(\PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::MIMETYPE_DEFAULT);
                        $drawing->setWidth(70);
                        $drawing->setHeight(70);
                        $drawing->setOffsetX(5);
                        $drawing->setOffsetY(5);
                        $drawing->setCoordinates($alpha++ . $rowNo);
                        $drawing->setWorksheet($spreadsheet->getActiveSheet());
                    } else {
                        $sheet->setCellValue($alpha++ . $rowNo, "");
                    }

                    // $val = $imgType;
                } else {
                    $sheet->setCellValue($alpha++ . $rowNo, $val);
                }
                
                foreach(range('A','Z') as $columnID) {
                    $spreadsheet->getActiveSheet()->getColumnDimension($columnID)
                        ->setAutoSize(true);
                }
            }
            $alpha = 'A';
            $rowNo++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
        $writer->save('php://output');
    }

    public function exportToExcelChunk(array $customAttributes = [], callable $builder = null, callable $callback = null, $custom_filename = null): void
    {
        $className = ($custom_filename) ? $custom_filename : $this->customExportFilename() ?? Str::plural(Str::afterLast(static::class, '\\'));
        $className = str_replace(' ', '_', $className);
        $filename = $className .'.xlsx';

        $attributes = empty($customAttributes) ? $this->attributes : $customAttributes;

        Cell\Cell::setValueBinder(new SetTimezoneBinder);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $writer = new Xlsx($spreadsheet);

        // Write headers
        $colIndex = 1;
        $rowIndex = 1; // Data starts from row 1
        foreach ($attributes as $key => $value) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($colLetter . $rowIndex, $value);
            $colIndex++;
        }

        $rowIndex = 2; // Data starts from row 2

        // Chunk processing
        $query = $builder ? $builder($this->query()) : $this->query();

        $query->chunk(1000, function ($collections) use (&$rowIndex, $sheet, $attributes, $callback) {
            foreach ($collections as $collection) {
                $colIndex = 1;
                foreach ($attributes as $key => $value) {
                    $value = $callback($collection, $key);
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $cellCoordinate = $colLetter . $rowIndex;
                    $sheet->setCellValue($cellCoordinate, $value);

                    $colIndex++;
                }
                $rowIndex++;
            }
        });

        // Output to browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
        $writer->save('php://output');
    }

    public function exportToExcelChunkMutate(array $customAttributes = [], array $mutateAttributes = [], callable $builder = null, callable $callback = null, $custom_filename = null): void
    {
        $className = ($custom_filename) ? $custom_filename : $this->customExportFilename() ?? Str::plural(Str::afterLast(static::class, '\\'));
        $className = str_replace(' ', '_', $className);
        $filename = $className .'.xlsx';

        $attributes = empty($customAttributes) ? $this->attributes : $customAttributes;

        Cell\Cell::setValueBinder(new SetTimezoneBinder);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $writer = new Xlsx($spreadsheet);

        // Write headers
        $colIndex = 1;
        $rowIndex = 1; // Data starts from row 1
        foreach ($attributes as $key => $value) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($colLetter . $rowIndex, $value);
            $colIndex++;
        }

        $rowIndex = 2; // Data starts from row 2

        // Chunk processing
        $query = $builder ? $builder($this->query()) : $this->query();

        $query->chunk(1000, function ($collections) use (&$rowIndex, $sheet, $attributes, $callback, $mutateAttributes) {
            foreach ($collections as $collection) {
                $colIndex = 1;
                foreach ($attributes as $key => $value) {
                    $value = $callback($collection, $key);
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $cellCoordinate = $colLetter . $rowIndex;

                    // Set RFID as Text
                    $type = $mutateAttributes[$key] ?? 'default';
                    switch ($type) {
                        case 'text':
                            $sheet->setCellValueExplicit($cellCoordinate, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                            break;

                        case 'datetime':
                            $sheet->setCellValue($cellCoordinate, $value);
                            $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm:ss');
                            break;

                        default:
                            $sheet->setCellValue($cellCoordinate, $value);
                            break;
                    }

                    $colIndex++;
                }
                $rowIndex++;
            }
        });

        // Output to browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($filename).'"');
        $writer->save('php://output');
    }


    private function checkImgExist($url){
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if($httpCode == 404) {
            /* Handle 404 here. */
            return false;
        }

        curl_close($handle);
        return true;

        /* Handle $response here. */
    }
}