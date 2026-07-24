<?php

namespace App\Filament\Resources\LearningMaterials\Pages;

use App\Filament\Resources\LearningMaterials\LearningMaterialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLearningMaterial extends EditRecord
{
    protected static string $resource = LearningMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Obriši')
                ->requiresConfirmation()
                ->modalHeading('Obriši edukacijski materijal')
                ->modalDescription('Jeste li sigurni da želite obrisati ovaj edukacijski materijal?')
                ->modalSubmitActionLabel('Obriši')
                ->modalCancelActionLabel('Odustani'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}