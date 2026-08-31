<?php

namespace App\Http\Controllers\PublicQr;

use App\Http\Controllers\Controller;
use App\Models\DocumentationItem;
use App\Models\QrCode;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentationQrController extends Controller
{
    public function show(
        string $token
    ) {
        $qrCode =
            $this->findQr(
                $token
            );

        /** @var DocumentationItem|null $documentationItem */
        $documentationItem =
            DocumentationItem::query()
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->first();

        abort_unless(
            $documentationItem,
            404,
            'Dokumentacija nije dostupna.'
        );

        /*
         * Tenant zaštita.
         */
        abort_unless(
            (int) $documentationItem->user_id
                ===
            (int) $qrCode->owner_id,
            404
        );

        $qrCode->recordScan();

        return view(
            'public.qr.documentation',
            compact(
                'documentationItem',
                'qrCode'
            )
        );
    }

    public function attachment(
        string $token,
        int $index
    ): StreamedResponse {
        $qrCode =
            $this->findQr(
                $token
            );

        /** @var DocumentationItem|null $documentationItem */
        $documentationItem =
            DocumentationItem::query()
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->first();

        abort_unless(
            $documentationItem,
            404
        );

        abort_unless(
            (int) $documentationItem->user_id
                ===
            (int) $qrCode->owner_id,
            404
        );

        $files =
            is_array(
                $documentationItem->prilozi
            )
                ? array_values(
                    $documentationItem->prilozi
                )
                : [];

        abort_unless(
            array_key_exists(
                $index,
                $files
            ),
            404
        );

        $path =
            $files[$index];

        abort_unless(
            is_string($path)
            && filled($path)
            && Storage::disk('public')
                ->exists($path),
            404
        );

        return Storage::disk('public')
            ->response(
                $path,
                basename($path),
                [
                    'Content-Disposition' =>
                        'inline; filename="'
                        . basename($path)
                        . '"',
                ]
            );
    }

    protected function findQr(
        string $token
    ): QrCode {
        return QrCode::query()
            ->where(
                'token',
                $token
            )
            ->where(
                'type',
                'documentation'
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'qrable_type',
                DocumentationItem::class
            )
            ->firstOrFail();
    }
}