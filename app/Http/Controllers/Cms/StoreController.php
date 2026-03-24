<?php

namespace App\Http\Controllers\Cms;

use Carbon\Carbon;
use App\Models\Store;
use Illuminate\Http\Request;
use App\View\Data\WarehouseData;
use App\Events\NotificationEvent;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreRequest;
use Illuminate\Support\Facades\Hash;
use App\Services\NotificationService;
use App\View\Data\MasterLocationData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Notifications\StoreNotification;
use App\Http\Controllers\Cms\CmsController;

class StoreController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'store';

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
        return (new Store)->getDatatables();
    }

    /**
     * Get lists.
     */
    public function getLists()
    {
        return [
            'locations' => MasterLocationData::lists(),
            'warehouses' => WarehouseData::lists(),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("cms.store.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Store List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        return view("cms.store.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'store' => $store,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Create Store',
                'method' => 'post',
                'url' => route('secretgate19.store.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        DB::beginTransaction();

        try{
            $store = Store::create([
                'slug' => $request->input('slug'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'location_id' => $request->input('location_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'verified_at' => $request->input('is_verified') ? Carbon::now()->format('Y-m-d H:i:s') : null,
                'is_active' => $request->input('is_active'),
                'is_delivery' => $request->input('is_delivery'),
                'is_pickup' => $request->input('is_pickup'),
            ]);

            $this->handleImage($store);

            foreach (['en', 'id'] as $locale) {
                $store->translations()->create([
                    'locale' => $locale,
                    'name' => $request->input("name_{$locale}"),
                    'description' => $request->input("description_{$locale}"),
                ]);
            }
            
            DB::commit();
            return to_route('secretgate19.store.index');

        } catch(\Exception $e){
            DB::rollback();
            report($e); // logs to laravel.log
            throw $e;   // keep stack trace
        }

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        return view("cms.store.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'store' => $store,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Edit Store',
                'method' => 'PUT',
                'url' => route('secretgate19.store.update', $store->slug)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, Store $store)
    {
        DB::beginTransaction();

        try{            
            $store->update([
                'slug' => $request->input('slug'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'location_id' => $request->input('location_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'verified_at' => $request->input('is_verified') ? Carbon::now()->format('Y-m-d H:i:s') : null,
                'is_active' => $request->input('is_active'),
                'is_delivery' => $request->input('is_delivery'),
                'is_pickup' => $request->input('is_pickup'),
            ]);
            
            $this->handleImage($store);

            foreach (['en', 'id'] as $locale) {
                $store->translations()->updateOrCreate([
                    'locale' => $locale
                ],[
                    'name' => $request->input("name_{$locale}"),
                    'description' => $request->input("description_{$locale}"),
                ]);
            }

            DB::commit();
            return to_route('secretgate19.store.index')
                ->with('success', 'Store updated successfully');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        $store->delete();

        return response()->json(null, 204);
    }

    /**
     * Handle Image.
     */
    private function handleImage($store)
    {
        $fields = ['logo']; // Masukkan nama field untuk handle image di storage
        foreach($fields as $field){
            if(request()->hasFile($field)){
                $file = request()->file($field);

                $store->saveFile(
                    $file,
                    'store',
                    [
                        'field' => $field,
                        'name' => $file->getClientOriginalName()
                    ]
                );
            }
        }        
    }
}