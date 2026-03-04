<?php

namespace App\Filament\Resources\RiskAssessments\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class RiskAssessmentsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tvrtka')->label('Tvrtka')->searchable(),

                TextColumn::make('broj_procjene')
                    ->label('Broj procjene')
                    ->alignCenter(),

                TextColumn::make('datum_izrade')
                    ->label('Datum izrade')
                    ->alignCenter()
                    ->date('d.m.Y.')
                    ->sortable(),

                TextColumn::make('vrsta_procjene')
                    ->label('Vrsta procjene')
                    ->alignCenter()
                    ->searchable(),

                TextColumn::make('revisions_count')
                    ->label('Broj revizija')
                    ->alignCenter()
                    ->counts('revisions'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Prikaz'),
                Tables\Actions\EditAction::make()->label('Uredi'),

                Tables\Actions\DeleteAction::make()
                    ->label('Obriši')
                    ->modalHeading('Obriši Procjenu rizika')
                    ->modalSubheading('Jeste li sigurni da želite obrisati ovu Procjenu rizika?')
                    ->successNotificationTitle('Procjena rizika je obrisana.'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->modalHeading('Obriši Procjene rizika')
                    ->modalSubheading('Jeste li sigurni da želite obrisati ove Procjene rizika?')
                    ->successNotificationTitle('Procjene rizika su obrisane.'),
            ]);
    }
}