<?php

namespace App\Http\Controllers;

use App\Filament\Widgets\TopSystemStatusBarWidget;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ZnrGeneralReportController extends Controller
{
    public function pdf(Request $request)
    {
        $user = Auth::user();

        abort_unless($user, 403);

        $statusData = TopSystemStatusBarWidget::makeSystemStatusData($user);

        $rows = collect($statusData['rows'] ?? [])
            ->map(fn (array $row) => [
                'label' => $row['label'],
                'expired' => $row['expired_count'] ?? 0,
                'soon' => $row['soon_count'] ?? 0,
            ])
            ->values()
            ->all();

        $totalExpired = $statusData['totalExpired'] ?? 0;
        $totalSoon = $statusData['totalSoon'] ?? 0;

        $systemStatus = $statusData['title'] ?? 'SVE U REDU';

        $actions = [];

        foreach ($rows as $row) {
            if (($row['expired'] ?? 0) > 0) {
                $actions[] = "Pregledati i riješiti istekle stavke: {$row['label']} ({$row['expired']}).";
            }
        }

        if ($totalSoon > 0) {
            $actions[] = "Planirati aktivnosti za stavke koje istječu u sljedećih 30 dana ({$totalSoon}).";
        }

        if (empty($actions)) {
            $actions[] = 'Nastaviti redovito praćenje rokova i preventivno održavati sustav.';
        }

        $summary = match ($statusData['state'] ?? 'ok') {
            'critical' => "Sustav je kritičan zbog {$totalExpired} isteklih stavki. Potrebno je prioritetno riješiti istekle obveze kako bi se osigurala usklađenost i smanjili rizici.",
            'warning' => $totalExpired > 0
                ? "Sustav zahtijeva pažnju jer postoji {$totalExpired} isteklih stavki. Potrebno ih je planirati i riješiti prema prioritetu."
                : "Sustav zahtijeva pažnju jer {$totalSoon} stavki istječe unutar 30 dana. Potrebno je pravovremeno planirati aktivnosti.",
            default => 'Sustav je trenutno uredan. Nema isteklih stavki ni kritičnih rokova u sljedećih 30 dana.',
        };

        $data = [
            'reportDate' => now()->format('d.m.Y. H:i'),
            'systemStatus' => $systemStatus,
            'totalExpired' => $totalExpired,
            'totalSoon' => $totalSoon,
            'summary' => $summary,
            'rows' => $rows,
            'actions' => $actions,

            'daysWithoutLta' => $statusData['daysWithoutLta'] ?? null,
            'ltaRecordDays' => $statusData['recordDaysWithoutLta'] ?? null,

            'ltaCount' => 0,
            'mtaCount' => 0,
            'faaCount' => 0,
            'openObservations' => 0,
            'openWorkTasks' => 0,
            'closedWorkTasks' => 0,
        ];

        $pdf = Pdf::loadView('pdf.znr-general-report', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => false,
            ]);

        return $pdf->stream('ZNR-izvjestaj-o-stanju-sustava.pdf');
    }
}