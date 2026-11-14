<?php

namespace App\Services;

use App\Models\PPEEquipment;
use App\Models\QrCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

class PPEEquipmentQrService
{
    public function getOrCreate(
        PPEEquipment $ppeEquipment,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        if (! $actor) {
            throw new AuthorizationException(
                'Korisnik nije prijavljen.'
            );
        }

        $ownerId =
            (int) $ppeEquipment->user_id;

        if ($ownerId <= 0) {
            throw new RuntimeException(
                'OZO zapis nema valjanog vlasnika.'
            );
        }

        if (
            ! $actor->isSuperAdmin()
            && (int) $actor->ownerId()
                !== $ownerId
        ) {
            throw new AuthorizationException(
                'Nemate pristup ovoj OZO opremi.'
            );
        }

        $qrCode =
            QrCode::query()
                ->firstOrNew([
                    'type' =>
                        'ppe_equipment',

                    'qrable_type' =>
                        PPEEquipment::class,

                    'qrable_id' =>
                        $ppeEquipment->getKey(),
                ]);

        $isNew =
            ! $qrCode->exists;

        /*
         * Ako postoji legacy QR s pogrešnim owner_id,
         * ovdje ga automatski usklađujemo s OZO zapisom.
         */
        $qrCode->owner_id =
            $ownerId;

        $qrCode->name =
            $this->qrName(
                $ppeEquipment
            );

        if ($isNew) {
            $qrCode->created_by =
                $actor->id;

            $qrCode->is_active =
                true;

            $qrCode->scan_count =
                0;
        } elseif (
            blank(
                $qrCode->created_by
            )
        ) {
            $qrCode->created_by =
                $actor->id;
        }

        if (
            blank(
                $qrCode->token
            )
        ) {
            $qrCode->token =
                QrCode::generateUniqueToken();
        }

        if (
            $isNew
            || $qrCode->isDirty()
        ) {
            $qrCode->save();
        }

        return $qrCode->refresh();
    }

    public function publicUrl(
        QrCode $qrCode
    ): string {
        $this->assertPPEEquipmentQr(
            $qrCode
        );

        return route(
            'public.ppe-equipment.show',
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
        $this->assertPPEEquipmentQr(
            $qrCode
        );

        $size =
            max(
                100,
                min(
                    2000,
                    $size
                )
            );

        $renderer =
            new ImageRenderer(
                new RendererStyle(
                    $size,
                    4
                ),
                new SvgImageBackEnd()
            );

        $writer =
            new Writer(
                $renderer
            );

        return $writer->writeString(
            $this->publicUrl(
                $qrCode
            )
        );
    }

    public function qrName(
        PPEEquipment $ppeEquipment
    ): string {
        $name =
            trim(
                (string)
                $ppeEquipment->name
            );

        if (
            filled(
                $ppeEquipment->standard
            )
        ) {
            $name .=
                ' - '
                . trim(
                    (string)
                    $ppeEquipment->standard
                );
        }

        return Str::limit(
            $name,
            191,
            ''
        );
    }

    public function regenerate(
        QrCode $qrCode
    ): QrCode {
        $this->assertPPEEquipmentQr(
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
        $this->assertPPEEquipmentQr(
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
        $this->assertPPEEquipmentQr(
            $qrCode
        );

        $qrCode->update([
            'is_active' =>
                true,
        ]);
    }

    protected function assertPPEEquipmentQr(
        QrCode $qrCode
    ): void {
        if (
            $qrCode->type
                !== 'ppe_equipment'
            || $qrCode->qrable_type
                !== PPEEquipment::class
        ) {
            throw new RuntimeException(
                'QR kod ne pripada Registru OZO.'
            );
        }
    }
}