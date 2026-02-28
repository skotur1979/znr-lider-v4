<?php

namespace App\Filament\Resources\DocumentationItems\Pages;

use App\Filament\Resources\DocumentationItems\DocumentationItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentationItems extends ListRecords
{
    protected static string $resource = DocumentationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nova dokumentacija'),
        ];
    }
}