<?php

use App\Console\Commands\SendInsuranceExpiryNotifications;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SendInsuranceExpiryNotifications::class)
    ->dailyAt(config('insurance-bot.notification_time'))
    ->withoutOverlapping()
    ->onOneServer();
