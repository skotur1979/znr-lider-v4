<?php

namespace App\Services;

use App\Models\Chemical;
use App\Models\QrCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;

class ChemicalQrService
{
    public function getOrCreate(
        Chemical $chemical,
        ?User $actor = null
    ): QrCode {
        $actor ??=
            Auth::user();

        abort_unless(
            $actor,
            403
        );

        /*
         * QR se ne generira za
         * soft-deleted zapis.
         */
        abort_if(
            $chemical->trashed(),
            404
        );

        /*
         * Legacy Chemical bez ownera
         * nije organizacijski zapis.
         */
        abort_unless(
            $chemical->user_id
                !== null
            && (int)
                $chemical->user_id
                > 0,
            404
        );

        /*
         * Defense-in-depth ownership.
         */
        if (
            ! $actor->isSuperAdmin()
        ) {
            $ownerId =
                (int)
                $actor->ownerId();

            abort_unless(
                $ownerId > 0,
                403
            );

            abort_unless(
                (int)
                $chemical->user_id
                ===
                $ownerId,
                404
            );
        }

        $qrCode =
            QrCode::query()
                ->firstOrCreate(
                    [
                        'type' =>
                            'chemical',

                        'qrable_type' =>
                            Chemical::class,

                        'qrable_id' =>
                            $chemical
                                ->getKey(),
                    ],
                    [
                        'owner_id' =>
                            (int)
                            $chemical
                                ->user_id,

                        'created_by' =>
                            $actor->id,

                        'name' =>
                            $this->qrName(
                                $chemical
                            ),

                        'is_active' =>
                            true,
                    ]
                );

        /*
         * DB invariant:
         * postojeći QR mora pripadati
         * istom owneru kao Chemical.
         */
        abort_unless(
            (int)
            $qrCode->owner_id
            ===
            (int)
            $chemical->user_id,
            409,
            'QR kod nema ispravnog vlasnika.'
        );

        /*
         * Ako je naziv proizvoda promijenjen,
         * osvježavamo naziv QR zapisa.
         */
        $currentName =
            $this->qrName(
                $chemical
            );

        if (
            (string)
            $qrCode->name
            !==
            $currentName
        ) {
            $qrCode
                ->forceFill([
                    'name' =>
                        $currentName,
                ])
                ->save();
        }

        return $qrCode;
    }

    public function publicUrl(
        QrCode $qrCode
    ): string {
        return route(
            'public.chemical.show',
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
        /*
         * Minimalna/maksimalna vrijednost
         * štiti servis ako ga jednog dana
         * pozove API.
         */
        $size =
            max(
                100,
                min(
                    $size,
                    2000
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

        return $writer
            ->writeString(
                $this->publicUrl(
                    $qrCode
                )
            );
    }

    public function qrName(
        Chemical $chemical
    ): string {
        return trim(
            (string)
            $chemical
                ->product_name
        );
    }

    public function regenerate(
        QrCode $qrCode
    ): QrCode {
        $qrCode
            ->forceFill([
                'token' =>
                    QrCode::
                        generateUniqueToken(),

                'is_active' =>
                    true,

                'scan_count' =>
                    0,

                'last_scanned_at' =>
                    null,
            ])
            ->save();

        return $qrCode
            ->refresh();
    }

    public function deactivate(
        QrCode $qrCode
    ): void {
        $qrCode->update([
            'is_active' =>
                false,
        ]);
    }

    public function activate(
        QrCode $qrCode
    ): void {
        $qrCode->update([
            'is_active' =>
                true,
        ]);
    }
}