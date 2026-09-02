<?php

namespace App\Http\Controllers;

use App\Models\FirstAidKit;
use App\Services\FirstAidKitQrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;

class FirstAidKitQrAdminController extends Controller
{
    public function show(
        FirstAidKit $firstAidKit,
        FirstAidKitQrService $qrService
    ) {
        $this->authorizeFirstAidKit(
            $firstAidKit
        );

        $firstAidKit->load([
            'items' => fn ($query) =>
                $query->orderBy(
                    'valid_until'
                ),
            'user',
        ]);

        $qrCode =
            $qrService->getOrCreate(
                $firstAidKit,
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
            'qr.first-aid-kit-admin',
            compact(
                'firstAidKit',
                'qrCode',
                'svg',
                'publicUrl'
            )
        );
    }

    public function regenerate(
        FirstAidKit $firstAidKit,
        FirstAidKitQrService $qrService
    ): RedirectResponse {
        $this->authorizeFirstAidKit(
            $firstAidKit
        );

        $qrCode =
            $qrService->getOrCreate(
                $firstAidKit,
                auth()->user()
            );

        $qrService->regenerate(
            $qrCode
        );

        return redirect()
            ->route(
                'first-aid-kit.qr.admin',
                [
                    'firstAidKit' =>
                        $firstAidKit,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je regeneriran. Stari QR kod više nije važeći.'
            );
    }

    public function deactivate(
        FirstAidKit $firstAidKit,
        FirstAidKitQrService $qrService
    ): RedirectResponse {
        $this->authorizeFirstAidKit(
            $firstAidKit
        );

        $qrCode =
            $qrService->getOrCreate(
                $firstAidKit,
                auth()->user()
            );

        $qrService->deactivate(
            $qrCode
        );

        return redirect()
            ->route(
                'first-aid-kit.qr.admin',
                [
                    'firstAidKit' =>
                        $firstAidKit,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je deaktiviran.'
            );
    }

    public function activate(
        FirstAidKit $firstAidKit,
        FirstAidKitQrService $qrService
    ): RedirectResponse {
        $this->authorizeFirstAidKit(
            $firstAidKit
        );

        $qrCode =
            $qrService->getOrCreate(
                $firstAidKit,
                auth()->user()
            );

        $qrService->activate(
            $qrCode
        );

        return redirect()
            ->route(
                'first-aid-kit.qr.admin',
                [
                    'firstAidKit' =>
                        $firstAidKit,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je ponovno aktiviran.'
            );
    }

    public function downloadPdf(
        FirstAidKit $firstAidKit,
        FirstAidKitQrService $qrService
    ) {
        $this->authorizeFirstAidKit(
            $firstAidKit
        );

        $qrCode =
            $qrService->getOrCreate(
                $firstAidKit,
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
                'pdf.first-aid-kit-qr-label',
                [
                    'firstAidKit' =>
                        $firstAidKit,

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
            'qr-prva-pomoc-'
            . $firstAidKit->id
            . '.pdf'
        );
    }

    protected function authorizeFirstAidKit(
        FirstAidKit $firstAidKit
    ): void {
        $user =
            auth()->user();

        abort_unless(
            $user,
            403
        );

        if (
            $user->isSuperAdmin()
        ) {
            return;
        }

        abort_unless(
            (int) $firstAidKit->user_id
                ===
            (int) $user->ownerId(),
            404
        );
    }
}