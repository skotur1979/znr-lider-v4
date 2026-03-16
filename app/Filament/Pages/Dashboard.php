<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardCalendarWidget;
use App\Filament\Widgets\DashboardDeadlinesGrid;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Nadzorna ploča';

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardDeadlinesGrid::class,
            DashboardCalendarWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}