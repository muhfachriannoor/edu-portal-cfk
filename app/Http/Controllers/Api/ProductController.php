<?php

namespace App\Http\Controllers\Api;

use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use App\Models\NotifyMe;
use App\View\Data\BrandData;
use Illuminate\Http\Request;
use App\Models\ProductOption;
use Illuminate\Http\Response;
use App\Models\ProductVariant;
use App\View\Data\ProductData;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use App\Models\ProductOptionValue;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserAddressRequest;
use Illuminate\Pagination\LengthAwarePaginator;


/**
 * @OA\Schema(
 *      schema="Product",
 *      title="Product",
 *      description="Product object.",
 *      
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="receiver_name", type="string", example="Natalie Wiyoko"),
 *      @OA\Property(property="phone_number", type="string", example="6285111233345"),
 *      @OA\Property(property="label", type="string", example="Home"),
 *      @OA\Property(property="address_line_1", type="string", example="126 University PDR, Central Park"),
 *      @OA\Property(property="district", type="string", example="Palmerah"),
 *      @OA\Property(property="city", type="string", example="West Jakarta"),
 *      @OA\Property(property="is_default", type="boolean", example=true),
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *      path="/featured-product",
     *      operationId="getFeaturedProduct",
     *      tags={"Product"},
     *      summary="Retrieve featured products randomly.",
     *      description="You can pass ?limit=10 to control how many products are returned.",
     *
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          required=false,
     *          description="Number of products to return",
     *          @OA\Schema(type="integer", example=10)
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Featured products retrieved successfully.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Products successfully retrieved."),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="product_name", type="string", example="Product"),
     *                      @OA\Property(property="product_slug", type="string", example="product"),
     *                      @OA\Property(property="store_name", type="string", example="Store"),
     *                      @OA\Property(property="brand_name", type="string", example="Brand"),
     *                      @OA\Property(property="image_url", type="string", example="http://url/image.jpg"),
     *                      @OA\Property(property="price", type="string", example="100.000"),
     *                      @OA\Property(property="rating", type="number", format="float", example=4.8),
     *                      @OA\Property(property="total_sold", type="integer", example=100),
     *                      @OA\Property(property="has_discount", type="boolean", example=false),
     *                      @OA\Property(property="discount_percentage", type="number", format="float", example=50),
     *                      @OA\Property(property="is_wishlist", type="boolean", example=false),
     *                      @OA\Property(property="is_bestseller", type="boolean", example=false),
     *                  )
     *              )
     *          )
     *      )
     * )
     */

    public function featured(): JsonResponse
    {
        $limit = request()->get('limit') ?? 10;
        $products = ProductData::listsForApi();

        $randomProducts = $products->count() > $limit 
            ? $products->random($limit) 
            : $products->shuffle();

        return response()->json([
            'success' => true,
            'message' => 'Products successfully retrieved.',
            'data' => $randomProducts,
        ], Response::HTTP_OK);
    }
    
    /**
     * @OA\Get(
     *      path="/detail-product/{slug}",
     *      operationId="getProductDetail",
     *      tags={"Product"},
     *      summary="Retrieve product details by slug.",
     *
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Product slug",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     * 
     *      @OA\Parameter(
     *          name="store_id",
     *          in="query",
     *          description="Optional: Filter product by specific store ID",
     *          required=false,
     *          @OA\Schema(type="integer", example=1)
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Product detail successfully retrieved.",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Product detail successfully retrieved."),
     *
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *
     *                  @OA\Property(
     *                      property="store_lists",
     *                      type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="store_id", type="integer", example=1),
     *                          @OA\Property(property="store_name", type="string", example="Sarinah Thamrin")
     *                      )
     *                  ),
     *
     *                  @OA\Property(
     *                      property="store",
     *                      type="object",
     *                      @OA\Property(property="store_id", type="integer", example=1),
     *                      @OA\Property(property="store_name", type="string", example="Unictive"),
     *                      @OA\Property(property="store_rating", type="string", example="4.8"),
     *                      @OA\Property(property="is_pickup", type="boolean", example=false),
     *                      @OA\Property(property="is_delivery", type="boolean", example=false),
     *                  ),
     *
     *                  @OA\Property(
     *                      property="brand",
     *                      type="object",
     *                      @OA\Property(property="brand_id", type="integer", example=1),
     *                      @OA\Property(property="brand_name", type="string", example="Batik"),
     *                      @OA\Property(property="brand_image", type="string", example="http://url/no_image.jpg"),
     *                  ),
     *
     *                  @OA\Property(
     *                      property="product",
     *                      type="object",
     *                      @OA\Property(property="id", type="integer", example=25),
     *                      @OA\Property(property="product_name", type="string", example="Batik LIMITED"),
     *                      @OA\Property(property="product_slug", type="string", example="batik-limited"),
     *                      @OA\Property(property="store_name", type="string", example="Unictive"),
     *                      @OA\Property(property="image_url", type="string", example="http://url/no_image.jpg"),
     *                      @OA\Property(property="price", type="string", example="100000"),
     *                      @OA\Property(property="rating", type="string", example="4.8"),
     *                      @OA\Property(property="total_sold", type="integer", example=100),
     *                      @OA\Property(property="has_discount", type="boolean", example=false),
     *                      @OA\Property(property="discount_percentage", type="string", example="0%"),
     *                      @OA\Property(property="new_price", type="string", example="0"),
     *                      @OA\Property(property="is_wishlist", type="boolean", example=true),
     *                      @OA\Property(property="is_bestseller", type="boolean", example=false)
     *                  ),
     *
     *                  @OA\Property(
     *                      property="images",
     *                      type="array",
     *                      @OA\Items(type="string", example="http://url/product.jpg")
     *                  ),
     *
     *                  @OA\Property(
     *                      property="options",
     *                      type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="name", type="string", example="Size"),
     *                          @OA\Property(property="type", type="string", example="text"),
     *                          @OA\Property(property="preview", type="string", nullable=true, example="http://url/preview.png"),
     *
     *                          @OA\Property(
     *                              property="list",
     *                              type="array",
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="id", type="integer", example=1),
     *                                  @OA\Property(property="name", type="string", example="S"),
     *
     *                                  @OA\Property(
     *                                      property="options",
     *                                      type="array",
     *                                      description="Partner combinations",
     *                                      @OA\Items(
     *                                          type="object",
     *                                          @OA\Property(property="id", type="integer", example=7),
     *                                          @OA\Property(property="name", type="string", example="Black"),
     *                                          @OA\Property(property="quantity", type="integer", example=100),
     *                                          @OA\Property(property="price", type="integer", example=100000),
     *                                          @OA\Property(property="new_price", type="integer", example=0),
     *                                          @OA\Property(property="has_discount", type="boolean", example=false),
     *                                          @OA\Property(property="discount_percentage", type="string", example="0%")
     *                                      )
     *                                  )
     *                              )
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(response=404, description="Product not found")
     * )
     */

    public function detail($slug): JsonResponse
    {
        $locale = app()->getLocale();
        $productsQuery = Product::query()
            ->where('is_active', true)
            ->where('slug', $slug);

        $stores = $productsQuery->get()->map(function($item){
            return [
                'store_id'  => $item->store?->id,
                'store_name'=> $item->store?->name
            ];
        });
        $product = $productsQuery            
            ->when(request('store_id'), function($query){
                $query->where('store_id', request('store_id'));
            })
            ->first(); // first product


        if(!$product)
            return response()->json([
                'success' => false,
                'message' => "Product not found"
            ], Response::HTTP_NOT_FOUND);

        $response = [
            'store_lists' => $stores,
            'store' => [
                'store_id'      => $product->store?->id,
                'store_name'    => $product->store?->name,
                'store_rating'  => '4.8',
                'store_image'   => $product->store?->file_url,
                'is_pickup'     => $product->store?->is_pickup,
                'is_delivery'   => $product->store?->is_delivery,
            ],
            'brand' => [
                'brand_id' => $product->brand?->id,
                'brand_name' => $product->brand?->name,
                'brand_image' => $product->brand?->file_url, // Ambil dari brand
            ],
            'product' => [
                'id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'store_name' => $product->store?->name,
                'image_url' => $product->default_image,
                'price' => number_format( $product->price, "0", ",", "."),

                // Price, Quantity
                'quantity' => $product->firstVariant->quantity,
                'is_lowstock' => ($product->quantity < 10),
                'has_discount' => (bool)$product->firstVariant->hasDiscount,
                'discount_percentage' => (string)$product->firstVariant->discountPercentage . "%",
                'new_price' => $product->firstVariant->newPrice,

                // Story, Material, Fiture
                'story' => $product->translation($locale)->additional['story'] ?? '',
                'material' => $product->translation($locale)->additional['material'] ?? '',
                'fiture' => $product->features->map(function($feature) use($locale) {
                    return [
                        'image' => $feature->file_url,
                        'text' => $feature->translation($locale)->additional['text']
                    ];
                }),

                // disiapkan dulu, belum integrasi
                'rating' => "4.8",
                'total_sold' => 100,
                'is_wishlist' => (bool) rand(0, 1),
                'is_bestseller' => (bool) rand(0, 1),
            ],
            'images' => $product->file_urls,
            'variants' => $this->buildVariantTree($product->options, $product->variants),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Product detail successfully retrieved.',
            'data' => $response,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/product-category/{slug}",
     *     operationId="getProductsByCategory",
     *     tags={"Product"},
     *     summary="Get products by category slug with optional subcategory filtering and pagination.",
     *     description="Returns products in the given category slug. 
     *                  Optional query parameters 'subcategory', 'page', and 'per_page' can be used.",
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Category slug (e.g. `fashion`).",
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="subcategory",
     *         in="query",
     *         required=false,
     *         description="Filter by subcategory slug (e.g. `men-s-wear`).",
     *         @OA\Schema(type="string", example="men-s-wear")
     *     ),
     *
     *     @OA\Parameter(
     *         name="is_bestseller",
     *         in="query",
     *         required=false,
     *         description="Filter products marked as bestseller. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Parameter(
     *         name="is_truly_indonesian",
     *         in="query",
     *         required=false,
     *         description="Filter products marked as truly Indonesian. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Parameter(
     *         name="is_limited_edition",
     *         in="query",
     *         required=false,
     *         description="Filter products marked as limited_edition. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Parameter(
     *         name="is_discount",
     *         in="query",
     *         required=false,
     *         description="Filter products marked as discount. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Parameter(
     *         name="method",
     *         in="query",
     *         required=false,
     *         description="Filter products by fulfillment method. Supports multiple values separated by comma (e.g., `pickup`, `delivery`, or `pickup,delivery`).",
     *         @OA\Schema(type="string", example="pickup,delivery")
     *     ),
     *
     *     @OA\Parameter(
     *         name="brand_ids",
     *         in="query",
     *         required=false,
     *         description="Filter by brand ID.",
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *
     *     @OA\Parameter(
     *         name="store_ids",
     *         in="query",
     *         required=false,
     *         description="Filter by store ID.",
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *
     *     @OA\Parameter(
     *         name="new_this_month",
     *         in="query",
     *         required=false,
     *         description="Return only products created in the current month. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort products by a specific rule. Available values: `newest`, `price_low_to_high`, `price_high_to_low`.",
     *         @OA\Schema(type="string", example="newest")
     *     ),
     *
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         required=false,
     *         description="Filter products by price range based on min_price. Examples: `10000`.",
     *         @OA\Schema(type="int", example=10000)
     *     ),
     *
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         required=false,
     *         description="Filter products by price range based on max_price. Examples: `99999`.",
     *         @OA\Schema(type="int", example=99999)
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination (default: 1).",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Items per page (default: 10). *Note: backend currently uses fixed 10.*",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of products retrieved successfully with pagination.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product detail successfully retrieved."),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 description="Pagination metadata",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="to", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="first_page_url", type="string", example="http://api.example.com/product-category/fashion?page=1"),
     *                 @OA\Property(property="last_page_url", type="string", example="http://api.example.com/product-category/fashion?page=3"),
     *                 @OA\Property(property="next_page_url", type="string", example="http://api.example.com/product-category/fashion?page=2"),
     *                 @OA\Property(property="prev_page_url", type="string", example=null)
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=25),
     *                     @OA\Property(property="category", type="string", example="fashion"),
     *                     @OA\Property(property="subcategory", type="string", example="fashion"),
     *                     @OA\Property(property="product_name", type="string", example="Batik LIMITED"),
     *                     @OA\Property(property="product_slug", type="string", example="batik-limited"),
     *                     @OA\Property(property="store_name", type="string", example="Unictive"),
     *                     @OA\Property(property="image_url", type="string", example="http://cms-sarinah.wsl.local/assets/img/custom/no_image.jpg"),
     *                     @OA\Property(property="price", type="string", example="150.000"),
     *                     @OA\Property(property="rating", type="string", example="4.8"),
     *                     @OA\Property(property="total_sold", type="integer", example=100),
     *                     @OA\Property(property="has_discount", type="boolean", example=false),
     *                     @OA\Property(property="discount_percentage", type="string", example="00.00"),
     *                     @OA\Property(property="new_price", type="string", example="150.000"),
     *                     @OA\Property(property="is_wishlist", type="boolean", example=false),
     *                     @OA\Property(property="is_bestseller", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Category not found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Category not found.")
     *         )
     *     )
     * )
     */
    public function category($slug)
    {
        $category = Category::where('slug', $slug)->first();
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $products = ProductData::listsFromCategoryApi($category->id)
            ->when(request('sub_category'), function ($collection) {
                return $collection->filter(fn($item) => $item['subcategory'] == request('sub_category'));
            })
            ->when(request('is_bestseller'), function($collection){
                return $collection->filter(fn($item) => $item['is_bestseller'] === true);
            })
            ->when(request('is_truly_indonesian'), function($collection){
                return $collection->filter(fn($item) => $item['is_truly_indonesian'] === true);
            })
            ->when(request('is_limited_edition'), function($collection){
                return $collection->filter(fn($item) => $item['is_limited_edition'] === true);
            })
            ->when(request('is_discount'), function($collection){
                return $collection->filter(fn($item) => $item['has_discount'] === true);
            })
            ->when(request('brand_ids'), function($collection){
                $brandIds = array_map('intval', explode(',', request('brand_ids')));
                return $collection->filter(fn($item) => in_array((int) $item['brand_id'], $brandIds, true));
            })
            ->when(request('store_ids'), function($collection){
                $storeIds = array_map('intval', explode(',', request('store_ids')));
                return $collection->filter(fn($item) => in_array((int) $item['store_id'], $storeIds, true));
            })
            ->when(request('new_this_month'), function($collection){
                return $collection->filter(fn($item) => !empty($item['created_at']) && Carbon::parse($item['created_at'])->format('m') === date('m'));
            })
            ->when(request('method'), function ($collection) {
                $methods = explode(',', request('method'));
                return $collection->filter(function ($item) use ($methods) {
                    $matchDelivery = in_array('delivery', $methods) && (bool)$item['is_delivery'];
                    $matchPickup = in_array('pickup', $methods) && (bool)$item['is_pickup'];
                    return $matchDelivery || $matchPickup;
                });
            })
            ->when(request('min_price') || request('max_price'), function ($collection) {
                $min = request('min_price'); $max = request('max_price');
                return $collection->filter(function ($item) use ($min, $max) {
                    $price = (int) str_replace('.', '', $item['new_price']);
                    if (!is_null($min) && $price < $min) return false;
                    if (!is_null($max) && $price > $max) return false;
                    return true;
                });
            })

            // --- TAHAP GROUPING PRIORITAS (SEBELUM SORTING & PAGINATION) ---
            ->when(true, function ($collection) {
                // Grouping berdasarkan slug produk
                return $collection->groupBy('product_slug')->map(function ($group) {
                    // Cari apakah ada varian dari Thamrin di grup slug ini
                    $priorityThamrin = $group->first(function ($item) {
                        return $item['location_path'] === 'THA/Stock/Thamrin';
                    });

                    // Jika ada Thamrin pilih itu, jika tidak ada pilih data pertama yang tersedia
                    return $priorityThamrin ?: $group->first();
                });
            })

            // --- TAHAP SORTING (Dilakukan setelah grouping agar akurat) ---
            ->when(request('sort_by'), function ($collection) {
                $sortBy = request('sort_by');
                return match ($sortBy) {
                    'newest' => $collection->sortByDesc(fn($item) => Carbon::parse($item['created_at'])),
                    'price_low_to_high' => $collection->sortBy(fn($item) => (int) str_replace('.', '', $item['new_price'])),
                    'price_high_to_low' => $collection->sortByDesc(fn($item) => (int) str_replace('.', '', $item['new_price'])),
                    default => $collection,
                };
            })
            ->values();
        
        // Pagination
        $perPage = 10;
        $page = request()->get('page', 1);
        $total = $products->count();

        $paginator = new LengthAwarePaginator(
            $products->forPage($page, $perPage),
            $total, $perPage, $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $paginatorArray = $paginator->toArray();
        $meta = $paginatorArray;
        unset($meta['data']);

        return response()->json([
            'success' => true,
            'message' => 'Product list by category successfully retrieved.',
            'meta' => $meta,
            'data' => array_values($paginatorArray['data'])
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/notify-me",
     *     operationId="notifyMe",
     *     tags={"Product"},
     *     summary="Register Notify Me for an out-of-stock product variant",
     *     description="Registers a notify-me alert for the authenticated user based on selected product variant options.",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "option_value_ids"},
     *             @OA\Property(
     *                 property="product_id",
     *                 type="integer",
     *                 example=12,
     *                 description="ID of the product"
     *             ),
     *             @OA\Property(
     *                  property="option_value_ids", 
     *                  type="array", @OA\Items(type="integer"), 
     *                  example={1, 5}, 
     *                  description="Array of selected product_option_value IDs (e.g., Color ID, Size ID)."
     *              ),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Notify successfully registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notify success.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - product not found, multiple variants selected, or already registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No product selected.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - missing or invalid token"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */

    public function notify_me(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Helper for error responses
        $error = fn($msg) => response()->json([
            'success' => false,
            'message' => $msg
        ], Response::HTTP_BAD_REQUEST);

        // Convert option IDs into array
        $options = $request->option_value_ids;

        // Build query
        $query = ProductVariant::where('product_id', $request->product_id);

        foreach ($options as $opt) {
            $query->whereJsonContains('combination', $opt);
        }

        // Get all variants matching the filters
        $variants = $query->get();

        // Validate variant count
        if ($variants->isEmpty()) {
            return $error('No product selected.');
        }

        if ($variants->count() > 1) {
            return $error('Please select one product to continue.');
        }

        // Single variant found
        $variant = $variants->first();

        // Check if notify request already exists
        $alreadyNotified = NotifyMe::where([
            'user_id' => $user->id,
            'variant_id' => $variant->id,
            'notified' => false
        ])->exists();

        if (!$alreadyNotified) {
            // return $error('This product variant has already been registered in your notify list.');

            // Create notify-me record
            NotifyMe::create([
                'user_id' => $user->id,
                'variant_id' => $variant->id,
                'notified' => false
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notify success.'
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/recommendation-product",
     *      operationId="getRecommendationProduct",
     *      tags={"Product"},
     *      summary="Retrieve recommendation products randomly.",
     *      description="You can pass ?limit=10 to control how many products are returned.",
     *      security={{"bearerAuth": {}}},
     *
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          required=false,
     *          description="Page number for pagination (default: 1).",
     *          @OA\Schema(type="integer", example=1)
     *      ),
     *
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          required=false,
     *          description="Items per page (default: 10). *Note: backend currently uses fixed 10.*",
     *          @OA\Schema(type="integer", example=10)
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Recommendation products retrieved successfully.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Products successfully retrieved."),
     *              @OA\Property(
     *                  property="meta",
     *                  type="object",
     *                  description="Pagination metadata",
     *                  @OA\Property(property="current_page", type="integer", example=1),
     *                  @OA\Property(property="from", type="integer", example=1),
     *                  @OA\Property(property="last_page", type="integer", example=3),
     *                  @OA\Property(property="per_page", type="integer", example=10),
     *                  @OA\Property(property="to", type="integer", example=10),
     *                  @OA\Property(property="total", type="integer", example=25),
     *                  @OA\Property(property="first_page_url", type="string", example="http://api.example.com/product-category/fashion?page=1"),
     *                  @OA\Property(property="last_page_url", type="string", example="http://api.example.com/product-category/fashion?page=3"),
     *                  @OA\Property(property="next_page_url", type="string", example="http://api.example.com/product-category/fashion?page=2"),
     *                  @OA\Property(property="prev_page_url", type="string", example=null)
     *              ),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="product_name", type="string", example="Product"),
     *                      @OA\Property(property="product_slug", type="string", example="product"),
     *                      @OA\Property(property="store_name", type="string", example="Store"),
     *                      @OA\Property(property="brand_name", type="string", example="Brand"),
     *                      @OA\Property(property="image_url", type="string", example="http://url/image.jpg"),
     *                      @OA\Property(property="price", type="string", example="100.000"),
     *                      @OA\Property(property="rating", type="number", format="float", example=4.8),
     *                      @OA\Property(property="total_sold", type="integer", example=100),
     *                      @OA\Property(property="has_discount", type="boolean", example=false),
     *                      @OA\Property(property="discount_percentage", type="number", format="float", example=50),
     *                      @OA\Property(property="is_wishlist", type="boolean", example=false),
     *                      @OA\Property(property="is_bestseller", type="boolean", example=false),
     *                  )
     *              )
     *          )
     *      )
     * )
     */

    public function recommendation()
    {
        $user = auth()->user();
        $interests = count($user->interests) > 0 ? $user->interests->pluck('id')->toArray() : [];            

        $products = ProductData::listsForApiRecommendation($user, $interests);

        // Pagination parameters
        $perPage = request()->get('per_page', 10);; // items per page
        $page = request()->get('page', 1); // current page
        $total = $products->count();

        $paginator = new LengthAwarePaginator(
            $products->forPage($page, $perPage),
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        $paginatorArray = $paginator->toArray();
        $meta = $paginatorArray;
        unset($meta['data']);

        return response()->json([
            'success' => true,
            'message' => 'Recommended products successfully retrieved.',
            'meta' => $meta,
            'data' => array_values($paginatorArray['data']),
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/bestseller-product",
     *      operationId="getBestSellerProduct",
     *      tags={"Product"},
     *      summary="Retrieve bestseller products randomly.",
     *      description="You can pass ?limit=10 to control how many products are returned.",
     *
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          required=false,
     *          description="Number of products to return",
     *          @OA\Schema(type="integer", example=10)
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Bestseller products retrieved successfully.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Products successfully retrieved."),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="product_name", type="string", example="Product"),
     *                      @OA\Property(property="product_slug", type="string", example="product"),
     *                      @OA\Property(property="store_name", type="string", example="Store"),
     *                      @OA\Property(property="brand_name", type="string", example="Brand"),
     *                      @OA\Property(property="image_url", type="string", example="http://url/image.jpg"),
     *                      @OA\Property(property="price", type="string", example="100.000"),
     *                      @OA\Property(property="rating", type="number", format="float", example=4.8),
     *                      @OA\Property(property="total_sold", type="integer", example=100),
     *                      @OA\Property(property="has_discount", type="boolean", example=false),
     *                      @OA\Property(property="discount_percentage", type="number", format="float", example=50),
     *                      @OA\Property(property="is_wishlist", type="boolean", example=false),
     *                      @OA\Property(property="is_bestseller", type="boolean", example=false),
     *                  )
     *              )
     *          )
     *      )
     * )
     */

    public function bestseller(): JsonResponse
    {
        $limit = request()->get('limit') ?? 10;
        $products = ProductData::listsForApiBestSeller();

        $randomProducts = $products->count() > $limit 
            ? $products->random($limit) 
            : $products->shuffle();

        return response()->json([
            'success' => true,
            'message' => 'Bestseller products successfully retrieved.',
            'data' => array_values($randomProducts->toArray()),
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/search-product",
     *      operationId="getSearchProduct",
     *      tags={"Product"},
     *      summary="Retrieve search products and stores by keyword.",
     *      description="You can pass ?keyword=product_name.",
     *
     *      @OA\Parameter(
     *          name="keyword",
     *          in="query",
     *          required=false,
     *          description="Name of products to return",
     *          @OA\Schema(type="string", example="baju batik")
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Products and stores retrieved successfully.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Products successfully retrieved."),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(
     *                      property="keyword",
     *                      type="object",
     *                      @OA\Property(property="label", type="string", example="Recent Searches"),
     *                      @OA\Property(
     *                          property="items",
     *                          type="array",
     *                          @OA\Items(type="string", example="baju batik")
     *                      )
     *                  ),
     *                  @OA\Property(
     *                      property="product",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="product_name", type="string", example="Batik HQ"),
     *                          @OA\Property(property="price", type="string", example="100.000"),
     *                          @OA\Property(property="new_price", type="string", example="50.000"),
     *                          @OA\Property(property="discount_percentage", type="string", example="50%"),
     *                          @OA\Property(property="has_discount", type="boolean", example=true),
     *                          @OA\Property(property="slug", type="string", example="batik-hq"),
     *                          @OA\Property(property="image_url", type="string", example="http://cms-sarinah.wsl.local/storage/product/8dlgzsWu43n9OrP4pA9NNY2Q9H5uDVEik4z0CCAt.jpg")
     *                      )
     *                  ),
     *                  @OA\Property(
     *                      property="brands",
     *                      type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="name", type="string", example="Unictive Media"),
     *                          @OA\Property(property="slug", type="string", example="unictive-media"),
     *                          @OA\Property(property="image_url", type="string", example="http://cms-sarinah.wsl.local/storage/store/CI5IUN7rODKS6qocnodB8ywiV2j5HGYfRiJIKgeM.png")
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function search()
    {
        $keyword = request()->get('keyword');
        $brands = BrandData::listsForApiKeyword($keyword);
        $products = ProductData::listsForApiKeyword($keyword);
        
        return response()->json([
            'success' => true,
            'message' => 'Search results retrieved successfully.',
            'data' => [[
                'label' => 'Products',
                'items' => $products->take(5)
            ],
            [
                'label' => 'Brands',
                'items' => $brands->take(5)
            ]],
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/search-product-result",
     *      operationId="getSearchProductResult",
     *      tags={"Product"},
     *      summary="Retrieve search product results.",
     *      description="Search products by keyword or brand.",
     *
     *     @OA\Parameter(
     *         name="subcategory",
     *         in="query",
     *         required=false,
     *         description="Filter by subcategory slug (e.g. `men-s-wear`).",
     *         @OA\Schema(type="string", example="men-s-wear")
     *     ),
     *
     *     @OA\Parameter(
     *         name="is_bestseller",
     *         in="query",
     *         required=false,
     *         description="Filter products marked as bestseller. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Parameter(
     *         name="is_truly_indonesian",
     *         in="query",
     *         required=false,
     *         description="Filter products marked as truly Indonesian. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Parameter(
     *         name="is_limited_edition",
     *         in="query",
     *         required=false,
     *         description="Filter products marked as limited_edition. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Parameter(
     *         name="is_discount",
     *         in="query",
     *         required=false,
     *         description="Filter products marked as discount. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Parameter(
     *         name="is_pickup",
     *         in="query",
     *         required=false,
     *         description="Filter products store's can pickup. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Parameter(
     *         name="is_delivery",
     *         in="query",
     *         required=false,
     *         description="Filter products store's can delivery. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Parameter(
     *         name="brand_ids",
     *         in="query",
     *         required=false,
     *         description="Filter by brand ID.",
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *
     *     @OA\Parameter(
     *         name="store_ids",
     *         in="query",
     *         required=false,
     *         description="Filter by store ID.",
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *
     *     @OA\Parameter(
     *         name="new_this_month",
     *         in="query",
     *         required=false,
     *         description="Return only products created in the current month. Use `1` to enable.",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort products by a specific rule. Available values: `newest`, `price_low_to_high`, `price_high_to_low`.",
     *         @OA\Schema(type="string", example="newest")
     *     ),
     *
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         required=false,
     *         description="Filter products by price range based on min_price. Examples: `10000`.",
     *         @OA\Schema(type="int", example=10000)
     *     ),
     *
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         required=false,
     *         description="Filter products by price range based on max_price. Examples: `99999`.",
     *         @OA\Schema(type="int", example=99999)
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination (default: 1).",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Items per page (default: 10). *Note: backend currently uses fixed 10.*",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of products retrieved successfully with pagination.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product detail successfully retrieved."),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 description="Pagination metadata",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="to", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="first_page_url", type="string", example="http://api.example.com/product-category/fashion?page=1"),
     *                 @OA\Property(property="last_page_url", type="string", example="http://api.example.com/product-category/fashion?page=3"),
     *                 @OA\Property(property="next_page_url", type="string", example="http://api.example.com/product-category/fashion?page=2"),
     *                 @OA\Property(property="prev_page_url", type="string", example=null)
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=25),
     *                     @OA\Property(property="category", type="string", example="fashion"),
     *                     @OA\Property(property="subcategory", type="string", example="fashion"),
     *                     @OA\Property(property="product_name", type="string", example="Batik LIMITED"),
     *                     @OA\Property(property="product_slug", type="string", example="batik-limited"),
     *                     @OA\Property(property="store_name", type="string", example="Unictive"),
     *                     @OA\Property(property="image_url", type="string", example="http://cms-sarinah.wsl.local/assets/img/custom/no_image.jpg"),
     *                     @OA\Property(property="price", type="string", example="150.000"),
     *                     @OA\Property(property="rating", type="string", example="4.8"),
     *                     @OA\Property(property="total_sold", type="integer", example=100),
     *                     @OA\Property(property="has_discount", type="boolean", example=false),
     *                     @OA\Property(property="discount_percentage", type="string", example="00.00"),
     *                     @OA\Property(property="new_price", type="string", example="150.000"),
     *                     @OA\Property(property="is_wishlist", type="boolean", example=false),
     *                     @OA\Property(property="is_bestseller", type="boolean", example=false),
     *                     @OA\Property(property="is_truly_indonesian", type="boolean", example=false),
     *                     @OA\Property(property="is_limited_edition", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Search Result not found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Search result not found.")
     *         )
     *     )
     * )
     */
    public function result()
    {
        $keyword = request('keyword', '');
        $products = ProductData::listsForApiSearchResult($keyword, request()->all());

        if (request('min_price') || request('max_price')) {
            $min = (int) request('min_price');
            $max = (int) request('max_price');
            $products = $products->filter(function ($item) use ($min, $max) {
                $price = (int) str_replace('.', '', $item['new_price']);
                if ($min && $price < $min) return false;
                if ($max && $price > $max) return false;
                return true;
            });
        }

        // Pagination
        $perPage = (int) request()->get('per_page', 10);
        $page = (int) request()->get('page', 1);
        $total = $products->count();

        $paginatedItems = $products->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $paginatedItems,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $meta = $paginator->toArray();
        unset($meta['data']);

        return response()->json([
            'success' => true,
            'message' => 'Product result successfully retrieved.',
            'meta' => $meta,
            'data' => $paginatedItems
        ], Response::HTTP_OK);
    }

    /**
     *  For handle product detail variants value 
     **/ 
    private function buildVariantTree($options, $allVariants, $currentPath = [])
    {
        if (empty($options)) {
            // Base case: no more options, return variant data
            $variant = $allVariants->first(function($variant) use($currentPath) {
                // Find variant that matches all selected option values
                $variantCombination = collect($variant->combination)->sort()->values();
                $currentCombination = collect($currentPath)->sort()->values();
                
                return $variantCombination->diff($currentCombination)->isEmpty() && 
                    $currentCombination->diff($variantCombination)->isEmpty();
            });
            
            return $variant ? [
                'quantity' => $variant->quantity,
                'price' => number_format($variant->price, "0", ",", "."),
                'new_price' => $variant->newPrice,
                'has_discount' => (bool)$variant->hasDiscount,
                'discount_percentage' => (string)$variant->discountPercentage . "%",
                'is_lowstock' => ($variant->quantity < 10)
            ] : null;
        }
        
        // Get current option (first in array)
        $currentOption = array_shift($options);
        $option = ProductOption::find($currentOption['option']);
        
        $result = [
            'name' => $option->name,
            'type' => $option->type,
            'preview' => $option->file_url,
            'options' => []
        ];
        
        foreach ($currentOption['values'] as $valueData) {
            $optionValue = ProductOptionValue::find($valueData['value_id']);
            
            $optionNode = [
                'id' => $optionValue->id,
                'name' => $optionValue->name,
                'value' => $optionValue->value
            ];
            
            // Add child options recursively
            $newPath = array_merge($currentPath, [$optionValue->id]);
            $childVariants = $this->buildVariantTree($options, $allVariants, $newPath);
            
            if (is_array($childVariants) && isset($childVariants['name'])) {
                // Child is another option level
                $optionNode['variants'] = $childVariants;
            } else {
                // Child is final variant data
                $optionNode = array_merge($optionNode, $childVariants ?? []);
            }
            
            $result['options'][] = $optionNode;
        }
        
        return $result;
    }

    private function emptyMeta()
    {
        return [
            'current_page' => 1, 'from' => null, 'last_page' => 1,
            'per_page' => 10, 'to' => null, 'total' => 0,
            'first_page_url' => request()->url() . '?page=1',
            'last_page_url' => request()->url() . '?page=1',
            'next_page_url' => null, 'prev_page_url' => null,
        ];
    }
}
