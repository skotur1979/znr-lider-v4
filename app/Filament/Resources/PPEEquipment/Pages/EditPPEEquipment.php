<?php

namespace App\Filament\Resources\PPEEquipment\Pages;

use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPPEEquipment extends EditRecord
{
    protected static string $resource = PPEEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Izbriši'),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? static::getResource()::getUrl('index');
    }
}