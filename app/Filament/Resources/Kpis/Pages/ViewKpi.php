<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewKpi extends ViewRecord
{
    protected static string $resource =
        KpiResource::class;

    protected string $view =
        'filament.resources.kpis.pages.view-kpi';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Povratak na KPI')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(
                    KpiResource::getUrl('index')
                ),
        ];
    }

    protected function getViewData(): array
    {
        $ownerId =
            KpiResource::resolveOwnerId();

        $record = $this->record->load([
            'values',
            'targetOverrides',
        ]);

        return [
            'record' => $record,

            'trend' =>
                $record->monthlyTrendForYear(
                    now()->year,
                    $ownerId
                ),

            'ownerId' => $ownerId,
        ];
    }
}