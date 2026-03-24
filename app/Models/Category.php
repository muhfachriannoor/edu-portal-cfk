<?php

namespace App\Models;

use App\Services\BaseModel;
use App\Traits\HasSeo;
use App\View\Data\CategoryData;
use App\View\Data\CategoryFormData;
use Illuminate\Http\JsonResponse;
use App\View\Data\SubCategoryData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use BaseModel, HasFactory, HasSeo;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * The attributes that should be cast to native types.
     * 
     * @var array<string, string>
     */
    protected $casts = [

    ];

    public function getDatatables($lang = 'en'): JsonResponse
    {
        // Load translations and admin relation
        $query = $this->query();

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('name', function(self $data) use ($lang) {
                return $data->name;
            })
            ->addColumn('actions', function (self $data){
                $key = $data->getPermissionKey();
                $currentAdmin = auth()->user();
                $actionButton = '';

                if ($currentAdmin->can("{$key}.view")) 
                    $actionButton .= "<a href='" . route("admin.category.show", $data->id) . "' class='inline-flex items-center px-3 py-3 bg-gray-100 text-blue-500 hover:text-blue-700 rounded-md shadow-sm hover:bg-gray-200 transition mr-3' title='View' ><span class='text-primary far fa-eye text-md'></span></a>";;
                    
                if ($currentAdmin->can("{$key}.update")) 
                    $actionButton .= "<a href='" . route("admin.category.edit", $data->id) . "' class='inline-flex items-center px-3 py-3 bg-gray-100 text-green-500 rounded-md shadow-sm hover:text-green-700 mr-3' title='Edit'><span class='text-success fas fa-edit text-md'></span></a>";
                
                if ($currentAdmin->can("{$key}.delete")) 
                    $actionButton .= "<button type='submit' class='inline-flex items-center px-3 py-3 bg-gray-100 text-red-500 rounded-md shadow-sm hover:text-red-700 delete-item' data-href='" . route("admin.category.destroy", $data->id) . "' data-page='Employee' title='Delete'> <span class='text-danger far fa-trash-alt text-md'></span> </button>";

                return $actionButton ? $actionButton : '#';
            })
            ->rawColumns(['description', 'actions', 'is_active'])
            ->toJson();
    }

    /**
     * @return 
     */
    protected static function booted()
    {
        foreach (['created','updated','deleted'] as $event) {
            static::$event(function ($model) {
                CategoryFormData::flush();
            });
        }
    }
}
