<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeAlcoholTestsRelationManager extends RelationManager
{
    protected static string $relationship = 'alcoholTests';

    protected static ?string $title = 'Alkotestiranja';

    protected static ?string $modelLabel = 'alkotestiranje';

    protected static ?string $pluralModelLabel = 'alkotestiranja';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Hidden::make('user_id')
                ->default(fn () => auth()->user()?->ownerId())
                ->dehydrated(),

            DatePicker::make('test_date')
                ->label('Datum kontrole')
                ->required()
                ->displayFormat('d.m.Y.')
                ->weekStartsOnMonday()
                ->timezone('Europe/Zagreb'),

            TextInput::make('result')
                ->label('Rezultat')
                ->placeholder('npr. 0,0')
                ->maxLength(50),

            TextInput::make('tested_by')
                ->label('Kontrolu proveo')
                ->maxLength(255),

            Textarea::make('note')
                ->label('Napomena')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('test_date', 'desc')
            ->columns([
                TextColumn::make('test_date')
                    ->label('Datum kontrole')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('result')
                    ->label('Rezultat')
                    ->badge()
                    ->color(fn ($state) => filled($state) && trim((string) $state) !== '0,0' && trim((string) $state) !== '0.0'
                        ? 'danger'
                        : 'success')
                    ->alignment(Alignment::Center),

                TextColumn::make('tested_by')
                    ->label('Kontrolu proveo')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('note')
                    ->label('Napomena')
                    ->wrap()
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Dodaj alkotestiranje'),
            ])
            ->actions([
                EditAction::make()
                    ->label('Uredi'),

                DeleteAction::make()
                    ->label('Obriši')
                    ->requiresConfirmation(),
            ]);
    }
}
