<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AddIdToProvinceCity extends Command
{
    protected $signature = 'json:add-ids-to-province-city';
    protected $description = 'Menambahkan ID ke provinsi dan kota di file JSON';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Path ke file JSON
        $filePath = public_path('assets/indo-region/province_with_city.json');

        if (!File::exists($filePath)) {
            $this->error('File JSON tidak ditemukan!');
            return;
        }

        // Membaca file JSON
        $jsonData = File::get($filePath);
        $data = json_decode($jsonData, true);

        if (!is_array($data)) {
            $this->error('Data JSON tidak valid!');
            return;
        }

        // Menambahkan ID untuk provinsi dan kota
        $provinceId = 1;
        foreach ($data as &$province) {
            $province['id'] = $provinceId++;

            $cityId = 1;
            foreach ($province['kota'] as &$city) {
                $city['id'] = $cityId++;
            }
        }

        // Menyimpan kembali perubahan ke file JSON
        $outputJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($filePath, $outputJson);

        $this->info("File berhasil diubah dan disimpan di: {$filePath}");
    }
}