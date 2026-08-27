<?php

namespace App\Http\Controllers\PublicQr;

use App\Http\Controllers\Controller;
use App\Models\Fire;
use App\Models\QrCode;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FireQrController extends Controller
{
    public function show(
        string $token
    ) {
        $qrCode =
            $this->findFireQr(
                $token
            );

        /** @var Fire|null $fire */
        $fire = Fire::query()
            ->whereKey(
                $qrCode->qrable_id
            )
            ->whereNull(
                'deleted_at'
            )
            ->first();

        abort_unless(
            $fire,
            404,
            'Vatrogasni aparat nije dostupan.'
        );

        /*
         * Tenant zaštita.
         */
        abort_unless(
            (int) $fire->user_id
                === (int) $qrCode->owner_id,
            404
        );

        $qrCode->recordScan();

        return view(
            'public.qr.fire',
            compact(
                'fire',
                'qrCode'
            )
        );
    }

    public function svg(
        string $token,
        QrCodeService $qrCodeService
    ): Response {
        $qrCode =
            $this->findFireQr(
                $token
            );

        return response(
            $qrCodeService->fireSvg(
                $qrCode
            ),
            200,
            [
                'Content-Type' =>
                    'image/svg+xml; charset=UTF-8',

                'Cache-Control' =>
                    'private, max-age=300',
            ]
        );
    }

    public function attachment(
        string $token,
        int $index
    ): StreamedResponse {
        $qrCode =
            $this->findFireQr(
                $token
            );

        /** @var Fire|null $fire */
        $fire = Fire::query()
            ->whereKey(
                $qrCode->qrable_id
            )
            ->whereNull(
                'deleted_at'
            )
            ->first();

        abort_unless(
            $fire,
            404
        );

        abort_unless(
            (int) $fire->user_id
                === (int) $qrCode->owner_id,
            404
        );

        $files =
            is_array($fire->pdf)
                ? array_values(
                    $fire->pdf
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

                    'X-Content-Type-Options' =>
                        'nosniff',

                    'Cache-Control' =>
                        'private, no-store, max-age=0',
                ]
            );
    }

    protected function findFireQr(
        string $token
    ): QrCode {
        return QrCode::query()
            ->where(
                'token',
                $token
            )
            ->where(
                'type',
                'fire'
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'qrable_type',
                Fire::class
            )
            ->firstOrFail();
    }
}
