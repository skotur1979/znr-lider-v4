<?php

namespace App\Filament\Resources\Inspections\RelationManagers;

use App\Filament\Resources\InspectionZones\InspectionZoneResource;
use App\Filament\Resources\Inspections\InspectionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ZonesRelationManager extends RelationManager
{
    protected static string $relationship = 'zones';

    protected static ?string $title = '5S zone';

    protected function getInspectionViewUrl(): string
    {
        $inspection = $this->getOwnerRecord();

        return InspectionResource::getUrl('view', [
            'record' => $inspection,
        ]) . '?relation=1';
    }

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
                    ->alignment(Alignment::Center)
                    ->formatStateUsing(fn ($state) => filled($state) ? $state : '-'),

                TextColumn::make('max_points')
                    ->label('Max')
                    ->alignment(Alignment::Center)
                    ->formatStateUsing(fn ($state) => filled($state) ? $state : '-'),

                TextColumn::make('percentage')
    ->label('Rezultat')
    ->alignment(Alignment::Center)
    ->html()
    ->state(function ($record) {
        $percentage = (float) ($record->percentage ?? 0);

        $styles = match (true) {
            $percentage < 40 => 'background:#991b1b;color:#ffffff;',
            $percentage < 60 => 'background:#f59e0b;color:#111827;',
            $percentage < 80 => 'background:#fde047;color:#111827;',
            default => 'background:#16a34a;color:#ffffff;',
        };

        return '<div style="
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:76px;
            height:36px;
            padding:0 12px;
            border-radius:10px;
            font-weight:800;
            font-size:16px;
            line-height:1;
            box-shadow:0 0 0 1px rgba(255,255,255,0.08) inset;
            ' . $styles . '
        ">' . e(number_format($percentage, 0)) . '%</div>';
    }),
            ])
            ->headerActions([
                CreateAction::make()->label('Dodaj zonu'),
            ])
            ->actions([
                Action::make('ocijeni')
                    ->label('Ocijeni zonu')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->url(fn ($record) => InspectionZoneResource::getUrl('edit', [
                        'record' => $record,
                        'return_url' => $this->getInspectionViewUrl(),
                    ])),

                EditAction::make()->label('Uredi zonu'),

                DeleteAction::make()->label('Obriši zonu'),
            ]);
    }
}