<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreModelOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->route('store'); // from {store:slug}

        if (!$store) {
            return $next($request);
        }

        // Loop through all route parameters
        foreach ($request->route()->parameters() as $param) {
            // Skip if it's the store itself
            if ($param === $store) {
                continue;
            }

            // Check if the param is a model and has a store_id or store relation
            if (is_object($param)) {
                // Case 1: Model has store_id
                if (isset($param->store_id) && $param->store_id !== $store->id) {
                    abort(404);
                }

                // Case 2: Model has store relation
                if (method_exists($param, 'store') && $param->store && $param->store->id !== $store->id) {
                    abort(404);
                }
            }
        }

        return $next($request);
    }
}
