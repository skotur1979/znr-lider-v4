<?php

namespace App\Providers;

use App\Filament\Widgets\DashboardCalendarWidget;
use App\Filament\Widgets\DashboardDeadlinesGrid;
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
    }
}
