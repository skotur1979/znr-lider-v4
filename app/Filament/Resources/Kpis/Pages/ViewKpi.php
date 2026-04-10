<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
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
            Section::make('Pregled KPI-a')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('name')->label('Naziv KPI-a'),
                            TextEntry::make('category')->label('Kategorija'),
                            TextEntry::make('unit')->label('Jedinica'),

                            TextEntry::make('target_value')
                                ->label('Cilj')
                                ->formatStateUsing(fn ($state, $record) => $record->formatNumberOnly($state)),

                            TextEntry::make('calculation_type')
                                ->label('Tip')
                                ->badge()
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'manual' => 'Ručno',
                                    'automatic' => 'Automatski',
                                    'formula' => 'Formula',
                                    default => $state,
                                })
                                ->color(fn ($state) => match ($state) {
                                    'manual' => 'gray',
                                    'automatic' => 'success',
                                    'formula' => 'warning',
                                    default => 'gray',
                                }),

                            TextEntry::make('current_status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'success' => 'U cilju',
                                    'warning' => 'Upozorenje',
                                    'danger' => 'Izvan cilja',
                                    default => 'Bez cilja',
                                })
                                ->color(fn ($state) => match ($state) {
                                    'success' => 'success',
                                    'warning' => 'warning',
                                    'danger' => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('is_active')
                                ->label('Aktivan')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state ? 'DA' : 'NE')
                                ->color(fn ($state) => $state ? 'success' : 'danger'),

                            TextEntry::make('show_on_dashboard')
                                ->label('Dashboard')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state ? 'DA' : 'NE')
                                ->color(fn ($state) => $state ? 'success' : 'gray'),

                            TextEntry::make('latest_value_display')
                                ->label('Zadnja vrijednost')
                                ->state(fn ($record) => $record->formatValue($record->latestValue()?->value)),

                            TextEntry::make('latest_period')
                                ->label('Zadnji period')
                                ->state(function ($record) {
                                    $latest = $record->latestValue();

                                    if (! $latest) {
                                        return '-';
                                    }

                                    return sprintf('%02d/%s', $latest->month, $latest->year);
                                }),
                        ]),

                    ViewEntry::make('monthly_trend')
                        ->label('')
                        ->view('filament.resources.kpis.infolists.monthly-line-chart')
                        ->viewData([
                            'record' => $this->record,
                            'trend' => $this->record->monthlyTrendForYear(now()->year),
                        ]),

                    TextEntry::make('formula_text')
                        ->label('Opis formule')
                        ->placeholder('-')
                        ->visible(fn ($record) => filled($record->formula_text)),

                    TextEntry::make('description')
                        ->label('Opis / napomena')
                        ->placeholder('-'),
                ]),
        ]);
    }
}