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
            Actions\ViewAction::make()
                ->label('Pregled'),

            Actions\DeleteAction::make()
                ->label('Deaktiviraj')
                ->requiresConfirmation(),

            Actions\RestoreAction::make()
                ->label('Vrati')
                ->requiresConfirmation(),

            Actions\ForceDeleteAction::make()
                ->label('Trajno obriši')
                ->requiresConfirmation(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! auth()->user()?->isSuperAdmin()) {
            unset($data['user_id']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}