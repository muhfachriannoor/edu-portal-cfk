<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenMissingException;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            auth()->shouldUse('api');
            
            // 1. Check the token and load the user
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return $this->errorResponse('User not found.', HttpResponse::HTTP_NOT_FOUND);
            }
        } catch (TokenMissingException $e) {
            // 2. Token is missing from the request (e.g., Authorization header is empty)
            return $this->errorResponse('Authentication token is missing.', Response::HTTP_UNAUTHORIZED);
        } catch (TokenExpiredException $e) {
            // 3. Token has expired (Access Token)
            return $this->errorResponse('Authentication token has expired.', Response::HTTP_UNAUTHORIZED);
        } catch (TokenInvalidException $e) {
            // 4. Token is invalid (e.g., tampered signature, wrong format)
            return $this->errorResponse('Authentication token is invalid', Response::HTTP_UNAUTHORIZED);
        } catch (TokenBlacklistedException $e) {
            // 5. Token is blacklisted (e.g., after logout)
            return $this->errorResponse('Authentication token is blacklisted.', Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            // 6. Generic JWT Error (e.g., header error, unknow error)
            return $this->errorResponse('Authentication failed: ' . $e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        // Token is valid and user is loaded, proceed with the request
        return $next($request);
    }

    /**
     * Helper function to return a standardized JSON error response.
     */
    protected function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
