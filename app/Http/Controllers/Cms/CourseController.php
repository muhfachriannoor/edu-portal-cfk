<?php

namespace App\Http\Controllers\Cms;

use App\Models\Course;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\View\Data\CategoryData;
use App\View\Data\SubCategoryData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CategoryRequest;
use App\Http\Controllers\Cms\CmsController;
use App\Http\Requests\CourseRequest;
use App\View\Data\CategoryFormData;

class CourseController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'course';
    
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
    return (new Course)->getDatatables();
    }

    /**
     * Get lists.
     */
    public function getLists()
    {
        return [
            'category' => CategoryFormData::lists(),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view("cms.course.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Course List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Course $course): View
    {
        return view("cms.course.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'course' => $course,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Create Course',
                'method' => 'post',
                'url' => route('admin.course.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CourseRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $request->validated();
            $this->handleImage($request);

            $course = Course::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'slug' => $request->input('slug'),
                'image' => $request->input('image'),
            ]);

            $course->categories()->attach($request->input('categories_id'));
            
            DB::commit();

            return to_route('admin.course.index')
                ->with('success', 'Course created successfully.');

        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course): View
    {
        return view("cms.course.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'course' => $course,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'View Course',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course): View
    {
        return view("cms.course.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'course' => $course,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Edit Course',
                'method' => 'put',
                'url' => route('admin.course.update', $course->id)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $request->validated();

            $dataToUpdate = [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'slug' => $request->input('slug'),
            ];
            
            if ($request->hasFile('image')) {
                $this->handleImage($request, $course);
                $dataToUpdate['image'] = $request->input('image');
            } 
        
            $course->update($dataToUpdate);

            DB::commit();
            
            return to_route('admin.category.index')
                ->with('success', 'Course updated successfully.');
                
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            return back()->withInput()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        $course->deleteImage();
        $course->delete();

        return response()->json(null, 204);
    }

    /**
     * Handle image function
     */
    protected function handleImage(Request $request, $course = null): void
    {
        if ($request->hasFile('image')) {
            if ($course) {
                $course->deleteImage();
            }

            $path = $request->file('image')->store('course', 'public');
            $request->merge(['image' => $path]);
        }
    }
}