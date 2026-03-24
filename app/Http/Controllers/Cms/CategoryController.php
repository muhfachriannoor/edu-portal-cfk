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
            // 'categories' => CategoryData::lists(),
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
                'url' => route('admin.category.store')
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

            Category::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'slug' => $request->input('slug'),
            ]);
            
            DB::commit();

            return to_route('admin.category.index')
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
                'url' => route('admin.category.update', $category->id)
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

            $dataToUpdate = [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'slug' => $request->input('slug'),
            ];
        
            $category->update($dataToUpdate);

            DB::commit();
            
            return to_route('admin.category.index')
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
        $category->delete();

        return response()->json(null, 204);
    }
}