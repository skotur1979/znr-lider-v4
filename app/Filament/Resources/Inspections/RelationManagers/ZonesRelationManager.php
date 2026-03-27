<?php

namespace App\Filament\Resources\Inspections\RelationManagers;

use App\Filament\Resources\InspectionZones\Pages\EditInspectionZone;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ZonesRelationManager extends RelationManager
{
    protected static string $relationship = 'zones';

    protected static ?string $title = '5S zone';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Zona')
                ->required(),

            TextInput::make('sort_order')
                ->label('Redoslijed')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Zona')
                    ->searchable(),

                TextColumn::make('total_points')
                    ->label('Bodovi')
                    ->formatStateUsing(fn ($state) => filled($state) ? $state : '-'),

                TextColumn::make('max_points')
                    ->label('Max')
                    ->formatStateUsing(fn ($state) => filled($state) ? $state : '-'),

                TextColumn::make('percentage')
                    ->label('Rezultat')
                    ->badge()
                    ->formatStateUsing(fn ($state) => filled($state) ? $state . '%' : '-'),
            ])
            ->headerActions([
                CreateAction::make()->label('Dodaj zonu'),
            ])
            ->actions([
                Action::make('ocijeni')
                    ->label('Ocijeni zonu')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->url(fn ($record) => EditInspectionZone::getUrl(['record' => $record])),

                EditAction::make()->label('Uredi zonu'),
                DeleteAction::make()->label('Obriši zonu'),
            ]);
    }
}