<?php

namespace App\Console;

use App\Crons\CronRegister;
use Illuminate\Console\Scheduling\Schedule;

class CronKernel
{
    public static function execute()
    {
        $schedule = app(Schedule::class);
        $schedule->call(CronRegister::class.'@everyMinute')->everyMinute()->name('everyMinutes')->withoutOverlapping();
        $schedule->call(CronRegister::class.'@everyHour')->hourly()->name('everyHour')->withoutOverlapping();
        $schedule->call(CronRegister::class.'@everyDay')->hourly()->name('everyDay')->withoutOverlapping();
        $schedule->call(CronRegister::class.'@everyWeek')->hourly()->name('everyWeek')->withoutOverlapping();
        $schedule->job(new \App\Jobs\RenewExpiredBudgets)->dailyAt('00:05')->withoutOverlapping();
    }
}
