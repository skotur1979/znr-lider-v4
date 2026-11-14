<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Chemicals\ChemicalResource;
use App\Models\Chemical;
use App\Services\ChemicalQrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;

class ChemicalQrAdminController extends Controller
{
    public function show(
        Chemical $chemical,
        ChemicalQrService $qrService
    ) {
        $this->authorizeChemical(
            $chemical,
            'view'
        );

        $chemical->load(
            'user'
        );

        $qrCode =
            $qrService->getOrCreate(
                $chemical,
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
            'qr.chemical-admin',
            compact(
                'chemical',
                'qrCode',
                'svg',
                'publicUrl'
            )
        );
    }

    public function regenerate(
        Chemical $chemical,
        ChemicalQrService $qrService
    ): RedirectResponse {
        $this->authorizeChemical(
            $chemical,
            'update'
        );

        $qrCode =
            $qrService->getOrCreate(
                $chemical,
                auth()->user()
            );

        $qrService->regenerate(
            $qrCode
        );

        return redirect()
            ->route(
                'chemical.qr.admin',
                [
                    'chemical' =>
                        $chemical,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je regeneriran. Stari QR kod više nije važeći.'
            );
    }

    public function deactivate(
        Chemical $chemical,
        ChemicalQrService $qrService
    ): RedirectResponse {
        $this->authorizeChemical(
            $chemical,
            'update'
        );

        $qrCode =
            $qrService->getOrCreate(
                $chemical,
                auth()->user()
            );

        $qrService->deactivate(
            $qrCode
        );

        return redirect()
            ->route(
                'chemical.qr.admin',
                [
                    'chemical' =>
                        $chemical,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je deaktiviran.'
            );
    }

    public function activate(
        Chemical $chemical,
        ChemicalQrService $qrService
    ): RedirectResponse {
        $this->authorizeChemical(
            $chemical,
            'update'
        );

        $qrCode =
            $qrService->getOrCreate(
                $chemical,
                auth()->user()
            );

        $qrService->activate(
            $qrCode
        );

        return redirect()
            ->route(
                'chemical.qr.admin',
                [
                    'chemical' =>
                        $chemical,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je ponovno aktiviran.'
            );
    }

    public function downloadPdf(
        Chemical $chemical,
        ChemicalQrService $qrService
    ) {
        $this->authorizeChemical(
            $chemical,
            'view'
        );

        $qrCode =
            $qrService->getOrCreate(
                $chemical,
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
                'pdf.chemical-qr-label',
                [
                    'chemical' =>
                        $chemical,

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

                    'isPhpEnabled' =>
                        false,

                    'defaultFont' =>
                        'DejaVu Sans',

                    'dpi' =>
                        150,
                ]);

        return $pdf->download(
            'qr-kemikalija-'
            . $chemical->id
            . '.pdf'
        );
    }

    protected function authorizeChemical(
        Chemical $chemical,
        string $permission = 'view'
    ): void {
        $user =
            auth()->user();

        abort_unless(
            $user,
            403
        );

        /*
         * Legacy zapis bez ownera
         * ne može dobiti organizacijski QR.
         */
        abort_unless(
            $chemical->user_id
                !== null,
            404
        );

        /*
         * Serverska granularna
         * permission provjera.
         */
        abort_unless(
            ChemicalResource::
                allowsModulePermission(
                    $permission
                ),
            403
        );

        /*
         * Superadmin smije upravljati
         * postojećim zapisima organizacija.
         */
        if (
            $user->isSuperAdmin()
        ) {
            return;
        }

        $ownerId =
            (int)
            $user->ownerId();

        abort_unless(
            $ownerId > 0,
            403
        );

        /*
         * Tuđi record vraća 404,
         * a ne otkriva da postoji.
         */
        abort_unless(
            (int)
            $chemical->user_id
            ===
            $ownerId,
            404
        );
    }
}