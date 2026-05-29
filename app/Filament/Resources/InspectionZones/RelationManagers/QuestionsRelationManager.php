<?php

namespace App\Filament\Resources\InspectionZones\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Alignment;
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
                return $query
                    ->with('answer')
                    ->orderByRaw("
                        FIELD(section,
                            'Sortiranje',
                            'Slaganje',
                            'Sjaj',
                            'Standardiziranje',
                            'Samoodržavanje'
                        )
                    ")
                    ->orderBy('id');
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
                    })
                    ->grow(false)
                    ->width('170px'),

                Tables\Columns\TextColumn::make('question')
                    ->label('Pitanje')
                    ->wrap()
                    ->grow(true)
                    ->extraAttributes([
                        'style' => 'white-space: normal; line-height: 1.5; font-size: 15px; min-width: 620px; font-weight: 600;',
                    ]),

                Tables\Columns\TextColumn::make('answer.score')
                    ->label('Ocjena')
                    ->alignment(Alignment::Center)
                    ->html()
                    ->state(function ($record) {
                        $score = $record->answer?->score;

                        $classes = match (true) {
                            $score === null => 'background:#6b7280;color:#ffffff;',
                            (int) $score === 0 => 'background:#991b1b;color:#ffffff;',
                            (int) $score === 1 => 'background:#dc2626;color:#ffffff;',
                            (int) $score === 2 => 'background:#f59e0b;color:#111827;',
                            (int) $score === 3 => 'background:#fde047;color:#111827;',
                            (int) $score === 4 => 'background:#84cc16;color:#111827;',
                            (int) $score === 5 => 'background:#16a34a;color:#ffffff;',
                            default => 'background:#6b7280;color:#ffffff;',
                        };

                        return '<div style="
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            min-width:52px;
                            height:42px;
                            padding:0 14px;
                            border-radius:12px;
                            font-weight:900;
                            font-size:20px;
                            line-height:1;
                            ' . $classes . '
                        ">' . e(filled($score) ? (string) $score : '-') . '</div>';
                    })
                    ->grow(false)
                    ->width('110px'),
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
            ->striped()
            ->actions([
                EditAction::make()
                ->label('Uredi pitanje')
                ->modalHeading('Uredi pitanje zone')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->form([
                        Select::make('section')
                            ->label('Sekcija')
                            ->options([
                                'Sortiranje' => '1 - SORTIRANJE',
                                'Slaganje' => '2 - SLAGANJE',
                                'Sjaj' => '3 - SJAJ',
                                'Standardiziranje' => '4 - STANDARDIZIRANJE',
                                'Samoodržavanje' => '5 - SAMOODRŽAVANJE',
                            ])
                            ->required(),

                        Textarea::make('question')
                            ->label('Pitanje')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
            ])
            ->headerActions([])
            ->paginated(false);
    }
}