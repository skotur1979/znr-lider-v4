<?php

namespace App\Filament\Resources\InspectionZones\Pages;

use App\Filament\Resources\Inspections\InspectionResource;
use App\Filament\Resources\InspectionZones\InspectionZoneResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInspectionZone extends ViewRecord
{
    protected static string $resource =
        InspectionZoneResource::class;

    public function mount(
        int|string $record
    ): void {
        /*
         * parent::mount() prvo učitava zonu kroz
         * InspectionZoneResource query.
         *
         * Time se primjenjuju tenant i permission
         * provjere prije bilo kakvog daljnjeg rada.
         */
        parent::mount($record);

        /*
         * Zona nema zasebnu View stranicu za korištenje.
         *
         * Pregled i ocjenjivanje rade se kroz
         * Nadzori -> 5S zone.
         *
         * Namjerno ne koristimo return_url ni HTTP referer
         * kako vanjski URL ne bi mogao upravljati redirectom.
         */
        $this->redirect(
            InspectionResource::getUrl(
                'index'
            ),
            navigate: true
        );
    }

    public function getTitle(): string
    {
        return 'Pregled ocjena zone';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}