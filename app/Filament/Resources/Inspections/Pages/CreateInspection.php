<?php

namespace App\Filament\Resources\Inspections\Pages;

use App\Filament\Resources\Inspections\InspectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInspection extends CreateRecord
{
    protected static string $resource =
        InspectionResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        return InspectionResource::fillOwnershipData(
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