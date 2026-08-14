<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Filament\Resources\Incidents\IncidentResource;
use App\Services\IncidentReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class IncidentReports extends Page
{
    protected static string $resource =
        IncidentResource::class;

    protected string $view =
        'filament.resources.incidents.pages.incident-reports';

    public string $year = 'all';

    public string $month = 'all';

    public ?string $type = null;

    public ?string $location = null;

    public ?string $employment = null;

    public ?string $cause = null;

    public ?string $injuryType = null;

    public ?string $bodyPart = null;

    public array $report = [];

    public function mount(): void
    {
        abort_unless(
            IncidentResource::canViewAny(),
            403
        );

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

    public function updatedType(): void
    {
        $this->loadData();
    }

    public function updatedLocation(): void
    {
        $this->loadData();
    }

    public function updatedEmployment(): void
    {
        $this->loadData();
    }

    public function updatedCause(): void
    {
        $this->loadData();
    }

    public function updatedInjuryType(): void
    {
        $this->loadData();
    }

    public function updatedBodyPart(): void
    {
        $this->loadData();
    }

    public function resetFilters(): void
    {
        $this->year =
            (string) now()->year;

        $this->month = 'all';
        $this->type = null;
        $this->location = null;
        $this->employment = null;
        $this->cause = null;
        $this->injuryType = null;
        $this->bodyPart = null;

        $this->loadData();
    }

    public function loadData(): void
    {
        abort_unless(
            IncidentResource::canViewAny(),
            403
        );

        $this->report = app(
            IncidentReportService::class
        )->report(
            $this->filters()
        );
    }

    protected function filters(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'type' => $this->type,
            'location' => $this->location,
            'employment' => $this->employment,
            'cause' => $this->cause,
            'injury_type' => $this->injuryType,
            'body_part' => $this->bodyPart,
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
                ->color('warning')
                ->action(function () {
                    abort_unless(
                        IncidentResource::canViewAny(),
                        403
                    );

                    $report = app(
                        IncidentReportService::class
                    )->report(
                        $this->filters()
                    );

                    $pdf = Pdf::loadView(
                        'pdf.incident-reports',
                        [
                            'report' => $report,
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
                        $this->year === 'all'
                            ? 'sve-godine'
                            : $this->year;

                    return response()
                        ->streamDownload(
                            fn () => print(
                                $pdf->output()
                            ),
                            'izvjestaj-incidenti-'
                                . $yearLabel
                                . '-'
                                . now()->format(
                                    'Y-m-d'
                                )
                                . '.pdf'
                        );
                }),

            Action::make('back')
                ->label('Popis incidenata')
                ->icon(
                    'heroicon-o-arrow-left'
                )
                ->color('gray')
                ->url(
                    IncidentResource::getUrl(
                        'index'
                    )
                ),
        ];
    }

    public function getTitle(): string
    {
        return 'Izvještaji incidenata';
    }
}