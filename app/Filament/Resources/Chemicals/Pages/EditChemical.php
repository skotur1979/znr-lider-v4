<?php

namespace App\Filament\Resources\Chemicals\Pages;

use App\Filament\Resources\Chemicals\ChemicalResource;
use Filament\Resources\Pages\EditRecord;

class EditChemical extends EditRecord
{
    protected static string $resource = ChemicalResource::class;

   protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? static::getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}