<?php

namespace App\Http\Controllers;

use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use App\Models\RiskAssessment;
use App\Services\RiskAssessmentQrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;

class RiskAssessmentQrAdminController extends Controller
{
    public function show(
        RiskAssessment $riskAssessment,
        RiskAssessmentQrService $qrService
    ) {
        $this->authorizeRiskAssessment(
            $riskAssessment,
            'view'
        );

        $qrCode = $qrService->getOrCreate(
            $riskAssessment,
            auth()->user()
        );

        $svg = $qrService->svg(
            $qrCode,
            500
        );

        $publicUrl = $qrService->publicUrl(
            $qrCode
        );

        $canManage =
            RiskAssessmentResource::canEdit(
                $riskAssessment
            );

        return view(
            'qr.risk-assessment-admin',
            compact(
                'riskAssessment',
                'qrCode',
                'svg',
                'publicUrl',
                'canManage'
            )
        );
    }

    public function regenerate(
        RiskAssessment $riskAssessment,
        RiskAssessmentQrService $qrService
    ): RedirectResponse {
        $this->authorizeRiskAssessment(
            $riskAssessment,
            'update'
        );

        $qrCode = $qrService->getOrCreate(
            $riskAssessment,
            auth()->user()
        );

        $qrService->regenerate(
            $qrCode
        );

        return redirect()
            ->route(
                'risk-assessment.qr.admin',
                [
                    'riskAssessment' =>
                        $riskAssessment,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je regeneriran. Stari QR kod više nije važeći.'
            );
    }

    public function deactivate(
        RiskAssessment $riskAssessment,
        RiskAssessmentQrService $qrService
    ): RedirectResponse {
        $this->authorizeRiskAssessment(
            $riskAssessment,
            'update'
        );

        $qrCode = $qrService->getOrCreate(
            $riskAssessment,
            auth()->user()
        );

        $qrService->deactivate(
            $qrCode
        );

        return redirect()
            ->route(
                'risk-assessment.qr.admin',
                [
                    'riskAssessment' =>
                        $riskAssessment,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je deaktiviran.'
            );
    }

    public function activate(
        RiskAssessment $riskAssessment,
        RiskAssessmentQrService $qrService
    ): RedirectResponse {
        $this->authorizeRiskAssessment(
            $riskAssessment,
            'update'
        );

        $qrCode = $qrService->getOrCreate(
            $riskAssessment,
            auth()->user()
        );

        $qrService->activate(
            $qrCode
        );

        return redirect()
            ->route(
                'risk-assessment.qr.admin',
                [
                    'riskAssessment' =>
                        $riskAssessment,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je ponovno aktiviran.'
            );
    }

    public function downloadPdf(
        RiskAssessment $riskAssessment,
        RiskAssessmentQrService $qrService
    ) {
        $this->authorizeRiskAssessment(
            $riskAssessment,
            'view'
        );

        $qrCode = $qrService->getOrCreate(
            $riskAssessment,
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
            'pdf.risk-assessment-qr-label',
            [
                'riskAssessment' =>
                    $riskAssessment,

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
            'qr-procjena-rizika-'
            . $riskAssessment->id
            . '.pdf'
        );
    }

    protected function authorizeRiskAssessment(
        RiskAssessment $riskAssessment,
        string $permission
    ): void {
        $user = auth()->user();

        abort_unless(
            $user,
            403
        );

        /*
         * Tenant zaštita.
         *
         * Superadmin može pregledavati sve.
         * Ostali korisnici samo zapise svoje
         * organizacije.
         */
        if (! $user->isSuperAdmin()) {
            abort_unless(
                (int) $riskAssessment->user_id
                    === (int) $user->ownerId(),
                404
            );
        }

        if ($permission === 'update') {
            abort_unless(
                RiskAssessmentResource::canEdit(
                    $riskAssessment
                ),
                403
            );

            return;
        }

        abort_unless(
            RiskAssessmentResource::canView(
                $riskAssessment
            ),
            403
        );
    }
}