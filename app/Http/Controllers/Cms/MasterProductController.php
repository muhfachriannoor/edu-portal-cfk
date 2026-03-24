<?php

namespace App\Http\Controllers\Cms;

use Carbon\Carbon;
use App\Models\Store;
use App\Models\Product;
use App\Models\Privilege;
use Illuminate\View\View;
use App\View\Data\BrandData;
use App\View\Data\StoreData;
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
use App\Jobs\SendStockNotificationJob;
use App\Models\NotifyMe;
use App\Models\ProductVariant;
use App\View\Data\ProductData;

class MasterProductController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'master_product';

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
        return (new Product)->getMasterDatatables();
    }

    /**
     * Get lists.
     */
    public function getLists($master_product = null): array
    {
        return [
            'stores' => StoreData::lists(),
            'categories' => CategoryData::lists(),
            'sub_categories' => SubCategoryData::lists($master_product->category_id ?? null),
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
    public function index(Store $store): View
    {
        return view("cms.master_product.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Master Product List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Product $master_product): View
    {
        return view("cms.master_product.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'product' => $master_product,
            'lists' => $this->getLists($master_product),
            'pageMeta' => [
                'title' => 'Create Product',
                'method' => 'post',
                'url' => route('secretgate19.master_product.store'),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try{
            foreach( $request->store_id as $store_id ){
                $store = Store::find($store_id);

                $master_product = Product::create([
                    'store_id' => $store_id,
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

                $this->handleFeature($store, $master_product, $request);
                $this->handleTranslation($store, $master_product, $request);
                $this->handleImages($store, $master_product, $request);
                $this->handleVariants($store, $master_product, $request);
                $this->handleSeo($store, $master_product, $request);
            }

            DB::commit();

            ProductData::flush();
            return to_route('secretgate19.master_product.index')
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
    public function show(Product $master_product): View
    {
        return view("cms.master_product.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'product' => $master_product,
            'lists' => $this->getLists($master_product),
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
    public function edit(Product $master_product): View
    {
        return view("cms.master_product.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'product' => $master_product,
            'lists' => $this->getLists($master_product),
            'pageMeta' => [
                'title' => 'Edit Product',
                'method' => 'put',
                'url' => route('secretgate19.master_product.update', ['master_product' => $master_product->id])
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $master_product): RedirectResponse
    {
        DB::beginTransaction();

        try{
            $store = Store::find($request->store_id);
            
            $master_product->update([
                'name' => $request->name,
                'store_id' => $request->store_id,
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

            $this->handleFeature($store, $master_product, $request);
            $this->handleTranslation($store, $master_product, $request);
            $this->handleImages($store, $master_product, $request);
            $this->handleVariants($store, $master_product, $request);
            $this->handleSeo($store, $master_product, $request);

            DB::commit();

            ProductData::flush();
            return to_route('secretgate19.master_product.show', ['master_product' => $master_product->id])
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
    public function destroy(Product $master_product)
    {
        // $master_product->syncRoles([]);
        $master_product->delete();
        ProductData::flush();

        return response()->json(null, 204);
    }

    /**
     * Handle Translation.
     */
    public function handleFeature($store, $master_product, $request): void
    {
        // Get the features data
        $featureImages = $request->file('feature_images');
        $features = json_decode($request->features_json, true);
        $imageIndex = 0;

        foreach($features as $item){
            $feature = $master_product->features()->updateOrCreate(
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
    public function handleTranslation($store, $master_product, $request): void
    {
        $fields = ['story', 'feature', 'material'];
        foreach (['en', 'id'] as $locale) {
            $additional = [
                'story' => $request->input("story_{$locale}"),
                // 'feature' => $request->input("feature_{$locale}"),
                'material' => $request->input("material_{$locale}"),
            ];

            $master_product->translations()->updateOrCreate([
                'locale' => $locale
            ],[
                'additional' => $additional,
            ]);
        }
    }

    /**
     * Handle Image.
     */
    public function handleImages($store, $master_product, $request): void
    {
        // Handle deleted_image_ids
        if($request->input('deleted_image_ids')){
            $ids = explode(",", $request->input('deleted_image_ids'));

            $master_product->files()->whereIn('id', $ids)->delete();
        }

        // Handle multiple product_images
        if ($request->has('product_images')) {
            $images = $request->file('product_images');
            $maxOrder = $master_product->files()->max('order');

            foreach($images as $index => $image){
                $master_product->saveFile(
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
            $images = $master_product->files()->where('field', 'like', 'product-image%')->orderBy('order')->get();

            foreach($images as $index => $image){
                $image->update(['field' => "product-image-{$order[$index]}", 'order' => $order[$index]]);
            }
        }
    }

    /**
     * Handle Variants.
     */
    public function handleVariants($store, $master_product, $request): void
    {
        $variants = is_array($request->variants_json) ? collect($request->variants_json) : json_decode($request->variants_json);
        
        $master_product->variants()
            ->where('store_id', '!=', $store->id)
            ->update(['quantity' => 0]);

        if (count($variants) > 0) {
            // Get all option values once to avoid querying inside the loop
            $allOptionValues = ProductOption::with('option_values')->get()->flatMap->option_values;

            // Delete removed variants before insert or update
            // Filter berdasarkan store_id agar tidak menghapus data toko lain yang sudah di-set 0
            $master_product->variants()
                ->where('store_id', $store->id)
                ->whereNotIn('id', $variants->pluck('id'))
                ->delete();

            foreach ($variants as $variant) {
                $oldQuantity = 0;

                if (!empty($variant->id)) {
                    $existingVariant = ProductVariant::find($variant->id);
                    $oldQuantity = $existingVariant ? (int) $existingVariant->quantity : 0;
                }

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
                $master_product_variant = $master_product->variants()->updateOrCreate(
                    [ 'id' => !empty($variant->id) ? $variant->id : null ], 
                    [
                        'store_id' => $store->id,
                        'product_id' => $master_product->id,
                        'slug' => generateSlug([$master_product->name, $variant->name]),
                        'combination' => $combinations,
                        'sku' => empty($variant->sku) ? null : $variant->sku,
                        'quantity' => $quantity,
                        'price' => $price
                    ]
                );

                // Start Trigger Job untuk Notif Me (Multi-Variant)
                if ($oldQuantity === 0 && $quantity > 0) {
                    $hasSubscribers = NotifyMe::where('variant_id', $master_product_variant->id)
                        ->where('notified', 0)
                        ->exists();
                    
                    if ($hasSubscribers) {
                        dispatch(new SendStockNotificationJob($master_product_variant))
                            ->onQueue('high');
                    }
                }

                // Handle Special Price
                if(!empty($variant->discount_period)){
                    list($start_at, $end_at) = explode(' - ', $variant->discount_period);
                    $master_product_variant->special_price()->updateOrCreate(
                        [
                            'product_variant_id' => $master_product_variant->id,
                            'start_at' => $start_at,
                            'end_at' => $end_at,
                            'is_active' => 1
                        ],
                        [
                            'type' => 'discount',
                            'discount' => (int) str_replace('.', '', $variant->discount_price),
                            'percentage' => calculatePercentage($master_product_variant->price, $variant->discount_price)
                        ]
                    );
                } else {
                    $master_product_variant->special_price()->update(['is_active' => 0]);
                }
            }
        } else {
            $defaultVariant = $master_product->variants()
                ->where('store_id', $store->id)
                ->where('product_id', $master_product->id)
                ->first();

            $oldQuantity = $defaultVariant ? (int) $defaultVariant->quantity : 0;
            $newQuantity = (int) $request->quantity;

            // Single default variant
            // Menggunakan store_id dan product_id sebagai unique matcher
            $variant = $master_product->variants()->updateOrCreate(
                [ 'store_id' => $store->id, 'product_id' => $master_product->id ],
                [
                    'slug' => generateSlug([$master_product->name]),
                    'sku' => empty($request->sku) ? null : $request->sku,
                    'quantity' => $request->quantity,
                    'price' => $request->price,
                    'combination' => null
                ]
            );

            // Start Trigger Job untuk Notif Me (Single-Variant)
            if ($oldQuantity === 0 && $newQuantity > 0) {
                $hasSubscribers = NotifyMe::where('variant_id', $variant->id)
                    ->where('notified', 0)
                    ->exists();

                if ($hasSubscribers) {
                    dispatch(new SendStockNotificationJob($variant))
                        ->onQueue('high');
                }
            }

            if($request->discount_price && $request->discount_period){
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
            } else {
                $variant->special_price()->update(['is_active' => 0]);
            }
        }
    }

    /**
     * Handle Variants.
     */
    public function handleSeo($store, $master_product, $request): void
    {
        $master_product->seo()->updateOrCreate(
            [ 'store_id' => $store->id, 'product_id' => $master_product->id ],
            [
                'meta_title' => $request->input('seo')['meta_title'] ?? null,
                'meta_description' => $request->input('seo')['meta_description'] ?? null,
                'meta_keywords' => $request->input('seo')['meta_keywords'] ?? null,
                'robot' => $request->input('seo')['robot'] ?? null,
            ]
        );
    }
}