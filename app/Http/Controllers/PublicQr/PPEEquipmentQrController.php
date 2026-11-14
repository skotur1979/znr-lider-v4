<?php

namespace App\Http\Controllers\PublicQr;

use App\Http\Controllers\Controller;
use App\Models\PPEEquipment;
use App\Models\QrCode;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PPEEquipmentQrController extends Controller
{
    public function show(
        string $token
    ) {
        $qrCode =
            $this->findQr(
                $token
            );

        $ppeEquipment =
            $this->findEquipment(
                $qrCode
            );

        $qrCode->recordScan();

        return view(
            'public.qr.ppe-equipment',
            compact(
                'ppeEquipment',
                'qrCode'
            )
        );
    }

    public function attachment(
        string $token,
        int $index
    ): StreamedResponse {
        abort_unless(
            $index >= 0,
            404
        );

        $qrCode =
            $this->findQr(
                $token
            );

        $ppeEquipment =
            $this->findEquipment(
                $qrCode
            );

        $files =
            is_array(
                $ppeEquipment->attachments
            )
                ? array_values(
                    $ppeEquipment->attachments
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
            $this->isSafeAttachmentPath(
                $path,
                $ppeEquipment
            ),
            404
        );

        abort_unless(
            Storage::disk('public')
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

    protected function findEquipment(
        QrCode $qrCode
    ): PPEEquipment {
        $ppeEquipment =
            PPEEquipment::query()
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        abort_unless(
            $ppeEquipment,
            404,
            'OZO oprema nije dostupna.'
        );

        abort_unless(
            (int) $ppeEquipment->user_id
                ===
            (int) $qrCode->owner_id,
            404
        );

        return $ppeEquipment;
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
                'ppe_equipment'
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'qrable_type',
                PPEEquipment::class
            )
            ->firstOrFail();
    }

    protected function isSafeAttachmentPath(
        mixed $path,
        PPEEquipment $ppeEquipment
    ): bool {
        if (
            ! is_string($path)
            || blank($path)
        ) {
            return false;
        }

        $path =
            str_replace(
                '\\',
                '/',
                $path
            );

        if (
            str_contains($path, '../')
            || str_contains($path, "\0")
        ) {
            return false;
        }

        /*
         * Stari prilozi.
         */
        $legacyPrefix =
            'ppe-equipment/attachments/';

        /*
         * Novi tenant-scoped prilozi.
         */
        $ownerPrefix =
            'ppe-equipment/'
            . (int) $ppeEquipment->user_id
            . '/attachments/';

        return
            str_starts_with(
                $path,
                $legacyPrefix
            )
            || str_starts_with(
                $path,
                $ownerPrefix
            );
    }
}