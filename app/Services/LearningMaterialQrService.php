<?php

namespace App\Services;

use App\Models\LearningMaterial;
use App\Models\QrCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;

class LearningMaterialQrService
{
    public function getOrCreate(
        LearningMaterial $learningMaterial,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        return QrCode::query()
            ->firstOrCreate(
                [
                    'type' => 'learning_material',
                    'qrable_type' => LearningMaterial::class,
                    'qrable_id' => $learningMaterial->getKey(),
                ],
                [
                    /*
                     * Globalni materijal:
                     * owner_id = NULL
                     *
                     * Organizacijski materijal:
                     * owner_id = ownerId()
                     */
                    'owner_id' => $learningMaterial->user_id,

                    'created_by' => $actor?->id,

                    'name' => $this->qrName(
                        $learningMaterial
                    ),

                    'is_active' => true,
                ]
            );
    }

    public function publicUrl(
        QrCode $qrCode
    ): string {
        return route(
            'public.learning-material.show',
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

        $writer = new Writer(
            $renderer
        );

        return $writer->writeString(
            $this->publicUrl(
                $qrCode
            )
        );
    }

    public function qrName(
        LearningMaterial $learningMaterial
    ): string {
        return trim(
            (string) $learningMaterial->title
        );
    }

    public function regenerate(
        QrCode $qrCode
    ): QrCode {
        $qrCode->forceFill([
            'token' => QrCode::generateUniqueToken(),
            'is_active' => true,
            'scan_count' => 0,
            'last_scanned_at' => null,
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