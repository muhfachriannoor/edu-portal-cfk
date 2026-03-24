<?php

namespace App\Http\Controllers\StoreCms;

use App\Models\Store;
use App\Models\SpecialPrice;
use App\Models\ProductVariant;
use App\View\Data\ProductVariantData;
use App\Http\Requests\SpecialPriceRequest;
use App\Http\Controllers\Cms\CmsController;
use App\Services\SpecialPriceImportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class SpecialPriceController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'special_price';

    /**
     * @var SpecialPriceImportService
     */
    protected SpecialPriceImportService $importService;

    /**
     * Display a listing of the resource.
     */
    public function __construct(SpecialPriceImportService $importService)
    {
        $this->authorizeResourceWildcard($this->resourceName);
        $this->importService = $importService;
    }

    /**
     * Display a listing of the resource.
     */
    public function datatables(Store $store)
    {
        return (new SpecialPrice)->getDatatables($store);
    }

    /**
     * Get lists.
     */
    public function getLists(Store $store, $special_price = null): array
    {
        return [
            'variants' => ProductVariantData::listsForStoreForm($store->id),
            'type' => [
                'discount' => 'Discount',
                'percentage' => 'Percentage'
            ]
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        return view("store_cms.special_price.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists($store),
            'pageMeta' => [
                'title' => 'Special Price List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store, SpecialPrice $special_price)
    {
        return view("store_cms.special_price.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'special_price' => $special_price,
            'lists' => $this->getLists($store, $special_price),
            'pageMeta' => [
                'title' => 'Create Special Price',
                'method' => 'post',
                'url' => route('store_cms.special_price.store', [$store]),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, SpecialPriceRequest $request)
    {
        list($start_at, $end_at) = explode(' - ', $request->period);
        
        SpecialPrice::create([
            'product_variant_id' => $request->product_variant_id,
            'type' => $request->type,
            'discount' => $request->discount ?? 0,
            'percentage' => $request->percentage ?? "0.00",
            'start_at' => $start_at,
            'end_at' => $end_at,
            'is_active' => $request->is_active
        ]);
        
        return to_route('store_cms.special_price.index', [$store])
            ->with('success', 'Special Price created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store, SpecialPrice $special_price)
    {
        return view("store_cms.special_price.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'special_price' => $special_price,
            'lists' => $this->getLists($store, $special_price),
            'pageMeta' => [
                'title' => 'View Special Price',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store, SpecialPrice $special_price)
    {
        return view("store_cms.special_price.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'special_price' => $special_price,
            'lists' => $this->getLists($store, $special_price),
            'pageMeta' => [
                'title' => 'Edit Special Price',
                'method' => 'put',
                'url' => route('store_cms.special_price.update', ['store' => $store, 'special_price' => $special_price->id])
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SpecialPriceRequest $request, Store $store, SpecialPrice $special_price)
    {
        list($start_at, $end_at) = explode(' - ', $request->period);
        
        $special_price->update([
            'product_variant_id' => $request->product_variant_id,
            'type' => $request->type,
            'discount' => $request->discount ?? 0,
            'percentage' => $request->percentage ?? "0.00",
            'start_at' => $start_at,
            'end_at' => $end_at,
            'is_active' => $request->is_active
        ]);
        
        return to_route('store_cms.special_price.show', ['store' => $store, 'special_price' => $special_price->id])
                ->with('success', 'Special Price updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, SpecialPrice $special_price)
    {
        // $special_price->syncRoles([]);
        $special_price->delete();

        return response()->json(null, 204);
    }

    /**
     * For Take Variant Detail using AJAX
     * File special_price/form.blade.php
     */
    public function variantDetail(Store $store, ProductVariant $variant): JsonResponse
    {
        if ($variant->store_id !== $store->id) {
            abort(404);
        }

        $variant->loadMissing(['product.files']);

        $product = $variant->product;

        return response()->json([
            'id' => $variant->id,
            'product_name' => $variant->name,
            'sku' => $variant->sku,
            'quantity' => $variant->quantity,
            'price' => $variant->price,
            'formatted_price' => 'IDR. ' . number_format($variant->price, 0, ',', '.'),
            'image_url' => $product?->default_image,
        ]);
    }

    /**
     * Import Special Price data from Excel file.
     */
    public function import(Store $store, Request $request): JsonResponse
    {
        $file = $request->file('file');

        try {
            // Read Excel file and convert to array
            $spreadSheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadSheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'The file could not be read as an Excel document.',
                'errors' => [[
                    'row' => null,
                    'field' => 'file',
                    'message' => 'Make sure the file format matches the sample Excel template.',
                ]],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Delegate heavy logic to service
        $result = $this->importService->prepareImport($store, $rows);

        // If there are any row-level errors, fail the whole file
        if ($result->hasErrors()) {
            return response()->json([
                'message' => 'Import failed. Please fix the errros and try again.',
                'errors' => $result->errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (empty($result->validRows)) {
            return response()->json([
                'message' => 'No valid data rows were found in the file.',
                'errors' => [],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Insert all valid rows in a single transaction
        DB::transaction(function () use ($result) {
            SpecialPrice::insert($result->validRows);
        });

        return response()->json([
            'message' => 'Successfully imported ' . count($result->validRows) . ' special price record(s).',
        ]);
    }

    /**
     * Handle Image.
     */
    // public function handleImages($store, $special_price, $request)
    // {
    //     // Handle deleted_image_ids
    //     if($request->input('deleted_image_ids')){
    //         $ids = explode(",", $request->input('deleted_image_ids'));

    //         $special_price->files()->whereIn('id', $ids)->delete();
    //     }

    //     // Handle default_image
    //     if($request->has('default_image')){
    //         $image = $request->file('default_image');

    //         $special_price->saveFile(
    //             $image,
    //             'special_price',
    //             [
    //                 'field' => 'default-image',
    //                 'name' => $image->getClientOriginalName()
    //             ]
    //         );
    //     }

    //     // Handle multiple special_price_images
    //     if ($request->has('special_price_images')) {
    //         $images = $request->file('special_price_images');

    //         foreach($images as $index => $image){
    //             $special_price->saveFile(
    //                 $image,
    //                 'special_price',
    //                 [
    //                     'field' => 'special_price-image',
    //                     'name' => $image->getClientOriginalName()
    //                 ]
    //             );
    //         }
    //     }

    //     // Handle multiple image_order
    //     if ($request->input('image_order')) {
    //         $order = $request->input('image_order');
    //         $images = $special_price->files()->where('field', 'special_price-image')->get();

    //         foreach($images as $index => $image){
    //             $image->update(['order' => $order[$index]]);
    //         }
    //     }
    // }

    /**
     * Handle Options.
     */
    // public function handleOptions($store, $special_price, $request)
    // {
    //     $options = is_array($request->options_json) ? $request->options_json : json_decode($request->options_json);

    //     foreach ($options as $option) {
    //         $type = $option->type ?? null; // for retrieve information

    //         // Update or create option
    //         $new_option = $special_price->options()->updateOrCreate(
    //             ['id' => $option->id ?? null],
    //             ['store_id' => $store->id, 'type' => $type, 'is_active' => ($request->has_variants)]
    //         );

    //         if (isset($option->preview) && !empty($option->preview)) {
    //             $preview = $option->preview;

    //             // Delete existing preview if exists
    //             if ($new_option->file) { // assuming relation is 'file'
    //                 // Delete file from disk
    //                 $existingPath = public_path("storage/{$new_option->path}");
    //                 if (file_exists($existingPath)) {
    //                     @unlink($existingPath);
    //                 }

    //                 // Delete DB record
    //                 $new_option->file()->delete();
    //             }

    //             // Base64 string → upload it
    //             if (preg_match('/^data:image\/(\w+);base64,/', $preview)) {
    //                 $new_option->addBase64File(
    //                     $preview,
    //                     'special_price',
    //                     ['field' => 'preview']
    //                 );
    //             }
    //         }

    //         // Update translations
    //         $this->updateTranslations($new_option, $option, ['en', 'id']);

    //         // Handle option values
    //         foreach ($option->values as $value) {
    //             $opt_value = $new_option->option_values()->updateOrCreate(
    //                 ['id' => $value->id ?? null],
    //                 [
    //                     'store_id' => $store->id, 
    //                     'order' => $value->order, 
    //                     'additional_data' => ($type !== 'text') ? [$type => $value->$type] : new \stdClass()
    //                 ]
    //             );

    //             // Update value translations
    //             $this->updateTranslations($opt_value, $value, ['en', 'id']);
    //         }
    //     }
    // }

    /**
     * Update translations for a model (option or option value)
     */
    // protected function updateTranslations($model, $data, $locales = ['en', 'id'])
    // {
    //     foreach ($locales as $locale) {
    //         $param = "name_{$locale}";
    //         $model->translations()->updateOrCreate(
    //             ['locale' => $locale],
    //             ['name' => $data->$param]
    //         );
    //     }
    // }

    /**
     * Handle Variants.
     */
    // public function handleVariants($store, $special_price, $request)
    // {
    //     $variants = is_array($request->variants_json) ? $request->variants_json : json_decode($request->variants_json);

    //     if (!empty($variants)) {
    //         // Get all option values once to avoid querying inside the loop
    //         $allOptionValues = $special_price->options()->with('option_values')->get()->flatMap->option_values;

    //         foreach ($variants as $variant) {
    //             $list = explode(" / ", $variant->name);

    //             // Get combination IDs
    //             $combinations = $allOptionValues
    //                 ->whereIn('name', $list)
    //                 ->pluck('id')
    //                 ->toArray();

    //             // Normalize price and quantity
    //             $price = str_replace(['.', ','], ['', '.'], $variant->price);
    //             $quantity = str_replace(['.', ','], '', $variant->quantity);

    //             // Use updateOrCreate for simplicity
    //             $special_price->variants()->updateOrCreate(
    //                 ['id' => !empty($variant->id) ? $variant->id : null], // match by id if exists
    //                 [
    //                     'store_id' => $store->id,
    //                     'special_price_id' => $special_price->id,
    //                     'slug' => generateSlug([$special_price->name, $variant->name]),
    //                     'combination' => $combinations,
    //                     'sku' => $variant->sku,
    //                     'quantity' => $quantity,
    //                     'price' => $price
    //                 ]
    //             );
    //         }
    //     } else {
    //         // Single default variant
    //         $special_price->variants()->updateOrCreate(
    //             ['special_price_id' => $special_price->id],
    //             [
    //                 'slug' => generateSlug([$special_price->name]),
    //                 'store_id' => $store->id,
    //                 'sku' => $request->sku,
    //                 'quantity' => $request->quantity,
    //                 'price' => $request->price
    //             ]
    //         );
    //     }
    // }

    /**
     * Handle Variants.
     */
    // public function handleSeo($store, $special_price, $request)
    // {
    //     $special_price->seo()->updateOrCreate(
    //         [ 'store_id' => $store->id, 'special_price_id' => $special_price->id ],
    //         [
    //             'meta_title' => $request->input('seo')['meta_title'] ?? null,
    //             'meta_description' => $request->input('seo')['meta_description'] ?? null,
    //             'meta_keywords' => $request->input('seo')['meta_keywords'] ?? null,
    //             'robot' => $request->input('seo')['robot'] ?? null,
    //         ]
    //     );
    // }
}