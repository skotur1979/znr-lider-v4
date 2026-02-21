<?php

namespace App\Filament\Resources\Fires\Pages;

use App\Filament\Resources\Fires\FireResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFire extends ViewRecord
{
    protected static string $resource = FireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Uredi'),
            Actions\DeleteAction::make()->requiresConfirmation(),
            Actions\RestoreAction::make()->requiresConfirmation(),
            Actions\ForceDeleteAction::make()->requiresConfirmation(),
        ];
    }
}