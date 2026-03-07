<?php

namespace App\Filament\Resources\Observations\Widgets;

use App\Filament\Resources\Observations\Pages\ListObservations;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ObservationStatsTopRow extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListObservations::class;
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $baseQuery = $this->getPageTableQuery();
        $year = $this->getSelectedYear();

        $nm = (clone $baseQuery)
            ->where('observation_type', 'Near Miss')
            ->count();

        $negative = (clone $baseQuery)
            ->where('observation_type', 'Negative Observation')
            ->count();

        $positive = (clone $baseQuery)
            ->where('observation_type', 'Positive Observation')
            ->count();

        return [
            Stat::make('GODINA', (string) $year)
                ->description('Odabrana godina'),

            Stat::make('NM', (string) $nm)
                ->description('Near Miss - skoro nezgoda'),

            Stat::make('NEGATIVNA', (string) $negative)
                ->description('Negativna zapažanja'),

            Stat::make('POZITIVNA', (string) $positive)
                ->description('Pozitivna zapažanja'),
        ];
    }

    protected function getSelectedYear(): int
    {
        $page = $this->getTablePageInstance();

        $year = data_get($page, 'tableFilters.year.value')
            ?? data_get($page, 'filters.year.value')
            ?? data_get($page, 'mountedTableFilters.year.value')
            ?? now()->year;

        return (int) $year;
    }
}