<?php

namespace App\Filament\Resources\InspectionZones\RelationManagers;

use App\Models\InspectionAnswer;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    protected static ?string $title = '5S pitanja i ocjene';

    protected $listeners = ['setScore'];

    public function setScore($data): void
    {
        $record = InspectionAnswer::find($data['id'] ?? null);

        if (! $record) {
            return;
        }

        $record->update([
            'score' => (int) ($data['score'] ?? 0),
        ]);

        Notification::make()
            ->title('Ocjena spremljena.')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query
                    ->with(['question'])
                    ->join('inspection_questions', 'inspection_questions.id', '=', 'inspection_answers.inspection_question_id')
                    ->orderByRaw("
                        FIELD(inspection_questions.section,
                            'Sortiranje',
                            'Slaganje',
                            'Sjaj',
                            'Standardiziranje',
                            'Samoodržavanje'
                        )
                    ")
                    ->orderBy('inspection_questions.id')
                    ->select('inspection_answers.*');
            })
            ->columns([
                Tables\Columns\TextColumn::make('question.section')
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

                Tables\Columns\TextColumn::make('question.question')
                    ->label('Pitanje')
                    ->wrap()
                    ->grow(true)
                    ->extraAttributes([
                        'style' => 'white-space: normal; line-height: 1.5; font-size: 15px; min-width: 620px; font-weight: 600;',
                    ]),

                Tables\Columns\ViewColumn::make('score_buttons')
                    ->label('Odaberi ocjenu')
                    ->view('filament.tables.columns.score-buttons')
                    ->alignment(Alignment::Center)
                    ->grow(false)
                    ->width('420px'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Trenutna')
                    ->alignment(Alignment::Center)
                    ->html()
                    ->state(function ($record) {
                        $score = $record->score;

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
                    ->width('100px'),
            ])
            ->groups([
                Tables\Grouping\Group::make('question.section')
                    ->label('Sekcija')
                    ->getTitleFromRecordUsing(fn ($record) => match ($record->question?->section) {
                        'Sortiranje' => '1 - SORTIRANJE',
                        'Slaganje' => '2 - SLAGANJE',
                        'Sjaj' => '3 - SJAJ',
                        'Standardiziranje' => '4 - STANDARDIZIRANJE',
                        'Samoodržavanje' => '5 - SAMOODRŽAVANJE',
                        default => $record->question?->section ?? '-',
                    }),
            ])
            ->defaultGroup('question.section')
            ->striped()
            ->actions([])
            ->headerActions([])
            ->paginated(false);
    }
}