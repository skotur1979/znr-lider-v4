<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Exports\ObservationsExport;
use App\Filament\Resources\Observations\ObservationResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $pregled =
            request()->query('pregled')
            ?? data_get(request()->query(), 'tableFilters.pregled.value')
            ?? data_get(request()->query(), 'filters.pregled.value');

        return match ($pregled) {
            'uskoro' => $query
                ->whereNotNull('target_date')
                ->whereIn('status', ['Not started', 'In progress'])
                ->whereDate('target_date', '>=', Carbon::today())
                ->whereDate('target_date', '<=', Carbon::today()->addDays(30)),

            'isteklo' => $query
                ->whereNotNull('target_date')
                ->whereIn('status', ['Not started', 'In progress'])
                ->whereDate('target_date', '<', Carbon::today()),

            default => $query,
        };
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