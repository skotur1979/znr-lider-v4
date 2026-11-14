<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Filament\Resources\PPELogs\PPELogResource;
use App\Models\PPELog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreatePPELog extends CreateRecord
{
    protected static string $resource =
        PPELogResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $user = Auth::user();

        if (
            ! $user
            || $user->isSuperAdmin()
            || ! PPELogResource::canCreate()
        ) {
            abort(403);
        }

        $ownerId =
            (int) $user->ownerId();

        if ($ownerId <= 0) {
            abort(403);
        }

        $oib = preg_replace(
            '/\s+/',
            '',
            trim(
                (string) (
                    $data['user_oib']
                    ?? ''
                )
            )
        );

        if ($oib === '') {
            throw ValidationException::withMessages([
                'user_oib' =>
                    'OIB je obavezan.',
            ]);
        }

        /*
         * Ne dopuštamo drugi Upisnik OZO za istog
         * zaposlenika unutar iste organizacije,
         * čak ni ako je stari zapis deaktiviran.
         * Takav zapis treba vratiti iz deaktiviranih.
         */
        $alreadyExists =
            PPELog::withTrashed()
                ->where(
                    'user_id',
                    $ownerId
                )
                ->where(
                    'user_oib',
                    $oib
                )
                ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'user_oib' =>
                    'Upisnik OZO za ovaj OIB već postoji. '
                    . 'Ako je zapis deaktiviran, vratite ga iz deaktiviranih zapisa.',
            ]);
        }

        $data['user_id'] =
            $ownerId;

        $data['user_oib'] =
            $oib;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
