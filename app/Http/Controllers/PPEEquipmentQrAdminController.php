<?php

namespace App\Http\Controllers;

use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
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
        $this->authorizeView(
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
        $this->authorizeUpdate(
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
        $this->authorizeUpdate(
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
        $this->authorizeUpdate(
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
        $this->authorizeView(
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

    protected function authorizeView(
        PPEEquipment $ppeEquipment
    ): void {
        abort_unless(
            auth()->check(),
            403
        );

        abort_unless(
            PPEEquipmentResource::canView(
                $ppeEquipment
            ),
            404
        );
    }

    protected function authorizeUpdate(
        PPEEquipment $ppeEquipment
    ): void {
        abort_unless(
            auth()->check(),
            403
        );

        abort_unless(
            PPEEquipmentResource::canEdit(
                $ppeEquipment
            ),
            404
        );
    }
}