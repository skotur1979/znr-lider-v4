<?php

namespace App\Http\Controllers\PublicQr;

use App\Http\Controllers\Controller;
use App\Models\Chemical;
use App\Models\QrCode;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChemicalQrController extends Controller
{
    public function show(
        string $token
    ) {
        $qrCode = $this->findQr(
            $token
        );

        $chemical = Chemical::query()
            ->whereKey(
                $qrCode->qrable_id
            )
            ->first();

        abort_unless(
            $chemical,
            404,
            'Kemikalija nije dostupna.'
        );

        abort_unless(
            (int) $chemical->user_id
                ===
            (int) $qrCode->owner_id,
            404
        );

        $qrCode->recordScan();

        return view(
            'public.qr.chemical',
            compact(
                'chemical',
                'qrCode'
            )
        );
    }

    public function attachment(
        string $token,
        int $index
    ): StreamedResponse {
        $qrCode = $this->findQr(
            $token
        );

        $chemical = Chemical::query()
            ->whereKey(
                $qrCode->qrable_id
            )
            ->first();

        abort_unless(
            $chemical,
            404
        );

        abort_unless(
            (int) $chemical->user_id
                ===
            (int) $qrCode->owner_id,
            404
        );

        $files = is_array(
            $chemical->attachments
        )
            ? array_values(
                $chemical->attachments
            )
            : [];

        abort_unless(
            array_key_exists(
                $index,
                $files
            ),
            404
        );

        $path = $files[$index];

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
                'chemical'
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'qrable_type',
                Chemical::class
            )
            ->firstOrFail();
    }
}