<?php

namespace App\Http\Controllers\PublicQr;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\RiskAssessment;
use App\Models\RiskAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiskAssessmentQrController extends Controller
{
    public function show(
        string $token
    ) {
        $qrCode =
            $this->findRiskAssessmentQr(
                $token
            );

        /** @var RiskAssessment|null $riskAssessment */
        $riskAssessment =
            RiskAssessment::query()
                ->with([
                    'revisions',
                    'attachments',
                ])
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->first();

        abort_unless(
            $riskAssessment,
            404,
            'Procjena rizika nije dostupna.'
        );

        /*
         * Dodatna tenant zaštita.
         */
        abort_unless(
            (int) $riskAssessment->user_id
                === (int) $qrCode->owner_id,
            404
        );

        $qrCode->recordScan();

        return view(
            'public.qr.risk-assessment',
            compact(
                'riskAssessment',
                'qrCode'
            )
        );
    }

    public function attachment(
        string $token,
        int $index
    ): StreamedResponse {
        $qrCode =
            $this->findRiskAssessmentQr(
                $token
            );

        /** @var RiskAssessment|null $riskAssessment */
        $riskAssessment =
            RiskAssessment::query()
                ->with('attachments')
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->first();

        abort_unless(
            $riskAssessment,
            404
        );

        abort_unless(
            (int) $riskAssessment->user_id
                === (int) $qrCode->owner_id,
            404
        );

        $attachments =
            $riskAssessment
                ->attachments
                ->values();

        abort_unless(
            $attachments->has($index),
            404
        );

        /** @var RiskAttachment $attachment */
        $attachment =
            $attachments->get($index);

        $path =
            $attachment->file_path;

        abort_unless(
            is_string($path)
            && filled($path),
            404
        );

        $path = ltrim(
            $path,
            '/'
        );

        /*
        * Novi prilozi procjene rizika
        * nalaze se na private/local disku.
        *
        * Public ostaje privremeni fallback
        * tijekom migracije starih zapisa.
        */
        if (
            Storage::disk('local')
                ->exists($path)
        ) {
            $disk = 'local';
        } elseif (
            Storage::disk('public')
                ->exists($path)
        ) {
            $disk = 'public';
        } else {
            abort(404);
        }

        $fileName = str_replace(
            ["\r", "\n", '"'],
            '',
            basename($path)
        );

        return Storage::disk($disk)
            ->response(
                $path,
                $fileName,
                [
                    'Content-Disposition' =>
                        'inline; filename="'
                        . $fileName
                        . '"',

                    'X-Content-Type-Options' =>
                        'nosniff',

                    'Cache-Control' =>
                        'private, no-store',
                ]
            );
    }

    protected function findRiskAssessmentQr(
        string $token
    ): QrCode {
        return QrCode::query()
            ->where(
                'token',
                $token
            )
            ->where(
                'type',
                'risk_assessment'
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'qrable_type',
                RiskAssessment::class
            )
            ->firstOrFail();
    }
}