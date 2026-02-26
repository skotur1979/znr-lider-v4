<?php

namespace App\Filament\Resources\RiskAssessments\Pages;

use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRiskAssessment extends ViewRecord
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Uredi'),
        ];
    }
}