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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
                ->rows(3)
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
            ->modifyQueryUsing(fn ($query) => $query->with(['question', 'inspection', 'zone']))
            ->defaultSort('inspection_question_id')
            ->columns([
                TextColumn::make('question.section_label')
                    ->label('Sekcija')
                    ->badge(),

                TextColumn::make('question.question')
                    ->label('Pitanje')
                    ->wrap()
                    ->limit(80),

                Tables\Columns\ViewColumn::make('score_buttons')
                    ->label('Ocjena')
                    ->view('filament.tables.columns.score-buttons'),

                TextColumn::make('score')
                    ->label('Trenutna ocjena')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        (int) $state <= 1 => 'danger',
                        (int) $state <= 3 => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('due_date')
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
                    ->alignment(Alignment::Center),
            ])
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
            ->headerActions([]);
    }
}