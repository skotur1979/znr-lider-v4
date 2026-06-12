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

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
{
    return new \Illuminate\Support\HtmlString('
        <div class="flex items-center gap-2">
            <span>KPI</span>

            <span
                title="KPI = Key Performance Indicators (Ključni pokazatelji uspješnosti)"
                class="cursor-help text-primary-500"
            >
                ⓘ
            </span>
        </div>
    ');
}

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
                ->label('Ažuriraj tekući mjesec')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Ažuriranje KPI vrijednosti')
                ->modalDescription('Sustav će ponovno izračunati automatske KPI vrijednosti za tekući mjesec. Postojeće vrijednosti za isti mjesec bit će ažurirane, a ručni KPI unosi neće se mijenjati.')
                ->modalSubmitActionLabel('Ažuriraj KPI')
                ->action(function () {
                    $result = app(KpiCalculationService::class)
                        ->generateForMonth(now()->month, now()->year);

                    Notification::make()
                        ->title('KPI vrijednosti su ažurirane.')
                        ->body(
                            'Kreirano: ' . $result['generated'] .
                            ' | Ažurirano: ' . $result['updated'] .
                            ' | Preskočeno: ' . $result['skipped']
                        )
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