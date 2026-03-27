<?php

namespace App\Filament\Resources\InspectionZones\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Pitanja zone';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->orderByRaw("
                    FIELD(section,
                        'Sortiranje',
                        'Slaganje',
                        'Sjaj',
                        'Standardiziranje',
                        'Samoodržavanje'
                    )
                ");
            })
            ->columns([
                Tables\Columns\TextColumn::make('section')
                    ->label('Sekcija')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'Sortiranje' => '1 - SORTIRANJE',
                        'Slaganje' => '2 - SLAGANJE',
                        'Sjaj' => '3 - SJAJ',
                        'Standardiziranje' => '4 - STANDARDIZIRANJE',
                        'Samoodržavanje' => '5 - SAMOODRŽAVANJE',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Sortiranje' => 'primary',
                        'Slaganje' => 'info',
                        'Sjaj' => 'success',
                        'Standardiziranje' => 'warning',
                        'Samoodržavanje' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('question')
                    ->label('Pitanje')
                    ->wrap(),

                Tables\Columns\TextColumn::make('answer.score')
                    ->label('Ocjena')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4 => 'success',
                        $state >= 2 => 'warning',
                        $state >= 1 => 'danger',
                        default => 'gray',
                    }),
            ])
            ->groups([
    Tables\Grouping\Group::make('section')
        ->label('Sekcija')
        ->getTitleFromRecordUsing(fn ($record) => match ($record->section) {
            'Sortiranje' => '🔵 1 - SORTIRANJE',
            'Slaganje' => '🔷 2 - SLAGANJE',
            'Sjaj' => '🟢 3 - SJAJ',
            'Standardiziranje' => '🟡 4 - STANDARDIZIRANJE',
            'Samoodržavanje' => '🔴 5 - SAMOODRŽAVANJE',
            default => $record->section,
        }),
])
->defaultGroup('section')
            ->defaultSort('section')
            ->striped()
            ->paginated(false);
    }
}