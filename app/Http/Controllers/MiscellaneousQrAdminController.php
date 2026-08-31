<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Models\Miscellaneous;
use App\Services\MiscellaneousQrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;

class MiscellaneousQrAdminController extends Controller
{
    public function show(
        Miscellaneous $miscellaneous,
        MiscellaneousQrService $qrService
    ) {
        $this->authorizeMiscellaneous(
            $miscellaneous,
            'view'
        );

        $qrCode = $qrService->getOrCreate(
            $miscellaneous,
            auth()->user()
        );

        $svg = $qrService->svg(
            $qrCode,
            500
        );

        $publicUrl = $qrService->publicUrl(
            $qrCode
        );

        return view(
            'qr.miscellaneous-admin',
            compact(
                'miscellaneous',
                'qrCode',
                'svg',
                'publicUrl'
            )
        );
    }

    public function regenerate(
        Miscellaneous $miscellaneous,
        MiscellaneousQrService $qrService
    ): RedirectResponse {
        $this->authorizeMiscellaneous(
            $miscellaneous,
            'update'
        );

        $qrCode = $qrService->getOrCreate(
            $miscellaneous,
            auth()->user()
        );

        $qrService->regenerate(
            $qrCode
        );

        return redirect()
            ->route(
                'miscellaneous.qr.admin',
                [
                    'miscellaneous' =>
                        $miscellaneous,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je regeneriran. Stari QR kod više nije važeći.'
            );
    }

    public function deactivate(
        Miscellaneous $miscellaneous,
        MiscellaneousQrService $qrService
    ): RedirectResponse {
        $this->authorizeMiscellaneous(
            $miscellaneous,
            'update'
        );

        $qrCode = $qrService->getOrCreate(
            $miscellaneous,
            auth()->user()
        );

        $qrService->deactivate(
            $qrCode
        );

        return redirect()
            ->route(
                'miscellaneous.qr.admin',
                [
                    'miscellaneous' =>
                        $miscellaneous,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je deaktiviran.'
            );
    }

    public function activate(
        Miscellaneous $miscellaneous,
        MiscellaneousQrService $qrService
    ): RedirectResponse {
        $this->authorizeMiscellaneous(
            $miscellaneous,
            'update'
        );

        $qrCode = $qrService->getOrCreate(
            $miscellaneous,
            auth()->user()
        );

        $qrService->activate(
            $qrCode
        );

        return redirect()
            ->route(
                'miscellaneous.qr.admin',
                [
                    'miscellaneous' =>
                        $miscellaneous,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je ponovno aktiviran.'
            );
    }

    public function downloadPdf(
        Miscellaneous $miscellaneous,
        MiscellaneousQrService $qrService
    ) {
        $this->authorizeMiscellaneous(
            $miscellaneous,
            'view'
        );

        $qrCode = $qrService->getOrCreate(
            $miscellaneous,
            auth()->user()
        );

        $svg = $qrService->svg(
            $qrCode,
            1000
        );

        $qrDataUri =
            'data:image/svg+xml;base64,'
            . base64_encode($svg);

        $pdf = Pdf::loadView(
            'pdf.miscellaneous-qr-label',
            [
                'miscellaneous' =>
                    $miscellaneous,

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
            'qr-ostalo-ispitivanje-'
            . $miscellaneous->id
            . '.pdf'
        );
    }

    protected function authorizeMiscellaneous(
        Miscellaneous $miscellaneous,
        string $permission
    ): void {
        /*
         * Deaktivirani zapis nema aktivnu
         * QR administraciju.
         */
        abort_if(
            $miscellaneous->trashed(),
            404
        );

        $user = auth()->user();

        abort_unless(
            $user,
            403
        );

        /*
         * Tenant zaštita.
         */
        if (! $user->isSuperAdmin()) {
            abort_unless(
                (int) $miscellaneous->user_id
                    ===
                (int) $user->ownerId(),
                404
            );
        }

        abort_unless(
            MiscellaneousResource
                ::ensureModulePermission(
                    $permission
                ),
            403
        );
    }
}