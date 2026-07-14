<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| KPI - automatsko generiranje
|--------------------------------------------------------------------------
*/

Schedule::command('kpi:generate')
    ->dailyAt('01:10')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->name('kpi-generate');

/*
|--------------------------------------------------------------------------
| Dnevni status e-mail
|--------------------------------------------------------------------------
*/

Schedule::command('emails:send-daily-status')
    ->weekdays()
    ->at('06:30')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->name('daily-status-email');

/*
|--------------------------------------------------------------------------
| Tjedni status e-mail
|--------------------------------------------------------------------------
*/

Schedule::command('emails:send-weekly-status')
    ->mondays()
    ->at('07:00')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->name('weekly-status-email');

/*
|--------------------------------------------------------------------------
| Održavanje sustava
|--------------------------------------------------------------------------
*/

Schedule::command('activitylogs:cleanup')
    ->dailyAt('02:00')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->name('cleanup-activity-logs');

Schedule::command('temp:cleanup')
    ->dailyAt('02:15')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->name('cleanup-temp');

/*
|--------------------------------------------------------------------------
| Backup baze
|--------------------------------------------------------------------------
*/

Schedule::command('backup:run --only-db')
    ->dailyAt('02:30')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->name('backup-database');

/*
|--------------------------------------------------------------------------
| Tjedni kompletni backup
|--------------------------------------------------------------------------
*/

Schedule::command('backup:run')
    ->weeklyOn(0, '03:00') // Nedjelja u 03:00
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->name('backup-full');

/*
|--------------------------------------------------------------------------
| Čišćenje starih backupa
|--------------------------------------------------------------------------
*/

Schedule::command('backup:clean')
    ->dailyAt('04:00')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->name('backup-clean');