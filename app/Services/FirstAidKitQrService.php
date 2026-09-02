<?php

namespace App\Services;

use App\Models\FirstAidKit;
use App\Models\QrCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;

class FirstAidKitQrService
{
    public function getOrCreate(
        FirstAidKit $firstAidKit,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        return QrCode::query()
            ->firstOrCreate(
                [
                    'type' => 'first_aid_kit',

                    'qrable_type' =>
                        FirstAidKit::class,

                    'qrable_id' =>
                        $firstAidKit->getKey(),
                ],
                [
                    'owner_id' =>
                        $firstAidKit->user_id,

                    'created_by' =>
                        $actor?->id,

                    'name' =>
                        $this->qrName(
                            $firstAidKit
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
        FirstAidKit $firstAidKit
    ): string {
        return
            'Prva pomoć - '
            . trim(
                (string)
                $firstAidKit->location
            );
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