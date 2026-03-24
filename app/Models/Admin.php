<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Activity;
use App\Models\Privilege;
use App\Services\BaseModel;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Admin extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use BaseModel, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean'
        ];
    }

    /**
     * @return MorphMany
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'userable');
    }

    /**
     * @return array
     */
    public function getAllRoles(): array
    {
        return Privilege::query()
            ->where('guard_name', 'admin')
            ->get()
            ->pluck('name', 'name')
            ->toArray();
    }

    /**
     * @return array
     */
    public function getAllRolesForMultiList(): array
    {
        return Privilege::query()
            ->where('guard_name', 'admin')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'Superadmin';
    }

    /**
     * @return mixed
     */
    public function getRoleAttribute()
    {
        return $this->getRoleNames()
            ->first();
    }

    /**
     * @return JsonResponse
     */
    public function getDatatables(): JsonResponse
    {
        $query = $this->query();

        return DataTables::eloquent($query)
            ->addColumn('role', function(self $data){
                return $data->role;
            })
            ->editColumn('is_active', function(self $data){
                switch($data->is_active){
                    case 0:
                        return '<span class="inline-block px-1.5 py-0.5 text-xs font-semibold text-white bg-red-500 rounded">Inactive</span>';

                    case 1:
                        return '<span class="inline-block px-1.5 py-0.5 text-xs font-semibold text-white bg-green-500 rounded">Active</span>';
                }
            })
            ->addColumn('actions', function (self $data){
                $key = $data->getPermissionKey();
                $currentAdmin = auth()->user();
                $actionButton = '';

                if ($currentAdmin->can("{$key}.view")) 
                    $actionButton .= "<a href='" . route("secretgate19.admin.show", $data->id) . "' class='inline-flex items-center px-3 py-3 bg-gray-100 text-blue-500 hover:text-blue-700 rounded-md shadow-sm hover:bg-gray-200 transition mr-3' title='View' ><span class='text-primary far fa-eye text-md'></span></a>";;
                    
                if ($currentAdmin->can("{$key}.update")) 
                    $actionButton .= "<a href='" . route("secretgate19.admin.edit", $data->id) . "' class='inline-flex items-center px-3 py-3 bg-gray-100 text-green-500 rounded-md shadow-sm hover:text-green-700 mr-3' title='Edit'><span class='text-success fas fa-edit text-md'></span></a>";
                
                if ($currentAdmin->can("{$key}.delete")) 
                    $actionButton .= "<button type='submit' class='inline-flex items-center px-3 py-3 bg-gray-100 text-red-500 rounded-md shadow-sm hover:text-red-700 delete-item' data-href='" . route("secretgate19.admin.destroy", $data->id) . "' data-page='Employee' title='Delete'> <span class='text-danger far fa-trash-alt text-md'></span> </button>";
                

                return $actionButton ? $actionButton : '#';
            })
            ->rawColumns(['is_activated', 'actions', 'is_active'])
            ->toJson();
    }
}
