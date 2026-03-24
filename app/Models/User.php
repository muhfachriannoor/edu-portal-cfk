<?php

namespace App\Models;

use App\Services\HasFile;
use App\Services\BaseModel;
use App\Services\HasExportExcel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notifiable;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFile, BaseModel, HasFactory, Notifiable, HasExportExcel, HasRoles;

    protected $guard_name = 'admin';

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'Superadmin';
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * @return string
     */
    public function encodeIdentifier(): string
    {
        return base64_encode("{$this->getKey()}:{$this->getAttributeValue('email')}");
    }

    /**
     * Get the sub-categories (interest) for the user.
     * 
     * @return BelongsToMany
     */
    public function getProfileImageAttribute()
    {
        return optional($this->file)->url ?? null;
    }

    /**
     * Get the sub-categories (interest) for the user.
     * 
     * @return BelongsToMany
     */
    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'user_interests', 'user_id', 'category_id')->whereNotNull('categories.parent_id');
    }

    /**
     * Get all addresses for the user (One user has Many addresses).
     * 
     * @return \Illuminate\Database\ELoquent\Relations\HasMany
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Get all addresses for the user (One user has Many addresses).
     * 
     * @return \Illuminate\Database\ELoquent\Relations\HasMany
     */
    public function getDefaultAddressAttribute()
    {
        $defaultAddress = $this->addresses()
            ->where('is_default', 1)
            ->first();

        if (!$defaultAddress) {
            $defaultAddress = $this->addresses()->first();
        }

        if (!$defaultAddress) {
            return null;
        }

        return $defaultAddress->only([
            'phone_number',
            'label',
            'address_line',
            'city',
            'district',
            'province',
            'postal_code',
        ]);
    }

    /**
     * Get all permissions associated with the user.
     * This is a placeholder method required by AdminComposer.
     * Since the 'users' table is not intended for permission management, 
     * this method returns an empty collection to prevent errors.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions(): Collection
    {
        return new Collection();
    }

    /**
     * @return JsonResponse
     */
    public function getDatatables(): JsonResponse
    {
        $query = $this->query();

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('mobile_number', function (self $data) {
                return $data->mobile_number ? $data->mobile_number : '-';
            })
            ->editColumn('created_at', function (self $data) {
                return $data->created_at->format('Y-m-d H:i');
            })
            ->editColumn('onboarding_completed', function (self $data) {
                switch($data->onboarding_completed){
                    case 0:
                        return '<span class="inline-block px-1.5 py-0.5 text-xs font-semibold text-white bg-red-500 rounded">Incomplete</span>';

                    case 1:
                        return '<span class="inline-block px-1.5 py-0.5 text-xs font-semibold text-white bg-green-500 rounded">Complete</span>';
                }
            })
            ->editColumn('is_active', function (self $data) {
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
                    $actionButton .= "<a href='" . route("secretgate19.customer.show", $data->id) . "' class='inline-flex items-center px-3 py-3 bg-gray-100 text-blue-500 hover:text-blue-700 rounded-md shadow-sm hover:bg-gray-200 transition mr-3' title='View' ><span class='text-primary far fa-eye text-md'></span></a>";;

                return $actionButton ? $actionButton : '#';
            })
            ->rawColumns(['actions', 'is_active', 'onboarding_completed'])
            ->toJson();
    }
}