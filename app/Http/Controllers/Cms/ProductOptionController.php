<?php

namespace App\Http\Controllers\Cms;

use Carbon\Carbon;
use App\Models\Store;
use App\Models\Privilege;
use App\View\Data\BrandData;
use Illuminate\Http\Request;
use App\Models\ProductOption;
use App\View\Data\CategoryData;
use App\View\Data\SubCategoryData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ProductRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Cms\CmsController;
use App\Http\Requests\ProductOptionRequest;

class ProductOptionController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'product_option';

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
    public function datatables(Store $store)
    {
        return (new ProductOption)->getDatatables($store);
    }

    /**
     * Get lists.
     */
    public function getLists($product_option = null)
    {
        return [
            
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("cms.product_option.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Product Option List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ProductOption $product_option)
    {
        return view("cms.product_option.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'product_option' => $product_option,
            'lists' => $this->getLists($product_option),
            'pageMeta' => [
                'title' => 'Create Product Option',
                'method' => 'post',
                'url' => route('secretgate19.product_option.store'),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductOptionRequest $request)
    {
        DB::beginTransaction();

        try{
            $this->handleOptions(null, $request);

            DB::commit();
            return to_route('secretgate19.product_option.index')
                ->with('success', 'Product Option created successfully');

        } catch(\Exception $e){
            DB::rollback();
            report($e); // logs to laravel.log
            throw $e;   // keep stack trace
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductOption $product_option)
    {
        // return $product_option->masterOptions;
        return view("cms.product_option.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'product_option' => $product_option,
            'lists' => $this->getLists($product_option),
            'pageMeta' => [
                'title' => 'View Product Option',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductOption $product_option)
    {
        return view("cms.product_option.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'product_option' => $product_option,
            'lists' => $this->getLists($product_option),
            'pageMeta' => [
                'title' => 'Edit Product Option',
                'method' => 'put',
                'url' => route('secretgate19.product_option.update', ['product_option' => $product_option->id])
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductOptionRequest $request, ProductOption $product_option)
    {
        DB::beginTransaction();

        try{
            $this->handleOptions($product_option, $request);

            DB::commit();
            return to_route('secretgate19.product_option.index')
                ->with('success', 'Product Option updated successfully');
        } catch(\Exception $e) {
            DB::rollback();
            report($e); // logs to laravel.log
            throw $e;   // keep stack trace
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductOption $product_option)
    {
        $product_option->delete();

        return response()->json(null, 204);
    }

    /**
     * Handle Options.
     */
    public function handleOptions($product_option, $request)
    {
        $option = is_array($request->options_json) ? $request->options_json : json_decode($request->options_json);

        $type = $option['type'] ?? null; // for retrieve information

        // Update or create option
        $new_option = ProductOption::updateOrCreate(
            ['id' => $option['id'] ?? null],
            ['type' => $type, 'is_active' => 1]
        );

        if (isset($request->image) && !empty($request->image)) {
            $file = request()->file('image');

            $new_option->saveFile(
                    $file,
                    'product_option',
                    [
                        'field' => 'image',
                        'name' => $file->getClientOriginalName()
                    ]
                );
        }

        // Update image_text translations
        foreach (['en', 'id'] as $locale) {
            $param = "image_text_{$locale}";
            $new_option->translations()->updateOrCreate(
                ['locale' => $locale],
                ['additional' => ['image_text' => $request->$param]]
            );
        }
        
        // Update translations
        $this->updateTranslations($new_option, $option, ['en', 'id']);

        // Handle option values
        foreach ($option['values'] as $value) {
            $opt_value = $new_option->option_values()->updateOrCreate(
                [ 'id' => $value['id'] ?? null ],
                [
                    'additional_data' => ($type !== 'text') ? [$type => $value[$type]] : new \stdClass()
                ]
            );

            // Update value translations
            $this->updateTranslations($opt_value, $value, ['en', 'id']);
        }
    }

    /**
     * Update translations for a model (option or option value)
     */
    protected function updateTranslations($model, $data)
    {
        foreach (['en', 'id'] as $locale) {
            $param = "name_{$locale}";
            $model->translations()->updateOrCreate(
                ['locale' => $locale],
                ['name' => $data[$param]]
            );
        }
    }
}