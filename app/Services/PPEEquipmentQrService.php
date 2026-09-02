<?php

namespace App\Services;

use App\Models\PPEEquipment;
use App\Models\QrCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;

class PPEEquipmentQrService
{
    public function getOrCreate(
        PPEEquipment $ppeEquipment,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        return QrCode::query()
            ->firstOrCreate(
                [
                    'type' =>
                        'ppe_equipment',

                    'qrable_type' =>
                        PPEEquipment::class,

                    'qrable_id' =>
                        $ppeEquipment->getKey(),
                ],
                [
                    'owner_id' =>
                        $ppeEquipment->user_id,

                    'created_by' =>
                        $actor?->id,

                    'name' =>
                        $this->qrName(
                            $ppeEquipment
                        ),

                    'is_active' =>
                        true,
                ]
            );
    }

    public function publicUrl(
        QrCode $qrCode
    ): string {
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

        return $name;
    }

    public function regenerate(
        QrCode $qrCode
    ): QrCode {
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