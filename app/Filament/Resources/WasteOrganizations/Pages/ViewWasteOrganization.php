<?php

namespace App\Filament\Resources\WasteOrganizations\Pages;

use App\Filament\Resources\WasteOrganizations\WasteOrganizationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWasteOrganization extends ViewRecord
{
    protected static string $resource = WasteOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}