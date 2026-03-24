<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Get locale from the header X-Localization header
        $locale = $request->header('X-Localization');

        // 2. Define valid locales.
        // Assuming `id` and `en` are the only supported locales, with `id` as default.
        $supportedLocales = ['id', 'en'];

        $determinedLocale = 'en'; // Default locale

        if ($locale && in_array(strtolower($locale), $supportedLocales)) {
            $determinedLocale = strtolower($locale);
        }

        // 3. Set the global Laravel application locale
        App::setLocale($determinedLocale);

        return $next($request);
    }
}
