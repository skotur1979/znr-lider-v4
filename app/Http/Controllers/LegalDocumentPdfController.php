<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class LegalDocumentPdfController extends Controller
{
    public function privacy(): Response
    {
        $pdf = Pdf::loadView('pdf.legal-privacy')
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->download('pravila-privatnosti-v' . config('legal.privacy_version') . '.pdf');
    }

    public function terms(): Response
    {
        $pdf = Pdf::loadView('pdf.legal-terms')
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->download('uvjeti-koristenja-v' . config('legal.terms_version') . '.pdf');
    }
}
