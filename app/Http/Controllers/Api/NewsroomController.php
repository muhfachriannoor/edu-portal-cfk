<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Newsroom;
use App\View\Data\NewsroomData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *      name="Newsroom",
 *      description="Endpoints for retrieving published Newsroom content."
 * )
 * 
 * @OA\Schema(
 *      schema="Newsroom",
 *      type="object",
 *      title="Newsroom Model",
 * 
 *      @OA\Property(property="slug", type="string", example="lorem-ipsum-title", description="URL-friendly slug for the article."),
 *      @OA\Property(property="title", type="string", example="Lorem Ipsum Title", description="The title of the newsroom item (localized)."),
 *      @OA\Property(property="content", type="string", example="Full content of the article (localized).", description="The main content of the newsroom item."),
 *      @OA\Property(property="image_url", type="string", format="url", example="http://localhost/storage/newsroom/image.jpg", nullable=true, description="URL to the featured image."),
 *      @OA\Property(property="published_at", type="string", format="date", example="2025-11-10", description="The date and time the article was published."),
 *      @OA\Property(property="view_count", type="integer", example=150, description="Total unique views of the article."),
 *      @OA\Property(property="meta_title", type="string", example="SEO Title", description="SEO Meta Title for search engines."),
 *      @OA\Property(property="meta_description", type="string", example="Brief SEO description.", description="SEO Meta Description.")
 * ),
 */
class NewsroomController extends Controller
{
    /**
     * @OA\Get(
     *      path="/newsrooms",
     *      operationId="indexNewsrooms",
     *      tags={"Newsroom"},
     *      summary="Retrieves a paginated list of newsrooms or a featured list.",
     *      description="Uses the 'featured' query parameter to switch between a full paginated list and a limited featured list. All data is retrieved from cache for performance.",
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
     *          name="featured",
     *          in="query",
     *          description="Set to 'true' to retrieve a limited featured list (no pagination).",
     *          required=false,
     *          @OA\Schema(type="boolean", enum={"true", "false"}, default=false)
     *      ),
     * 
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="Used with 'featured=true'. Specifies the number of items to return (e.g., 4 for the homepage).",
     *          required=false,
     *          @OA\Schema(type="integer", default=4)
     *      ),
     * 
     *      @OA\Parameter(
     *          name="search",
     *          in="query",
     *          description="Case-insensitive search keyword applied to title and content.",
     *          required=false,
     *          @OA\Schema(type="string", example="fashion")
     *      ),
     * 
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="Sorting criteria. Use 'popular' for highest view counts.",
     *          required=false,
     *          @OA\Schema(type="string", enum={"newest", "oldest", "popular"}, example="newest")
     *      ),
     * 
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="Page number for the paginated list.",
     *          required=false,
     *          @OA\Schema(type="integer", default=1)
     *      ),
     * 
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Items per page for the paginated list.",
     *          required=false,
     *          @OA\Schema(type="integer", default=10)
     *      ),
     * 
     *      @OA\Response(
     *          response=200,
     *          description="List of newsroom items retrieved successfully.",
     * 
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Newsroom list retrieved successfully."),
     *              @OA\Property(
     *                  property="meta",
     *                  type="object",
     *                  description="Pagination metadata (present only when featured=false).",
     *                  
     *                  @OA\Property(property="current_page", type="integer", example=2),
     *                  @OA\Property(property="first_page_url", type="string", example="https://example.com/api/newsrooms?page=1"),
     *                  @OA\Property(property="from", type="integer", example=4),
     *                  @OA\Property(property="last_page", type="integer", example=5),
     *                  @OA\Property(property="last_page_url", type="string", example="https://example.com/api/newsrooms?page=5"),
     *                  @OA\Property(property="links", type="array",
     *                      @OA\Items(type="object",
     *                          @OA\Property(property="url", type="string", nullable=true, example="https://example.com/api/newsrooms?page=2"),
     *                          @OA\Property(property="label", type="string", example="2"),
     *                          @OA\Property(property="active", type="boolean", example=true)
     *                      )
     *                  ),
     *                  @OA\Property(property="path", type="string", example="https://example.com/api/newsrooms"),
     *                  @OA\Property(property="per_page", type="integer", example=3),
     *                  @OA\Property(property="to", type="integer", example=6),
     *                  @OA\Property(property="total", type="integer", example=13),
     *              ),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(ref="#/components/schemas/Newsroom")
     *              ),
     *          )
     *      )
     * )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $locale = app()->getLocale();

        // Load all newsroom items from cache (array) and wrap in a collection.
        $allNewsrooms = NewsroomData::listsForApi($locale);
        $collection = collect($allNewsrooms);

        // 0. Filter out future published items
        // Only show items with published_at <= today (or null just in case)
        $today = now()->toDateString();

        $collection = $collection->filter(function (array $item) use ($today) {
            return $item['published_at'] <= $today;
        })->values();

        // 1. Handle featured list (no pagination, search, or sorting).
        if ($request->boolean('featured')) {
            $limit = (int) $request->query('limit', 4); // Default 4 items
            $featured = $collection->take($limit)->values();

            return response()->json([
                'success' => true,
                'message' => 'Featured newsroom list retrieved successfully.',
                'data' => $featured,
            ], Response::HTTP_OK);
        }

        // 2. Apply search filter if "search" parameter is provided.
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $searchLower = mb_strtolower($search);

            $collection = $collection->filter(function (array $item) use ($searchLower) {
                $title = mb_strtolower((string) ($item['title'] ?? ''));
                $content = mb_strtolower(strip_tags((string) ($item['content'] ?? '')));

                return str_contains($title, $searchLower) || str_contains($content, $searchLower);
            })->values();
        }

        $sort = strtolower((string) $request->query('sort', 'newest'));
        if ($sort === 'popular') {
            $collection = $collection->sortByDesc('view_count')->values();
        } elseif ($sort === 'newest') {
            $collection = $collection->sortByDesc('published_at')->values();
        } else {
            $collection = $collection->sortBy('published_at')->values();
        }
        
        // 4. Manual pagination using LengthAwarePaginator.
        $perPage = max((int) $request->query('per_page', 10), 1);
        $page    = max((int) $request->query('page', 1), 1);

        $total = $collection->count();
        $items = $collection->forPage($page, $perPage)->values();

        // Remove "page" from query so Laravel can build proper pagination URLs.
        $query = $request->query();
        unset($query['page']);

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(),
            'pageName' => 'page',
            'query' => $query,
        ]);

        $paginatorArray = $paginator->toArray();
        $meta = $paginatorArray;
        unset($meta['data']);

        return response()->json([
            'success' => true,
            'message' => 'Newsroom list retrieved successfully.',
            'meta' => $meta,
            'data' => $paginatorArray['data'],
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/newsrooms/{slug}",
     *      operationId="showNewsroom",
     *      tags={"Newsroom"},
     *      summary="Retrieves a single newsroom item by its unique slug.",
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
     *          description="The unique slug of the published newsroom item.",
     *          required=true,
     *          @OA\Schema(type="string", example="lorem-ipsum-title")
     *      ),
     * 
     *      @OA\Response(
     *          response=200,
     *          description="Newsroom item retrieved successfully.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Newsroom item retrieved successfully"),
     *              @OA\Property(property="data", ref="#/components/schemas/Newsroom")
     *          )
     *      ),
     *      
     *      @OA\Response(
     *          response=404,
     *          description="Newsroom not found or not published.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Newsroom not found or not published.")
     *          )
     *      )
     * )
     */
    public function show(string $slug, Request $request): JsonResponse
    {
        $locale = app()->getLocale();

        // Get all newsroom data from the cache
        $allNewsrooms = NewsroomData::listsForApi($locale);
        $newsroomData = collect($allNewsrooms)->firstWhere('slug', $slug);

        if (!$newsroomData) {
            return response()->json([
                'success' => false,
                'message' => 'Newsroom not found or not published.'
            ], Response::HTTP_NOT_FOUND);
        }

        // -- Unique View Logic --
        $ip = $request->ip();
        $cacheKey = "newsroom_viewed:{$slug}:{$ip}";

        if (Cache::add($cacheKey, true, now()->addDay())) {
            Newsroom::where('slug', $slug)->increment('view_count');
        }

        return response()->json([
            'success' => true,
            'message' => 'Newsroom item retrieved successfully.',
            'data' => $newsroomData
        ], Response::HTTP_OK);
    }
}