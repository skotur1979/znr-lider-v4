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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /*
         * Vlasništvo zapisa ne mijenjamo prilikom uređivanja.
         */
        $data['user_id'] = $this->record->user_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

            DeleteAction::make()
                ->requiresConfirmation(),

            ForceDeleteAction::make()
                ->requiresConfirmation(),

            RestoreAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }
}
