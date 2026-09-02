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
            PPEEquipment::query()
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->first();

        abort_unless(
            $ppeEquipment,
            404,
            'OZO oprema nije dostupna.'
        );

        /*
         * Dodatna tenant zaštita.
         */
        abort_unless(
            (int) $ppeEquipment->user_id
                ===
            (int) $qrCode->owner_id,
            404
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
        $qrCode =
            $this->findQr(
                $token
            );

        $ppeEquipment =
            PPEEquipment::query()
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->first();

        abort_unless(
            $ppeEquipment,
            404
        );

        abort_unless(
            (int) $ppeEquipment->user_id
                ===
            (int) $qrCode->owner_id,
            404
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
            is_string(
                $path
            )
            && filled(
                $path
            )
            && Storage::disk(
                'public'
            )->exists(
                $path
            ),
            404
        );

        return Storage::disk(
            'public'
        )->response(
            $path,
            basename(
                $path
            ),
            [
                'Content-Disposition' =>
                    'inline; filename="'
                    . basename(
                        $path
                    )
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
}