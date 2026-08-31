<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Exports\ObservationsExport;
use App\Filament\Resources\Observations\ObservationResource;
use App\Filament\Resources\Pages\BaseListRecords;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ListObservations extends BaseListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource =
        ObservationResource::class;

    public function getMaxContentWidth(): ?string
    {
        return 'screen-2xl';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo zapažanje')
                ->icon('heroicon-o-plus')
                ->visible(
                    fn (): bool =>
                        ObservationResource::canCreate()
                )
                ->before(
                    ObservationResource
                        ::beforeModulePermission(
                            'create'
                        )
                ),

            Actions\Action::make(
                'public_observation_qr'
            )
                ->label(
                    'QR kod za prijavu'
                )
                ->icon(
                    'heroicon-o-qr-code'
                )
                ->color(
                    'success'
                )
                ->visible(
                    fn (): bool =>
                        auth()->user()
                        && ! auth()->user()
                            ->isSuperAdmin()
                        && ObservationResource
                            ::allowsModulePermission(
                                'create'
                            )
                )
                ->url(
                    fn (): string =>
                        route(
                            'observation.qr.admin'
                        )
                )
                ->openUrlInNewTab(),

            Actions\Action::make('reports')
                ->label('Izvještaji')
                ->icon(
                    'heroicon-o-chart-bar-square'
                )
                ->color('info')
                ->action(function () {
                    if (
                        ! ObservationResource
                            ::allowsModulePermission(
                                'view'
                            )
                    ) {
                        return;
                    }

                    return redirect(
                        ObservationResource::getUrl(
                            'reports'
                        )
                    );
                }),

            Actions\Action::make(
                'export_pdf'
            )
                ->label(
                    'Izvoz popisa u PDF'
                )
                ->icon(
                    'heroicon-o-arrow-down-tray'
                )
                ->color('warning')
                ->action(function () {
                    if (
                        ! ObservationResource
                            ::allowsModulePermission(
                                'view'
                            )
                    ) {
                        return null;
                    }

                    $observations =
                        $this
                            ->getFilteredSortedTableQuery()
                            ->get();

                    $pdf =
                        Pdf::loadView(
                            'pdf.observations',
                            compact(
                                'observations'
                            )
                        )
                            ->setPaper(
                                'a4',
                                'landscape'
                            )
                            ->setOptions([
                                'isHtml5ParserEnabled' =>
                                    true,

                                'isRemoteEnabled' =>
                                    true,

                                'isPhpEnabled' =>
                                    true,

                                'dpi' =>
                                    96,

                                'defaultFont' =>
                                    'DejaVu Sans',
                            ]);

                    return response()
                        ->streamDownload(
                            fn () =>
                                print(
                                    $pdf->output()
                                ),
                            'zapazanja-popis-'
                                . now()->format(
                                    'Y-m-d'
                                )
                                . '.pdf'
                        );
                }),

            Actions\Action::make(
                'export_excel'
            )
                ->label(
                    'Izvoz popisa u Excel'
                )
                ->icon(
                    'heroicon-o-document-arrow-down'
                )
                ->color('success')
                ->action(function () {
                    if (
                        ! ObservationResource
                            ::allowsModulePermission(
                                'view'
                            )
                    ) {
                        return null;
                    }

                    $observationIds =
                        $this
                            ->getFilteredSortedTableQuery()
                            ->pluck(
                                'observations.id'
                            )
                            ->toArray();

                    return Excel::download(
                        new ObservationsExport(
                            $observationIds
                        ),
                        'zapazanja-popis-'
                            . now()->format(
                                'Y-m-d'
                            )
                            . '.xlsx'
                    );
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return ObservationResource
            ::getWidgets();
    }

    protected function getTableQuery(): Builder
    {
        $query =
            parent::getTableQuery();

        $pregled =
            request()->query(
                'pregled'
            )
            ?? data_get(
                request()->query(),
                'tableFilters.pregled.value'
            )
            ?? data_get(
                request()->query(),
                'filters.pregled.value'
            );

        return match ($pregled) {
            'uskoro' =>
                $query
                    ->whereNotNull(
                        'target_date'
                    )
                    ->whereIn(
                        'status',
                        [
                            'Not started',
                            'In progress',
                        ]
                    )
                    ->whereDate(
                        'target_date',
                        '>=',
                        Carbon::today()
                    )
                    ->whereDate(
                        'target_date',
                        '<=',
                        Carbon::today()
                            ->addDays(30)
                    ),

            'isteklo' =>
                $query
                    ->whereNotNull(
                        'target_date'
                    )
                    ->whereIn(
                        'status',
                        [
                            'Not started',
                            'In progress',
                        ]
                    )
                    ->whereDate(
                        'target_date',
                        '<',
                        Carbon::today()
                    ),

            default =>
                $query,
        };
    }

    public function getSelectedYearRaw(): ?string
    {
        $year =
            data_get(
                $this->getTableFilterState(
                    'year'
                ),
                'value'
            )
            ?? data_get(
                $this->tableFilters,
                'year.value'
            )
            ?? data_get(
                $this,
                'filters.year.value'
            )
            ?? data_get(
                request()->query(),
                'tableFilters.year.value'
            )
            ?? data_get(
                request()->query(),
                'filters.year.value'
            );

        if (
            blank($year)
            || $year === 'all'
            || $year === 'SVE'
        ) {
            return null;
        }

        return (string) $year;
    }

    public function getSelectedYearLabel(): string
    {
        return $this
            ->getSelectedYearRaw()
            ?? 'SVE';
    }
}