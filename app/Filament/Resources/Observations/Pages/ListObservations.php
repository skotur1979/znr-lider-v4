<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Resources\Observations\ObservationResource;
use App\Exports\ObservationsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListObservations extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ObservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo zapažanje'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    $observations = ObservationResource::getEloquentQuery()
                        ->orderByDesc('incident_date')
                        ->get();

                    $pdf = Pdf::loadView('pdf.observations', compact('observations'))
                        ->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'zapazanja-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => Excel::download(
                    new ObservationsExport(),
                    'zapazanja-' . now()->format('Y-m-d') . '.xlsx'
                )),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return ObservationResource::getWidgets();
    }

    public function getSelectedYearRaw(): ?string
    {
        $year =
            data_get($this->getTableFilterState('year'), 'value')
            ?? data_get($this->tableFilters, 'year.value')
            ?? data_get($this, 'filters.year.value')
            ?? data_get(request()->query(), 'tableFilters.year.value')
            ?? data_get(request()->query(), 'filters.year.value');

        if (blank($year) || $year === 'all' || $year === 'SVE') {
            return null;
        }

        return (string) $year;
    }

    public function getSelectedYearLabel(): string
    {
        return $this->getSelectedYearRaw() ?? 'SVE';
    }
}