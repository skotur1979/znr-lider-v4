<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Fires\FireResource;
use App\Models\Fire;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FireQrAdminController extends Controller
{
    public function show(
        Fire $fire,
        QrCodeService $qrCodeService
    ) {
        $this->authorizeFire(
            $fire,
            'view'
        );

        $qrCode =
            $qrCodeService
                ->getOrCreateForFire(
                    $fire,
                    auth()->user()
                );

        $svg =
            $qrCodeService
                ->fireSvg(
                    $qrCode,
                    500
                );

        $publicUrl =
            $qrCodeService
                ->publicFireUrl(
                    $qrCode
                );

        return view(
            'qr.fire-admin',
            compact(
                'fire',
                'qrCode',
                'svg',
                'publicUrl'
            )
        );
    }

    public function regenerate(
        Fire $fire,
        QrCodeService $qrCodeService
    ): RedirectResponse {
        $this->authorizeFire(
            $fire,
            'update'
        );

        $qrCode =
            $qrCodeService
                ->getOrCreateForFire(
                    $fire,
                    auth()->user()
                );

        $qrCodeService->regenerate(
            $qrCode
        );

        return redirect()
            ->route(
                'fire.qr.admin',
                [
                    'fire' => $fire,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je regeneriran. Stari QR kod više nije važeći.'
            );
    }

    public function deactivate(
        Fire $fire,
        QrCodeService $qrCodeService
    ): RedirectResponse {
        $this->authorizeFire(
            $fire,
            'update'
        );

        $qrCode =
            $qrCodeService
                ->getOrCreateForFire(
                    $fire,
                    auth()->user()
                );

        $qrCodeService->deactivate(
            $qrCode
        );

        return redirect()
            ->route(
                'fire.qr.admin',
                [
                    'fire' => $fire,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je deaktiviran.'
            );
    }

    public function activate(
        Fire $fire,
        QrCodeService $qrCodeService
    ): RedirectResponse {
        $this->authorizeFire(
            $fire,
            'update'
        );

        $qrCode =
            $qrCodeService
                ->getOrCreateForFire(
                    $fire,
                    auth()->user()
                );

        $qrCodeService->activate(
            $qrCode
        );

        return redirect()
            ->route(
                'fire.qr.admin',
                [
                    'fire' => $fire,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je ponovno aktiviran.'
            );
    }

    public function regularInspection(
        Request $request,
        Fire $fire
    ): RedirectResponse {
        $this->authorizeFire(
            $fire,
            'update'
        );

        $validated =
            $request->validate([
                'regular_examination_valid_from' => [
                    'required',
                    'date',
                ],

                'visible' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'remark' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'action' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ]);

        $fire->update([
            'regular_examination_valid_from' =>
                $validated[
                    'regular_examination_valid_from'
                ],

            'visible' =>
                $validated['visible']
                ?? $fire->visible,

            'remark' =>
                $validated['remark']
                ?? $fire->remark,

            'action' =>
                $validated['action']
                ?? $fire->action,
        ]);

        return redirect()
            ->route(
                'fire.qr.admin',
                [
                    'fire' => $fire,
                ]
            )
            ->with(
                'qr_success',
                'Redovni pregled vatrogasnog aparata je evidentiran. Novi rok vrijedi 3 mjeseca od upisanog datuma.'
            );
    }

    public function downloadPdf(
        Fire $fire,
        QrCodeService $qrCodeService
    ) {
        $this->authorizeFire(
            $fire,
            'view'
        );

        $qrCode =
            $qrCodeService
                ->getOrCreateForFire(
                    $fire,
                    auth()->user()
                );

        $svg =
            $qrCodeService
                ->fireSvg(
                    $qrCode,
                    1000
                );

        $qrDataUri =
            'data:image/svg+xml;base64,'
            . base64_encode($svg);

        $pdf =
            Pdf::loadView(
                'pdf.fire-qr-label',
                [
                    'fire' => $fire,
                    'qrCode' => $qrCode,
                    'qrDataUri' => $qrDataUri,
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
            'qr-vatrogasni-aparat-'
            . $fire->id
            . '.pdf'
        );
    }

    protected function authorizeFire(
        Fire $fire,
        string $permission
    ): void {
        abort_if(
            $fire->trashed(),
            404
        );

        $user =
            auth()->user();

        abort_unless(
            $user,
            403
        );

        if (! $user->isSuperAdmin()) {
            abort_unless(
                (int) $fire->user_id
                    === (int) $user->ownerId(),
                404
            );
        }

        abort_unless(
            FireResource::ensureModulePermission(
                $permission
            ),
            403
        );
    }
}
