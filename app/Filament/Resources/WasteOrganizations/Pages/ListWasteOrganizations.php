<?php

namespace App\Filament\Resources\WasteOrganizations\Pages;

use App\Filament\Resources\WasteOrganizations\WasteOrganizationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWasteOrganizations extends ListRecords
{
    protected static string $resource = WasteOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova organizacija'),
        ];
    }
}