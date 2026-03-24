<?php

namespace App\Http\Controllers\Api;

use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\View\Data\CategoryData;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;


class SubscribeController extends Controller
{
    /**
     * @OA\Post(
     *     path="/subscribe",
     *     operationId="postSubscribe",
     *     tags={"Subscribe"},
     *     summary="Subscribe an email to the newsletter.",
     *     description="Add a new subscriber. If the email already exists, it will be skipped.",
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         description="Email to subscribe",
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     
     *     @OA\Response(
     *         response=200,
     *         description="Email subscribed successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email has been subscribed successfully.")
     *         )
     *     ),
     *     
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The email field is required.")
     *         )
     *     ),
     *     
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function subscribe(Request $request)
    {
        Subscriber::firstOrCreate(
            ['email' => $request->email],
            ['is_subscribe' => true]
        );

        return response()->json([
            'success' => true,
            'message' => 'Email has been subscribe successfully.'
        ], Response::HTTP_OK);
    }
}
