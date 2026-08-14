<?php

namespace App\Filament\Resources\Chemicals\Pages;

use App\Filament\Resources\Chemicals\ChemicalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChemical extends CreateRecord
{
    protected static string $resource = ChemicalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /*
         * Standardna multi-tenant logika.
         *
         * Glavni korisnik:
         * user_id = njegov ID
         *
         * Podkorisnik:
         * user_id = ID glavnog korisnika organizacije
         *
         * Superadmin standardno ne može kreirati
         * poslovne zapise kroz BaseResource.
         */
        return ChemicalResource::fillOwnershipData($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}