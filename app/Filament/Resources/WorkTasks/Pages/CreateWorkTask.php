<?php

namespace App\Filament\Resources\WorkTasks\Pages;

use App\Filament\Resources\WorkTasks\WorkTaskResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWorkTask extends CreateRecord
{
    protected static string $resource =
        WorkTaskResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $user = Auth::user();

        abort_unless(
            $user,
            403
        );

        /*
         * Superadmin ne kreira zadatak
         * u ime organizacije.
         */
        abort_if(
            $user->isSuperAdmin(),
            403
        );

        $ownerId =
            $user->ownerId();

        abort_unless(
            $ownerId,
            403
        );

        /*
         * Organizacija kojoj zadatak pripada.
         */
        $data['user_id'] =
            $ownerId;

        /*
         * Stvarni korisnik koji je
         * napravio radni zadatak.
         */
        $data['created_by_user_id'] =
            $user->id;

        /*
         * Ako toggle nije uključen,
         * zadatak je osoban.
         */
        $data['is_shared_with_organization'] =
            (bool) (
                $data[
                    'is_shared_with_organization'
                ]
                ?? false
            );

        /*
         * Novi zadatak je otvoren.
         */
        $data['is_done'] =
            false;

        $data['completed_at'] =
            null;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }
}