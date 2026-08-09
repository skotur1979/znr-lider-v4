<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Filament\Resources\Incidents\IncidentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIncident extends CreateRecord
{
    protected static string $resource =
        IncidentResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        return IncidentResource::fillOwnershipData(
            $data
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl(
            'index'
        );
    }
}