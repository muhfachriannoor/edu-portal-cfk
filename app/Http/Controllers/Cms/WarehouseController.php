<?php

namespace App\Http\Controllers\Cms;

use App\Models\Warehouse;
use App\Http\Requests\WarehouseRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Cms\CmsController;
use Illuminate\Http\JsonResponse;

class WarehouseController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'warehouse';

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
        return (new Warehouse)->getDatatables(); 
    }

    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        return view("cms.{$this->resourceName}.index", [
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'Warehouse List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Warehouse $warehouse)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'warehouse' => $warehouse,
            'pageMeta' => [
                'title' => 'Create Warehouse',
                'method' => 'post',
                'url' => route('secretgate19.warehouse.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WarehouseRequest $request)
    {
        DB::beginTransaction();

        try {
            // 1. Cek Validated and Create
            $validateData = $request->validated();
            $data = Warehouse::create($validateData);
            
            DB::commit();

            return to_route('secretgate19.warehouse.index')
                ->with('success', 'Warehouse created successfully.');

        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'warehouse' => $warehouse,
            'pageMeta' => [
                'title' => 'View Warehouse',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'warehouse' => $warehouse,
            'pageMeta' => [
                'title' => 'Edit Warehouse',
                'method' => 'PUT',
                'url' => route('secretgate19.warehouse.update', $warehouse->id)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        DB::beginTransaction();

        try {
            // 1. Cek Validated and Update
            $validateData = $request->validated();
            $warehouse->update($validateData);

            DB::commit();
            
            return to_route('secretgate19.warehouse.index')
                ->with('success', 'Warehouse updated successfully.');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->delete();

        return response()->json(null, 204);
    }

    /**
     * Handle Image.
     */
    private function handleImage($warehouse)
    {
        $fields = ['logo'];
        foreach($fields as $field){
            if(request()->hasFile($field)){
                $file = request()->file($field);

                $warehouse->saveFile(
                    $file,
                    'warehouse',
                    [
                        'field' => $field,
                        'name' => $file->getClientOriginalName()
                    ]
                );
            }
        }        
    }
}