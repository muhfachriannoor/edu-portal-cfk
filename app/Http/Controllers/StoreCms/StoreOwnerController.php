<?php

namespace App\Http\Controllers\StoreCms;

use Carbon\Carbon;
use App\Models\Store;
use App\Models\Privilege;
use App\Models\StoreOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\StoreOwnerRequest;
use App\Http\Controllers\Cms\CmsController;

class StoreOwnerController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'store_owner';

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
    public function datatables(Store $store)
    {
        return (new StoreOwner)->getDatatables($store);
    }

    /**
     * Get lists.
     */
    public function getLists($store)
    {
        return [
            'roles' => Privilege::query()
                ->where('store_id', $store->id)
                ->where('guard_name', 'store_owner')
                ->get()
                ->pluck('name', 'name')
                ->toArray()
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        return view("store_cms.store_owner.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists($store),
            'pageMeta' => [
                'title' => 'Store Owner'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store, StoreOwner $storeOwner)
    {
        return view("store_cms.store_owner.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'store_owner' => $storeOwner,
            'lists' => $this->getLists($store),
            'pageMeta' => [
                'title' => 'Create Store Owner',
                'method' => 'post',
                'url' => route('store_cms.store_owner.store', [$store]),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, StoreOwnerRequest $request)
    {
        $storeOwner = StoreOwner::create([
            'store_id' => $store->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->is_active,
        ]);

        $storeOwner->assignRole($request->input('role'));

        return to_route('store_cms.store_owner.index', [$store])
            ->with('success', 'Store Owner created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, StoreOwner $storeOwner)
    {
        return view("store_cms.store_owner.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'store_owner' => $storeOwner,
            'lists' => $this->getLists($store),
            'pageMeta' => [
                'title' => 'View Store Owner',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store, StoreOwner $storeOwner)
    {
        return view("store_cms.store_owner.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'store_owner' => $storeOwner,
            'lists' => $this->getLists($store),
            'pageMeta' => [
                'title' => 'Edit Store Owner',
                'method' => 'put',
                'url' => route('store_cms.store_owner.update', ['store' => $store, 'store_owner' => $storeOwner->id])
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreOwnerRequest $request, Store $store, StoreOwner $storeOwner)
    {
        if ($request->filled('password')) {
            $request->merge(['password' => bcrypt($request->password)]);
        }

        $storeOwner->update(            
            $request->filled('password') ? 
                $request->except(['role']) : 
                $request->except(['role', 'password'])
            
        );
        
        $storeOwner->syncRoles([$request->input('role')]);

        return to_route('secretgate19.store_owner.index')
            ->with('success', 'Store Owner updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, StoreOwner $storeOwner)
    {
        $storeOwner->syncRoles([]);
        $storeOwner->delete();

        return response()->json(null, 204);
    }
}