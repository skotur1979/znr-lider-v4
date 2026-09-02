<?php

namespace App\Filament\Resources\PPEEquipment\Pages;

use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPPEEquipment extends EditRecord
{
    protected static string $resource =
        PPEEquipmentResource::class;

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership postojećeg OZO zapisa
         * nikada se ne mijenja kroz edit formu.
         */
        unset(
            $data['user_id']
        );

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Izbriši')
                ->requiresConfirmation()
                ->modalHeading(
                    'Izbriši OZO opremu'
                )
                ->modalDescription(
                    'Jesi li siguran/a da želiš izbrisati ovu OZO opremu?'
                )
                ->modalSubmitActionLabel(
                    'Izbriši'
                )
                ->modalCancelActionLabel(
                    'Odustani'
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}