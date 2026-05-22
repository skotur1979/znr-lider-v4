<?php

namespace App\Filament\Resources\LegalAcceptances\Pages;

use App\Exports\LegalAcceptancesExport;
use App\Filament\Resources\LegalAcceptances\LegalAcceptanceResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListLegalAcceptances extends ListRecords
{
    protected static string $resource = LegalAcceptanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    $records = $this->getFilteredSortedTableQuery()
                        ->get();

                    $pdf = Pdf::loadView('pdf.legal-acceptances', [
                        'records' => $records,
                    ])
                        ->setPaper('a4', 'landscape')
                        ->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                            'defaultFont' => 'DejaVu Sans',
                        ]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'gdpr-evidencija-' . now()->format('Y-m-d-H-i-s') . '.pdf'
                    );
                }),

            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $records = $this->getFilteredSortedTableQuery()
                        ->get();

                    return Excel::download(
                        new LegalAcceptancesExport($records),
                        'gdpr-evidencija-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
                    );
                }),
        ];
    }
}
