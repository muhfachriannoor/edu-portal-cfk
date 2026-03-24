<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserInterestRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;


/**
 * @OA\Tag(
 *      name="User",
 *      description="Endpoints for managing user's."
 * )
 * 
 * @OA\Schema(
 *      schema="Interest",
 *      title="Interest",
 *      description="A user's subscribed sub-category.",
 * 
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="name", type="string", example="Home fragnances"),
 *      @OA\Property(property="slug", type="string", example="home-fragnances"),
 *      @OA\Property(property="image_url", type="string", format="url", example="http://localhost/storage/path/to/image.jpg", nullable=true)
 * )
 */
class UserInterestController extends Controller
{
    /**
     * @OA\Get(
     *      path="/user/interests",
     *      operationId="getUserInterests",
     *      tags={"User"},
     *      summary="Retrieve the user's current interests.",
     *      description="Fetches the list of sub-categories the authenticated user is currently subscribed to.",
     *      security={{"bearerAuth": {}}},
     *      
     *      @OA\Response(
     *          response=200,
     *          description="Interests successfully retrieved.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="User interests successfully retrieved."),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(
     *                      property="interests",
     *                      type="array",
     *                      @OA\Items(ref="#/components/schemas/Interest")
     *                  )
     *              )
     *          )
     *      ),
     * 
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $user->load(['interests:id,name,slug,image']);

        $interests = $user->interests->map(function ($interest) {
            return [
                'id' => $interest->id,
                'name' => $interest->name,
                'slug' => $interest->slug,
                'image_url' => $interest->image ? Storage::url($interest->image) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'User interests successfully retrieved.',
            'data' => [
                'interests' => $interests,
            ]
        ], Response::HTTP_OK);
    }
    
    /**
     * @OA\Post(
     *      path="/user/interests",
     *      operationId="updateUserInterests",
     *      tags={"User"},
     *      summary="Update the user's list of interests (sub-categories).",
     *      description="Replaces the user's current interests with the provided list of sub-category IDs. Requires authentication.",
     *      security={{"bearerAuth": {}}},
     *      
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"sub_category_ids"},
     *              @OA\Property(
     *                  property="sub_category_ids",
     *                  type="array",
     *                  description="List of SubCategory IDs to sync as user interests.",
     *                  @OA\Items(type="integer", example=1)
     *              )
     *          )
     *      ),
     * 
     *      @OA\Response(
     *          response=200,
     *          description="Interests successfully updated.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="User interests successfully updated."),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(
     *                      property="interests",
     *                      type="array",
     *                      @OA\Items(ref="#/components/schemas/Interest")
     *                  )
     *              )
     *          )
     *      ),
     * 
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function updateInterests(UserInterestRequest $request): JsonResponse
    {
        $user = auth()->user();
        $subCategoryIds = $request->validated('sub_category_ids');
        $user->interests()->sync($subCategoryIds);

        if ($user->onboarding_completed === false) {
            $user->onboarding_completed = true;
            $user->save();
        }

        $user->load(['interests:id,name,slug,image']);

        $interests = $user->interests->map(function ($interest) {
            return [
                'id' => $interest->id,
                'name' => $interest->name,
                'slug' => $interest->slug,
                'image_url' => $interest->image ? Storage::url($interest->image) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'User interests successfully updated.',
            'data' => [
                'interests' => $interests,
            ]
        ], Response::HTTP_OK);
    }
}
