<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Filament\Resources\PPELogs\PPELogResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePPELog extends CreateRecord
{
    protected static string $resource = PPELogResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        /*
         * Upisnik OZO je organizacijski zapis.
         *
         * Glavni korisnik i svi njegovi podkorisnici
         * spremaju isti ownerId().
         */
        if ($user->isSuperAdmin()) {
            abort(403);
        }

        $data['user_id'] = $user->ownerId();

        return $data;
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
