<?php

namespace App\Filament\Resources\DocumentationItems\Pages;

use App\Filament\Resources\DocumentationItems\DocumentationItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentationItem extends EditRecord
{
    protected static string $resource =
        DocumentationItemResource::class;

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership postojećeg zapisa
         * nikada se ne mijenja kroz edit formu.
         */
        unset($data['user_id']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}