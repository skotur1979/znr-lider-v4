<?php

namespace App\Filament\Resources\DocumentationItems\Pages;

use App\Filament\Resources\DocumentationItems\DocumentationItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentationItem extends ViewRecord
{
    protected static string $resource = DocumentationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
