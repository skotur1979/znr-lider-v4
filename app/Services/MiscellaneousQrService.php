<?php

namespace App\Services;

use App\Models\Miscellaneous;
use App\Models\QrCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;

class MiscellaneousQrService
{
    public function getOrCreate(
        Miscellaneous $miscellaneous,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        return QrCode::query()
            ->firstOrCreate(
                [
                    'type' => 'miscellaneous',
                    'qrable_type' => Miscellaneous::class,
                    'qrable_id' => $miscellaneous->getKey(),
                ],
                [
                    'owner_id' => $miscellaneous->user_id,
                    'created_by' => $actor?->id,
                    'name' => $this->qrName($miscellaneous),
                    'is_active' => true,
                ]
            );
    }

    public function publicUrl(
        QrCode $qrCode
    ): string {
        return route(
            'public.miscellaneous.show',
            [
                'token' => $qrCode->token,
            ]
        );
    }

    public function svg(
        QrCode $qrCode,
        int $size = 500
    ): string {
        $renderer = new ImageRenderer(
            new RendererStyle(
                $size,
                4
            ),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString(
            $this->publicUrl($qrCode)
        );
    }

    public function qrName(
        Miscellaneous $miscellaneous
    ): string {
        $name = trim(
            (string) $miscellaneous->name
        );

        if (filled($miscellaneous->report_number)) {
            $name .= ' - '
                . trim(
                    (string) $miscellaneous->report_number
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
            'is_active' => false,
        ]);
    }

    public function activate(
        QrCode $qrCode
    ): void {
        $qrCode->update([
            'is_active' => true,
        ]);
    }
}