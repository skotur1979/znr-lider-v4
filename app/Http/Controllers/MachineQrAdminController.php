<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Machines\MachineResource;
use App\Models\Machine;
use App\Models\QrCode;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class MachineQrAdminController extends Controller
{
    public function show(
        Machine $machine,
        QrCodeService $qrCodeService
    ) {
        $this->authorizeMachine(
            $machine,
            'view'
        );

        $qrCode =
            $qrCodeService->getOrCreateForMachine(
                $machine,
                auth()->user()
            );

        $svg =
            $qrCodeService->machineSvg(
                $qrCode,
                500
            );

        $publicUrl =
            $qrCodeService->publicMachineUrl(
                $qrCode
            );

        return view(
            'qr.machine-admin',
            compact(
                'machine',
                'qrCode',
                'svg',
                'publicUrl'
            )
        );
    }

    public function regenerate(
        Machine $machine,
        QrCodeService $qrCodeService
    ): RedirectResponse {
        $this->authorizeMachine(
            $machine,
            'update'
        );

        $qrCode =
            $qrCodeService->getOrCreateForMachine(
                $machine,
                auth()->user()
            );

        $qrCodeService->regenerate(
            $qrCode
        );

        return redirect()
            ->route(
                'machine.qr.admin',
                [
                    'machine' => $machine,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je regeneriran. Stari QR kod više nije važeći.'
            );
    }

    public function deactivate(
        Machine $machine,
        QrCodeService $qrCodeService
    ): RedirectResponse {
        $this->authorizeMachine(
            $machine,
            'update'
        );

        $qrCode =
            $qrCodeService->getOrCreateForMachine(
                $machine,
                auth()->user()
            );

        $qrCodeService->deactivate(
            $qrCode
        );

        return redirect()
            ->route(
                'machine.qr.admin',
                [
                    'machine' => $machine,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je deaktiviran.'
            );
    }

    public function activate(
        Machine $machine,
        QrCodeService $qrCodeService
    ): RedirectResponse {
        $this->authorizeMachine(
            $machine,
            'update'
        );

        $qrCode =
            $qrCodeService->getOrCreateForMachine(
                $machine,
                auth()->user()
            );

        $qrCodeService->activate(
            $qrCode
        );

        return redirect()
            ->route(
                'machine.qr.admin',
                [
                    'machine' => $machine,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je ponovno aktiviran.'
            );
    }

    public function downloadSvg(
    Machine $machine,
    QrCodeService $qrCodeService
): Response {
    $this->authorizeMachine(
        $machine,
        'view'
    );

    $qrCode =
        $qrCodeService->getOrCreateForMachine(
            $machine,
            auth()->user()
        );

    $svg =
        $qrCodeService->machineSvg(
            $qrCode,
            1000
        );

    $fileName =
        'qr-radna-oprema-'
        . $machine->id
        . '.svg';

    return response(
        $svg,
        200,
        [
            'Content-Type' =>
                'image/svg+xml; charset=UTF-8',

            'Content-Disposition' =>
                'attachment; filename="'
                . $fileName
                . '"',
        ]
    );
}

    public function downloadPdf(
        Machine $machine,
        QrCodeService $qrCodeService
    ) {
        $this->authorizeMachine(
            $machine,
            'view'
        );

        $qrCode =
            $qrCodeService->getOrCreateForMachine(
                $machine,
                auth()->user()
            );

        $svg =
            $qrCodeService->machineSvg(
                $qrCode,
                1000
            );

        $qrDataUri =
            'data:image/svg+xml;base64,'
            . base64_encode($svg);

        $pdf = Pdf::loadView(
            'pdf.machine-qr-label',
            [
                'machine' => $machine,
                'qrCode' => $qrCode,
                'qrDataUri' => $qrDataUri,
            ]
        )
            ->setPaper(
                'a4',
                'portrait'
            )
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 150,
            ]);

        return $pdf->download(
            'qr-radna-oprema-'
            . $machine->id
            . '.pdf'
        );
    }

    protected function authorizeMachine(
        Machine $machine,
        string $permission
    ): void {
        abort_if(
            $machine->trashed(),
            404
        );

        $user = auth()->user();

        abort_unless(
            $user,
            403
        );

        if (! $user->isSuperAdmin()) {
            abort_unless(
                (int) $machine->user_id
                    === (int) $user->ownerId(),
                404
            );
        }

        abort_unless(
            MachineResource::ensureModulePermission(
                $permission
            ),
            403
        );
    }
}
