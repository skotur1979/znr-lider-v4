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
        // Super admin kreira globalni test koji vide svi korisnici
        if (Auth::user()?->isSuperAdmin()) {
            $data['user_id'] = null;
        } else {
            // Obični korisnik/admin kreira svoj privatni test
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}