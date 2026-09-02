<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Services\LearningMaterialQrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;

class LearningMaterialQrAdminController extends Controller
{
    public function show(
        LearningMaterial $learningMaterial,
        LearningMaterialQrService $qrService
    ) {
        $this->authorizeLearningMaterial(
            $learningMaterial
        );

        $learningMaterial->load([
            'category',
            'user',
        ]);

        $qrCode = $qrService->getOrCreate(
            $learningMaterial,
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
            'qr.learning-material-admin',
            compact(
                'learningMaterial',
                'qrCode',
                'svg',
                'publicUrl'
            )
        );
    }

    public function regenerate(
        LearningMaterial $learningMaterial,
        LearningMaterialQrService $qrService
    ): RedirectResponse {
        $this->authorizeLearningMaterial(
            $learningMaterial
        );

        $qrCode = $qrService->getOrCreate(
            $learningMaterial,
            auth()->user()
        );

        $qrService->regenerate(
            $qrCode
        );

        return redirect()
            ->route(
                'learning-material.qr.admin',
                [
                    'learningMaterial' =>
                        $learningMaterial,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je regeneriran. Stari QR kod više nije važeći.'
            );
    }

    public function deactivate(
        LearningMaterial $learningMaterial,
        LearningMaterialQrService $qrService
    ): RedirectResponse {
        $this->authorizeLearningMaterial(
            $learningMaterial
        );

        $qrCode = $qrService->getOrCreate(
            $learningMaterial,
            auth()->user()
        );

        $qrService->deactivate(
            $qrCode
        );

        return redirect()
            ->route(
                'learning-material.qr.admin',
                [
                    'learningMaterial' =>
                        $learningMaterial,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je deaktiviran.'
            );
    }

    public function activate(
        LearningMaterial $learningMaterial,
        LearningMaterialQrService $qrService
    ): RedirectResponse {
        $this->authorizeLearningMaterial(
            $learningMaterial
        );

        $qrCode = $qrService->getOrCreate(
            $learningMaterial,
            auth()->user()
        );

        $qrService->activate(
            $qrCode
        );

        return redirect()
            ->route(
                'learning-material.qr.admin',
                [
                    'learningMaterial' =>
                        $learningMaterial,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je ponovno aktiviran.'
            );
    }

    public function downloadPdf(
        LearningMaterial $learningMaterial,
        LearningMaterialQrService $qrService
    ) {
        $this->authorizeLearningMaterial(
            $learningMaterial
        );

        $learningMaterial->load([
            'category',
        ]);

        $qrCode = $qrService->getOrCreate(
            $learningMaterial,
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
            'pdf.learning-material-qr-label',
            [
                'learningMaterial' =>
                    $learningMaterial,

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
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 150,
            ]);

        return $pdf->download(
            'qr-edukacijski-materijal-'
            . $learningMaterial->id
            . '.pdf'
        );
    }

    protected function authorizeLearningMaterial(
        LearningMaterial $learningMaterial
    ): void {
        $user = auth()->user();

        abort_unless(
            $user,
            403
        );

        /*
         * QR za globalni materijal administrira
         * samo superadmin.
         */
        if (
            (bool) $learningMaterial->is_global
            || $learningMaterial->user_id === null
        ) {
            abort_unless(
                $user->isSuperAdmin(),
                403
            );

            return;
        }

        /*
         * Superadmin smije administrirati
         * i organizacijske materijale.
         */
        if ($user->isSuperAdmin()) {
            return;
        }

        /*
         * Organizacijski korisnik:
         * samo materijal svoje organizacije.
         */
        abort_unless(
            (int) $learningMaterial->user_id
                ===
            (int) $user->ownerId(),
            404
        );
    }
}