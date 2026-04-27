<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ZnrGeneralReportController extends Controller
{
    public function pdf(Request $request)
    {
        // OVDJE kasnije možeš zamijeniti s pravim podacima iz baze.
        // Za početak šaljemo strukturu koju već imaš na dashboardu.

        $data = [
            'reportDate' => now()->format('d.m.Y. H:i'),
            'systemStatus' => 'KRITIČNO',
            'totalExpired' => 219,
            'totalSoon' => 23,

            'summary' => 'Sustav je kritičan zbog 219 isteklih stavki. Potrebno je pregledati i riješiti istekle obveze kako bi se osigurala usklađenost i smanjili rizici.',

            'rows' => [
                ['label' => 'Liječnički pregledi', 'expired' => 31, 'soon' => 2],
                ['label' => 'Edukacije', 'expired' => 72, 'soon' => 4],
                ['label' => 'Radna oprema', 'expired' => 18, 'soon' => 11],
                ['label' => 'Aparati', 'expired' => 2, 'soon' => 0],
                ['label' => 'Ostala ispitivanja', 'expired' => 62, 'soon' => 5],
                ['label' => 'OZO', 'expired' => 25, 'soon' => 1],
                ['label' => 'Prva pomoć', 'expired' => 7, 'soon' => 0],
                ['label' => 'Zapažanja', 'expired' => 2, 'soon' => 0],
            ],

            'actions' => [
                'Pregledati i riješiti istekle edukacije.',
                'Provjeriti ostala ispitivanja s isteklim rokom.',
                'Ažurirati liječničke preglede zaposlenika.',
                'Pregledati stavke radne opreme koje uskoro istječu.',
                'Riješiti otvorene kritične stavke po prioritetu.',
            ],
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