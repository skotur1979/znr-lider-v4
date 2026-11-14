<?php

namespace App\Services;

use App\Models\FirstAidKit;
use App\Models\QrCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FirstAidKitQrService
{
    public function getOrCreate(
        FirstAidKit $firstAidKit,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        $this->authorizeKit(
            $firstAidKit,
            $actor
        );

        $ownerId =
            (int) $firstAidKit->user_id;

        $qrCode =
            QrCode::query()
                ->firstOrNew([
                    'type' =>
                        'first_aid_kit',

                    'qrable_type' =>
                        FirstAidKit::class,

                    'qrable_id' =>
                        $firstAidKit->getKey(),
                ]);

        $isNew =
            ! $qrCode->exists;

        $qrCode->forceFill([
            'owner_id' =>
                $ownerId,

            'name' =>
                $this->qrName(
                    $firstAidKit
                ),
        ]);

        if ($isNew) {
            $qrCode->forceFill([
                'created_by' =>
                    $actor?->id,

                'is_active' =>
                    true,
            ]);
        }

        $qrCode->save();

        return $qrCode->refresh();
    }

    public function publicUrl(
        QrCode $qrCode
    ): string {
        return route(
            'public.first-aid-kit.show',
            [
                'token' =>
                    $qrCode->token,
            ]
        );
    }

    public function svg(
        QrCode $qrCode,
        int $size = 500
    ): string {
        $renderer =
            new ImageRenderer(
                new RendererStyle(
                    $size,
                    4
                ),
                new SvgImageBackEnd()
            );

        return (
            new Writer(
                $renderer
            )
        )->writeString(
            $this->publicUrl(
                $qrCode
            )
        );
    }

    public function qrName(
        FirstAidKit $firstAidKit
    ): string {
        return
            'Prva pomoć - '
            . trim(
                (string) $firstAidKit->location
            );
    }

    public function regenerate(
        QrCode $qrCode
    ): QrCode {
        $this->authorizeQr(
            $qrCode
        );

        $qrCode->forceFill([
            'token' =>
                QrCode::generateUniqueToken(),

            'is_active' =>
                true,

            'scan_count' =>
                0,

            'last_scanned_at' =>
                null,
        ])->save();

        return $qrCode->refresh();
    }

    public function deactivate(
        QrCode $qrCode
    ): void {
        $this->authorizeQr(
            $qrCode
        );

        $qrCode->update([
            'is_active' =>
                false,
        ]);
    }

    public function activate(
        QrCode $qrCode
    ): void {
        $this->authorizeQr(
            $qrCode
        );

        $qrCode->update([
            'is_active' =>
                true,
        ]);
    }

    protected function authorizeKit(
        FirstAidKit $firstAidKit,
        ?User $actor
    ): void {
        if (! $actor) {
            throw new AuthorizationException(
                'Korisnik nije prijavljen.'
            );
        }

        $ownerId =
            (int) $firstAidKit->user_id;

        if ($ownerId <= 0) {
            throw ValidationException::withMessages([
                'user_id' =>
                    'Ormarić prve pomoći nema valjanu organizaciju.',
            ]);
        }

        if (
            ! $actor->isSuperAdmin()
            && (int) $actor->ownerId()
                !== $ownerId
        ) {
            throw new AuthorizationException(
                'Nemate pristup ovom ormariću prve pomoći.'
            );
        }
    }

    protected function authorizeQr(
        QrCode $qrCode
    ): void {
        $actor =
            Auth::user();

        if (! $actor) {
            throw new AuthorizationException(
                'Korisnik nije prijavljen.'
            );
        }

        if (
            $qrCode->type
                !== 'first_aid_kit'
            || $qrCode->qrable_type
                !== FirstAidKit::class
        ) {
            throw ValidationException::withMessages([
                'qr' =>
                    'QR kod nije QR kod ormarića prve pomoći.',
            ]);
        }

        if (
            ! $actor->isSuperAdmin()
            && (int) $actor->ownerId()
                !== (int) $qrCode->owner_id
        ) {
            throw new AuthorizationException(
                'Nemate pravo upravljati ovim QR kodom.'
            );
        }
    }
}