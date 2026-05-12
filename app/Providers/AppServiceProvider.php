<?php

namespace App\Providers;

use App\Filament\Widgets\DashboardCalendarWidget;
use App\Filament\Widgets\DashboardDeadlinesGrid;
use App\Models\OperationalLog;
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

        Event::listen('eloquent.saving: *', function (string $eventName, array $data) {
            $model = $data[0] ?? null;

            if (! $model instanceof Model) {
                return;
            }

            if (! Auth::check()) {
                return;
            }

            if (! Schema::hasColumn($model->getTable(), 'user_id')) {
                return;
            }

            $user = Auth::user();

            /*
             * Operativni dnevnik je privatni zapis korisnika.
             * Ne smije se spremati na ownerId(), nego na stvarnog prijavljenog korisnika.
             */
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

            /*
             * Svi ostali moduli ostaju organizacijski:
             * glavni korisnik i podkorisnici spremaju na ownerId().
             */
            $model->user_id = $user->ownerId();
        });
    }
}