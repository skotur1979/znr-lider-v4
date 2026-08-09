<?php

namespace App\Filament\Resources\WasteOrganizations\Pages;

use App\Filament\Resources\WasteOrganizations\WasteOrganizationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWasteOrganization extends CreateRecord
{
    protected static string $resource = WasteOrganizationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        abort_unless($user, 403);

        /*
         * Organizacijske zapise kreiraju glavni korisnik
         * i podkorisnici svoje organizacije.
         */
        abort_if($user->isSuperAdmin(), 403);

        $data['user_id'] = $user->ownerId();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}