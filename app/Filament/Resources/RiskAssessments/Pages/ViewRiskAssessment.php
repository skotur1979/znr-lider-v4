<?php

namespace App\Filament\Resources\RiskAssessments\Pages;

use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRiskAssessment extends ViewRecord
{
    protected static string $resource =
        RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('qrCode')
                ->label('QR kod')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->url(
                    fn (): string =>
                        route(
                            'risk-assessment.qr.admin',
                            [
                                'riskAssessment' =>
                                    $this->getRecord(),
                            ]
                        )
                )
                ->openUrlInNewTab(),

            EditAction::make()
                ->label('Uredi'),
        ];
    }
}