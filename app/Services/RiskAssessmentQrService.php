<?php

namespace App\Services;

use App\Models\QrCode;
use App\Models\RiskAssessment;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;

class RiskAssessmentQrService
{
    public function getOrCreate(
        RiskAssessment $riskAssessment,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        return QrCode::query()
            ->firstOrCreate(
                [
                    'type' => 'risk_assessment',
                    'qrable_type' => RiskAssessment::class,
                    'qrable_id' => $riskAssessment->getKey(),
                ],
                [
                    'owner_id' => $riskAssessment->user_id,
                    'created_by' => $actor?->id,
                    'name' => $this->qrName($riskAssessment),
                    'is_active' => true,
                ]
            );
    }

    public function publicUrl(
        QrCode $qrCode
    ): string {
        return route(
            'public.risk-assessment.show',
            [
                'token' => $qrCode->token,
            ]
        );
    }

    public function svg(
        QrCode $qrCode,
        int $size = 500
    ): string {
        $renderer = new ImageRenderer(
            new RendererStyle(
                $size,
                4
            ),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString(
            $this->publicUrl($qrCode)
        );
    }

    public function qrName(
        RiskAssessment $riskAssessment
    ): string {
        $name = 'Procjena rizika';

        if (filled($riskAssessment->broj_procjene)) {
            $name .= ' - '
                . $riskAssessment->broj_procjene;
        }

        if (filled($riskAssessment->tvrtka)) {
            $name .= ' - '
                . $riskAssessment->tvrtka;
        }

        return $name;
    }

    public function regenerate(
        QrCode $qrCode
    ): QrCode {
        $qrCode->forceFill([
            'token' => QrCode::generateUniqueToken(),
            'is_active' => true,
            'scan_count' => 0,
            'last_scanned_at' => null,
        ])->save();

        return $qrCode->refresh();
    }

    public function deactivate(
        QrCode $qrCode
    ): void {
        $qrCode->update([
            'is_active' => false,
        ]);
    }

    public function activate(
        QrCode $qrCode
    ): void {
        $qrCode->update([
            'is_active' => true,
        ]);
    }
}