<?php

namespace App\Http\Controllers;

use App\Models\PPEEquipment;
use App\Services\PPEEquipmentQrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;

class PPEEquipmentQrAdminController extends Controller
{
    public function show(
        PPEEquipment $ppeEquipment,
        PPEEquipmentQrService $qrService
    ) {
        $this->authorizePPEEquipment(
            $ppeEquipment
        );

        $qrCode =
            $qrService->getOrCreate(
                $ppeEquipment,
                auth()->user()
            );

        $svg =
            $qrService->svg(
                $qrCode,
                500
            );

        $publicUrl =
            $qrService->publicUrl(
                $qrCode
            );

        return view(
            'qr.ppe-equipment-admin',
            compact(
                'ppeEquipment',
                'qrCode',
                'svg',
                'publicUrl'
            )
        );
    }

    public function regenerate(
        PPEEquipment $ppeEquipment,
        PPEEquipmentQrService $qrService
    ): RedirectResponse {
        $this->authorizePPEEquipment(
            $ppeEquipment
        );

        $qrCode =
            $qrService->getOrCreate(
                $ppeEquipment,
                auth()->user()
            );

        $qrService->regenerate(
            $qrCode
        );

        return redirect()
            ->route(
                'ppe-equipment.qr.admin',
                [
                    'ppeEquipment' =>
                        $ppeEquipment,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je regeneriran. Stari QR kod više nije važeći.'
            );
    }

    public function deactivate(
        PPEEquipment $ppeEquipment,
        PPEEquipmentQrService $qrService
    ): RedirectResponse {
        $this->authorizePPEEquipment(
            $ppeEquipment
        );

        $qrCode =
            $qrService->getOrCreate(
                $ppeEquipment,
                auth()->user()
            );

        $qrService->deactivate(
            $qrCode
        );

        return redirect()
            ->route(
                'ppe-equipment.qr.admin',
                [
                    'ppeEquipment' =>
                        $ppeEquipment,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je deaktiviran.'
            );
    }

    public function activate(
        PPEEquipment $ppeEquipment,
        PPEEquipmentQrService $qrService
    ): RedirectResponse {
        $this->authorizePPEEquipment(
            $ppeEquipment
        );

        $qrCode =
            $qrService->getOrCreate(
                $ppeEquipment,
                auth()->user()
            );

        $qrService->activate(
            $qrCode
        );

        return redirect()
            ->route(
                'ppe-equipment.qr.admin',
                [
                    'ppeEquipment' =>
                        $ppeEquipment,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je ponovno aktiviran.'
            );
    }

    public function downloadPdf(
        PPEEquipment $ppeEquipment,
        PPEEquipmentQrService $qrService
    ) {
        $this->authorizePPEEquipment(
            $ppeEquipment
        );

        $qrCode =
            $qrService->getOrCreate(
                $ppeEquipment,
                auth()->user()
            );

        $svg =
            $qrService->svg(
                $qrCode,
                1000
            );

        $qrDataUri =
            'data:image/svg+xml;base64,'
            . base64_encode(
                $svg
            );

        $pdf =
            Pdf::loadView(
                'pdf.ppe-equipment-qr-label',
                [
                    'ppeEquipment' =>
                        $ppeEquipment,

                    'qrCode' =>
                        $qrCode,

                    'qrDataUri' =>
                        $qrDataUri,
                ]
            )
                ->setPaper(
                    'a4',
                    'portrait'
                )
                ->setOptions([
                    'isHtml5ParserEnabled' =>
                        true,

                    'isRemoteEnabled' =>
                        false,

                    'defaultFont' =>
                        'DejaVu Sans',

                    'dpi' =>
                        150,
                ]);

        return $pdf->download(
            'qr-ozo-'
            . $ppeEquipment->id
            . '.pdf'
        );
    }

    protected function authorizePPEEquipment(
        PPEEquipment $ppeEquipment
    ): void {
        $user =
            auth()->user();

        abort_unless(
            $user,
            403
        );

        /*
         * Superadmin smije administrativno
         * pristupiti svakom postojećem OZO zapisu.
         */
        if (
            $user->isSuperAdmin()
        ) {
            return;
        }

        /*
         * Organizacijski korisnik smije pristupiti
         * samo OZO zapisima svoje organizacije.
         */
        abort_unless(
            (int) $ppeEquipment->user_id
                ===
            (int) $user->ownerId(),
            404
        );
    }
}