<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use App\View\Data\BrandData;
use App\View\Data\StoreData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\View\Data\CourierData;
use App\View\Data\VoucherData;
use App\View\Data\CategoryData;
use App\View\Data\IndoRegionData;
use Illuminate\Http\JsonResponse;
use App\View\Data\SubCategoryData;
use App\Http\Controllers\Controller;
use App\View\Data\MasterAddressData;


/**
 * @OA\Tag(
 *      name="Master Data",
 *      description="Endpoints for retrieving Category and SubCategory information."
 * )
 * 
 * @OA\Schema(
 *      schema="SubCategory",
 *      type="object",
 *      title="SubCategory Model",
 *      
 *      @OA\Property(property="id", type="integer", example=101, description="Unique ID of the subcategory."),
 *      @OA\Property(property="category_id", type="integer", example=1, description="ID of the parent category."),
 *      @OA\Property(property="name", type="string", example="Home Fragrances", description="Name of the subcategory."),
 *      @OA\Property(property="slug", type="string", example="home-fragrances", description="URL-friendly slug."),
 *      @OA\Property(property="description", type="string", example="Brief description of the subcategory.", nullable=true),
 *      @OA\Property(property="order", type="integer", example=1, description="Display order sequence."),
 *      @OA\Property(property="image_url", type="string", format="url", example="http://localhost/storage/sub_category/image.jpg", nullable=true, description="URL to the subcategory image.")
 * ),
 * 
 * @OA\Schema(
 *      schema="Category",
 *      type="object",
 *      title="Category Model",
 *      
 *      @OA\Property(property="id", type="integer", example=1, description="Unique ID of the category."),
 *      @OA\Property(property="name", type="string", example="Home Goods", description="Name of the category"),
 *      @OA\Property(property="slug", type="string", example="home-goods", description="URL-friendly slug."),
 *      @OA\Property(property="description", type="string", example="Goods for the home and living space.", nullable=true),
 *      @OA\Property(property="order", type="integer", example=1, description="Display order sequence."),
 *      @OA\Property(property="image_url", type="string", format="url", example="http://localhost/storage/category/image.jpg", nullable=true, description="URL to the category image."),
 *      @OA\Property(property="is_navbar", type="boolean", example=false)
 * ),
 * 
 * @OA\Schema(
 *      schema="CategoryDetail",
 *      title="Category Detail Model",
 *      
 *      @OA\Property(property="id", type="integer", example=1, description="Unique ID of the category."),
 *      @OA\Property(property="name", type="string", example="Home Goods", description="Name of the category"),
 *      @OA\Property(property="slug", type="string", example="home-goods", description="URL-friendly slug."),
 *      @OA\Property(property="description", type="string", example="Goods for the home and living space.", nullable=true),
 *      @OA\Property(property="order", type="integer", example=1, description="Display order sequence."),
 *      @OA\Property(property="image_url", type="string", format="url", example="http://localhost/storage/category/image.jpg", nullable=true, description="URL to the category image."),
 *      @OA\Property(property="is_navbar", type="boolean", example=false),
 *      @OA\Property(property="meta_title", type="string"),
 *      @OA\Property(property="meta_description", type="string"),
 *      @OA\Property(property="meta_keywords", type="string"),
 *      
 *      @OA\Property(property="sub_categories", type="array", description="List of sub-categories under this category.",
 *          @OA\Items(ref="#/components/schemas/SubCategory")
 *      )
 * ),
 * 
 * @OA\Schema(
 *      schema="Courier",
 *      type="object",
 *      title="Courier Model",
 * 
 *      @OA\Property(property="id", type="string", example="019ac8b6-76b1-72f1-bc60-fb0ae906ff62", description="Unique ID of the courier."),
 *      @OA\Property(property="name", type="string", example="Pick up in the store", description="Name of the courier service."),
 *      @OA\Property(property="description", type="string", example="Courier service for pick-up in store", nullable=true, description="Optional description of the courier.")
 * )
 * 
 * @OA\Schema(
 *      schema="Voucher",
 *      type="object",
 *      title="Voucher Model",
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="code", type="string", example="WELCOME10"),
 *      @OA\Property(
 *          property="title",
 *          type="string",
 *          example="Enjoy Rp 50.000 off! Shop batik, home decor, and more",
 *          description="Localized voucher title / headline."
 *      ),
 *      @OA\Property(
 *          property="type",
 *          type="string",
 *          enum={"percentage", "fixed_amount"},
 *          example="fixed_amount"
 *      ),
 *      @OA\Property(
 *          property="amount",
 *          type="string",
 *          example="Rp 50.000",
 *          description="Formatted discount amount for display."
 *      ),
 *      @OA\Property(
 *          property="amount_raw",
 *          type="integer",
 *          example=50000,
 *          description="Raw discount amount (e.g. 50000 for Rp 50.000, or 10 for 10%)."
 *      ),
 *      @OA\Property(
 *          property="min_transaction_amount",
 *          type="integer",
 *          example=200000,
 *          nullable=true,
 *          description="Minimum order amount (after special price) required to use this voucher."
 *      ),
 *      @OA\Property(
 *          property="min_transaction_amount_label",
 *          type="string",
 *          example="Rp 200.000",
 *          nullable=true
 *      ),
 *      @OA\Property(
 *          property="max_discount_amount",
 *          type="integer",
 *          example=50000,
 *          nullable=true,
 *          description="Maximum discount amount for percentage vouchers (ignored for fixed_amount)."
 *      ),
 *      @OA\Property(
 *          property="max_discount_amount_label",
 *          type="string",
 *          example="Rp 50.000",
 *          nullable=true,
 *          description="Formatted label for maximum discount."
 *      ),
 *      @OA\Property(
 *          property="start_date",
 *          type="string",
 *          format="date-time",
 *          example="2025-11-10 08:00:00"
 *      ),
 *      @OA\Property(
 *          property="end_date",
 *          type="string",
 *          format="date-time",
 *          example="2025-12-26 23:59:59"
 *      ),
 *      @OA\Property(
 *          property="expiration_date_label",
 *          type="string",
 *          example="26/12/2025",
 *          description="Formatted expiration date for UI (dd/mm/YYYY)."
 *      )
 * ),
 * 
 * @OA\Schema(
 *      schema="Store",
 *      type="object",
 *      title="Store Model",
 *      
 *      @OA\Property(property="id", type="integer", example=1, description="Unique ID of the store."),
 *      @OA\Property(property="name", type="string", example="Home Goods", description="Name of the store"),
 *      @OA\Property(property="slug", type="string", example="home-goods", description="URL-friendly slug."),
 *      @OA\Property(property="description", type="string", example="Goods for the home and living space.", nullable=true),
 *      @OA\Property(property="image_url", type="string", format="url", example="http://localhost/storage/category/image.jpg", nullable=true, description="URL to the category image."),
 * ),
 * 
 * @OA\Schema(
 *      schema="Brand",
 *      type="object",
 *      title="Brand Model",
 *      
 *      @OA\Property(property="id", type="integer", example=1, description="Unique ID of the store."),
 *      @OA\Property(property="name", type="string", example="Home Goods", description="Name of the store"),
 *      @OA\Property(property="slug", type="string", example="home-goods", description="URL-friendly slug."),
 *      @OA\Property(property="image_url", type="string", format="url", example="http://localhost/storage/category/image.jpg", nullable=true, description="URL to the category image."),
 * ),
 */
class MasterDataController extends Controller
{
    /**
     * @OA\Get(
     *      path="/master/categories",
     *      operationId="indexCategories",
     *      tags={"Master Data"},
     *      summary="Retrieve a list of all active main categories (Category only)",
     * 
     *      @OA\Parameter(
     *          name="X-Localization",
     *          in="header",
     *          description="The desired language for localized data ('id' or 'en').",
     *          required=false,
     *          @OA\Schema(type="string", default="en", enum={"id", "en"})
     *      ),    
     * 
     *      @OA\Response(
     *          response=200,
     *          description="List of categories successfully retrieved.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Category list retrieved successfully."),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(ref="#/components/schemas/Category")
     *              )
     *          )
     *      )
     * )
     */
    public function indexCategories(): JsonResponse
    {
        $locale = app()->getLocale();
        $data = CategoryData::listsForApi($locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.master_data.category_retrieve_success'),
            'data' => $data,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/master/categories/{slug}",
     *      operationId="showCategoryDetail",
     *      tags={"Master Data"},
     *      summary="Retrieve category detail by slug with SEO and keep-exploring list.",
     * 
     *      @OA\Parameter(
     *          name="X-Localization",
     *          in="header",
     *          description="The desired language for localized data ('id' or 'en').",
     *          required=false,
     *          @OA\Schema(type="string", default="en", enum={"id", "en"})
     *      ),
     * 
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          required=true,
     *          description="Slug of the root category.",
     *          @OA\Schema(type="string", example="fashion")
     *      ),
     * 
     *      @OA\Response(
     *          response=200,
     *          description="Category detail retrieved successfully.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Category detail retrieved successfully."),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="category", ref="#/components/schemas/CategoryDetail"),
     *                  @OA\Property(property="keep_exploring", type="array",
     *                      @OA\Items(type="object",
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string"),
     *                          @OA\Property(property="slug", type="string"),
     *                          @OA\Property(property="image_url", type="string", format="url", nullable=true)
     *                      )
     *                  )
     *              ),
     *          )
     *      ),
     * 
     *      @OA\Response(
     *          response=404,
     *          description="Category not found.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Category not found.")
     *          )
     *      )
     * )
     */
    public function showCategoryDetail(string $slug, Request $request): JsonResponse
    {
        $locale = app()->getLocale();

        // Retrieve root category detail by slug
        $category = CategoryData::detailBySlug($slug, $locale);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => trans('api.master_data.category_not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        $keepExploring = CategoryData::othersForDetail($category['id'], $locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.master_data.category_detail_retrieve_success'),
            'data' => [
                'category' => $category,
                'keep_exploring' => $keepExploring,
            ]
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/master/sub-categories",
     *      operationId="indexSubCategories",
     *      tags={"Master Data"},
     *      summary="Retrieve a list of all active subcategories (Sub Category only)",
     *      description="The list can be optionally filtered by the 'category_id' query parameter.",
     * 
     *      @OA\Parameter(
     *          name="X-Localization",
     *          in="header",
     *          description="The desired language for localized data ('id' or 'en').",
     *          required=false,
     *          @OA\Schema(type="string", default="en", enum={"id", "en"})
     *      ),
     *      
     *      @OA\Parameter(
     *          name="category_id",
     *          in="query",
     *          description="The ID of the main category to filter subcategories.",
     *          required=false,
     *          
     *          @OA\Schema(type="integer", example=1)
     *      ),
     * 
     *      @OA\Response(
     *          response=200,
     *          description="List of subcategories successfully retrieved.",
     * 
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="SubCategory list retrieved successfully"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(ref="#/components/schemas/SubCategory")
     *              )
     *          )
     *      )
     * )
     */
    public function indexSubCategories(): JsonResponse
    {
        $locale = app()->getLocale();
        $categoryId = request('category_id');

        // Method SubCategory (with filter)
        $data = SubCategoryData::listsForApi($categoryId, $locale);

        $message = $categoryId 
                ? "Subcategories filtered by category ID {$categoryId} retrieved successfully." 
                : "Subcategory list retrieved successfully.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/master/categories-with-subs",
     *      operationId="categoriesWithSubCategories",
     *      tags={"Master Data"},
     *      summary="Retrieve all Categories, with their associated SubCategories nested inside.",
     *      description="This provides the complete, hierarchical master data list.",
     * 
     *      @OA\Parameter(
     *          name="X-Localization",
     *          in="header",
     *          description="The desired language for localized data ('id' or 'en').",
     *          required=false,
     *          @OA\Schema(type="string", default="en", enum={"id", "en"})
     *      ),    
     * 
     *      @OA\Response(
     *          response=200,
     *          description="Complete hierarchical master list successfully retrieved.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Full master category list retrieved successfully."),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      allOf={
     *                          @OA\Schema(ref="#/components/schemas/Category"),
     *                          @OA\Schema(
     *                              @OA\Property(
     *                                  property="sub_categories",
     *                                  type="array",
     *                                  description="List of subcategories belonging to this category.",
     *                                  @OA\Items(ref="#/components/schemas/SubCategory")
     *                              )
     *                          )
     *                      }
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function categoriesWithSubCategories(): JsonResponse
    {
        $locale = app()->getLocale();
        $data = CategoryData::listsWithSubCategories($locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.master_data.full_master_category_retrieve_success'),
            'data' => $data
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/master/sub-categories/homepage-featured",
     *      operationId="homepageSubCategories",
     *      tags={"Master Data"},
     *      summary="Retrieve 6 random subcategories with images for the homepage/featured section (cached 5 mins).",
     * 
     *      @OA\Parameter(
     *          name="X-Localization",
     *          in="header",
     *          description="The desired language for localized data ('id' or 'en').",
     *          required=false,
     *          @OA\Schema(type="string", default="en", enum={"id", "en"})
     *      ),
     * 
     *      @OA\Response(
     *          response=200,
     *          description="Featured subcategories retrieved successfully.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Featured subcategories retrieved successfully."),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="slug", type="string"),
     *                      @OA\Property(property="image_url", type="string", format="url")
     *                  )
     *              )
     *          )
     *      ),
     * 
     *      @OA\Response(response=500, description="Server Error")
     * )
     */
    public function homepageSubCategories(): JsonResponse
    {
        $locale = app()->getLocale();

        $data = SubCategoryData::listsForHomepage($locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.master_data.feature_subcategory_retrieve_success'),
            'data' => $data
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/master/master-address",
     *      operationId="indexMasterAddress",
     *      tags={"Master Data"},
     *      summary="Retrieve master address list (country → subdistrict).",
     *      description="The response is cached. Supports keyword search and result limiting.",
     *
     *      @OA\Parameter(
     *          name="keyword",
     *          in="query",
     *          required=false,
     *          description="Search keyword applied to country, province, city, district, or subdistrict name.",
     *          @OA\Schema(
     *              type="string",
     *              example="jakarta"
     *          )
     *      ),
     *
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          required=false,
     *          description="Maximum number of records to return (max: 100).",
     *          @OA\Schema(
     *              type="integer",
     *              default=100,
     *              maximum=100,
     *              example=50
     *          )
     *      ),
     *
     *      @OA\Parameter(
     *          name="id",
     *          in="query",
     *          required=false,
     *          description="Subdistrict id for retrieve the data.",
     *          @OA\Schema(
     *              type="string",
     *              example=""
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Master address list retrieved successfully.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Master address list retrieved successfully"),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="subdistrict_id", type="string", example="RID64626"),
     *                      @OA\Property(property="country_name", type="string", example="Indonesia"),
     *                      @OA\Property(property="province_name", type="string", example="DKI Jakarta"),
     *                      @OA\Property(property="city_name", type="string", example="Jakarta Pusat"),
     *                      @OA\Property(property="district_name", type="string", example="Menteng"),
     *                      @OA\Property(property="subdistrict_name", type="string", example="Gondangdia")
     *                  )
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Server Error or File Not Found"
     *      )
     * )
     */
    public function master_address()
    {
        $id = request('id');
        $keyword = request('keyword');
        $limit = min((int) request('limit', 100), 100);

        $data = MasterAddressData::lists();
        $data = collect($data)
            ->when($id, function($items) use($id){
                return $items->filter(function ($item) use ($id) {
                    return $item['subdistrict_id'] === $id;
                });
            })
            ->when($keyword, function($items) use($keyword){
                $keyword = Str::lower($keyword);

                return $items->filter(function ($item) use ($keyword) {
                    return Str::contains(Str::lower($item['country_name'] ?? ''), $keyword)
                        || Str::contains(Str::lower($item['province_name'] ?? ''), $keyword)
                        || Str::contains(Str::lower($item['city_name'] ?? ''), $keyword)
                        || Str::contains(Str::lower($item['district_name'] ?? ''), $keyword)
                        || Str::contains(Str::lower($item['subdistrict_name'] ?? ''), $keyword);
                });
            })
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'message' => "Master address list retrieved successfully",
            'data' => $data
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/master/couriers",
     *      operationId="indexCouriers",
     *      tags={"Master Data"},
     *      summary="Retrieve a list of all active couriers",
     * 
     *      @OA\Parameter(
     *          name="X-Localization",
     *          in="header",
     *          description="The desired language for localized data ('id' or 'en').",
     *          required=false,
     *          @OA\Schema(type="string", default="en", enum={"id", "en"})
     *      ),
     * 
     *      @OA\Response(
     *          response=200,
     *          description="List of couriers successfully retrieved.",
     * 
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Courier list retrieved successfully."),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(ref="#/components/schemas/Courier")
     *              )
     *          )
     *      )
     * )
     */
    public function indexCouriers(Request $request): JsonResponse
    {
        $locale = app()->getLocale();
        
        $data = CourierData::listsForApi($locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.master_data.couried_retrieve_success'),
            'data' => $data,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/master/vouchers",
     *      operationId="indexVouchers",
     *      tags={"Master Data"},
     *      summary="Retrieve a list of currently available vouchers for the checkout popup.",
     *      description="Returns active vouchers filtered by date range and usage_limit/used_count. Min/max transaction are not filtered here and will be validated when applying a voucher.",
     *
     *      @OA\Parameter(
     *          name="X-Localization",
     *          in="header",
     *          description="The desired language for localized data ('id' or 'en').",
     *          required=false,
     *          @OA\Schema(type="string", default="en", enum={"id", "en"})
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Voucher list successfully retrieved.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Voucher list retrieved successfully."),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(ref="#/components/schemas/Voucher")
     *              )
     *          )
     *      )
     * )
     */
    public function indexVouchers(): JsonResponse
    {
        $locale = app()->getLocale();
        $data   = VoucherData::listsForApi($locale);

        return response()->json([
            'success' => true,
            'message' => trans('api.master_data.voucher_retrieve_success'),
            'data'    => $data,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/master/stores",
     *      operationId="masterStores",
     *      tags={"Master Data"},
     *      summary="Retrieve a list of all stores.",
     *      description="Optionally filter stores by brand_id. Returns brand list with id, name, slug, and image_url.",
     *
     *      @OA\Parameter(
     *          name="store_id",
     *          in="query",
     *          required=false,
     *          description="Filter stores by store ID",
     *          @OA\Schema(type="integer", example=1)
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Store list retrieved successfully.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Store list retrieved successfully."),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(ref="#/components/schemas/Store")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Server Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Server Error")
     *          )
     *      )
     * )
     */
    public function stores(): JsonResponse
    {
        $locale = app()->getLocale();
        $storeId = request('store_id');

        // Method Store (with filter)
        $data = StoreData::listsForApi($storeId, $locale);

        $message = $storeId 
                ? "Store filtered by store ID {$storeId} retrieved successfully." 
                : "Store list retrieved successfully.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/master/brands",
     *      operationId="masterBrands",
     *      tags={"Master Data"},
     *      summary="Retrieve a list of all brands.",
     *      description="Optionally filter brands by brand_id. Returns brand list with id, name, slug, and image_url.",
     *
     *      @OA\Parameter(
     *          name="brand_id",
     *          in="query",
     *          required=false,
     *          description="Filter brands by brand ID",
     *          @OA\Schema(type="integer", example=1)
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Brand list retrieved successfully.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Brand list retrieved successfully."),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(ref="#/components/schemas/Brand")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Server Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Server Error")
     *          )
     *      )
     * )
     */

    public function brands(): JsonResponse
    {
        $locale = app()->getLocale();
        $brandId = request('store_id');

        // Method Store (with filter)
        $data = BrandData::listsForApi($brandId, $locale);

        $message = $brandId 
                ? "Brand filtered by brand ID {$brandId} retrieved successfully." 
                : "Brand list retrieved successfully.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], Response::HTTP_OK);
    }
}
