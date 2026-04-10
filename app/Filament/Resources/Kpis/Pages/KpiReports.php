<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use App\Services\KpiCalculationService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class KpiReports extends Page
{
    protected static string $resource = KpiResource::class;

    protected string $view = 'filament.resources.kpis.pages.kpi-reports';

    public int $year;

    public array $groups = [];

    public function mount(): void
    {
        $this->year = now()->year;
        $this->loadData();
    }

    public function updatedYear(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->groups = app(KpiCalculationService::class)
            ->yearlyReportGrouped($this->year)
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dashboard')
                ->label('KPI Dashboard')
                ->icon('heroicon-o-chart-bar-square')
                ->color('warning')
                ->url(KpiResource::getUrl('dashboard')),

            Action::make('bulk_entry')
                ->label('Bulk unos')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(KpiResource::getUrl('bulk-entry')),
        ];
    }

    public function getTitle(): string
    {
        return 'KPI Izvještaji';
    }
}