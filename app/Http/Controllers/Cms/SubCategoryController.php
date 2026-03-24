<?php

namespace App\Http\Controllers\Cms;

use App\Models\SubCategory;
use App\View\Data\SubCategoryData;
use App\Http\Requests\SubCategoryRequest;
use App\View\Data\CategoryData;
use Illuminate\Http\Request;
use App\Http\Controllers\Cms\CmsController;
use Illuminate\Support\Facades\DB;

class SubCategoryController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'sub_category';

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
        return (new SubCategory)->getDatatables();
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
    public function index()
    {
        return view("cms.sub_category.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Sub Category List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(SubCategory $subCategory)
    {
        return view("cms.sub_category.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'subCategory' => $subCategory,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Create Sub Category',
                'method' => 'post',
                'url' => route('secretgate19.sub_category.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SubCategoryRequest $request)
    {
        DB::beginTransaction();

        try {
            $request->validated();
            $this->handleImage($request);

            $data = SubCategory::create([
                'category_id' => $request->input('category_id'),
                'name' => $request->input('name_en'),
                'slug' => $request->input('slug'),
                'description' => $request->input('description_en'),
                'order' => $request->input('order'),
                'is_active' => $request->boolean('is_active'),
                'image' => $request->input('image'),
            ]);

            // 3. Handle Translations (Name & Description)
            foreach (['en', 'id'] as $locale) {
                $data->translations()->create([
                    'locale' => $locale,
                    'name' => $request->input("name_{$locale}"),
                    'description' => $request->input("description_{$locale}"),
                ]);
            }
            
            DB::commit();

            return to_route('secretgate19.sub_category.index')
                ->with('success', 'Sub Category created successfully');

        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SubCategory $subCategory)
    {
        return view("cms.sub_category.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'subCategory' => $subCategory,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'View Sub Category',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubCategory $subCategory)
    {
        return view("cms.sub_category.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'subCategory' => $subCategory,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Edit Sub Category',
                'method' => 'put',
                'url' => route('secretgate19.sub_category.update', $subCategory->id)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SubCategoryRequest $request, SubCategory $subCategory)
    {
        DB::beginTransaction();

        try {
            $request->validated();
        
            $dataToUpdate = [
                'category_id' => $request->input('category_id'),
                'name' => $request->input('name_en'),
                'slug' => $request->input('slug'),
                'description' => $request->input('description_en'),
                'order' => $request->input('order'),
                'is_active' => $request->boolean('is_active'),
            ];
            
            if ($request->hasFile('image')) {
                $this->handleImage($request, $subCategory);
                $dataToUpdate['image'] = $request->input('image');
            } 
         
            $subCategory->update($dataToUpdate);

            foreach (['en', 'id'] as $locale) {
                $subCategory->translations()->updateOrCreate([
                    'locale' => $locale
                ],[
                    'name' => $request->input("name_{$locale}"),
                    'description' => $request->input("description_{$locale}"),
                ]);
            }

            DB::commit();
            
            return to_route('secretgate19.sub_category.index')
                ->with('success', 'Sub Category updated successfully.');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubCategory $subCategory)
    {
        $subCategory->translations()->delete();
        $subCategory->deleteImage();
        $subCategory->delete();

        return response()->json(null, 204);
    }

    /**
     * Handle image function
     */
    protected function handleImage(Request $request, $subCategory = null): void
    {
        if ($request->hasFile('image')) {
            if ($subCategory) {
                $subCategory->deleteImage();
            }

            $path = $request->file('image')->store('sub_category', 'public');
            $request->merge(['image' => $path]);
        }
    }

    /**
     * Handle image function
     */
    protected function subCategoryList()
    {
        $data = SubCategoryData::listsForApi( request()->get('category') );

        return response()->json(['data' => $data]);
    }
}