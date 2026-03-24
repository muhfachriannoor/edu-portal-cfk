<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UploadProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Http\Requests\UpdateEmailPreferenceRequest;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{

    /**
     * @OA\Get(
     *      path="/user/profile",
     *      operationId="getUserProfile",
     *      tags={"User"},
     *      security={{"bearerAuth": {}}},
     *      summary="Get the user's complete profile, including interests.",
     * 
     *      @OA\Response(
     *          response=200,
     *          description="User profile successfully retrieved.",
     * 
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="User profile retrieved"),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/User"),
     *                      @OA\Schema(
     *                          @OA\Property(
     *                              property="interests",
     *                              type="array",
     *                              description="List of user's sub-category interests.",
     *                              @OA\Items(ref="#/components/schemas/Interest")
     *                          )
     *                      )
     *                  }
     *              )
     *          )
     *      ),
     * 
     *      @OA\Response(response=401, description="Unathorized")
     * )
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();
        $user->load('interests:id,name,slug');
        $data = $user->only([
            'id', 'name', 'email', 'mobile_number', 'title', 'language', 'location', 'date_of_birth','avatar_url', 'onboarding_completed', 'is_active', 'email_verified_at'
        ]);

        $data['interests'] = $user->interests->map(function ($interest) {
            return [
                'id' => $interest->id,
                'name' => $interest->name,
                'slug' => $interest->slug,
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved',
            'data' => $data,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Put(
     *     path="/user/profile",
     *     operationId="updateUserProfile",
     *     tags={"User"},
     *     summary="Update the authenticated user's profile and optionally their interests.",
     *     description="Updates basic profile information and, if provided, replaces the user's interests with the given list of sub-category IDs.",
     *     security={{"bearerAuth": {}}}, 
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", nullable=true, example="john@example.com"),
     *             @OA\Property(property="mobile_number", type="string", nullable=true, example="6281234567890"),
     *             @OA\Property(property="title", type="string", nullable=true, example="Mr"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", nullable=true, example="1990-01-01"),
     *             @OA\Property(property="language", type="string", nullable=true, example="en"),
     *             @OA\Property(property="location", type="string", nullable=true, example="Jakarta"),
     *             @OA\Property(
     *                 property="sub_category_ids",
     *                 type="array",
     *                 nullable=true,
     *                 description="Optional list of SubCategory objects to sync as user interests. If provided as an empty array, all interests will be cleared.",
     *                  @OA\Items(type="object",
     *                      @OA\Property(property="id", type="integer", example=5),
     *                      @OA\Property(property="name", type="string", example="Men's Wear")
     *                  )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User profile successfully updated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile successfully updated."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", nullable=true, example="john@example.com"),
     *                     @OA\Property(property="mobile_number", type="string", nullable=true, example="6281234567890"),
     *                     @OA\Property(property="title", type="string", nullable=true, example="Mr"),
     *                     @OA\Property(property="date_of_birth", type="string", format="date", nullable=true, example="1990-01-01"),
     *                     @OA\Property(property="language", type="string", nullable=true, example="en"),
     *                     @OA\Property(property="location", type="string", nullable=true, example="Jakarta"),
     *                     @OA\Property(property="onboarding_completed", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="interests",
     *                         type="array",
     *                         description="Updated list of user interests.",
     *                         @OA\Items(ref="#/components/schemas/Interest")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        $user = auth()->user();

        // Get validated data
        $data = $request->validated();

        // Extract interests from payload if any
        $subCategoryPayload  = $data['sub_category_ids'] ?? null;
        unset($data['sub_category_ids']);

        // Existing: update basic profile fields (name, email, etc)
        $user->fill($data);
        $user->save();

        // Sync interests only if the field is present in the request payload
        if ($request->has('sub_category_ids')) {

            // Default: if null, treat as empty array to clear all interests
            $subCategoryIds = [];

            if (is_array($subCategoryPayload)) {
                // Extract only the IDs, remove null/duplicates
                $subCategoryIds = collect($subCategoryPayload)
                    ->pluck('id')
                    ->filter()        // remove null / falsy
                    ->unique()        // avoid duplicates
                    ->values()
                    ->all();
            }

            $user->interests()->sync($subCategoryIds);

            // Mark onboarding as completed if not yet
            if ($user->onboarding_completed === false) {
                $user->onboarding_completed = true;
                $user->save();
            }
        }

        // Reload interests relation for response
        $user->load(['interests:id,name,slug,image']);

        // Map interests to API-friendly structure (same as UserInterestController)
        $interests = $user->interests->map(function ($interest) {
            return [
                'id' => $interest->id,
                'name' => $interest->name,
                'slug' => $interest->slug,
                'image_url' => $interest->image ? Storage::url($interest->image) : null,
            ];
        });

        $userPayload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile_number' => $user->mobile_number,
            'title' => $user->title,
            'date_of_birth' => $user->date_of_birth,
            'language' => $user->language,
            'location' => $user->location,
            'onboarding_completed' => (bool) $user->onboarding_completed,
            'interests' => $interests,
        ];

        return response()->json([
            'success' => true,
            'message' => 'User profile successfully updated.',
            'data' => [
                'user' => $userPayload,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Put(
     *      path="/user/change-password",
     *      operationId="changePassword",
     *      tags={"User"},
     *      security={{"bearerAuth": {}}},
     *      summary="Change password",
     *      description="Change the authenticated user's password.",
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"current_password","new_password","new_password_confirmation"},
     *              @OA\Property(property="current_password", type="string", example="password"),
     *              @OA\Property(property="new_password", type="string", example="12345678"),
     *              @OA\Property(property="new_password_confirmation", type="string", example="12345678")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Password successfully updated",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Password successfully updated.")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Current password incorrect",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Current password is incorrect.")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="The new_password field is required.")
     *          )
     *      )
     * )
     */
    public function change_password(ChangePasswordRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success'  => false,
                'message' => 'Current password is incorrect.',
            ], 400);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success'  => true,
            'message' => 'Password successfully updated.',
        ]);
    }

    /**
     * @OA\Post(
     *      path="/user/upload-profile",
     *      operationId="uploadProfile",
     *      tags={"User"},
     *      security={{"bearerAuth": {}}},
     *      summary="Upload or update the user's profile image",
     *      description="Uploads and updates the authenticated user's profile image.",
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"image"},
     *                  @OA\Property(
     *                      property="image",
     *                      type="string",
     *                      format="binary",
     *                      description="Profile image file (jpg, jpeg, png)"
     *                  )
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Profile image successfully updated",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Profile image successfully updated."),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(property="email", type="string", format="email", example="natalie@email.com"),
     *                  @OA\Property(property="name", type="string", example="Natalie Wiyoko"),
     *                  @OA\Property(
     *                      property="profile_image",
     *                      type="string",
     *                      example="url"
     *                  )
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="The image field is required.")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unable to upload profile image.")
     *          )
     *      )
     * )
     */
    public function upload_profile(UploadProfileRequest $request)
    {
        $user = auth()->user();
        $file = $request->file('image');

        if($file){
            $user->saveFile(
                $file,
                'user',
                [
                    'field' => 'profile',
                    'name' => $file->getClientOriginalName()
                ]
            );

            $user = $user->fresh(['files']);

            $user->update([ 'avatar_url' => $user->profile_image ]);
        }
        
        return response()->json([
            'success'  => true,
            'message' => 'Profile image successfully updated.',
            'data' => [
                'email' => $user->email,
                'name' => $user->name,
                'profile_image' => $user->profile_image,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *      path="/user/email-preference",
     *      operationId="emailPreference",
     *      tags={"User"},
     *      security={{"bearerAuth": {}}},
     *      summary="Get user's email notification preferences",
     *      description="Retrieve all available email preference categories for the authenticated user.",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Email preferences successfully retrieved",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Data has been successfully retrieved."),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(
     *                      property="unsubscribe",
     *                      type="object",
     *                      @OA\Property(property="title", type="string", example="Unsubscribe from all email list"),
     *                      @OA\Property(property="text", type="string", example="You won't receive any email, including important emails."),
     *                      @OA\Property(property="value", type="boolean", example=false)
     *                  ),
     *                  @OA\Property(
     *                      property="brand_news_story",
     *                      type="object",
     *                      @OA\Property(property="title", type="string", example="Brand News & Stories"),
     *                      @OA\Property(property="text", type="string", example="Email me updates and promotions for Sarinah Retail and Sarinah Club."),
     *                      @OA\Property(property="value", type="boolean", example=false)
     *                  ),
     *                  @OA\Property(
     *                      property="new_product_launch",
     *                      type="object",
     *                      @OA\Property(property="title", type="string", example="New Product Launches"),
     *                      @OA\Property(property="text", type="string", example="Get updates when we release new arrivals, limited editions, or seasonal drops."),
     *                      @OA\Property(property="value", type="boolean", example=false)
     *                  ),
     *                  @OA\Property(
     *                      property="back_in_stock_alert",
     *                      type="object",
     *                      @OA\Property(property="title", type="string", example="Back-in-Stock Alerts"),
     *                      @OA\Property(property="text", type="string", example="Be notified when your favourite items or saved products are available again."),
     *                      @OA\Property(property="value", type="boolean", example=false)
     *                  ),
     *                  @OA\Property(
     *                      property="order_account_update",
     *                      type="object",
     *                      @OA\Property(property="title", type="string", example="Order & Account Updates"),
     *                      @OA\Property(property="text", type="string", example="Important notifications about your order, shipping status, returns, and account activity."),
     *                      @OA\Property(property="value", type="boolean", example=false)
     *                  ),
     *                  @OA\Property(
     *                      property="wishlist_price_drop_alert",
     *                      type="object",
     *                      @OA\Property(property="title", type="string", example="Wishlist & Price-Drop Alerts"),
     *                      @OA\Property(property="text", type="string", example="Get notified when items you like go on sale or drop in price."),
     *                      @OA\Property(property="value", type="boolean", example=false)
     *                  )
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unable to retrieve data.")
     *          )
     *      )
     * )
     */
    public function email_preference()
    {
        $user = auth()->user();
        $locale = app()->getLocale();
        $setting = Setting::where('key', 'EMAIL_PREFERENCE')->first();

        $data = collect($setting->data[$locale])->map(function($set, $key) use($user) {
            return [
                'text' => $set['text'],
                'title' => $set['title'],
                'value' => $user->email_preference[$key] ?? false
            ];
        });

        return response()->json([
            'success'  => true,
            'message' => 'Data has been successfully retrieved.',
            'data' => $data
        ]);
    }

    /**
     * @OA\Put(
     *      path="/user/email-preference",
     *      operationId="updateEmailPreference",
     *      tags={"User"},
     *      security={{"bearerAuth": {}}},
     *      summary="Update user's email notification preferences",
     *      description="Updates the authenticated user's selected email notification preference settings.",
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={
     *                  "brand_news_story",
     *                  "new_product_launch",
     *                  "back_in_stock_alert",
     *                  "order_account_update",
     *                  "wishlist_price_drop_alert",
     *                  "unsubscribe"
     *              },
     *              @OA\Property(
     *                  property="brand_news_story",
     *                  type="boolean",
     *                  example=true
     *              ),
     *              @OA\Property(
     *                  property="new_product_launch",
     *                  type="boolean",
     *                  example=true
     *              ),
     *              @OA\Property(
     *                  property="back_in_stock_alert",
     *                  type="boolean",
     *                  example=false
     *              ),
     *              @OA\Property(
     *                  property="order_account_update",
     *                  type="boolean",
     *                  example=true
     *              ),
     *              @OA\Property(
     *                  property="wishlist_price_drop_alert",
     *                  type="boolean",
     *                  example=false
     *              ),
     *              @OA\Property(
     *                  property="unsubscribe",
     *                  type="boolean",
     *                  example=false
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Email preferences successfully updated",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Data has been successfully updated."
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="The brand_news_story field must be true or false."
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unable to update preferences.")
     *          )
     *      )
     * )
     */
    public function update_email_preference(UpdateEmailPreferenceRequest $request)
    {
        $data = $request->all();
        $user = auth()->user();

        $user->update([
            'email_preference' => $data
        ]);

        return response()->json([
            'success'  => true,
            'message' => 'Data has been successfully updated.'
        ]);
    }
}
