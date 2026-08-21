<?php

namespace App\Filament\Resources\Inspections\Pages;

use App\Filament\Resources\Inspections\InspectionResource;
use App\Services\InspectionZoneReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;

class ZoneResultsReport extends Page
{
    protected static string $resource =
        InspectionResource::class;

    protected string $view =
        'filament.resources.inspections.pages.zone-results-report';

    protected Width|string|null $maxContentWidth =
        '7xl';

    public string $year = 'all';

    public string $location = '';

    public string $zone = '';

    /*
     * Nadzor iz kojeg smo otvorili izvještaj.
     * Koristi se za povratak na karticu 5S zone.
     */
    public ?int $inspectionId = null;

    public function mount(): void
    {
        $this->year =
            (string) now()->year;

        $inspectionId =
            request()->integer('inspection');

        $this->inspectionId =
            $inspectionId > 0
                ? $inspectionId
                : null;
    }

    public function getTitle(): string
    {
        return 'Izvještaj rezultata zona';
    }

    public function getHeading(): string
    {
        return 'Izvještaj rezultata zona';
    }

    public function getReportProperty(): array
    {
        return app(
            InspectionZoneReportService::class
        )->report([
            'year' =>
                $this->year,

            'location' =>
                $this->location,

            'zone' =>
                $this->zone,
        ]);
    }

    public function resetFilters(): void
    {
        $this->year =
            (string) now()->year;

        $this->location = '';

        $this->zone = '';
    }

    /*
     * Ako je izvještaj otvoren iz konkretnog nadzora,
     * vraćamo korisnika direktno na:
     *
     * Uredi nadzor -> 5S zone.
     *
     * Ako ID nadzora nije dostupan,
     * fallback je popis nadzora.
     */
    public function getBackUrl(): string
    {
        if ($this->inspectionId) {
            return InspectionResource::getUrl(
                'edit',
                [
                    'record' =>
                        $this->inspectionId,
                ]
            ) . '?relation=1';
        }

        return InspectionResource::getUrl(
            'index'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Povratak na 5S zone')
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