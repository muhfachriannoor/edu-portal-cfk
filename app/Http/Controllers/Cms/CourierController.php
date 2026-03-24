<?php

namespace App\Http\Controllers\Cms;

use App\Models\Courier;
use App\Http\Requests\CourierRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Cms\CmsController;
use Illuminate\Http\JsonResponse;

class CourierController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'courier';

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
        return (new Courier)->getDatatables(); 
    }

    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        return view("cms.{$this->resourceName}.index", [
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'Courier List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Courier $courier)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'courier' => $courier,
            'pageMeta' => [
                'title' => 'Create Courier',
                'method' => 'post',
                'url' => route('secretgate19.courier.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CourierRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            // 1. Create Data
            $data = Courier::create([
                'name'      => $request->input('name_en'),
                'key'       => $request->input('key'),
                'is_active' => $request->boolean('is_active'),
                'is_pickup' => $request->boolean('is_pickup'),
                'fee'       => $request->input('fee')
            ]);

            // 2. Handle Translations
            foreach (['en', 'id'] as $locale) {
                $data->translations()->create([
                    'locale' => $locale,
                    'name' => $request->input("name_{$locale}"),
                    'description' => $request->input("description_{$locale}"),
                ]);
            }
            
            DB::commit();

            return to_route('secretgate19.courier.index')
                ->with('success', 'Courier created successfully.');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Courier $courier)
    {
        $courier->load('translations'); 

        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'courier' => $courier,
            'pageMeta' => [
                'title' => 'Edit Courier',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Courier $courier)
    {
        $courier->load('translations'); 

        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'courier' => $courier,
            'pageMeta' => [
                'title' => 'Edit Courier',
                'method' => 'PUT',
                'url' => route('secretgate19.courier.update', $courier->id)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CourierRequest $request, Courier $courier): RedirectResponse
    {
        DB::beginTransaction();

        try {
            // 1. Update Data
            $courier->update([
                'name'      => $request->input('name_en'),
                'key'       => $request->input('key'),
                'is_active' => $request->boolean('is_active'),
                'is_pickup' => $request->boolean('is_pickup'),
                'fee'       => $request->input('fee')
            ]);

            // 2. Handle Translations (UpdateOrCreate)
            foreach (['en', 'id'] as $locale) {
                $courier->translations()->updateOrCreate([
                    'locale' => $locale
                ],[
                    'name' => $request->input("name_{$locale}"),
                    'description' => $request->input("description_{$locale}"),
                ]);
            }

            DB::commit();
            
            return to_route('secretgate19.courier.index')
                ->with('success', 'Courier updated successfully.');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Courier $courier): JsonResponse
    {
        $courier->translations()->delete();
        $courier->delete();

        return response()->json(null, 204);
    }
}