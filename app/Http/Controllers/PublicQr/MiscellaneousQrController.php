<?php

namespace App\Http\Controllers\PublicQr;

use App\Http\Controllers\Controller;
use App\Models\Miscellaneous;
use App\Models\QrCode;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MiscellaneousQrController extends Controller
{
    public function show(
        string $token
    ) {
        $qrCode = $this->findQr(
            $token
        );

        /** @var Miscellaneous|null $miscellaneous */
        $miscellaneous =
            Miscellaneous::query()
                ->with('category')
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->whereNull(
                    'deleted_at'
                )
                ->first();

        abort_unless(
            $miscellaneous,
            404,
            'Ispitivanje nije dostupno.'
        );

        /*
         * Dodatna tenant zaštita.
         */
        abort_unless(
            (int) $miscellaneous->user_id
                ===
            (int) $qrCode->owner_id,
            404
        );

        $qrCode->recordScan();

        return view(
            'public.qr.miscellaneous',
            compact(
                'miscellaneous',
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

        /** @var Miscellaneous|null $miscellaneous */
        $miscellaneous =
            Miscellaneous::query()
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->whereNull(
                    'deleted_at'
                )
                ->first();

        abort_unless(
            $miscellaneous,
            404
        );

        abort_unless(
            (int) $miscellaneous->user_id
                ===
            (int) $qrCode->owner_id,
            404
        );

        $files =
            is_array(
                $miscellaneous->pdf
            )
                ? array_values(
                    $miscellaneous->pdf
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

        /*
         * URL nikada ne prima stvarnu
         * putanju dokumenta.
         */
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
                'miscellaneous'
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'qrable_type',
                Miscellaneous::class
            )
            ->firstOrFail();
    }
}