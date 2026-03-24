<?php

namespace App\Http\Controllers\Cms;

use App\Models\Store;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\ProductOption;
use App\Models\ProductSarinah;
use App\Models\ProductVariant;
use App\Models\ProductOptionValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Cms\CmsController;

class SarinahProductController extends CmsController
{
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        
    }

    public function readJson()
    {
        $path = public_path('storage/sample_product.json');

        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
                'data' => []
            ], 404);
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true); // decode as array

        return $this->handleSync( $data );
    }


    /**
     * Sync data.
     */
    public function sync()
    {
        $url = Setting::where('key', 'SARINAH_API')->first()->data['url'];
        try {
            $response = Http::timeout(10)  // set 10 seconds timeout
                ->acceptJson()
                ->get($url);

            // If the API returns non-200, throw exception
            $response->throw();

            return $this->handleSync( $response->json() );

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Handles cURL errors like timeout, DNS failure (cURL 28)
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            // Handles other unexpected exceptions
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function handleSync($data){
        $data = collect($data);
        foreach($data as $item){
            $item = (object)$item;
            
            $this->createProduct($item);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product has been successfully sync'
        ]);
    }

    public function createProduct($item){
        $variants = collect($item->variants);
        $variantJson = [];

        foreach($variants as $variant){
            $variantJson = $this->getVariantJson($item);
            $storeIds = $variantJson->pluck('store_id')
                ->filter()
                ->unique()
                ->values();

            foreach($storeIds as $storeId){

                foreach($variantJson as $detail){

                    if(!isset($detail['store_id']) || ($detail['store_id'] !== $storeId)) continue;
                    
                    $product = Product::updateOrCreate([
                        'sarinah_product_id' => $item->productId
                    ],
                    [
                        'name' => $item->productName,
                        'store_id' => $storeId,
                        'slug' => generateSlug([$item->productName]),
                        'category_id' => null,
                        'sub_category_id' => null,
                        'brand_id' => null,
                        'is_active' => true,
                        'is_bestseller' => false,
                        'tags' => '',
                        'options' => $this->getOptionJson($item)
                    ]);

                    ProductVariant::updateOrCreate(
                        [
                            'sarinah_variant_id' => $detail['variant_id']
                        ], // match by id if exists
                        [
                            'store_id' => $storeId,
                            'product_id' => $product->id,
                            'slug' => generateSlug([$product->name, $detail['name']]),
                            'combination' => $detail['combination'],
                            'sku' => null,
                            'quantity' => $detail['stock'],
                            'price' => $detail['sellPrice']
                        ]
                    );
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Product has been sync successfully'
        ]);
    }

    public function getVariantJson($item)
    {
        // Product option lookup (cached in-memory)
        $productOptionValues = ProductOptionValue::with('translations')
            ->get()
            ->map(function ($opt) {
                $en = optional($opt->translations->firstWhere('locale', 'en'))->name;
                $id = optional($opt->translations->firstWhere('locale', 'id'))->name;

                return [
                    'id'            => $opt->id,
                    'name_en'       => strtolower($en),
                    'name_id'       => strtolower($id),
                    'original_name' => $en,
                ];
            })
            ->unique('name_en')
            ->values();

        // Store lookup (cached)
        $storeLookup = Store::with('masterLocation')
            ->get()
            ->pluck('id', 'masterLocation.location_path_api');

        return collect($item->variants)
            ->flatMap(function ($variant) use ($productOptionValues, $storeLookup) {

                /** -------------------------
                 * Build attribute combination
                 * ------------------------*/
                $ids   = null;
                $names = [];

                foreach ($variant['attributes'] as $attr) {
                    if ($attr['value'] === 'n/a') continue;

                    $value = strtolower($attr['value']);

                    $option = $productOptionValues->first(
                        fn ($opt) => $opt['name_en'] === $value || $opt['name_id'] === $value
                    );

                    if (!$option) {
                        $data = [
                            'name'      => $attr['attribute'],
                            'type'      => 'text',
                            'is_active' => true
                        ];

                        foreach(['en', 'id'] as $locale){
                            $productOption = ProductOption::createOrUpdateByName($data);

                            $option = ProductOptionValue::createOrUpdateByName(
                                $productOption,
                                [ 'name' => $attr['value'] ]
                            );
                        }
                    }

                    $ids[]   = $option['id'];
                    $names[] = $option['original_name'];
                }

                $base = [
                    'variant_id' => $variant['variantId'],
                    'combination'=> $ids,
                    'name'       => implode(' / ', $names),
                ];

                /** -------------------------
                 * Map rows (locations)
                 * ------------------------*/
                return collect($variant['rows'])
                    ->map(function ($row) use ($base, $storeLookup) {
                        if (! $storeLookup->has($row['locationName'])) {
                            return null;
                        }

                        return array_merge($base, [
                            'store_id'  => $storeLookup[$row['locationName']],
                            'stock'     => $row['stock'],
                            'sellPrice' => $row['sellPrice'],
                        ]);
                    })
                    ->filter();
            })
            ->values();
    }


    public function getOptionJson($item){
        $optionJson = [];

        $variants = collect($item->variants);
        $uniqueAttribute = collect($variants)
            ->pluck('attributes')
            ->flatten(1)
            ->pluck('attribute')
            ->unique()
            ->values();
        $uniqueValue = collect($variants)
            ->pluck('attributes')
            ->flatten(1)
            ->pluck('value')
            ->unique()
            ->values();

        $options = ProductOption::query()
            ->whereHas('translations', function($query) use($uniqueAttribute) {
                $query->whereIn('name', $uniqueAttribute);
            })
            ->get()
            ->map(function($option){
                return [
                    'id' => $option->id,
                    'name' => $option->name,
                ];
            });

        $optionValues = ProductOptionValue::query()
            ->whereHas('translations', function($query) use($uniqueValue) {
                $query->whereIn('name', $uniqueValue);
            })
            ->get()
            ->map(function($value){
                return [
                    'id' => $value->id,
                    'name' => $value->name,
                    'option_id' => $value->product_option_id
                ];
            });
        
        foreach($options as $option){
            $optionValueJson = [];
            $values = $optionValues->where('option_id', $option['id'])->values();
            
            foreach($values as $key => $value){
                $optionValueJson[] = [
                    "id" => "",
                    "order" => $key,
                    "name_en" => $value['name'],
                    "value_id" => $value['id']
                ];
            }

            $optionJson[] = [
                'option' => $option['id'],
                'option_text' => $option['name'],
                'values' => $optionValueJson
            ];
        }

        return $optionJson;
    }
}