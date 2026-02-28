<?php

namespace App\Filament\Resources\Chemicals\Pages;

use App\Filament\Resources\Chemicals\ChemicalResource;
use Filament\Resources\Pages\EditRecord;

class EditChemical extends EditRecord
{
    protected static string $resource = ChemicalResource::class;

    public function getMaxContentWidth(): ?string
{
    return 'full';
}
}