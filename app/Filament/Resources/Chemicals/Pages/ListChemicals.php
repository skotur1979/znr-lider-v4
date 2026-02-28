<?php

namespace App\Filament\Resources\Chemicals\Pages;

use App\Filament\Resources\Chemicals\ChemicalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChemicals extends ListRecords
{
    protected static string $resource = ChemicalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova kemikalija'),
        ];
    }
}