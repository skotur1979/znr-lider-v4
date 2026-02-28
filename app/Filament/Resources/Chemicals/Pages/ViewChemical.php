<?php

namespace App\Filament\Resources\Chemicals\Pages;

use App\Filament\Resources\Chemicals\ChemicalResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewChemical extends ViewRecord
{
    protected static string $resource = ChemicalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
