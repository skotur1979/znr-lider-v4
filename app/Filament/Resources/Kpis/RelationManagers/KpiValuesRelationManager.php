<?php

namespace App\Filament\Resources\Kpis\RelationManagers;

use App\Models\Kpi;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KpiValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected static ?string $title = 'Mjesečne vrijednosti KPI-a';

    public function form(Schema $schema): Schema
    {
        /** @var Kpi|null $owner */
        $owner = $this->getOwnerRecord();

        return $schema->components([
            Select::make('month')
                ->label('Mjesec')
                ->options([
                    1 => '01',
                    2 => '02',
                    3 => '03',
                    4 => '04',
                    5 => '05',
                    6 => '06',
                    7 => '07',
                    8 => '08',
                    9 => '09',
                    10 => '10',
                    11 => '11',
                    12 => '12',
                ])
                ->required(),

            TextInput::make('year')
                ->label('Godina')
                ->numeric()
                ->required()
                ->default(now()->year),

            TextInput::make('value')
                ->label('Vrijednost')
                ->numeric()
                ->required(),

            Placeholder::make('kpi_info')
                ->label('KPI')
                ->content(fn () => $owner?->name ?? '-'),

            TextInput::make('source_label')
                ->label('Izvor podatka')
                ->maxLength(255)
                ->placeholder('Ručno, Excel, Izračun, ONTO...'),

            Textarea::make('note')
                ->label('Komentar')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->defaultSort('month', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Dodaj mjesečnu vrijednost'),
            ])
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
                    ->label('Auto')
                    ->boolean(),

                TextColumn::make('source_label')
                    ->label('Izvor')
                    ->toggleable(),

                TextColumn::make('note')
                    ->label('Komentar')
                    ->wrap()
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make()->label('Uredi'),
                DeleteAction::make()->label('Obriši'),
            ]);
    }
}