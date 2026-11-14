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
        return PPEEquipmentResource::prepareData(
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