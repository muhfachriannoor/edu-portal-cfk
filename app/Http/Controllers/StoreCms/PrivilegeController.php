<?php

namespace App\Http\Controllers\StoreCms;

use App\Models\Store;
use App\Models\Client;
use App\Models\Privilege;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Requests\ClientRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\PrivilegeRequest;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\StoreCms\CmsController;

class PrivilegeController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'store_privilege';

    /**
     * Display a listing of the resource.
     */
    public function datatables()
    {
        return (new Privilege)->getDatatablesStore('store_owner');
    }

    /**
     * Get lists.
     */
    public function getLists($privilege)
    {
        return [
            'categories' => $privilege->getPermissionCategory('store_owner'),
            'permissions' => $privilege->getAllPermissionList('store_owner'),
            'currentPermissions' => $privilege->permissions->pluck('id')->toArray()     
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        return view('store_cms.privilege.index', [
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'Privilege'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store, Privilege $privilege)
    {
        return view('store_cms.privilege.form', [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'privilege' => $privilege,
            'lists' => $this->getLists($privilege),
            'pageMeta' => [
                'title' => 'Create Privilege',
                'method' => 'post',
                'url' => route('store_cms.store_privilege.store', [$store]),
                'submit_text' => 'Submit',
                'class' => 'create'
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, PrivilegeRequest $request)
    {
        $privilege = Privilege::create(['name' => $request->name, 'guard_name' => 'store_owner', 'store_id' => $store->id]);
        
        $permissions = Permission::whereIn('id', $request->input('permissions'))->pluck('name')->toArray();
        $privilege->syncPermissions($permissions);

        return to_route('store_cms.store_privilege.index', $store)
            ->with('success', 'Privilege created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, Privilege $privilege)
    {
        return view('store_cms.privilege.form', [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'privilege' => $privilege,
            'lists' => $this->getLists($privilege),
            'pageMeta' => [
                'title' => 'View Privilege',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store, Privilege $privilege)
    {
        return view('store_cms.privilege.form', [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'privilege' => $privilege,
            'lists' => $this->getLists($privilege),
            'pageMeta' => [
                'title' => 'Edit Privilege',
                'method' => 'put',
                'url' => route('store_cms.store_privilege.update', ['store' => $store, 'privilege' => $privilege->id]),
                'submit_text' => 'Update',
                'class' => 'edit'
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PrivilegeRequest $request, Store $store, Privilege $privilege)
    {
        $privilege->update(
            [
                ...$request->only(['name']),
            ]
        );

        $permissions = Permission::whereIn('id', $request->input('permissions'))->pluck('name')->toArray();
        $privilege->syncPermissions($permissions);

        return to_route('store_cms.store_privilege.index', $store)
            ->with('success', 'Privilege created successfully');;
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, Privilege $privilege)
    {
        $privilege->syncPermissions([]);
        $privilege->delete();

        return response()->json(null, 204);
    }
}