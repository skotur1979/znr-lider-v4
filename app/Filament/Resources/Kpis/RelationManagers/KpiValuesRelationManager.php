<?php

namespace App\Filament\Resources\Kpis\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KpiValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected static ?string $title = 'Mjesečne vrijednosti KPI-a';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->defaultSort('month', 'desc')
            ->columns([
                TextColumn::make('month')
                    ->label('Mjesec')
                    ->formatStateUsing(fn ($state) => str_pad((string) $state, 2, '0', STR_PAD_LEFT))
                    ->sortable(),

                TextColumn::make('year')
                    ->label('Godina')
                    ->sortable(),

                TextColumn::make('value')
                    ->label('Vrijednost')
                    ->sortable(),

                IconColumn::make('auto_generated')
                    ->label('Automatsko')
                    ->boolean(),

                TextColumn::make('source_label')
                    ->label('Izvor')
                    ->badge()
                    ->color(fn ($state) => match ((string) $state) {
                        'Incidenti', 'ONTO', 'Zapažanja', 'Nadzori', 'Nalazi nadzora', 'Formula AFR', 'Formula ASR' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('note')
                    ->label('Komentar')
                    ->wrap(),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}