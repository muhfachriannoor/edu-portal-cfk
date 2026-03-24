<?php

use App\Http\Middleware\ApiMaintenanceMiddleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use App\Http\Middleware\JwtAuthMiddleware;
use Illuminate\Http\Middleware\HandleCors;
use App\Http\Middleware\EnsureStoreModelOwnership;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        //hook:   __DIR__.'/../routes/hook.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::prefix('hook')
                    ->middleware([]) // bisa kosong (NO middleware)
                    ->group(base_path('routes/hook.php'));
        }
        
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(HandleCors::class);
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'auth' => Authenticate::class,
            'ensure.store.ownership' => EnsureStoreModelOwnership::class,
            'auth.jwt' => JwtAuthMiddleware::class,
            'set.locale' => SetLocale::class,
            'api.maintenance' => ApiMaintenanceMiddleware::class,
            'signed' => ValidateSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
