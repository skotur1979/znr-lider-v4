<?php

namespace App\Filament\Resources\InspectionZones;

use App\Filament\Resources\InspectionZones\Pages;
use App\Filament\Resources\InspectionZones\RelationManagers\AnswersRelationManager;
use App\Filament\Resources\InspectionZones\RelationManagers\QuestionsRelationManager;
use App\Models\InspectionZone;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InspectionZoneResource extends Resource
{
    protected static ?string $model = InspectionZone::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'inspection-zones';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Zona')
                ->schema([
                    TextInput::make('name')
                        ->label('Naziv zone')
                        ->required(),

                    TextInput::make('total_points')
                        ->label('Ukupno bodova')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('max_points')
                        ->label('Maksimalno bodova')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('percentage')
                        ->label('Postotak')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state) => filled($state) ? $state . '%' : '-'),

                    Textarea::make('note')
                        ->label('Napomena zone')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
{
    return $table->columns([
        TextColumn::make('name')
            ->label('Zona'),

        TextColumn::make('percentage')
            ->label('Rezultat')
            ->alignment(\Filament\Support\Enums\Alignment::Center)
            ->html()
            ->state(function ($record) {
                $percentage = (float) $record->percentage;

                $classes = match (true) {
                    $percentage < 40 => 'background:#991b1b;color:#ffffff;',
                    $percentage < 60 => 'background:#f59e0b;color:#111827;',
                    $percentage < 80 => 'background:#fde047;color:#111827;',
                    default => 'background:#16a34a;color:#ffffff;',
                };

                return '<div style="
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    min-width:80px;
                    height:40px;
                    padding:0 14px;
                    border-radius:10px;
                    font-weight:800;
                    font-size:18px;
                    line-height:1;
                    box-shadow:0 0 0 1px rgba(255,255,255,0.08) inset;
                    ' . $classes . '
                ">' . e(number_format($percentage, 0)) . '%</div>';
            }),
    ]);
}

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
            AnswersRelationManager::class,

        ];
    }

    public static function getPages(): array
{
    return [
        'index' => Pages\ListInspectionZones::route('/'),
        'view' => Pages\ViewInspectionZone::route('/{record}'),
        'edit' => Pages\EditInspectionZone::route('/{record}/edit'),
    ];
}
}