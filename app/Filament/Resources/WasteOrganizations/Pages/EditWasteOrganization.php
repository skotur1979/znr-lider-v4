<?php

namespace App\Filament\Resources\WasteOrganizations\Pages;

use App\Filament\Resources\WasteOrganizations\WasteOrganizationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWasteOrganization extends EditRecord
{
    protected static string $resource = WasteOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
