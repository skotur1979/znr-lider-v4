<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Observations\ObservationResource;
use App\Services\ObservationReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ObservationReports extends Page
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        ObservationResource::class;

    protected string $view =
        'filament.resources.observations.pages.observation-reports';

    public string $year = 'all';

    public string $month = 'all';

    public ?string $location = null;

    public ?string $responsible = null;

    public ?string $priority = null;

    public ?string $status = null;

    public ?string $type = null;

    public ?string $hazard = null;

    /*
     * NOVO - izvor prijave.
     */
    public ?string $source = null;

    public array $report = [];

    public function mount(): void
    {
        if (
            $this->redirectIfMissingModulePermission(
                'view'
            )
        ) {
            return;
        }

        $this->year =
            (string) now()->year;

        $this->loadData();
    }

    public function updatedYear(): void
    {
        $this->loadData();
    }

    public function updatedMonth(): void
    {
        $this->loadData();
    }

    public function updatedLocation(): void
    {
        $this->loadData();
    }

    public function updatedResponsible(): void
    {
        $this->loadData();
    }

    public function updatedPriority(): void
    {
        $this->loadData();
    }

    public function updatedStatus(): void
    {
        $this->loadData();
    }

    public function updatedType(): void
    {
        $this->loadData();
    }

    public function updatedHazard(): void
    {
        $this->loadData();
    }

    public function updatedSource(): void
    {
        $this->loadData();
    }

    public function resetFilters(): void
    {
        $this->year =
            (string) now()->year;

        $this->month =
            'all';

        $this->location =
            null;

        $this->responsible =
            null;

        $this->priority =
            null;

        $this->status =
            null;

        $this->type =
            null;

        $this->hazard =
            null;

        $this->source =
            null;

        $this->loadData();
    }

    public function loadData(): void
    {
        if (
            ! ObservationResource
                ::canViewModule()
        ) {
            return;
        }

        $this->report =
            app(
                ObservationReportService::class
            )->report(
                $this->filters()
            );
    }

    protected function filters(): array
    {
        return [
            'year' =>
                $this->year,

            'month' =>
                $this->month,

            'location' =>
                $this->location,

            'responsible' =>
                $this->responsible,

            'priority' =>
                $this->priority,

            'status' =>
                $this->status,

            'type' =>
                $this->type,

            'hazard' =>
                $this->hazard,

            'source' =>
                $this->source,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make(
                'export_report_pdf'
            )
                ->label(
                    'Izvoz izvještaja u PDF'
                )
                ->icon(
                    'heroicon-o-arrow-down-tray'
                )
                ->color(
                    'warning'
                )
                ->action(
                    function () {
                        if (
                            ! ObservationResource
                                ::allowsModulePermission(
                                    'view'
                                )
                        ) {
                            return null;
                        }

                        $report =
                            app(
                                ObservationReportService::class
                            )->report(
                                $this->filters()
                            );

                        $pdf =
                            Pdf::loadView(
                                'pdf.observation-reports',
                                [
                                    'report' =>
                                        $report,

                                    'filters' =>
                                        $this->filters(),
                                ]
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

                        $yearLabel =
                            $this->year
                                === 'all'
                                ? 'sve-godine'
                                : $this->year;

                        return response()
                            ->streamDownload(
                                fn () =>
                                    print(
                                        $pdf->output()
                                    ),
                                'izvjestaj-zapazanja-'
                                    . $yearLabel
                                    . '-'
                                    . now()->format(
                                        'Y-m-d'
                                    )
                                    . '.pdf'
                            );
                    }
                ),

            Action::make('back')
                ->label(
                    'Popis zapažanja'
                )
                ->icon(
                    'heroicon-o-arrow-left'
                )
                ->color('gray')
                ->url(
                    ObservationResource
                        ::getUrl(
                            'index'
                        )
                ),
        ];
    }

    public function getTitle(): string
    {
        return 'Izvještaji zapažanja';
    }
}