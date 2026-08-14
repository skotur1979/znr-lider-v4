<?php

namespace App\Filament\Resources\PPEEquipment\Pages;

use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePPEEquipment extends CreateRecord
{
    protected static string $resource = PPEEquipmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        /*
         * Superadmin kreira GLOBALNI zapis registra OZO.
         * Organizacijski korisnik kreira zapis svoje organizacije.
         */
        $data['user_id'] = $user->isSuperAdmin()
            ? null
            : $user->ownerId();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}