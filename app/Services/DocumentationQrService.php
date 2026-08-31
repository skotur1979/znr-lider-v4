<?php

namespace App\Services;

use App\Models\DocumentationItem;
use App\Models\QrCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;

class DocumentationQrService
{
    public function getOrCreate(
        DocumentationItem $documentationItem,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        return QrCode::query()
            ->firstOrCreate(
                [
                    'type' => 'documentation',
                    'qrable_type' => DocumentationItem::class,
                    'qrable_id' => $documentationItem->getKey(),
                ],
                [
                    'owner_id' => $documentationItem->user_id,
                    'created_by' => $actor?->id,
                    'name' => $this->qrName($documentationItem),
                    'is_active' => true,
                ]
            );
    }

    public function publicUrl(
        QrCode $qrCode
    ): string {
        return route(
            'public.documentation.show',
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
        DocumentationItem $documentationItem
    ): string {
        $name = trim(
            (string) $documentationItem->naziv
        );

        if (
            filled(
                $documentationItem->tvrtka
            )
        ) {
            return $name
                . ' - '
                . trim(
                    (string) $documentationItem->tvrtka
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