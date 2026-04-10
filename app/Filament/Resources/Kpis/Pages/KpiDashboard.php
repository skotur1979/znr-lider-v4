<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use App\Services\KpiCalculationService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class KpiDashboard extends Page
{
    protected static string $resource = KpiResource::class;

    protected string $view = 'filament.resources.kpis.pages.kpi-dashboard';

    public int $month;
    public int $year;

    public array $groups = [];

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;

        $this->loadData();
    }

    public function updatedMonth(): void
    {
        $this->loadData();
    }

    public function updatedYear(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->groups = app(KpiCalculationService::class)
            ->dashboardGroups($this->month, $this->year)
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reports')
                ->label('Izvještaji')
                ->icon('heroicon-o-document-chart-bar')
                ->color('warning')
                ->url(KpiResource::getUrl('reports')),

            Action::make('bulk_entry')
                ->label('Bulk unos')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(KpiResource::getUrl('bulk-entry')),
        ];
    }

    public function getTitle(): string
    {
        return 'KPI Dashboard';
    }
}