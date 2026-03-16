<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\DashboardDeadlinesGrid;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Nadzorna ploča';

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardDeadlinesGrid::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}