<?php

namespace App\Http\Controllers\Cms;

use Carbon\Carbon;
use App\Models\Newsroom;
use Illuminate\Http\Request;
use App\Http\Requests\NewsroomRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Cms\CmsController;
use App\View\Data\NewsroomData;
use Illuminate\Http\JsonResponse;

class NewsroomController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'newsroom';

    /**
     * Constructor: Authorize resource wildcard.
     */
    public function __construct()
    {
        $this->authorizeResourceWildcard($this->resourceName);
    }

    /**
     * Display a listing of the resource for Datatables.
     */
    public function datatables()
    {
        return (new Newsroom)->getDatatables(); 
    }

    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        return view("cms.{$this->resourceName}.index", [ // cms.newsroom.index
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'Newsroom List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Newsroom $newsroom)
    {
        return view("cms.{$this->resourceName}.form", [ // cms.newsroom.form
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'newsroom' => $newsroom,
            'pageMeta' => [
                'title' => 'Create Newsroom Article',
                'method' => 'post',
                'url' => route('secretgate19.newsroom.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NewsroomRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $isActive = $request->boolean('is_active');
            // 1. Create Article
            $newsroom = Newsroom::create([

                // Data wajib
                'admin_id' => auth()->user()->id, // Admin yang sedang login
                'slug' => $request->input('slug'),
                'is_active' => $isActive,
                
                // Data Penjadwalan & SEO
                'published_at' => $isActive
                    ? ($request->input('published_at') ?? Carbon::now()->format('Y-m-d'))
                    : $request->input('published_at'),
            ]);

            // 2. Handle Image
            $this->handleImage($newsroom); 

            // 3. Handle Translations & SEO Metadata
            foreach (['en', 'id'] as $locale) {
                $newsroom->translations()->create([
                    'locale' => $locale,
                    'name' => $request->input("title_{$locale}"), // Mapped ke Title
                    'description' => $request->input("content_{$locale}"), // Mapped ke Content
                ]);

                $metaTitle = $request->input("meta_title_{$locale}");
                $metaDesc = $request->input("meta_description_{$locale}");
                $metaKey = $request->input("meta_keywords_{$locale}");

                if (!empty($metaTitle) || !empty($metaDesc) || !empty($metaKey)) {
                    $newsroom->seos()->create([
                        'locale'           => $locale,
                        'meta_title'       => $metaTitle,
                        'meta_description' => $metaDesc,
                        'meta_keywords'    => $metaKey,
                    ]);
                }
            }
            
            DB::commit();

            return to_route('secretgate19.newsroom.index')
                ->with('success', 'Newsroom article created successfully.');

        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Newsroom $newsroom)
    {
        $newsroom->load('translations'); 

        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'newsroom' => $newsroom,
            'pageMeta' => [
                'title' => 'Edit Newsroom Article',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Newsroom $newsroom)
    {
        // Load translations agar data terjemahan ada di form
        $newsroom->load('translations'); 

        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'newsroom' => $newsroom,
            'pageMeta' => [
                'title' => 'Edit Newsroom Article',
                'method' => 'PUT',
                'url' => route('secretgate19.newsroom.update', $newsroom->slug)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NewsroomRequest $request, Newsroom $newsroom): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $isActive = $request->boolean('is_active');

            // 1. Update Article
            $newsroom->update([
                'slug' => $request->input('slug'),
                'is_active' => $isActive,

                // Update published_at jika status diaktifkan atau jika diisi.
                'published_at' => $isActive
                    ? ($request->input('published_at') ?? Carbon::now()->format('Y-m-d'))
                    : $request->input('published_at'),
            ]);
            
            // 2. Handle Image
            $this->handleImage($newsroom);

            // 3. Handle Translations (UpdateOrCreate)
            foreach (['en', 'id'] as $locale) {
                $newsroom->translations()->updateOrCreate([
                    'locale' => $locale
                ],[
                    'name' => $request->input("title_{$locale}"),
                    'description' => $request->input("content_{$locale}"),
                ]);

                $metaTitle = $request->input("meta_title_{$locale}");
                $metaDesc = $request->input("meta_description_{$locale}");
                $metaKey = $request->input("meta_keywords_{$locale}");

                if (!empty($metaTitle) || !empty($metaDesc) || !empty($metaKey)) {
                    $newsroom->seos()->updateOrCreate(
                        ['locale' => $locale],
                        [
                            'meta_title'       => $metaTitle,
                            'meta_description' => $metaDesc,
                            'meta_keywords'    => $metaKey,
                        ]
                    );
                } else {
                    $newsroom->seos()->where('locale', $locale)->delete();
                }
            }

            DB::commit();
            
            return to_route('secretgate19.newsroom.index')
                ->with('success', 'Newsroom article updated successfully.');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Newsroom $newsroom): JsonResponse
    {
        $newsroom->translations()->delete();
        $newsroom->delete();

        return response()->json(null, 204);
    }

    /**
     * Handle Image.
     */
    private function handleImage($newsroom)
    {
        $fields = ['image'];
        foreach($fields as $field){
            if(request()->hasFile($field)){
                $file = request()->file($field);

                $newsroom->saveFile(
                    $file,
                    'newsroom',
                    [
                        'field' => $field,
                        'name' => $file->getClientOriginalName()
                    ]
                );
            }
        }        
    }
}