<?php

namespace App\Crons;

use App\Crons\Ressources\TaskDueNotificationRessouces;

class CronRegister
{
    public function everyMinute()
    {
        app()->make(TaskDueNotificationRessouces::class)();
    }

    public function everyHour() {}

    public function everyDay() {}

    public function everyWeek() {}
}
