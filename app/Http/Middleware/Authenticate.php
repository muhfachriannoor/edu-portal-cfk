<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        if (! $request->expectsJson()) {
            // Admin routes
            if ($request->is('secretgate19') || $request->is('secretgate19/*')) {
                return route('secretgate19.login');
            }
        }

        return null;
    }
}
