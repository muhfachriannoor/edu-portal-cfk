<?php

namespace App\Http\Controllers\Cms;

use App\Models\Category;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\View\Data\CategoryData;
use App\View\Data\SubCategoryData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CategoryRequest;
use App\Http\Controllers\Cms\CmsController;

class CategoryController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'category';
    
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
        return (new Category)->getDatatables();
    }

    /**
     * Get lists.
     */
    public function getLists()
    {
        return [
            'categories' => CategoryData::lists(),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view("cms.category.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Category List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Category $category): View
    {
        return view("cms.category.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'category' => $category,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Create Category',
                'method' => 'post',
                'url' => route('secretgate19.category.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $request->validated();
            $this->handleImage($request);

            $parentId = $request->input('parent_id') ?: null;
            $isNavbar = $request->input('is_navbar') ?: 0;

            $category = Category::create([
                'name' => $request->input('name_en'),
                'slug' => $request->input('slug'),
                'description' => $request->input('description_en'),
                'order' => $request->input('order'),
                'is_active' => $request->boolean('is_active'),
                'is_navbar' => $isNavbar,
                'image' => $request->input('image'),
                'icon_image' => $request->input('icon_image'),
                'parent_id' => $parentId,
            ]);

            // 3. Handle Translations & SEO Metadata
            foreach (['en', 'id'] as $locale) {
                $category->translations()->create([
                    'locale' => $locale,
                    'name' => $request->input("name_{$locale}"),
                    'description' => $request->input("description_{$locale}"),
                ]);

                $metaTitle = $request->input("meta_title_{$locale}");
                $metaDesc = $request->input("meta_description_{$locale}");
                $metaKey = $request->input("meta_keywords_{$locale}");

                if (!empty($metaTitle) || !empty($metaDesc) || !empty($metaKey)) {
                    $category->seos()->create([
                        'locale' => $locale,
                        'meta_title' => $metaTitle,
                        'meta_description' => $metaDesc,
                        'meta_keywords' => $metaKey,
                    ]);
                }
            }
            
            DB::commit();

            return to_route('secretgate19.category.index')
                ->with('success', 'Category created successfully.');

        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): View
    {
        return view("cms.category.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'category' => $category,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'View Category',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): View
    {
        return view("cms.category.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'category' => $category,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Edit Category',
                'method' => 'put',
                'url' => route('secretgate19.category.update', $category->id)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $request->validated();
            
            // Menggunakan boolean helper untuk konsistensi data tinyint(1)
            $isNavbar = $request->boolean('is_navbar');
            $parentId = $request->input('parent_id') ?: null;

            $dataToUpdate = [
                'name'        => $request->input('name_en'),
                'slug'        => $request->input('slug'),
                'description' => $request->input('description_en'),
                'order'       => $request->input('order'),
                'is_active'   => $request->boolean('is_active'),
                'is_navbar'   => $isNavbar,
                'parent_id'   => $parentId,
            ];
            
            // Handle upload gambar
            $this->handleImage($request, $category);
            
            if ($request->has('image')) { 
                $dataToUpdate['image'] = $request->input('image');
            }

            if ($request->has('icon_image')) { 
                $dataToUpdate['icon_image'] = $request->input('icon_image');
            }
        
            $category->update($dataToUpdate);

            // Handle Translations & SEO Metadata
            foreach (['en', 'id'] as $locale) {
                // Update atau buat translasi Nama & Deskripsi
                $category->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'name'        => $request->input("name_{$locale}"),
                        'description' => $request->input("description_{$locale}"),
                    ]
                );

                $metaTitle = $request->input("meta_title_{$locale}");
                $metaDesc = $request->input("meta_description_{$locale}");
                $metaKey = $request->input("meta_keywords_{$locale}");

                if (!empty($metaTitle) || !empty($metaDesc) || !empty($metaKey)) {
                    $category->seos()->updateOrCreate(
                        ['locale' => $locale],
                        [
                            'meta_title'       => $metaTitle,
                            'meta_description' => $metaDesc,
                            'meta_keywords'    => $metaKey,
                        ]
                    );
                } else {
                    $category->seos()->where('locale', $locale)->delete();
                }
            }

            DB::commit();
            
            return to_route('secretgate19.category.index')
                ->with('success', 'Category updated successfully.');
                
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            return back()->withInput()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->translations()->delete();
        $category->deleteImage();
        $category->delete();

        return response()->json(null, 204);
    }

    /**
     * Handle image function
     */
    protected function handleImage(Request $request, $category = null): void
    {
        $fieldNameImage = 'image';
        $fieldNameIcon = 'icon_image';

        if ($request->hasFile($fieldNameImage)) {
            if ($category) {
                $category->deleteImage();
            }

            $path = $request->file($fieldNameImage)->store('category', 'public');
            $request->merge(['image' => $path]);
        }

        if ($request->hasFile($fieldNameIcon)) {
            if ($category) {
                $category->deleteIconImage();
            }

            $path = $request->file($fieldNameIcon)->store('category/icons', 'public');
            $request->merge(['icon_image' => $path]);
        }
    }
}