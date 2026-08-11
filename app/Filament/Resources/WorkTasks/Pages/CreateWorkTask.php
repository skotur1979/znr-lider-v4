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
        * Radni zadatak je poslovni zapis organizacije.
        *
        * Superadmin može pregledavati zadatke organizacija,
        * ali ih ne kreira u njihovo ime.
        */
        abort_if($user->isSuperAdmin(), 403);

        $ownerId = $user->ownerId();

        abort_unless($ownerId, 403);

        /*
        * Ownership uvijek određujemo serverski.
        */
        $data['user_id'] = $ownerId;

        /*
        * Novi zadatak uvijek počinje kao otvoren.
        */
        $data['is_done'] = false;
        $data['completed_at'] = null;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}