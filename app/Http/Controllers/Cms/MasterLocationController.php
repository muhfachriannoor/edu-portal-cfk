<?php

namespace App\Http\Controllers\Cms;

use App\Models\MasterLocation;
use App\Http\Requests\MasterLocationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Cms\CmsController;
use Illuminate\Support\Facades\DB;

class MasterLocationController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'master_location';

    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->authorizeResourceWildcard($this->resourceName);
    }

    /**
     * Display a listing of the resource.
     */
    public function datatables()
    {
        return (new MasterLocation)->getDatatables();
    }

    /**
     * Get lists.
     */
    public function getLists()
    {
        $path = public_path('storage/master_address.json');

        if (!file_exists($path)) {
            return [
                'master_data' => [],
            ];
        }

        $json = file_get_contents($path);

        return [
            'master_data' => json_decode($json, true)
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("cms.master_location.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Master Location List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(MasterLocation $masterLocation)
    {
        return view("cms.master_location.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'masterLocation' => $masterLocation,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Create Master Location',
                'method' => 'post',
                'url' => route('secretgate19.master_location.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MasterLocationRequest $request)
    {
        DB::beginTransaction();

        try {
            $validateData = $request->validated();

            $data = MasterLocation::create($validateData);

            // 3. Handle Translations (Name & Description)
            foreach (['en', 'id'] as $locale) {
                $data->translations()->create([
                    'locale' => $locale,
                    'name' => $request->input("point_operational_{$locale}"),
                ]);
            }
            
            DB::commit();

            return to_route('secretgate19.master_location.index')
                ->with('success', 'Master Location created successfully');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MasterLocation $masterLocation)
    {
        return view("cms.master_location.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'masterLocation' => $masterLocation,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'View Master Location',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterLocation $masterLocation)
    {
        // return $this->getLists();
        return view("cms.master_location.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'masterLocation' => $masterLocation,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Edit Master Location',
                'method' => 'put',
                'url' => route('secretgate19.master_location.update', $masterLocation->id)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MasterLocationRequest $request, MasterLocation $masterLocation)
    {
        DB::beginTransaction();

        try {
            $validateData = $request->validated();
            $masterLocation->update($validateData);
           
            foreach (['en', 'id'] as $locale) {
                $masterLocation->translations()->updateOrCreate([
                    'locale' => $locale
                ],[
                    'name' => $request->input("point_operational_{$locale}"),
                ]);
            }

            DB::commit();
            
            return to_route('secretgate19.master_location.index')
                ->with('success', 'Master Location updated successfully');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterLocation $masterLocation)
    {
        $masterLocation->delete();

        return response()->json(null, 204);
    }
}