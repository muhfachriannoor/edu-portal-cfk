<?php

namespace App\Models;

use App\Services\BaseModel;
use App\Traits\HasSeo;
use App\View\Data\CategoryData;
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
        'description',
        'order',
        'is_active',
        'image',
        'parent_id',
        'is_navbar',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'icon_image',
    ];

    /**
     * The attributes that should be cast to native types.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_navbar' => 'boolean',
    ];

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations->firstWhere('locale', $locale);
    }

    public function getNameAttribute()
    {
        return optional($this->translation())->name ?? '';
    }

    public function getDescriptionAttribute()
    {
        return optional($this->translation())->description ?? '';
    }

    public function getCategoryDisplayNameAttribute()
    {
        $names = [];
        $category = $this;

        while ($category) {
            $names[] = $category->name;
            $category = $category->parent;
        }

        return implode(' > ', array_reverse($names));

        // // If this is a child category, show parent name first
        // if ($this->parent_id) {
        //     $parent = $this->parent()->first();
            
        //     return $parent->name . ' > ' . $this->name;
        // }

        // // If it's a parent category, show its own name
        // return $this->name;
    }

    /**
     * Get the sub-categories for the category.
     * 
     * @return HasMany
     */
    public function subCategories(): HasMany
    {
        // One Category has many SubCategories
        return $this->hasMany(SubCategory::class, 'category_id');
    }

    /**
     * Get the parent category of this category (if applicable).
     * using column parent_id in categories
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the children category of this category (if applicable).
     * using column parent_id in categories
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Recursive get children descendant
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Recursive get children descendant
     */
    public function getAllDescendantsAttribute()
    {
        $result = collect();

        $walk = function ($categories) use (&$walk, &$result) {
            foreach ($categories as $category) {
                $result->push($category);
                $walk($category->allChildren);
            }
        };

        $walk($this->allChildren);

        return $result;
    }

    /**
     * Get the users who are interested in this category (which acts as a sub-category).
     * 
     * @return BelongsToMany
     */
    public function interestedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_interests', 'category_id', 'user_id')->whereNotNull('categories.parent_id');
    }

    /**
     * Delete Image in public
     */
    public function deleteImage(): void
    {
        if ($path = $this->getAttributeValue('image')) {
            Storage::delete($path);
        }
    }

    /**
     * Delete Icon Image in public
     */
    public function deleteIconImage(): void
    {
        if ($path = $this->getAttributeValue('icon_image')) {
            Storage::delete($path);
        }
    }

    public function getDatatables($lang = 'en'): JsonResponse
    {
        // Load translations and admin relation
        $query = $this->query()->with(['translations', 'parent']);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('name', function(self $data) use ($lang) {
                return $data->category_display_name;
            })
            ->editColumn('description', function (self $data) use ($lang) {
                return $data->description ? substr($data->description, 0, 10). '...' : '';
                return $data->translation($lang)->description ? substr($data->translation($lang)->descriptionn, 0, 10). '...' : '';
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
                    $actionButton .= "<a href='" . route("secretgate19.category.show", $data->id) . "' class='inline-flex items-center px-3 py-3 bg-gray-100 text-blue-500 hover:text-blue-700 rounded-md shadow-sm hover:bg-gray-200 transition mr-3' title='View' ><span class='text-primary far fa-eye text-md'></span></a>";;
                    
                if ($currentAdmin->can("{$key}.update")) 
                    $actionButton .= "<a href='" . route("secretgate19.category.edit", $data->id) . "' class='inline-flex items-center px-3 py-3 bg-gray-100 text-green-500 rounded-md shadow-sm hover:text-green-700 mr-3' title='Edit'><span class='text-success fas fa-edit text-md'></span></a>";
                
                if ($currentAdmin->can("{$key}.delete")) 
                    $actionButton .= "<button type='submit' class='inline-flex items-center px-3 py-3 bg-gray-100 text-red-500 rounded-md shadow-sm hover:text-red-700 delete-item' data-href='" . route("secretgate19.category.destroy", $data->id) . "' data-page='Employee' title='Delete'> <span class='text-danger far fa-trash-alt text-md'></span> </button>";

                return $actionButton ? $actionButton : '#';
            })
            ->rawColumns(['description', 'actions', 'is_active'])
            ->toJson();
    }

    /**
     * @return 
     */
    // protected static function booted()
    // {
    //     foreach (['created','updated','deleted'] as $event) {
    //         static::$event(function ($model) {
    //             CategoryData::flush();
    //             SubCategoryData::flush();
    //         });
    //     }
    // }
}
