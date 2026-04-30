<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Exports\IncidentsExport;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Filament\Resources\Incidents\Widgets\IncidentsOverview;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListIncidents extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = IncidentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            IncidentsOverview::class,
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
        $incidents = $this->getFilteredSortedTableQuery()
            ->get();

        $pdf = Pdf::loadView('pdf.incidents', compact('incidents'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'dpi' => 96,
                'defaultFont' => 'DejaVu Sans',
            ]);

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
                    $filters = $this->getTableFiltersForm()->getState();

                    return Excel::download(
                        new IncidentsExport($filters),
                        'incidenti-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }

    public function getSelectedYearRaw(): ?string
{
    $year =
        data_get($this->tableFilters, 'godina_filter.value')
        ?? data_get(request()->query(), 'filters.godina_filter.value')
        ?? data_get(request()->query(), 'tableFilters.godina_filter.value');

    if (blank($year) || $year === 'all' || $year === 'SVE') {
        return null;
    }

    return (string) $year;
}

public function getSelectedYearLabel(): string
{
    return $this->getSelectedYearRaw() ?? 'SVE';
}

    private function applyTableFiltersToQuery(Builder $query): Builder
    {
        $filters = $this->getTableFiltersForm()->getState();

        $status = data_get($filters, 'status.value');

        $query = match ($status) {
            'trashed' => $query->onlyTrashed(),
            'all' => $query->withTrashed(),
            default => $query->withoutTrashed(),
        };

        $type = data_get($filters, 'type_of_incident.value');

        if (filled($type)) {
            $query->where('type_of_incident', $type);
        }

        $year = data_get($filters, 'godina_filter.value');

        if (filled($year)) {
            $query->whereYear('date_occurred', $year);
        }

        return $query;
    }
}