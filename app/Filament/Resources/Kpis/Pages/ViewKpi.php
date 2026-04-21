<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use Filament\Resources\Pages\ViewRecord;

class ViewKpi extends ViewRecord
{
    protected static string $resource = KpiResource::class;

    protected string $view = 'filament.resources.kpis.pages.view-kpi';

    protected function getViewData(): array
    {
        $record = $this->record->load('values');

        return [
            'record' => $record,
            'trend' => $record->monthlyTrendForYear(now()->year),
            'ownerId' => KpiResource::resolveOwnerId(),
        ];
    }
}