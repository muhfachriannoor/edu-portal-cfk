<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OnboardingUserRequest;
use App\Models\MasterAddress;
use App\View\Data\IndoRegionData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserOnboardingController extends Controller
{
    /**
     * @OA\Post(
     *     path="/user/onboarding",
     *     operationId="onboardingUser",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     summary="Complete user onboarding, updating profile data, interests and default address.",
     *     description="Updates any combination of profile data (name, language), default address (subdistrict_id, address_line) and interests (sub_category_ids).
     *                  All fields are optional; only provided fields will be updated.
     *                  However, profile + address are treated as a single block: if any of name/language/subdistrict_id/address_line is sent, then all of them must be sent.
     *                  Once onboarding_completed is true for the user, this endpoint can no longer be used.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Any combination of profile, address and interests to be updated.",
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 nullable=true,
     *                 example="Budi Santoso"
     *             ),
     *             @OA\Property(
     *                 property="language",
     *                 type="string",
     *                 nullable=true,
     *                 example="id"
     *             ),
     *
     *             @OA\Property(
     *                 property="subdistrict_id",
     *                 type="string",
     *                 nullable=true,
     *                 example="RID52510",
     *                 description="Subdistrict ID from /master_addresses"
     *             ),
     *             @OA\Property(
     *                 property="phone_number",
     *                 type="string",
     *                 nullable=true,
     *                 example="081234567",
     *                 description="Phone Number"
     *             ),
     *             @OA\Property(
     *                 property="address_line",
     *                 type="string",
     *                 nullable=true,
     *                 example="Jl. Mawar Indah No. 5, RT 03 RW 08"
     *             ),
     *             @OA\Property(
     *                 property="postal_code",
     *                 type="string",
     *                 nullable=true,
     *                 example="1234",
     *                 description="Postal Code"
     *             ),
     *
     *             @OA\Property(
     *                 property="sub_category_ids",
     *                 type="array",
     *                 nullable=true,
     *                 description="Optional list of SubCategory IDs selected as user interests. If sent as an empty array, existing interests will be cleared.",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Onboarding successfully completed (or partially updated).",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Onboarding successfully completed."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user_profile",
     *                     type="object",
     *                     description="The updated user profile. Address fields may be null if no address was provided.",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", nullable=true, example="Budi Santoso"),
     *                     @OA\Property(property="email", type="string", example="budi@gmail.com"),
     *                     @OA\Property(property="language", type="string", nullable=true, example="id"),
     *                     @OA\Property(property="onboarding_completed", type="boolean", example=true),
     *                     @OA\Property(property="address", type="object",
     *                          @OA\Property(property="province_id", type="string", example="R1615621"),
     *                          @OA\Property(property="province_name", type="string", example="Bali"),
     *                          @OA\Property(property="city_id", type="string", example="R7760984"),
     *                          @OA\Property(property="city_name", type="string", example="Kab. Badung"),
     *                          @OA\Property(property="district_id", type="string", example="R80017139"),
     *                          @OA\Property(property="district_name", type="string", example="Abiansemal"),
     *                          @OA\Property(property="subdistrict_id", type="string", example="RID52510"),
     *                          @OA\Property(property="subdistrict_name", type="string", example="Angantaka"),
     *                          @OA\Property(property="receiver_name", type="string", example="Budi Santoso"),
     *                          @OA\Property(property="address_line", type="string", example="Jl. Mawar Indah No. 5, RT 03 RW 08"),
     *                          @OA\Property(property="phone_number", type="string", example="081234"),
     *                          @OA\Property(property="postal_code", type="string", example="12345"),
     *                          @OA\Property(property="is_default", type="boolean", example=true),
     *                          @OA\Property(property="label", type="string", example="Home"),
     *                     ),
     *                 ),
     *                 @OA\Property(
     *                     property="interests",
     *                     type="array",
     *                     description="The user's updated list of interests.",
     *                     @OA\Items(ref="#/components/schemas/Interest")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *      
     *     @OA\Response(
     *          response=409,
     *          description="Onboarding already completed.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Onboarding has already been completed.")
     *          )
     *     ),
     * 
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function onBoardingUser(OnboardingUserRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->onboarding_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding has already been completed.',
            ], Response::HTTP_CONFLICT);
        }

        $validatedData = $request->validated();

        DB::beginTransaction();

        try {
            // --- 1. DATA SEPARATION ---
            $profileFields = ['name', 'language'];
            $addressFields = ['subdistrict_id', 'address_line', 'phone_number', 'postal_code'];
            $interestField = 'sub_category_ids';

            $profileData  = collect($validatedData)->only($profileFields)->toArray();
            $addressInput = collect($validatedData)->only($addressFields)->toArray();

            // sub_category_ids is optional
            $hasInterestField = array_key_exists($interestField, $validatedData);
            $subCategoryIds   = $hasInterestField ? ($validatedData[$interestField] ?? []) : null;

            // --- 2. HANDLE ADDRESS IF EXISTS ---
            if (isset($addressInput['subdistrict_id'])) {
                // Retrieve master address details using subdistrict_id
                $masterAddress = MasterAddress::where('subdistrict_id', $addressInput['subdistrict_id'])->first();

                if (!$masterAddress) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid subdistrict_id.',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                // --- 3. BUILD ADDRESS DATA ---
                $addressData = [
                    'province_id' => $masterAddress->province_id,
                    'province_name' => $masterAddress->province_name,
                    'city_id' => $masterAddress->city_id,
                    'city_name' => $masterAddress->city_name,
                    'district_id' => $masterAddress->district_id,
                    'district_name' => $masterAddress->district_name,
                    'subdistrict_id' => $addressInput['subdistrict_id'],
                    'subdistrict_name' => $masterAddress->subdistrict_name,
                    'receiver_name' => $validatedData['name'] ?? $user->name,
                    'address_line' => $addressInput['address_line'] ?? null,
                    'phone_number'=> $addressInput['phone_number'] ?? null,
                    'postal_code' => $addressInput['postal_code'] ?? null,
                    'is_default' => true,
                    'label' => 'Home',
                ];

                // --- 4. CREATE DEFAULT ADDRESS (IF REQUIRED) ---
                // Ensure only ONE default address per user
                $user->addresses()->where('is_default', true)->update(['is_default' => false]);
                $user->addresses()->create($addressData);
            } else {
                $addressData = [];
            }

            // --- 5. SYNCHRONIZE INTERESTS (OPTIONAL) ---
            if ($hasInterestField) {
                // Sync interests
                $user->interests()->sync($subCategoryIds);
            }

            // Mark onboarding as completed if address or interests are provided
            $profileData['onboarding_completed'] = true;
            $profileData['mobile_number'] =  $validatedData['phone_number'];

            // Update user profile
            $user->fill($profileData);
            $user->save();

            DB::commit();

            // --- 5. FORMAT RESPONSE ---
            $user->load(['interests:id,name,slug,image']);
            
            $interests = $user->interests->map(function ($interest) {
                return [
                    'id'        => $interest->id,
                    'name'      => $interest->name,
                    'slug'      => $interest->slug,
                    'image_url' => $interest->image ? Storage::url($interest->image) : null,
                ];
            });

            // Base user profile response
            $userProfileResponse = $user->only([
                'id',
                'name',
                'email',
                'language',
                'onboarding_completed',
            ]);

            // Attach default address info if created in this request
            $userProfileResponse['address'] = $addressData;

            return response()->json([
                'success' => true,
                'message' => 'Onboarding successfully completed.',
                'data'    => [
                    'user_profile' => $userProfileResponse,
                    'interests'    => $interests,
                ],
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Onboarding Failed for User {$user->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Onboarding failed due to an internal error.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}