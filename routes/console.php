<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('kpi:generate')
    ->dailyAt('01:10');

Schedule::command('emails:send-daily-status')
    ->weekdays()
    ->at('06:30');

Schedule::command('emails:send-weekly-status')
    ->mondays()
    ->at('07:00');

/*
|--------------------------------------------------------------------------
| Activity logs cleanup
|--------------------------------------------------------------------------
| Briše aktivnosti starije od 30 dana
*/

Schedule::command('activitylogs:cleanup')
    ->dailyAt('02:00');

Schedule::command('temp:cleanup')
    ->dailyAt('02:15');

/*
|--------------------------------------------------------------------------
| Backup
|--------------------------------------------------------------------------
| Dnevno se radi samo backup baze.
| Tjedno se radi kompletni backup baze + dokumenata.
| Svaki dan se čiste stari backupi prema config/backup.php.
*/

Schedule::command('backup:run --only-db')
    ->dailyAt('02:30');

Schedule::command('backup:run')
    ->weeklyOn(0, '03:00'); // nedjelja u 03:00

Schedule::command('backup:clean')
    ->dailyAt('04:00');