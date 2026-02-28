<?php

namespace App\Filament\Resources\Observations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ObservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('incident_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('observation_type')
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('item')
                    ->searchable(),
                TextColumn::make('potential_incident_type')
                    ->searchable(),
                TextColumn::make('responsible')
                    ->searchable(),
                TextColumn::make('target_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status'),
                TextColumn::make('picture_path')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
