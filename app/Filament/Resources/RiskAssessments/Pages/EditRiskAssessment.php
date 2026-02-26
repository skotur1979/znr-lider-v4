<?php

namespace App\Filament\Resources\RiskAssessments\Pages;

use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditRiskAssessment extends EditRecord
{
    protected static string $resource = RiskAssessmentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}