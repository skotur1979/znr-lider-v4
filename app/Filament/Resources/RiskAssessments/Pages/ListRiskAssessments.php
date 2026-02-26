<?php

namespace App\Filament\Resources\RiskAssessments\Pages;

use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRiskAssessments extends ListRecords
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nova Procjena Rizika'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    $riskAssessments = RiskAssessmentResource::getEloquentQuery()
                        ->with(['participants', 'revisions', 'attachments'])
                        ->orderBy('tvrtka')
                        ->orderBy('broj_procjene')
                        ->get();

                    $pdf = Pdf::loadView('pdf.risk-assessments', compact('riskAssessments'))
                        ->setPaper('a4', 'landscape'); // ✅ kao kod first aid footer širine

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'procjene-rizika-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),
        ];
    }
}