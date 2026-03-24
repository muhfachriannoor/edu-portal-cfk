<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Firebase\JWT\JWT;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if(env('APP_ENV') != 'local') {
            URL::forceScheme('https');
        }

        if (class_exists(JWT::class)) {
            JWT::$leeway = 300;
        }
    }
}
