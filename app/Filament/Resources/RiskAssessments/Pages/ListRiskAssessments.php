<?php

namespace App\Filament\Resources\RiskAssessments\Pages;

use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRiskAssessments extends ListRecords
{
    protected static string $resource =
        RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Procjena Rizika')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon(
                    'heroicon-o-arrow-down-tray'
                )
                ->color('warning')
                ->action(function () {
                    /*
                     * Koristimo query same tablice.
                     *
                     * Time PDF poštuje:
                     * - tenant scope
                     * - aktivne filtre
                     * - pretragu
                     * - sortiranje
                     */
                    $riskAssessments = $this
                        ->getFilteredSortedTableQuery()
                        ->with([
                            'participants',
                            'revisions',
                            'attachments',
                        ])
                        ->get();

                    $pdf = Pdf::loadView(
                        'pdf.risk-assessments',
                        compact(
                            'riskAssessments'
                        )
                    )
                        ->setPaper(
                            'a4',
                            'landscape'
                        )
                        ->setOptions([
                            'isHtml5ParserEnabled' =>
                                true,

                            'isRemoteEnabled' =>
                                true,

                            'isPhpEnabled' =>
                                true,

                            'dpi' =>
                                96,

                            'defaultFont' =>
                                'DejaVu Sans',
                        ]);

                    return response()
                        ->streamDownload(
                            fn () => print(
                                $pdf->output()
                            ),
                            'procjene-rizika-'
                            . now()->format(
                                'Y-m-d'
                            )
                            . '.pdf'
                        );
                }),
        ];
    }
}