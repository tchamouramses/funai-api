<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tasks:send-due-notifications')->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('fitness:weekly-summary')->weeklyOn(1, '08:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::job(new \App\Jobs\RenewExpiredBudgets)->dailyAt('00:05')
    ->withoutOverlapping()
    ->runInBackground();
