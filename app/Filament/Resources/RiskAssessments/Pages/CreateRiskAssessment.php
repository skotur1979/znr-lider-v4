<?php

namespace App\Filament\Resources\RiskAssessments\Pages;

use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRiskAssessment extends CreateRecord
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return RiskAssessmentResource::fillOwnershipData($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}