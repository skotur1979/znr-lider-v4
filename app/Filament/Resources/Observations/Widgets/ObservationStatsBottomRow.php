<?php

namespace App\Filament\Resources\Observations\Widgets;

use App\Filament\Resources\Observations\Pages\ListObservations;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ObservationStatsBottomRow extends StatsOverviewWidget
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

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $baseQuery = $this->getPageTableQuery();

        $total = (clone $baseQuery)->count();

        $notStarted = (clone $baseQuery)
            ->where('status', 'Not started')
            ->count();

        $inProgress = (clone $baseQuery)
            ->where('status', 'In progress')
            ->count();

        $complete = (clone $baseQuery)
            ->where('status', 'Complete')
            ->count();

        return [
            Stat::make('UKUPNO', (string) $total)
                ->description('Ukupno prijavljeno'),

            Stat::make('NIJE ZAPOČETO', (string) $notStarted)
                ->description('Otvorena zapažanja')
                ->color('danger'),

            Stat::make('U TIJEKU', (string) $inProgress)
                ->description('Aktivna obrada')
                ->color('warning'),

            Stat::make('ZAVRŠENO', (string) $complete)
                ->description('Riješena zapažanja')
                ->color('success'),
        ];
    }
}