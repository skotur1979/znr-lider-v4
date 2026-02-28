<?php

namespace App\Filament\Resources\Chemicals\Pages;

use App\Filament\Resources\Chemicals\ChemicalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateChemical extends CreateRecord
{
    protected static string $resource = ChemicalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] ??= Auth::id();
        return $data;
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}