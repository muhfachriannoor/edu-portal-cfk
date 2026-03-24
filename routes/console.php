<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:update-expired')->everyMinute()->withoutOverlapping();
Schedule::command('orders:send-payment-reminder')->everyMinute()->withoutOverlapping();
Schedule::command('orders:send-pickup-reminder')->everyMinute()->withoutOverlapping();

// Add your new auto-complete scheduler
Schedule::command('orders:auto-complete-delivery')
    ->dailyAt('00:01')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/orders_auto_complete.log'));