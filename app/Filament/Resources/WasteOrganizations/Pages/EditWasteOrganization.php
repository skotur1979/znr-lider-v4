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
         * Ownership postojećeg zapisa
         * nikada se ne mijenja uređivanjem.
         */
        $data['user_id'] = $this->record->user_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Prikaži'),

            DeleteAction::make()
                ->label('Deaktiviraj')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        ! $this->record->trashed()
                        && WasteOrganizationResource::canDelete(
                            $this->record
                        )
                ),

            RestoreAction::make()
                ->label('Vrati')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        $this->record->trashed()
                        && WasteOrganizationResource::canRestore(
                            $this->record
                        )
                ),

            ForceDeleteAction::make()
                ->label('Trajno izbriši')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        $this->record->trashed()
                        && WasteOrganizationResource::canForceDelete(
                            $this->record
                        )
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }
}
