<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Exports\KpisExport;
use App\Filament\Resources\Kpis\KpiResource;
use App\Services\KpiCalculationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListKpis extends ListRecords
{
    protected static string $resource = KpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dashboard')
                ->label('KPI Dashboard')
                ->icon('heroicon-o-chart-bar-square')
                ->color('info')
                ->url(static::getResource()::getUrl('dashboard')),

            Action::make('reports')
                ->label('Izvještaji')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray')
                ->url(static::getResource()::getUrl('reports')),

            Action::make('bulk_entry')
                ->label('Bulk unos')
                ->icon('heroicon-o-table-cells')
                ->color('warning')
                ->url(static::getResource()::getUrl('bulk-entry')),

            Action::make('generate_current_month')
                ->label('Generiraj tekući mjesec')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    app(KpiCalculationService::class)->generateForMonth(now()->month, now()->year);

                    Notification::make()
                        ->title('KPI vrijednosti su generirane za tekući mjesec.')
                        ->success()
                        ->send();
                }),

            Action::make('exportExcel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new KpisExport(),
                        'kpi-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),

            Action::make('create')
                ->label('Novi KPI')
                ->icon('heroicon-o-plus')
                ->color('warning')
                ->url(static::getResource()::getUrl('create')),
        ];
    }
}