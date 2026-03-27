<?php

namespace App\Filament\Resources\InspectionZones\RelationManagers;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Pitanja zone';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('section')
                ->label('Sekcija')
                ->options([
                    'Sort' => 'Sort',
                    'Set in order' => 'Set in order',
                    'Shine' => 'Shine',
                    'Standardize' => 'Standardize',
                    'Sustain' => 'Sustain',
                ])
                ->required(),

            Textarea::make('question')
                ->label('Pitanje')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('section')->label('Sekcija')->badge(),
                Tables\Columns\TextColumn::make('question')->label('Pitanje')->wrap(),
            ])
            ->headerActions([
                CreateAction::make()->label('Dodaj pitanje')
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}