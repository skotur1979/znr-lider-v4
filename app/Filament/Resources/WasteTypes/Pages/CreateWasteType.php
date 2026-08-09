<?php

namespace App\Filament\Resources\WasteTypes\Pages;

use App\Filament\Resources\WasteTypes\WasteTypeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWasteType extends CreateRecord
{
    protected static string $resource = WasteTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        abort_unless($user, 403);

        /*
         * Vrste otpada kreiraju organizacijski korisnici.
         * Superadmin ih samo administrira.
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