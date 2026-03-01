<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Exports\IncidentsExport;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Filament\Resources\Incidents\Widgets\IncidentsOverview;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListIncidents extends ListRecords
{
    protected static string $resource = IncidentResource::class;

    /**
     * ✅ Stats kartice gore (widget koji već imaš: IncidentsOverview.php)
     */
    protected function getHeaderWidgets(): array
{
    return [
        \App\Filament\Resources\Incidents\Widgets\IncidentsOverview::class,
    ];
}

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novi incident'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    // ✅ isti query kao tablica (user scope + soft delete scope uklonjen u resource)
                    $query = IncidentResource::getEloquentQuery();

                    // ✅ primijeni filtere iz tablice (status / vrsta / godina)
                    $query = $this->applyTableFiltersToQuery($query);

                    $incidents = $query
                        ->orderByDesc('date_occurred')
                        ->get();

                    $pdf = Pdf::loadView('pdf.incidents', compact('incidents'))
                        ->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'incidenti-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    // ✅ proslijedi trenutno stanje filtera u export
                    $filters = $this->getTableFiltersForm()->getState();

                    return Excel::download(
                        new IncidentsExport($filters),
                        'incidenti-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }

    /**
     * ✅ Primijeni filtere iz tablice na query (za export)
     * Napomena: moraš imati filtere u IncidentResource::table():
     * - status (active/trashed/all)
     * - type_of_incident
     * - godina_filter
     */
    private function applyTableFiltersToQuery(Builder $query): Builder
    {
        $filters = $this->getTableFiltersForm()->getState();

        // status filter (kao Machines)
        $status = data_get($filters, 'status.value');
        $query = match ($status) {
            'trashed' => $query->onlyTrashed(),
            'all'     => $query->withTrashed(),
            default   => $query->withoutTrashed(),
        };

        // vrsta incidenta
        $type = data_get($filters, 'type_of_incident.value');
        if ($type) {
            $query->where('type_of_incident', $type);
        }

        // godina
        $year = data_get($filters, 'godina_filter.value');
        if ($year) {
            $query->whereYear('date_occurred', $year);
        }

        return $query;
    }
}