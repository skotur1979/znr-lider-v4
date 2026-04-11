<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use App\Services\KpiCalculationService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class KpiDashboard extends Page
{
    protected static string $resource = KpiResource::class;

    protected string $view = 'filament.resources.kpis.pages.kpi-dashboard';

    public string $viewMode = 'month';

    public int $month;
    public int $year;

    public ?int $compareMonth = null;
    public ?int $compareYear = null;

    public array $groups = [];
    public array $chartRows = [];
    public array $availableKpis = [];
    public array $selectedChartKpis = [];

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;
        $this->compareMonth = null;
        $this->compareYear = null;

        $this->loadData();
    }

    public function updatedViewMode(): void
    {
        if ($this->viewMode === 'year') {
            $this->compareMonth = null;
        }

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

    public function updatedCompareMonth(): void
    {
        if (blank($this->compareMonth)) {
            $this->compareMonth = null;
        }

        $this->loadData();
    }

    public function updatedCompareYear(): void
    {
        if (blank($this->compareYear)) {
            $this->compareYear = null;
        }

        $this->loadData();
    }

    public function updatedSelectedChartKpis(): void
    {
        $this->buildChartRows();
    }

    public function selectAllKpis(): void
    {
        $this->selectedChartKpis = collect($this->availableKpis)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->buildChartRows();
    }

    public function clearSelectedKpis(): void
    {
        $this->selectedChartKpis = [];
        $this->buildChartRows();
    }

    public function selectCategory(string $category): void
    {
        $this->selectedChartKpis = collect($this->availableKpis)
            ->where('category', $category)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $this->buildChartRows();
    }

    protected function loadData(): void
    {
        $service = app(KpiCalculationService::class);

        if ($this->viewMode === 'year') {
            $collection = $service->dashboardGroupsYearly(
                $this->year,
                $this->compareYear,
            );
        } else {
            $collection = $service->dashboardGroups(
                $this->month,
                $this->year,
                $this->compareMonth,
                $this->compareYear,
            );
        }

        $this->groups = $collection->toArray();

        $flat = $collection->flatten(1)->values();

        $this->availableKpis = $flat
            ->map(fn (array $row) => [
                'id' => (string) $row['id'],
                'name' => $row['name'],
                'category' => $row['category'] ?? '',
            ])
            ->all();

        if (empty($this->selectedChartKpis)) {
            $this->selectedChartKpis = $flat
                ->take(8)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();
        } else {
            $validIds = $flat->pluck('id')->map(fn ($id) => (string) $id)->all();
            $this->selectedChartKpis = array_values(array_intersect($this->selectedChartKpis, $validIds));
        }

        $this->buildChartRows($flat);
    }

    protected function buildChartRows(?Collection $flat = null): void
    {
        $flat ??= collect($this->groups)->flatten(1);

        $selectedIds = collect($this->selectedChartKpis)
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->chartRows = $flat
            ->filter(fn (array $row) => in_array((string) $row['id'], $selectedIds, true))
            ->map(function (array $row) {
                return [
                    'id' => (string) $row['id'],
                    'name' => $row['name'],
                    'category' => $row['category'] ?? '',
                    'current_value' => $row['current_value'] ?? null,
                    'compare_value' => $row['compare_value'] ?? null,
                    'formatted_current' => $row['formatted_current'] ?? '-',
                    'formatted_compare' => $row['formatted_compare'] ?? '-',
                ];
            })
            ->sortByDesc(function (array $row) {
                return max(
                    (float) ($row['current_value'] ?? 0),
                    (float) ($row['compare_value'] ?? 0)
                );
            })
            ->values()
            ->all();
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