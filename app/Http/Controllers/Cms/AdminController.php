<?php

namespace App\Http\Controllers\Cms;

use Carbon\Carbon;
use App\Models\Admin;
use App\Http\Requests\AdminRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Cms\CmsController;

class AdminController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'admin';

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
        return (new Admin)->getDatatables();
    }

    /**
     * Get lists.
     */
    public function getLists()
    {
        return [
            'roles' => auth()->user()->getAllRoles()
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("cms.admin.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Admin List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Admin $admin)
    {
        return view("cms.admin.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'admin' => $admin,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Create Admin',
                'method' => 'post',
                'url' => route('secretgate19.admin.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminRequest $request)
    {
        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->is_active,
        ]);

        $admin->assignRole($request->input('role'));

        return to_route('admin.admin.index')
            ->with('success', 'Admin created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        return view("cms.admin.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'admin' => $admin,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'View Admin',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin)
    {
        return view("cms.admin.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'admin' => $admin,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Edit Admin',
                'method' => 'put',
                'url' => route('admin.admin.update', $admin->id)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminRequest $request, Admin $admin)
    {
        if ($request->filled('password')) {
            $request->merge(['password' => bcrypt($request->password)]);
        }

        $admin->update(            
            $request->filled('password') ? 
                $request->except(['role']) : 
                $request->except(['role', 'password'])
            
        );
        
        $admin->syncRoles([$request->input('role')]);

        return to_route('secretgate19.admin.index')
            ->with('success', 'Admin updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        $admin->syncRoles([]);
        $admin->delete();

        return response()->json(null, 204);
    }
}