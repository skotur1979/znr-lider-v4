<?php

namespace App\Filament\Resources\Fires\Pages;

use App\Filament\Resources\Fires\FireResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFire extends EditRecord
{
    protected static string $resource = FireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Pregled'),
            Actions\DeleteAction::make()->requiresConfirmation(),
            Actions\RestoreAction::make()->requiresConfirmation(),
            Actions\ForceDeleteAction::make()->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}