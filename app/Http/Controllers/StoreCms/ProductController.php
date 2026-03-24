<?php

namespace App\Http\Controllers\StoreCms;

use Carbon\Carbon;
use App\Models\Store;
use App\Models\Product;
use App\Models\Privilege;
use App\View\Data\BrandData;
use Illuminate\Http\Request;
use App\Models\ProductOption;
use App\View\Data\CategoryData;
use App\Models\ProductOptionValue;
use App\View\Data\SubCategoryData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ProductRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Cms\CmsController;

class ProductController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'product';

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
        return (new Product)->getDatatables($store);
    }

    /**
     * Get lists.
     */
    public function getLists($product = null)
    {
        return [
            'categories' => CategoryData::lists(),
            'sub_categories' => SubCategoryData::lists($product->category_id ?? null),
            'brands' => BrandData::lists(),
            'options' => ProductOption::all()->pluck('name', 'id')->toArray(),
            'option_values' => ProductOptionValue::all()->groupBy('product_option_id')
                ->map(function ($group) {
                    return $group->mapWithKeys(function ($item) {
                        return [$item->id => $item->name];
                    });
                })
                ->toArray(),
            'robot' => [
                'index,follow' => "Index, Follow",
                'noindex,nofollow' => "No Index, No Follow",
            ]
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        return view("store_cms.product.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Product List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store, Product $product)
    {
        return view("store_cms.product.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'product' => $product,
            'lists' => $this->getLists($product),
            'pageMeta' => [
                'title' => 'Create Product',
                'method' => 'post',
                'url' => route('store_cms.product.store', [$store]),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, ProductRequest $request)
    {
        DB::beginTransaction();

        try{
            $product = Product::create([
                'store_id' => $store->id,
                'name' => $request->name,
                'slug' => generateSlug([$request->name]),
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'brand_id' => $request->brand_id,
                'is_active' => $request->is_active,
                'is_bestseller' => $request->is_bestseller,
                'is_truly_indonesian' => $request->is_truly_indonesian,
                'is_limited_edition' => $request->is_limited_edition,
                'main_image_index' => $request->main_image_index ?? 0,
                'tags' => $request->tags,
                'options' => $request->options_json
            ]);

            $this->handleFeature($store, $product, $request);
            $this->handleTranslation($store, $product, $request);
            $this->handleImages($store, $product, $request);
            $this->handleVariants($store, $product, $request);
            $this->handleSeo($store, $product, $request);

            DB::commit();
            return to_route('store_cms.product.index', [$store])
                ->with('success', 'Product created successfully');

        } catch(\Exception $e){
            DB::rollback();
            report($e); // logs to laravel.log
            throw $e;   // keep stack trace
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, Product $product)
    {
        return view("store_cms.product.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'product' => $product,
            'lists' => $this->getLists($product),
            'pageMeta' => [
                'title' => 'View Product',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store, Product $product)
    {
        return view("store_cms.product.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'product' => $product,
            'lists' => $this->getLists($product),
            'pageMeta' => [
                'title' => 'Edit Product',
                'method' => 'put',
                'url' => route('store_cms.product.update', ['store' => $store, 'product' => $product->id])
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Store $store, Product $product)
    {
        DB::beginTransaction();

        try{
            $product->update([
                'name' => $request->name,
                'slug' => generateSlug([$request->name]),
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'brand_id' => $request->brand_id,
                'is_active' => $request->is_active,
                'is_bestseller' => $request->is_bestseller,
                'is_truly_indonesian' => $request->is_truly_indonesian,
                'is_limited_edition' => $request->is_limited_edition,
                'main_image_index' => $request->main_image_index,
                'tags' => $request->tags,
                'options' => $request->options_json
            ]);

            $this->handleFeature($store, $product, $request);
            $this->handleTranslation($store, $product, $request);
            $this->handleImages($store, $product, $request);
            $this->handleVariants($store, $product, $request);
            $this->handleSeo($store, $product, $request);

            DB::commit();
            return to_route('store_cms.product.show', ['store' => $store, 'product' => $product->id])
                ->with('success', 'Product updated successfully');
        } catch(\Exception $e) {
            DB::rollback();
            report($e); // logs to laravel.log
            throw $e;   // keep stack trace
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, Product $product)
    {
        // $product->syncRoles([]);
        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * Handle Translation.
     */
    public function handleFeature($store, $product, $request)
    {
        // Get the features data
        $featureImages = $request->file('feature_images');
        $features = json_decode($request->features_json, true);
        $imageIndex = 0;

        foreach($features as $item){
            $feature = $product->features()->updateOrCreate(
                ['id' => $item['id']],
                []
            );

            foreach(['en', 'id'] as $locale){
                $param = 'text_' . $locale;

                $item[$param];
                $feature->translations()->updateOrCreate([
                    'locale' => $locale
                ],[
                    'additional' => ['text' => $item[$param]]
                ]);
            }

            if($item['id']){ // Update new image
                if($item['image_changed']){
                    $image = $featureImages[$imageIndex];
                    $feature->saveFile(
                        $image,
                        'product_feature',
                        [
                            'field' => 'image',
                            'name' => $image->getClientOriginalName()
                        ]
                    );

                    $imageIndex++;
                }
            } else { // Insert new image
                $image = $featureImages[$imageIndex];
                $feature->saveFile(
                    $image,
                    'product_feature',
                    [
                        'field' => 'image',
                        'name' => $image->getClientOriginalName()
                    ]
                );

                $imageIndex++;
            }
        }
    }

    /**
     * Handle Translation.
     */
    public function handleTranslation($store, $product, $request)
    {
        $fields = ['story', 'feature', 'material'];
        foreach (['en', 'id'] as $locale) {
            $additional = [
                'story' => $request->input("story_{$locale}"),
                // 'feature' => $request->input("feature_{$locale}"),
                'material' => $request->input("material_{$locale}"),
            ];

            $product->translations()->updateOrCreate([
                'locale' => $locale
            ],[
                'additional' => $additional,
            ]);
        }
    }

    /**
     * Handle Image.
     */
    public function handleImages($store, $product, $request)
    {
        // Handle deleted_image_ids
        if($request->input('deleted_image_ids')){
            $ids = explode(",", $request->input('deleted_image_ids'));

            $product->files()->whereIn('id', $ids)->delete();
        }

        // Handle multiple product_images
        if ($request->has('product_images')) {
            $images = $request->file('product_images');
            $maxOrder = $product->files()->max('order');

            foreach($images as $index => $image){
                $product->saveFile(
                    $image,
                    'product',
                    [
                        'field' => 'product-image',
                        'name' => $image->getClientOriginalName(),
                        'order' => $maxOrder + $index + 1 // memastikan urutan image tidak kacau
                    ]
                );
            }
        }
        
        // Handle multiple image_order
        if ($request->input('image_order')) {
            $order = $request->input('image_order');
            $images = $product->files()->where('field', 'like', 'product-image%')->orderBy('order')->get();

            foreach($images as $index => $image){
                $image->update(['field' => "product-image-{$order[$index]}", 'order' => $order[$index]]);
            }
        }
    }

    /**
     * Handle Variants.
     */
    public function handleVariants($store, $product, $request)
    {
        $variants = is_array($request->variants_json) ? collect($request->variants_json) : json_decode($request->variants_json);

        if (count($variants) > 0) {
            // Get all option values once to avoid querying inside the loop
            $allOptionValues = ProductOption::with('option_values')->get()->flatMap->option_values;

            // Delete removed variants before insert or update
            $product->variants()->whereNotIn('id', $variants->pluck('id'))->delete();

            foreach ($variants as $variant) {
                $list = explode(" / ", $variant->name);

                // Get combination IDs
                $combinations = $allOptionValues
                    ->whereIn('name', $list)
                    ->pluck('id')
                    ->toArray();

                // Normalize price and quantity
                $price = str_replace(['.', ','], ['', '.'], $variant->price);
                $quantity = str_replace(['.', ','], '', $variant->quantity);

                // Use updateOrCreate for simplicity
                $product_variant = $product->variants()->updateOrCreate(
                    [ 'id' => !empty($variant->id) ? $variant->id : null ], // match by id if exists
                    [
                        'store_id' => $store->id,
                        'product_id' => $product->id,
                        'slug' => generateSlug([$product->name, $variant->name]),
                        'combination' => $combinations,
                        'sku' => empty($variant->sku) ? null : $variant->sku,
                        'quantity' => $quantity,
                        'price' => $price
                    ]
                );

                // Handle Special Price
                if(!empty($variant->discount_period)){
                    list($start_at, $end_at) = explode(' - ', $variant->discount_period);
                    // $product_variant->special_price()->update(['is_active' => 0]);
                    $product_variant->special_price()->updateOrCreate(
                        [
                            'product_variant_id' => $product_variant->id,
                            'start_at' => $start_at,
                            'end_at' => $end_at,
                            'is_active' => 1
                        ],
                        [
                            'type' => 'discount',
                            'discount' => (int) str_replace('.', '', $variant->discount_price),
                            'percentage' => calculatePercentage($product_variant->price, $variant->discount_price)
                        ]
                    );
                } else {
                    $product_variant->special_price()->update(['is_active' => 0]);
                }
            }
        } else {
            // Single default variant
            $variant = $product->variants()->updateOrCreate(
                [ 'store_id' => $store->id, 'product_id' => $product->id ],
                [
                    
                    'slug' => generateSlug([$product->name]),
                    'store_id' => $store->id,
                    'sku' => empty($variant->sku) ? null : $variant->sku,
                    'quantity' => $request->quantity,
                    'price' => $request->price,
                    'combination' => null
                ]
            );

            if($request->discount_period){
                list($start_at, $end_at) = explode(' - ', $request->discount_period);
                $variant->special_price()->updateOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'start_at' => $start_at,
                        'end_at' => $end_at,
                        'is_active' => 1
                    ],
                    [
                        'type' => 'discount',
                        'discount' => (int) str_replace('.', '', $request->discount_price),
                        'percentage' => calculatePercentage($variant->price, $request->discount_price)
                    ]
                );
            }
        }
    }

    /**
     * Handle Variants.
     */
    public function handleSeo($store, $product, $request)
    {
        $product->seo()->updateOrCreate(
            [ 'store_id' => $store->id, 'product_id' => $product->id ],
            [
                'meta_title' => $request->input('seo')['meta_title'] ?? null,
                'meta_description' => $request->input('seo')['meta_description'] ?? null,
                'meta_keywords' => $request->input('seo')['meta_keywords'] ?? null,
                'robot' => $request->input('seo')['robot'] ?? null,
            ]
        );
    }
}