<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Sarinah E-commerce",
 *     description="This is the API documentation for Sarinah E-commerce.",
 *     @OA\Contact(
 *         email="support@example.com"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * ),
 * 
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Sarinah E-commerce host server api"
 *  ),
 * 
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT",
 *      description="Enter JWT Bearer token **only**"
 * )
*/

class Controller extends BaseController
{
    protected function sendSuccess(int $code, string $message = null, $data = null): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function sendError(int $code, string $message = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
        ], $code);
    }
}
