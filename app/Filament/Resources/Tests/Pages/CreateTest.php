<?php

namespace App\Filament\Resources\Tests\Pages;

use App\Filament\Resources\Tests\TestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTest extends CreateRecord
{
    protected static string $resource = TestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        // Superadmin kreira globalni test.
        if ($user->isSuperAdmin()) {
            $data['user_id'] = null;
        } else {
            // Glavni korisnik i svi njegovi podkorisnici
            // koriste vlasnika organizacije.
            $data['user_id'] = $user->ownerId();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}