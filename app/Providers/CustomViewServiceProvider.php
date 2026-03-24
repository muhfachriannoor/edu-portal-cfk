<?php

namespace App\Providers;

use App\Http\View\Composers\AdminComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CustomViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('*', AdminComposer::class);
    }
}
