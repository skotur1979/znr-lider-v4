<?php

namespace App\Filament\Resources\WorkTasks\Pages;

use App\Filament\Resources\WorkTasks\WorkTaskResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWorkTask extends CreateRecord
{
    protected static string $resource = WorkTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        abort_unless($user, 403);

        /*
         * Radni zadatak pripada organizaciji.
         * Glavni korisnik i podkorisnici zato uvijek
         * spremaju ownerId().
         */
        abort_if($user->isSuperAdmin(), 403);

        $data['user_id'] = $user->ownerId();
        $data['is_done'] = false;
        $data['completed_at'] = null;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}