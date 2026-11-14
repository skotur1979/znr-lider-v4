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
        $qrCode =
            $this->findQr(
                $token
            );

        /*
         * Chemical::query() namjerno
         * ne koristi withTrashed().
         *
         * Deaktivirana kemikalija
         * javno nije dostupna.
         */
        $chemical =
            Chemical::query()
                ->whereKey(
                    $qrCode
                        ->qrable_id
                )
                ->first();

        abort_unless(
            $chemical,
            404,
            'Kemikalija nije dostupna.'
        );

        /*
         * QR i Chemical moraju imati
         * istog ownera.
         */
        abort_unless(
            $chemical->user_id
                !== null
            && (int)
                $chemical->user_id
                ===
                (int)
                $qrCode->owner_id,
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
        $qrCode =
            $this->findQr(
                $token
            );

        $chemical =
            Chemical::query()
                ->whereKey(
                    $qrCode
                        ->qrable_id
                )
                ->first();

        abort_unless(
            $chemical,
            404
        );

        abort_unless(
            $chemical->user_id
                !== null
            && (int)
                $chemical->user_id
                ===
                (int)
                $qrCode->owner_id,
            404
        );

        $files =
            is_array(
                $chemical
                    ->attachments
            )
                ? array_values(
                    $chemical
                        ->attachments
                )
                : [];

        abort_unless(
            $index >= 0
            && array_key_exists(
                $index,
                $files
            ),
            404
        );

        $path =
            $files[
                $index
            ];

        abort_unless(
            is_string(
                $path
            )
            && filled(
                $path
            ),
            404
        );

        /*
         * Normalizacija relativne putanje.
         */
        $path =
            str_replace(
                '\\',
                '/',
                ltrim(
                    trim(
                        $path
                    ),
                    '/'
                )
            );

        /*
         * Path traversal zaštita.
         */
        abort_if(
            preg_match(
                '#(^|/)\.\.(/|$)#',
                $path
            ),
            404
        );

        /*
         * QR kemikalija ne smije poslužiti
         * neku drugu vrstu javne datoteke.
         *
         * Podržava:
         * chemicals/file.pdf
         *
         * i nove:
         * chemicals/{ownerId}/file.pdf
         */
        abort_unless(
            str_starts_with(
                $path,
                'chemicals/'
            ),
            404
        );

        abort_unless(
            Storage::disk(
                'public'
            )->exists(
                $path
            ),
            404
        );

        $fileName =
            str_replace(
                [
                    '"',
                    "\r",
                    "\n",
                ],
                '',
                basename(
                    $path
                )
            );

        return Storage::disk(
            'public'
        )->response(
            $path,
            $fileName,
            [
                'Content-Disposition' =>
                    'inline; filename="'
                    . $fileName
                    . '"',

                'X-Content-Type-Options' =>
                    'nosniff',
            ]
        );
    }

    protected function findQr(
        string $token
    ): QrCode {
        /*
         * Prazan / nerazuman token
         * odmah odbacujemo.
         */
        abort_if(
            blank(
                $token
            )
            || strlen(
                $token
            ) > 255,
            404
        );

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