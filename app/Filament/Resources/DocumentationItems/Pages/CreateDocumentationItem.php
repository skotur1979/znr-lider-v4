<?php

namespace App\Filament\Resources\DocumentationItems\Pages;

use App\Filament\Resources\DocumentationItems\DocumentationItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentationItem extends CreateRecord
{
    protected static string $resource =
        DocumentationItemResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        return DocumentationItemResource::fillOwnershipData(
            $data
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}