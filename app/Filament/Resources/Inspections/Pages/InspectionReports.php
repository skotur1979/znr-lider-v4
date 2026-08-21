<?php

namespace App\Filament\Resources\Inspections\Pages;

use App\Filament\Resources\Inspections\InspectionResource;
use App\Services\InspectionReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;

class InspectionReports extends Page
{
    protected static string $resource =
        InspectionResource::class;

    protected string $view =
        'filament.resources.inspections.pages.inspection-reports';

    protected Width|string|null $maxContentWidth =
        '7xl';

    public string $year = 'all';

    public string $month = 'all';

    public string $location = '';

    public string $performed_by = '';

    public string $category = '';

    public string $finding_status = '';

    public string $workflow_status = '';

    public string $responsible_person = '';

    /*
     * Nadzor iz kojeg je izvještaj otvoren.
     *
     * Koristi se samo za povratak
     * na karticu Nalazi nadzora.
     */
    public ?int $inspectionId = null;

    public function mount(): void
    {
        $this->year =
            (string) now()->year;

        $inspectionId =
            request()->integer(
                'inspection'
            );

        $this->inspectionId =
            $inspectionId > 0
                ? $inspectionId
                : null;
    }

    public function getTitle(): string
    {
        return 'Izvještaji nadzora';
    }

    public function getHeading(): string
    {
        return 'Izvještaji nadzora';
    }

    public function getReportProperty(): array
    {
        return app(
            InspectionReportService::class
        )->report([
            'year' =>
                $this->year,

            'month' =>
                $this->month,

            'location' =>
                $this->location,

            'performed_by' =>
                $this->performed_by,

            'category' =>
                $this->category,

            'finding_status' =>
                $this->finding_status,

            'workflow_status' =>
                $this->workflow_status,

            'responsible_person' =>
                $this->responsible_person,
        ]);
    }

    public function resetFilters(): void
    {
        $this->year =
            (string) now()->year;

        $this->month =
            'all';

        $this->location =
            '';

        $this->performed_by =
            '';

        $this->category =
            '';

        $this->finding_status =
            '';

        $this->workflow_status =
            '';

        $this->responsible_person =
            '';
    }

    public function getBackUrl(): string
    {
        if ($this->inspectionId) {
            return InspectionResource::getUrl(
                'edit',
                [
                    'record' =>
                        $this->inspectionId,
                ]
            ) . '?relation=0';
        }

        return InspectionResource::getUrl(
            'index'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(
                    $this->inspectionId
                        ? 'Povratak na nalaze'
                        : 'Popis nadzora'
                )
                ->icon(
                    'heroicon-o-arrow-left'
                )
                ->color('gray')
                ->url(
                    fn (): string =>
                        $this->getBackUrl()
                ),
        ];
    }
}