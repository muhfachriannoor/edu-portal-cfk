<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserAddressRequest;
use App\Models\MasterAddress;
use App\View\Data\IndoRegionData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


/**
 * @OA\Schema(
 *      schema="AddressItem",
 *      title="AddressItem",
 *      description="User Address object.",
 *      
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="receiver_name", type="string", example="Natalie Wiyoko"),
 *      @OA\Property(property="phone_number", type="string", example="6285111233345"),
 *      @OA\Property(property="label", type="string", example="Home"),
 *      @OA\Property(property="address_line", type="string", example="126 University PDR, Central Park, RT 01/RW 02"),
 *      @OA\Property(property="province_id", type="string", example="R1615621", description="Province ID"),
 *      @OA\Property(property="province_name", type="string", example="Bali", description="Province Name"),
 *      @OA\Property(property="city_id", type="string", example="R7760984", description="City ID"),
 *      @OA\Property(property="city_name", type="string", example="Kab. Badung", description="City Name"),
 *      @OA\Property(property="district_id", type="string", example="R80017139", description="District ID"),
 *      @OA\Property(property="district_name", type="string", example="Abiansemal", description="District Name"),
 *      @OA\Property(property="subdistrict_id", type="string", example="RID64626", description="Subdistrict ID"),
 *      @OA\Property(property="subdistrict_name", type="string", example="Abiansemal", description="Subdistrict Name"),
 *      @OA\Property(property="postal_code", type="string", example="1234567"),
 *      @OA\Property(property="is_default", type="boolean", example=true),
 * )
 * 
 * @OA\Schema(
 *      schema="UserAddressRequest",
 *      title="UserAddressRequest",
 *      description="Payload for creating/updating a user address.",
 *      
 *      @OA\Property(property="receiver_name", type="string", example="Budi Santoso"),
 *      @OA\Property(property="phone_number", type="string", example="6281234567890"),
 *      @OA\Property(property="label", type="string", example="Office"),
 *      @OA\Property(property="address_line", type="string", example="Jl. Sudirman Kav. 12, Tower A Lantai 5"),
 *      @OA\Property(property="subdistrict_id", type="string", example="RID64626", description="Subdistrict ID"),
 *      @OA\Property(property="postal_code", type="string", example="75123"),
 *      @OA\Property(property="is_default", type="boolean", example=false)
 * )
 */
class UserAddressController extends Controller
{
    /**
     * Transform single address model to API response format
     */
    private function transformAddress($address): array
    {
        $locale = app()->getLocale();

        $masterAddress = $address->masterAddress;

        return [
            'id' => $address->id,
            'receiver_name' => $address->receiver_name,
            'phone_number' => $address->phone_number,
            'label' => $address->label,
            'address_line' => $address->address_line,

            'province_id' => $masterAddress->province_id,
            'province_name' => $masterAddress->province_name,
            'city_id' => $masterAddress->city_id,
            'city_name' => $masterAddress->city_name,
            'district_id' => $masterAddress->district_id,
            'district_name' => $masterAddress->district_name,
            'subdistrict_id' => $masterAddress->subdistrict_id,
            'subdistrict_name' => $masterAddress->subdistrict_name,

            'postal_code' => $address->postal_code,
            'is_default' => (bool) $address->is_default,
        ];
    }

    /**
     * @OA\Get(
     *      path="/user/addresses",
     *      operationId="getUserAddresses",
     *      tags={"User"},
     *      summary="Retrieve all addresses for the authenticated user.",
     *      security={{"bearerAuth": {}}},
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
     *          name="is_default",
     *          in="query",
     *          description="If true, only the default address will be returned. If not provided, all addresses will be returned.",
     *          required=false,
     *          @OA\Schema(type="string", enum={"true", "false"})
     *      ),
     *      
     *      @OA\Response(
     *          response=200,
     *          description="List of user addresses successfully retrieved.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="User addresses successfully retrieved."),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  @OA\Items(ref="#/components/schemas/AddressItem")
     *              )
     *          )
     *      ),
     * 
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $isDefault = $request->query('is_default');

        if ($isDefault === 'true') {
            $addresses = $user->addresses()->where('is_default', true)->get();
        } else {
            $addresses = $user->addresses;
        }

        $addresses = $addresses->map(function ($address) {
            return $this->transformAddress($address);
        });

        return response()->json([
            'success' => true,
            'message' => 'User addresses successfully retrieved.',
            'data' => $addresses,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *      path="/user/addresses",
     *      operationId="storeUserAddress",
     *      tags={"User"},
     *      summary="Create a new address for the authenticated user.",
     *      security={{"bearerAuth": {}}},
     *      
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UserAddressRequest")
     *      ),
     * 
     *      @OA\Response(
     *          response=201,
     *          description="Address successfully created.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Address successfully added."),
     *              @OA\Property(property="data", ref="#/components/schemas/AddressItem")
     *          )
     *      ),
     * 
     *      @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(UserAddressRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validated();
        
        // Retrieve the master address details using subdistrict_id
        $masterAddress = MasterAddress::where('subdistrict_id', $data['subdistrict_id'])->first();

        if (!$masterAddress) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid subdistrict_id.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Update the address data with information from master_addresses
        $data['province_id'] = $masterAddress->province_id;
        $data['province_name'] = $masterAddress->province_name;
        $data['city_id'] = $masterAddress->city_id;
        $data['city_name'] = $masterAddress->city_name;
        $data['district_id'] = $masterAddress->district_id;
        $data['district_name'] = $masterAddress->district_name;
        $data['subdistrict_name'] = $masterAddress->subdistrict_name;

        // Logic Check 1: If the new address is set as default, set the old ones to false.
        if (isset($data['is_default']) && $data['is_default']) {
            $user->addresses()->update(['is_default' => false]);
        } 
        // Logic Check 2: If this is the user's FIRST address, automatically make it default
        elseif ($user->addresses()->count() === 0)  {
            $data['is_default'] = true;
        }

        // Create the new address
        $address = $user->addresses()->create($data);

        $responseAddress = $this->transformAddress($address->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Address successfully added.',
            'data' => $responseAddress,
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Put(
     *      path="/user/addresses/{id}",
     *      operationId="updateUserAddress",
     *      tags={"User"},
     *      summary="Update an existing address.",
     *      security={{"bearerAuth": {}}},
     *      
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the address to update.",
     *          @OA\Schema(type="integer")
     *      ),
     * 
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UserAddressRequest")
     *      ),
     *      
     *      @OA\Response(
     *          response=200,
     *          description="Address successfully updated.",
     * 
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Address successfully updated."),
     *              @OA\Property(property="data", ref="#/components/schemas/AddressItem")
     *          )
     *      ),
     *      
     *      @OA\Response(response=404, description="Address not found or unauthorized"),
     *      @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(UserAddressRequest $request, int $id): JsonResponse
    {
        // Ensure only the user's own address can be updated
        $user = auth()->user();
        $address = $user->addresses()->findOrFail($id);
        $data = $request->validated();

        // Retrieve the master address details using subdistrict_id
        $masterAddress = MasterAddress::where('subdistrict_id', $data['subdistrict_id'])->first();

        if (!$masterAddress) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid subdistrict_id.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Update the address data with information from master_addresses
        $data['province_id'] = $masterAddress->province_id;
        $data['province'] = $masterAddress->province_name;
        $data['city_id'] = $masterAddress->city_id;
        $data['city'] = $masterAddress->city_name;
        $data['district_id'] = $masterAddress->district_id;
        $data['district_name'] = $masterAddress->district_name;
        $data['subdistrict_name'] = $masterAddress->subdistrict_name;

        // Logic: If is_default is set to true, disable the default flag on other addresses
        if (isset($data['is_default']) && $data['is_default']) {
            $user->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update($data);

        $responseAddress = $this->transformAddress($address->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Address successfully updated.',
            'data' => $responseAddress,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *      path="/user/addresses/{id}",
     *      operationId="deleteUserAddress",
     *      tags={"User"},
     *      summary="Delete on address.",
     *      description="Cannot delete the only address or the only default address.",
     *      security={{"bearerAuth": {}}},
     * 
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the address to delete",
     *          @OA\Schema(type="integer")
     *      ),
     * 
     *      @OA\Response(
     *          response=200,
     *          description="Address successfully deleted.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Address successfully deleted.")
     *          )
     *      ),
     * 
     *      @OA\Response(response=403, description="Forbidden: Cannot delete the last remaining default address"),
     *      @OA\Response(response=404, description="Address not found or unauthorized")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $address = auth()->user()->addresses()->findOrFail($id);
        $user = auth()->user();

        // Prevention: Do not allow the user to delete the last default address
        if ($address->is_default && $user->addresses()->count() === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the last remaining default address.'
            ], Response::HTTP_FORBIDDEN);
        }

        $address->delete();

        // If the deleted address was the default, set another address as the new default
        if ($address->is_default) {
            $newDefault = $user->addresses()->first();

            if ($newDefault) {
                $newDefault->is_default = true;
                $newDefault->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Address successfully deleted.'
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *      path="/user/addresses/{id}/default",
     *      operationId="setDefaultAddress",
     *      tags={"User"},
     *      summary="Set a specific address as the default address.",
     *      security={{"bearerAuth": {}}},
     *      
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the address to set as default",
     *          @OA\Schema(type="integer")
     *      ),
     * 
     *      @OA\Response(
     *          response=200,
     *          description="Default address successfully updated.",
     *          
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Default address successfully changed."),
     *              @OA\Property(property="data", ref="#components/schemas/AddressItem")
     *          )
     *      ),
     * 
     *      @OA\Response(
     *          response=404,
     *          description="Address not found or unauthorized"
     *      )
     * )
     */
    public function setDefaultAddress(int $id): JsonResponse
    {
        $user = auth()->user();

        // Ensure the address belongs to the user
        $newDefault = $user->addresses()->findOrFail($id);

        // If the selected address is already default, return success without database update
        if ($newDefault->is_default) {
            return response()->json([
                'success' => true,
                'message' => 'This address is already the default address.',
                'data' => $newDefault,
            ], Response::HTTP_OK);
        }

        // 1. CRUCIAL LOGIC: Set all other user addresses to non-default
        // This ensures only one default address exists at a time.
        $user->addresses()->update(['is_default' => false]);

        // 2. Set the selected address as the new default
        $newDefault->is_default = true;
        $newDefault->save();

        return response()->json([
            'success' => true,
            'message' => 'Default address successfully changed.',
            'data' => $this->transformAddress($newDefault->fresh()),
        ], Response::HTTP_OK);
    }
}