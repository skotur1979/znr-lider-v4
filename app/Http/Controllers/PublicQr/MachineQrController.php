<?php

namespace App\Http\Controllers\PublicQr;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\QrCode;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MachineQrController extends Controller
{
    public function show(
        string $token
    ) {
        $qrCode = $this->findMachineQr($token);

        /** @var Machine|null $machine */
        $machine = Machine::query()
            ->whereKey($qrCode->qrable_id)
            ->whereNull('deleted_at')
            ->first();

        abort_unless(
            $machine,
            404,
            'Radna oprema nije dostupna.'
        );

        /*
         * Dodatna tenant zaštita.
         */
        abort_unless(
            (int) $machine->user_id
                === (int) $qrCode->owner_id,
            404
        );

        $qrCode->recordScan();

        return view(
            'public.qr.machine',
            compact(
                'machine',
                'qrCode'
            )
        );
    }

    public function svg(
        string $token,
        QrCodeService $qrCodeService
    ): Response {
        $qrCode = $this->findMachineQr($token);

        return response(
            $qrCodeService->machineSvg(
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
        $qrCode = $this->findMachineQr($token);

        /** @var Machine|null $machine */
        $machine = Machine::query()
            ->whereKey($qrCode->qrable_id)
            ->whereNull('deleted_at')
            ->first();

        abort_unless(
            $machine,
            404
        );

        abort_unless(
            (int) $machine->user_id
                === (int) $qrCode->owner_id,
            404
        );

        $files = is_array($machine->pdf)
            ? array_values($machine->pdf)
            : [];

        abort_unless(
            array_key_exists($index, $files),
            404
        );

        $path = $files[$index];

        abort_unless(
            is_string($path)
                && filled($path)
                && Storage::disk('public')->exists($path),
            404
        );

        /*
         * Ne primamo putanju datoteke iz URL-a.
         * Iz URL-a primamo samo index i sami dohvaćamo
         * stvarnu putanju iz Machine modela.
         */
        return Storage::disk('public')->response(
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

    protected function findMachineQr(
        string $token
    ): QrCode {
        return QrCode::query()
            ->where('token', $token)
            ->where('type', 'machine')
            ->where('is_active', true)
            ->where(
                'qrable_type',
                Machine::class
            )
            ->firstOrFail();
    }
}
