<?php

namespace App\Filament\Resources\WasteOrganizations\Pages;

use App\Filament\Resources\WasteOrganizations\WasteOrganizationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWasteOrganization extends CreateRecord
{
    protected static string $resource = WasteOrganizationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}