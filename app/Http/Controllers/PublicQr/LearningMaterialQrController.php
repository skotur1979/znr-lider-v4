<?php

namespace App\Http\Controllers\PublicQr;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\QrCode;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningMaterialQrController extends Controller
{
    public function show(
        string $token
    ) {
        $qrCode = $this->findQr(
            $token
        );

        $learningMaterial =
            LearningMaterial::query()
                ->with([
                    'category',
                ])
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        abort_unless(
            $learningMaterial,
            404,
            'Edukacijski materijal nije dostupan.'
        );

        $this->validateOwnership(
            $learningMaterial,
            $qrCode
        );

        $qrCode->recordScan();

        return view(
            'public.qr.learning-material',
            compact(
                'learningMaterial',
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

        $learningMaterial =
            LearningMaterial::query()
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        abort_unless(
            $learningMaterial,
            404
        );

        $this->validateOwnership(
            $learningMaterial,
            $qrCode
        );

        /*
         * Koristimo postojeći model helper
         * koji objedinjuje file_path + files[].
         */
        $files = array_values(
            $learningMaterial->getAllFiles()
        );

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
            && Storage::disk(
                'public'
            )->exists($path),
            404
        );

        return Storage::disk(
            'public'
        )->response(
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
                'learning_material'
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'qrable_type',
                LearningMaterial::class
            )
            ->firstOrFail();
    }

    protected function validateOwnership(
        LearningMaterial $learningMaterial,
        QrCode $qrCode
    ): void {
        /*
         * Globalni materijal:
         *
         * is_global = true
         * user_id = NULL
         * owner_id QR-a = NULL
         */
        if (
            (bool) $learningMaterial->is_global
        ) {
            abort_unless(
                $learningMaterial->user_id === null
                && $qrCode->owner_id === null,
                404
            );

            return;
        }

        /*
         * Organizacijski materijal:
         *
         * owner materijala mora biti
         * isti kao owner QR koda.
         */
        abort_unless(
            $learningMaterial->user_id !== null
            &&
            (int) $learningMaterial->user_id
                ===
            (int) $qrCode->owner_id,
            404
        );
    }
}