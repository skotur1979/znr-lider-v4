<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class LegalDocumentPdfController extends Controller
{
    protected function pdf(string $view, string $filename): Response
    {
        $pdf = Pdf::loadView($view)
            ->setPaper('a4')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->download($filename);
    }

    public function privacy(): Response
    {
        return $this->pdf(
            'pdf.legal-privacy',
            'pravila-privatnosti-v' . config('legal.privacy_version') . '.pdf'
        );
    }

    public function terms(): Response
    {
        return $this->pdf(
            'pdf.legal-terms',
            'uvjeti-koristenja-v' . config('legal.terms_version') . '.pdf'
        );
    }

    public function cookies(): Response
    {
        return $this->pdf(
            'pdf.legal-cookies',
            'politika-kolacica-v' . config('legal.cookies_version') . '.pdf'
        );
    }

    public function dpa(): Response
    {
        return $this->pdf(
            'pdf.legal-dpa',
            'dpa-v' . config('legal.dpa_version') . '.pdf'
        );
    }

    public function security(): Response
    {
        return $this->pdf(
            'pdf.legal-security',
            'politika-sigurnosti-v' . config('legal.security_version') . '.pdf'
        );
    }

    public function retention(): Response
    {
        return $this->pdf(
            'pdf.legal-retention',
            'zadrzavanje-podataka-v' . config('legal.retention_version') . '.pdf'
        );
    }
}
