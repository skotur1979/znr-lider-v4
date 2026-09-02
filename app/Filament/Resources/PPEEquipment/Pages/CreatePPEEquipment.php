<?php

namespace App\Filament\Resources\PPEEquipment\Pages;

use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePPEEquipment extends CreateRecord
{
    protected static string $resource =
        PPEEquipmentResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        /*
         * Registar OZO je organizacijski modul.
         *
         * BaseResource automatski postavlja:
         *
         * user_id = ownerId()
         *
         * za glavnog korisnika i podkorisnike.
         *
         * Superadmin ne kreira OZO zapise
         * u ime organizacije.
         */
        return PPEEquipmentResource::fillOwnershipData(
            $data
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }
}