<?php

namespace App\Filament\Resources\WasteTypes\Pages;

use App\Filament\Resources\WasteTypes\WasteTypeResource;
use Filament\Resources\Pages\EditRecord;

class EditWasteType extends EditRecord
{
    protected static string $resource = WasteTypeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /*
         * Ownership vrste otpada se ne mijenja
         * prilikom uređivanja.
         */
        $data['user_id'] = $this->record->user_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }
}