<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewKpi extends ViewRecord
{
    protected static string $resource = KpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Uredi KPI'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ViewEntry::make('kpi_overview')
                ->label('')
                ->view('filament.resources.kpis.infolists.simple-kpi-overview')
                ->viewData([
                    'record' => $this->record,
                    'trend' => $this->record->monthlyTrendForYear(now()->year),
                ]),
        ]);
    }
}