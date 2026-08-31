<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Observations\ObservationResource;
use App\Models\User;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;

class ObservationQrAdminController extends Controller
{
    public function show(
        QrCodeService $qrCodeService
    ) {
        $owner =
            $this->authorizedOwner(
                'create'
            );

        $qrCode =
            $qrCodeService
                ->getOrCreateForObservationOwner(
                    $owner,
                    auth()->user()
                );

        $svg =
            $qrCodeService
                ->observationSvg(
                    $qrCode,
                    900
                );

        $publicUrl =
            $qrCodeService
                ->publicObservationUrl(
                    $qrCode
                );

        return view(
            'qr.observation-admin',
            compact(
                'owner',
                'qrCode',
                'svg',
                'publicUrl'
            )
        );
    }

    public function regenerate(
        QrCodeService $qrCodeService
    ): RedirectResponse {
        $owner =
            $this->authorizedOwner(
                'update'
            );

        $qrCode =
            $qrCodeService
                ->getOrCreateForObservationOwner(
                    $owner,
                    auth()->user()
                );

        $qrCodeService->regenerate(
            $qrCode
        );

        return redirect()
            ->route(
                'observation.qr.admin'
            )
            ->with(
                'qr_success',
                'QR kod je regeneriran. Stari poster više nije važeći.'
            );
    }

    public function deactivate(
        QrCodeService $qrCodeService
    ): RedirectResponse {
        $owner =
            $this->authorizedOwner(
                'update'
            );

        $qrCode =
            $qrCodeService
                ->getOrCreateForObservationOwner(
                    $owner,
                    auth()->user()
                );

        $qrCodeService->deactivate(
            $qrCode
        );

        return redirect()
            ->route(
                'observation.qr.admin'
            )
            ->with(
                'qr_success',
                'QR kod za prijavu zapažanja je deaktiviran.'
            );
    }

    public function activate(
        QrCodeService $qrCodeService
    ): RedirectResponse {
        $owner =
            $this->authorizedOwner(
                'update'
            );

        $qrCode =
            $qrCodeService
                ->getOrCreateForObservationOwner(
                    $owner,
                    auth()->user()
                );

        $qrCodeService->activate(
            $qrCode
        );

        return redirect()
            ->route(
                'observation.qr.admin'
            )
            ->with(
                'qr_success',
                'QR kod za prijavu zapažanja je ponovno aktiviran.'
            );
    }

    public function downloadPdf(
        QrCodeService $qrCodeService
    ) {
        $owner =
            $this->authorizedOwner(
                'view'
            );

        $qrCode =
            $qrCodeService
                ->getOrCreateForObservationOwner(
                    $owner,
                    auth()->user()
                );

        $svg =
            $qrCodeService
                ->observationSvg(
                    $qrCode,
                    1400
                );

        $qrDataUri =
            'data:image/svg+xml;base64,'
            . base64_encode(
                $svg
            );

        $pdf =
            Pdf::loadView(
                'pdf.observation-qr-poster',
                [
                    'owner' =>
                        $owner,

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
            'qr-poster-prijava-zapazanja.pdf'
        );
    }

    protected function authorizedOwner(
        string $permission
    ): User {
        /** @var User|null $user */
        $user =
            auth()->user();

        abort_unless(
            $user,
            403
        );

        /*
         * QR za javnu prijavu izrađuje organizacija,
         * ne superadmin.
         */
        abort_if(
            $user->isSuperAdmin(),
            403
        );

        abort_unless(
            ObservationResource
                ::allowsModulePermission(
                    $permission
                ),
            403
        );

        $ownerId =
            $user->ownerId();

        abort_unless(
            $ownerId,
            403
        );

        return User::query()
            ->whereKey(
                $ownerId
            )
            ->firstOrFail();
    }
}
