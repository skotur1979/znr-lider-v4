<?php

namespace App\Providers;

use App\Filament\Widgets\DashboardCalendarWidget;
use App\Filament\Widgets\DashboardDeadlinesGrid;
use App\Models\OperationalLog;
use App\Services\ActivityLogger;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Livewire::component(
            'app.filament.widgets.dashboard-deadlines-grid',
            DashboardDeadlinesGrid::class
        );

        Livewire::component(
            'app.filament.widgets.dashboard-calendar-widget',
            DashboardCalendarWidget::class
        );

        Event::listen(Login::class, function (Login $event): void {
            ActivityLogger::log(
                module: 'Sustav',
                action: 'login',
                title: 'Korisnik se prijavio',
                description: 'Korisnik: ' . ($event->user?->name ?? $event->user?->email ?? '-'),
                record: $event->user,
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            ActivityLogger::log(
                module: 'Sustav',
                action: 'logout',
                title: 'Korisnik se odjavio',
                description: 'Korisnik: ' . ($event->user?->name ?? $event->user?->email ?? '-'),
                record: $event->user,
            );
        });

        Event::listen(Failed::class, function (Failed $event): void {
            ActivityLogger::log(
                module: 'Sustav',
                action: 'failed_login',
                title: 'Neuspješna prijava',
                description: 'Pokušaj prijave za e-mail: ' . ($event->credentials['email'] ?? '-'),
            );
        });

        Event::listen('eloquent.saving: *', function (string $eventName, array $data) {
            $model = $data[0] ?? null;

            if (! $model instanceof Model) {
                return;
            }
            // VAŽNO:
            // ActivityLog mora zadržati stvarnog korisnika koji je napravio akciju.
            // Ne smije se pregaziti ownerId-em.
            if ($model instanceof ActivityLog) {
                return;
            }

            if (! Auth::check()) {
                return;
            }

            if (! Schema::hasColumn($model->getTable(), 'user_id')) {
                return;
            }

            $user = Auth::user();

            if ($model instanceof OperationalLog) {
                if (! $user?->isSuperAdmin()) {
                    $model->user_id = $user->id;
                } elseif (! $model->exists && empty($model->user_id)) {
                    $model->user_id = $user->id;
                }

                return;
            }

            if ($user?->isSuperAdmin()) {
                if (! $model->exists && empty($model->user_id)) {
                    $model->user_id = $user->id;
                }

                return;
            }

            $model->user_id = $user->ownerId();
        });
    }
}