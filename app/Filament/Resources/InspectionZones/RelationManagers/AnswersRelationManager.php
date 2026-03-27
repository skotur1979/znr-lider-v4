<?php

namespace App\Filament\Resources\InspectionZones\RelationManagers;

use App\Filament\Resources\Observations\ObservationResource;
use App\Models\Employee;
use App\Models\InspectionZoneAnswer;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    protected static ?string $title = '5S pitanja i ocjene';

    protected $listeners = ['setScore'];

    public function setScore($data): void
    {
        $record = InspectionZoneAnswer::find($data['id'] ?? null);

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

    protected function getEmployeeSuggestions(): array
    {
        return Employee::query()
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    protected function getObservationCreateUrl($record): string
    {
        $inspection = $record->inspection;
        $zone = $record->zone;
        $question = $record->question;

        return ObservationResource::getUrl('create', [
            'inspection_finding_id' => $record->id,
            'user_id' => $inspection?->user_id ?? auth()->id(),
            'incident_date' => $inspection?->performed_at?->format('Y-m-d'),
            'observation_type' => 'Negative Observation',
            'location' => trim(($inspection?->location ?? '') . ' - ' . ($zone?->name ?? ''), ' -'),
            'item' => $question?->section_label ?? '5S',
            'potential_incident_type' => $zone?->name ?? 'Zona',
            'action' => $question?->question ?? '',
            'responsible' => $record->responsible_person ?? '',
            'target_date' => $record->due_date?->format('Y-m-d'),
            'status' => 'Not started',
            'comments' => 'Kreirano iz 5S nadzora ' . ($inspection?->number ?? ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('question.section_label')
                ->label('Sekcija')
                ->disabled()
                ->dehydrated(false),

            Textarea::make('question.question')
                ->label('Pitanje')
                ->disabled()
                ->dehydrated(false)
                ->rows(4)
                ->columnSpanFull(),

            Select::make('score')
                ->label('Ocjena (0-5)')
                ->options([
                    0 => '0 - Nije započeto',
                    1 => '1',
                    2 => '2',
                    3 => '3',
                    4 => '4',
                    5 => '5 - Najbolja praksa',
                ])
                ->required(),

            Select::make('finding_status')
                ->label('Vrsta nalaza')
                ->options([
                    'ok' => 'Uredno',
                    'recommendation' => 'Preporuka',
                    'noncompliance' => 'Nepravilnost',
                    'critical' => 'Kritična nepravilnost',
                ])
                ->default('recommendation'),

            Select::make('action_required')
                ->label('Treba akcija')
                ->options([
                    0 => 'Ne',
                    1 => 'Da',
                ])
                ->default(0),

            TextInput::make('responsible_person')
                ->label('Odgovorna osoba / zaduženje')
                ->datalist($this->getEmployeeSuggestions())
                ->placeholder('Odaberi iz prijedloga ili ručno upiši'),

            DatePicker::make('due_date')
                ->label('Rok za provedbu')
                ->displayFormat('d.m.Y.'),

            FileUpload::make('photo_path')
                ->label('Slika')
                ->image()
                ->disk('public')
                ->directory('inspection-zone-answers')
                ->acceptedFileTypes(['image/*'])
                ->imageEditor()
                ->downloadable()
                ->openable()
                ->helperText('Na mobitelu i tabletu možeš slikati ili odabrati postojeću sliku.')
                ->columnSpanFull(),

            Textarea::make('note')
                ->label('Napomena / opažanje')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query
                    ->with(['question', 'inspection', 'zone'])
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
                        'style' => 'white-space: normal; line-height: 1.5; font-size: 15px; min-width: 560px; font-weight: 600;',
                    ]),

                Tables\Columns\ViewColumn::make('score_buttons')
                    ->label('Ocjena')
                    ->view('filament.tables.columns.score-buttons')
                    ->alignment(Alignment::Center)
                    ->grow(false),

                Tables\Columns\TextColumn::make('score')
                    ->label('Trenutna ocjena')
                    ->formatStateUsing(fn ($state) => filled($state) ? (string) $state : '-')
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
                            min-width:48px;
                            height:40px;
                            padding:0 12px;
                            border-radius:10px;
                            font-weight:800;
                            font-size:20px;
                            line-height:1;
                            box-shadow:0 0 0 1px rgba(255,255,255,0.08) inset;
                            ' . $classes . '
                        ">' . e((string) $score) . '</div>';
                    })
                    ->grow(false)
                    ->width('140px'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Rok')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(function ($state) {
                        if (blank($state)) {
                            return null;
                        }

                        $date = Carbon::parse($state)->startOfDay();
                        $today = Carbon::today();

                        if ($date->lt($today)) {
                            return 'danger';
                        }

                        if ($date->lte($today->copy()->addDays(14))) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->alignment(Alignment::Center)
                    ->grow(false)
                    ->width('120px'),
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
            ->actions([
                ActionGroup::make([
                    EditAction::make()->label('Detalji / uredi'),

                    Action::make('createObservation')
                        ->label('Napravi negativno zapažanje')
                        ->icon('heroicon-o-exclamation-circle')
                        ->color('warning')
                        ->visible(fn ($record) => filled($record->inspection_id) && blank($record->observation_id))
                        ->url(fn ($record) => $this->getObservationCreateUrl($record)),
                ]),
            ])
            ->headerActions([])
            ->paginated(false);
    }
}