<?php

use App\Services\SystemTaskMonitor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler heartbeat
|--------------------------------------------------------------------------
*/

Schedule::call(function (): void {
    app(SystemTaskMonitor::class)->heartbeat();
})
    ->name('system-scheduler-heartbeat')
    ->everyMinute()
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| KPI - automatsko generiranje
|--------------------------------------------------------------------------
*/

Schedule::command('kpi:generate')
    ->name('kpi-generate')
    ->dailyAt('01:10')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Dnevni status e-mail
|--------------------------------------------------------------------------
*/

Schedule::command('emails:send-daily-status')
    ->name('daily-status-email')
    ->weekdays()
    ->at('08:30')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Tjedni status e-mail
|--------------------------------------------------------------------------
*/

Schedule::command('emails:send-weekly-status')
    ->name('weekly-status-email')
    ->mondays()
    ->at('08:00')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Održavanje sustava
|--------------------------------------------------------------------------
*/

Schedule::command('activitylogs:cleanup')
    ->name('cleanup-activity-logs')
    ->dailyAt('02:00')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping();

Schedule::command('temp:cleanup')
    ->name('cleanup-temp')
    ->dailyAt('02:15')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Backup baze
|--------------------------------------------------------------------------
*/

Schedule::command('backup:run --only-db')
    ->name('backup-database')
    ->dailyAt('02:30')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->before(function (): void {
        app(SystemTaskMonitor::class)->start(
            'database_backup',
            'Dnevni backup baze'
        );
    })
    ->onSuccess(function (): void {
        app(SystemTaskMonitor::class)->success(
            taskKey: 'database_backup',
            taskName: 'Dnevni backup baze',
            message: 'Dnevni backup baze uspješno je izrađen.',
        );
    })
    ->onFailure(function (): void {
        app(SystemTaskMonitor::class)->failure(
            taskKey: 'database_backup',
            taskName: 'Dnevni backup baze',
            error: 'Naredba backup:run --only-db nije uspješno završena.',
        );
    });

/*
|--------------------------------------------------------------------------
| Tjedni kompletni backup
|--------------------------------------------------------------------------
*/

Schedule::command('backup:run')
    ->name('backup-full')
    ->weeklyOn(0, '03:00')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->before(function (): void {
        app(SystemTaskMonitor::class)->start(
            'full_backup',
            'Tjedni kompletni backup'
        );
    })
    ->onSuccess(function (): void {
        app(SystemTaskMonitor::class)->success(
            taskKey: 'full_backup',
            taskName: 'Tjedni kompletni backup',
            message: 'Kompletni backup uspješno je izrađen.',
        );
    })
    ->onFailure(function (): void {
        app(SystemTaskMonitor::class)->failure(
            taskKey: 'full_backup',
            taskName: 'Tjedni kompletni backup',
            error: 'Tjedni kompletni backup nije uspješno završen.',
        );
    });

/*
|--------------------------------------------------------------------------
| Čišćenje starih backupa
|--------------------------------------------------------------------------
*/

Schedule::command('backup:clean')
    ->name('backup-clean')
    ->dailyAt('04:00')
    ->timezone('Europe/Zagreb')
    ->withoutOverlapping()
    ->before(function (): void {
        app(SystemTaskMonitor::class)->start(
            'backup_cleanup',
            'Čišćenje starih backupa'
        );
    })
    ->onSuccess(function (): void {
        app(SystemTaskMonitor::class)->success(
            taskKey: 'backup_cleanup',
            taskName: 'Čišćenje starih backupa',
            message: 'Stari backupi uspješno su očišćeni.',
        );
    })
    ->onFailure(function (): void {
        app(SystemTaskMonitor::class)->failure(
            taskKey: 'backup_cleanup',
            taskName: 'Čišćenje starih backupa',
            error: 'Čišćenje starih backupa nije uspješno završeno.',
        );
    });