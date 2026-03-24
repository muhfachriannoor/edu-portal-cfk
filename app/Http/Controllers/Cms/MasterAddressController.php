<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;
use App\Models\MasterAddress;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\Cms\CmsController;

class MasterAddressController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'master_address';

    /**
     * Constructor: Authorize resource wildcard.
     */
    public function __construct()
    {
        $this->authorizeResourceWildcard($this->resourceName);
    }

    /**
     * Display a listing of the resource for Datatables.
     */
    public function datatables()
    {
        return (new MasterAddress)->getDatatables();
    }

    public function import(Request $request)
    {
        // 1️⃣ Validate file
        $request->validate([
            'file' => 'required|file|mimes:csv|max:20480', // max 20MB
        ]);

        if (!$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $filePath = $file->getPathname();

        // 2️⃣ Open CSV
        if (($handle = fopen($filePath, 'r')) === false) {
            return response()->json(['success' => false, 'message' => 'Cannot open file'], 500);
        }

        // 3️⃣ Read header
        $header = fgetcsv($handle);
        if (!$header) {
            return response()->json(['success' => false, 'message' => 'Empty CSV file'], 400);
        }

        $batchSize = 1000; // insert 1000 rows at a time
        $batch = [];
        $rowCount = 0;
        $now = now();

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;

                $batch[] = [
                    'id'   => $row[0] ?? null,
                    'subdistrict_id'   => $row[1] ?? null,
                    'subdistrict_name' => $row[2] ?? null,
                    'district_id'      => $row[4] ?? null,
                    'district_name'    => $row[5] ?? null,
                    'city_id'          => $row[7] ?? null,
                    'city_name'        => $row[8] ?? null,
                    'province_id'      => $row[9] ?? null,
                    'province_name'    => $row[10] ?? null,
                    'country_id'       => $row[11] ?? null,
                    'country_name'     => $row[12] ?? null,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];

                // Insert batch
                if (count($batch) === $batchSize) {
                    MasterAddress::upsert(
                        $batch,
                        ['id'],
                        [
                            'subdistrict_id', 'subdistrict_name',
                            'district_id', 'district_name',
                            'city_id', 'city_name',
                            'province_id', 'province_name',
                            'country_id', 'country_name',
                            'updated_at'
                        ]
                    );
                    $batch = []; // reset batch
                }
            }

            // Insert remaining
            if (!empty($batch)) {
                MasterAddress::upsert(
                    $batch,
                    ['id'],
                    [
                        'subdistrict_id', 'subdistrict_name',
                        'district_id', 'district_name',
                        'city_id', 'city_name',
                        'province_id', 'province_name',
                        'country_id', 'country_name',
                        'updated_at'
                    ]
                );
            }

            DB::commit();
            fclose($handle);

            return response()->json([
                'success' => true,
                'message' => "Import completed successfully. Total rows imported: {$rowCount}"
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        return view("cms.{$this->resourceName}.index", [
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'Master Address List'
            ]
        ]);
    }
}