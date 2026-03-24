<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class MaintenanceController extends Controller
{
    public function set(Request $request): JsonResponse
    {
        // Minimal validation
        $enabled = (bool) $request->boolean('enabled', false);
        $message = (string) $request->input('message', 'Service is under maintenance.');
        $retryAfter = (int) $request->input('retry_after', 300);

        Cache::forever('maintenance:enabled', $enabled);
        Cache::forever('maintenance:message', $message);
        Cache::forever('maintenance:retry_after', max(0, $retryAfter));

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $enabled,
                'message' => $message,
                'retry_after' => max(0, $retryAfter),
            ],
            'message' => 'Maintenance state updated.',
        ]);
    }
}