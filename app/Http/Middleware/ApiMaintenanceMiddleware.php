<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiMaintenanceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Bypass admin maintenance endpoint
        if ($request->is('api/admin/maintenance')) {
            return $next($request);
        }

        $enabled = (bool) Cache::get('maintenance:enabled', false);

        if (!$enabled) {
            return $next($request);
        }

        $message = (string) Cache::get('maintenance:message', 'Service is under maintenance.');
        $retryAfter = (int) Cache::get('maintenance:retry_after', 300); // seconds

        return response()->json([
            'success' => false,
            'code' => 'MAINTENANCE',
            'message' => $message,
        ], Response::HTTP_SERVICE_UNAVAILABLE)->header('Retry-After', $retryAfter);
    }
}