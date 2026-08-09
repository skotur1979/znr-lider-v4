<?php

namespace App\Filament\Resources\WorkPermits\Pages;

use App\Filament\Resources\WorkPermits\WorkPermitResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWorkPermit extends CreateRecord
{
    protected static string $resource = WorkPermitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        /*
         * Dozvola za rad je poslovni zapis organizacije.
         *
         * Superadmin može pregledavati zapise organizacija,
         * ali ih ne kreira u njihovo ime.
         */
        if ($user->isSuperAdmin()) {
            abort(403);
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            abort(403);
        }

        /*
         * Ownership uvijek postavljamo serverski.
         *
         * Ne vjerujemo eventualnom user_id iz forme,
         * URL-a ili Livewire statea.
         */
        $data['user_id'] = $ownerId;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
