<?php

namespace App\Http\Controllers\Cms;

use App\Models\Client;
use App\Models\Privilege;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Requests\ClientRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Cms\CmsController;
use App\Http\Requests\PrivilegeRequest;
use Spatie\Permission\Models\Permission;

class PrivilegeController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'privilege';

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
        return (new Privilege)->getDatatables();
    }

    /**
     * Get lists.
     */
    public function getLists($privilege)
    {
        return [
            'categories' => $privilege->getPermissionCategory(),
            'permissions' => $privilege->getAllPermissionList(),
            'currentPermissions' => $privilege->permissions->pluck('id')->toArray()     
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('cms.privilege.index', [
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'Privilege List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Privilege $privilege)
    {
        return view('cms.privilege.form', [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'privilege' => $privilege,
            'lists' => $this->getLists($privilege),
            'pageMeta' => [
                'title' => 'Create Privilege',
                'method' => 'post',
                'url' => route('secretgate19.privilege.store'),
                'submit_text' => 'Submit',
                'class' => 'create'
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PrivilegeRequest $request)
    {
        $privilege = Privilege::create(['name' => $request->name, 'guard_name' => 'admin']);
        
        $permissions = Permission::whereIn('id', $request->input('permissions'))->pluck('name')->toArray();
        $privilege->syncPermissions($permissions);

        return to_route('secretgate19.privilege.index')
            ->with('success', 'Privilege created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Privilege $privilege)
    {
        return view('cms.privilege.form', [
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
    public function edit(Privilege $privilege)
    {
        return view('cms.privilege.form', [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'privilege' => $privilege,
            'lists' => $this->getLists($privilege),
            'pageMeta' => [
                'title' => 'Edit Privilege',
                'method' => 'put',
                'url' => route('secretgate19.privilege.update', $privilege->id),
                'submit_text' => 'Update',
                'class' => 'edit'
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PrivilegeRequest $request, Privilege $privilege)
    {
        $privilege->update(
            [
                ...$request->only(['name']),
            ]
        );

        $permissions = Permission::whereIn('id', $request->input('permissions'))->pluck('name')->toArray();
        $privilege->syncPermissions($permissions);

        return to_route('secretgate19.privilege.index')
            ->with('success', 'Privilege created successfully');;
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Privilege $privilege)
    {
        $privilege->syncPermissions([]);
        $privilege->delete();

        return response()->json(null, 204);
    }
}