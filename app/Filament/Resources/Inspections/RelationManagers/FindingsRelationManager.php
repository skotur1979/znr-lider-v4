<?php

namespace App\Filament\Resources\Inspections\RelationManagers;

use App\Filament\Resources\Observations\ObservationResource;
use App\Models\Employee;
use App\Models\InspectionFinding;
use App\Filament\Resources\Inspections\InspectionResource;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Filament\Tables\Filters\SelectFilter;
use App\Exports\InspectionFindingsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\StorageQuotaService;

class FindingsRelationManager extends RelationManager
{
    protected static string $relationship = 'findings';

    protected static ?string $title = 'Nalazi nadzora';

    protected function getEmployeeSuggestions(): array
{
    $inspection = $this->getOwnerRecord();

    $ownerId = $inspection?->user_id;

    if (! $ownerId) {
        return [];
    }

    return Employee::query()
        ->where('user_id', $ownerId)
        ->whereNotNull('name')
        ->where('name', '<>', '')
        ->orderBy('name')
        ->pluck('name')
        ->unique()
        ->values()
        ->all();
}

    protected function mutateFindingData(array $data): array
{
    $data['title'] = mb_substr(trim((string) ($data['description'] ?? '')), 0, 255);

    $categorySelect = $data['category_select'] ?? null;
    $categoryCustom = $data['category_custom'] ?? null;
    $currentCategory = $data['category'] ?? null;

    if ($categorySelect === '__custom__') {
        $data['category'] = filled($categoryCustom)
            ? trim((string) $categoryCustom)
            : (filled($currentCategory) ? trim((string) $currentCategory) : 'Ostalo');
    } elseif (filled($categorySelect)) {
        $data['category'] = $categorySelect;
    } elseif (filled($currentCategory)) {
        $data['category'] = trim((string) $currentCategory);
    } else {
        $data['category'] = 'Ostalo';
    }

    $statusSelect = $data['finding_status_select'] ?? null;
    $statusCustom = $data['finding_status_custom'] ?? null;
    $currentStatus = $data['finding_status'] ?? null;

    if ($statusSelect === '__custom__') {
        $data['finding_status'] = filled($statusCustom)
            ? trim((string) $statusCustom)
            : (filled($currentStatus) ? trim((string) $currentStatus) : 'recommendation');
    } elseif (filled($statusSelect)) {
        $data['finding_status'] = $statusSelect;
    } elseif (filled($currentStatus)) {
        $data['finding_status'] = trim((string) $currentStatus);
    } else {
        $data['finding_status'] = 'recommendation';
    }

    unset(
        $data['category_select'],
        $data['category_custom'],
        $data['finding_status_select'],
        $data['finding_status_custom']
    );

    return $data;
}

    protected function getObservationCreateUrl(InspectionFinding $record): string
    {
        $inspection = $record->inspection;

        return ObservationResource::getUrl('create', [
            'inspection_finding_id' => $record->id,
            'user_id' => $inspection->user_id
                ?? auth()->user()?->ownerId(),
            'incident_date' => optional($inspection->performed_at)?->format('Y-m-d'),
            'observation_type' => 'Negative Observation',
            'location' => $inspection->location ?? '',
            'item' => $record->category ?: 'Nalaz iz nadzora',
            'potential_incident_type' => $record->category ?? 'Ostalo',
            'picture_path' => $record->photo_path ?? '',
            'action' => $record->description ?: 'Nalaz iz nadzora',
            'responsible' => $record->responsible_person ?? '',
            'target_date' => optional($record->due_date)?->format('Y-m-d'),
            'status' => 'Not started',
            'comments' => 'Kreirano iz nadzora ' . ($inspection->number ?? ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Hidden::make('title')
                ->dehydrated(true)
                ->default(''),

            Select::make('category_select')
                ->label('Područje')
                ->options([
                    'OZO' => 'OZO',
                    'Radna oprema' => 'Radna oprema',
                    'Vatrogasni aparati' => 'Vatrogasni aparati',
                    'Prva pomoć' => 'Prva pomoć',
                    'Radno mjesto' => 'Radno mjesto',
                    'Vanjski izvođači' => 'Vanjski izvođači',
                    'Dokumentacija' => 'Dokumentacija',
                    'Kemikalije' => 'Kemikalije',
                    'Ostalo' => 'Ostalo',
                    '__custom__' => 'Ručno upiši...',
                ])
                ->searchable()
                ->live()
                ->dehydrated(true)
                ->afterStateHydrated(function (Select $component, ?InspectionFinding $record) {
                    $known = [
                        'OZO',
                        'Radna oprema',
                        'Vatrogasni aparati',
                        'Prva pomoć',
                        'Radno mjesto',
                        'Vanjski izvođači',
                        'Dokumentacija',
                        'Kemikalije',
                        'Ostalo',
                    ];

                    $value = $record?->category;

                    if (blank($value)) {
                        $component->state(null);
                        return;
                    }

                    $component->state(in_array($value, $known, true) ? $value : '__custom__');
                })
                ->afterStateUpdated(function (callable $set, $state) {
                    if ($state !== '__custom__') {
                        $set('category', $state);
                        $set('category_custom', null);
                    } else {
                        $set('category', null);
                    }
                })
                ->columnSpan(1),

            TextInput::make('category_custom')
                ->label('Ručno upiši područje')
                ->visible(fn (callable $get) => $get('category_select') === '__custom__')
                ->live(onBlur: true)
                ->dehydrated(true)
                ->afterStateHydrated(function (TextInput $component, ?InspectionFinding $record) {
                    $known = [
                        'OZO',
                        'Radna oprema',
                        'Vatrogasni aparati',
                        'Prva pomoć',
                        'Radno mjesto',
                        'Vanjski izvođači',
                        'Dokumentacija',
                        'Kemikalije',
                        'Ostalo',
                    ];

                    $value = $record?->category;

                    if (filled($value) && ! in_array($value, $known, true)) {
                        $component->state($value);
                    }
                })
                ->afterStateUpdated(fn (callable $set, ?string $state) => $set('category', filled($state) ? trim($state) : null))
                ->columnSpan(1),

            Hidden::make('category')
                ->dehydrated(true),

            Select::make('finding_status_select')
                ->label('Vrsta nalaza')
                ->options([
                    'ok' => 'Uredno',
                    'recommendation' => 'Preporuka',
                    'noncompliance' => 'Nepravilnost',
                    'critical' => 'Kritična nepravilnost',
                    '__custom__' => 'Ručno upiši...',
                ])
                ->default('recommendation')
                ->searchable()
                ->live()
                ->dehydrated(true)
                ->afterStateHydrated(function (Select $component, ?InspectionFinding $record) {
                    $known = ['ok', 'recommendation', 'noncompliance', 'critical'];
                    $value = $record?->finding_status;

                    if (blank($value)) {
                        $component->state('ok');
                        return;
                    }

                    $component->state(in_array($value, $known, true) ? $value : '__custom__');
                })
                ->afterStateUpdated(function (callable $set, $state) {
                    if ($state !== '__custom__') {
                        $set('finding_status', $state);
                        $set('finding_status_custom', null);
                    } else {
                        $set('finding_status', null);
                    }
                })
                ->columnSpan(1),

            TextInput::make('finding_status_custom')
                ->label('Ručno upiši vrstu nalaza')
                ->visible(fn (callable $get) => $get('finding_status_select') === '__custom__')
                ->live(onBlur: true)
                ->dehydrated(true)
                ->afterStateHydrated(function (TextInput $component, ?InspectionFinding $record) {
                    $known = ['ok', 'recommendation', 'noncompliance', 'critical'];
                    $value = $record?->finding_status;

                    if (filled($value) && ! in_array($value, $known, true)) {
                        $component->state($value);
                    }
                })
                ->afterStateUpdated(fn (callable $set, ?string $state) => $set('finding_status', filled($state) ? trim($state) : null))
                ->columnSpan(1),

            Hidden::make('finding_status')
                ->default('recommendation')
                ->dehydrated(true),

            Textarea::make('description')
                ->label('Što je uočeno / pronađeno')
                ->rows(4)
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function (callable $set, ?string $state) {
                    $set('title', mb_substr(trim((string) $state), 0, 255));
                })
                ->columnSpanFull(),

            Select::make('workflow_status')
                ->label('Status postupanja')
                ->options([
                    'open' => 'Nije započeto',
                    'in_progress' => 'U tijeku',
                    'closed' => 'Zatvoreno',
                    'resolved_no_action' => 'Riješeno bez akcija',
                    'converted_to_observation' => 'Pretvoreno u zapažanje',
                    'rejected' => 'Odbačeno',
                ])
                ->default('open')
                ->required()
                ->columnSpan(1),

            Select::make('action_required')
                ->label('Treba akcija')
                ->options([
                    0 => 'Ne',
                    1 => 'Da',
                ])
                ->default(0)
                ->required()
                ->columnSpan(1),

            TextInput::make('responsible_person')
                ->label('Odgovorna osoba / zaduženje')
                ->datalist($this->getEmployeeSuggestions())
                ->placeholder('Odaberi iz prijedloga ili ručno upiši')
                ->maxLength(255)
                ->columnSpan(1),

            DatePicker::make('due_date')
                ->label('Rok za provedbu')
                ->displayFormat('d.m.Y.')
                ->columnSpan(1),

            FileUpload::make('photo_path')
                ->label('Slika')
                ->image()
                ->disk('public')
                ->directory('inspection-findings')
                ->visibility('public')
                ->acceptedFileTypes([
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                ])
                ->maxSize(30720)
                ->preserveFilenames()
                ->downloadable()
                ->openable()
                ->imageEditor()
                ->extraInputAttributes([
                    'accept' => 'image/*',
                    'capture' => 'environment',
                ])
                ->helperText(function () {
                    $ownerId = $this->getOwnerRecord()?->user_id;

                    if (! $ownerId) {
                        return 'Na mobitelu i tabletu možeš odmah slikati kamerom ili odabrati postojeću sliku.';
                    }

                    return 'Na mobitelu i tabletu možeš odmah slikati kamerom ili odabrati postojeću sliku. '
                        . 'Iskorištenost prostora organizacije: '
                        . app(StorageQuotaService::class)
                            ->usageText((int) $ownerId);
                })
                ->rules([
                    function () {
                        return function (
                            string $attribute,
                            mixed $value,
                            \Closure $fail
                        ): void {
                            $ownerId =
                                $this->getOwnerRecord()?->user_id;

                            if (! $ownerId) {
                                return;
                            }

                            if (
                                ! app(StorageQuotaService::class)
                                    ->canUpload(
                                        $value,
                                        (int) $ownerId
                                    )
                            ) {
                                $fail(
                                    'Dosegnut je maksimalni prostor za pohranu dokumenata organizacije. '
                                    . 'Obrišite nepotrebne priloge ili kontaktirajte administratora.'
                                );
                            }
                        };
                    },
                ])
                ->columnSpanFull(),
            Textarea::make('resolution_note')
                ->label('Napomena / rješenje')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->emptyStateHeading('Nema nalaza nadzora')
            ->emptyStateDescription('Stvori nalaz nadzora kako bi započeo.')
            ->columns([
    TextColumn::make('category')
        ->label('Područje')
        ->badge()
        ->alignment(Alignment::Center)
        ->searchable()
        ->wrap(),

    TextColumn::make('description')
    ->label('Što je uočeno / pronađeno')
    ->searchable()
    ->html()
    ->formatStateUsing(function (?string $state) {
        $text = trim((string) $state);
        $text = mb_strlen($text) > 90
            ? mb_substr($text, 0, 90) . '...'
            : $text;

        return nl2br(e(wordwrap($text, 30, "\n", true)));
    })
    ->tooltip(fn ($record) => $record->description),

    TextColumn::make('finding_status')
        ->label('Vrsta')
        ->badge()
        ->color(fn (?string $state) => match ($state) {
            'ok' => 'success',
            'recommendation' => 'warning',
            'noncompliance' => 'danger',
            'critical' => 'danger',
            default => 'gray',
        })
        ->formatStateUsing(fn (?string $state) => match ($state) {
            'ok' => 'Uredno',
            'recommendation' => 'Preporuka',
            'noncompliance' => 'Nepravilnost',
            'critical' => 'Kritična nepravilnost',
            default => $state ?: '-',
        })
        ->alignment(Alignment::Center),

    TextColumn::make('workflow_status')
        ->label('Status postupanja')
        ->badge()
        ->color(fn (?string $state) => match ($state) {
            'open' => 'gray',
            'in_progress' => 'warning',
            'closed' => 'success',
            'resolved_no_action' => 'success',
            'converted_to_observation' => 'info',
            'rejected' => 'danger',
            default => 'gray',
        })
        ->formatStateUsing(fn (?string $state) => match ($state) {
            'open' => 'Nije započeto',
            'in_progress' => 'U tijeku',
            'closed' => 'Zatvoreno',
            'resolved_no_action' => 'Riješeno bez akcija',
            'converted_to_observation' => 'Pretvoreno u zapažanje',
            'rejected' => 'Odbačeno',
            default => $state ?: '-',
        })
        ->alignment(Alignment::Center),

    TextColumn::make('responsible_person')
        ->label('Odgovorna osoba')
        ->searchable()
        ->wrap()
        ->placeholder('-'),

    TextColumn::make('due_date')
        ->label('Rok')
        ->date('d.m.Y.')
        ->badge()
        ->color(function ($state, InspectionFinding $record) {
            if (in_array($record->workflow_status, ['closed', 'rejected', 'resolved_no_action'], true)) {
                return 'success';
            }

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

            return null;
        })
        ->alignment(Alignment::Center),
        ])
        ->filters([
            SelectFilter::make('category')
                ->label('Područje')
                ->options(function (): array {
                    return $this->getOwnerRecord()
                        ->findings()
                        ->whereNotNull('category')
                        ->where('category', '<>', '')
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->toArray();
                })
                ->searchable(),

    SelectFilter::make('finding_status')
        ->label('Vrsta')
        ->options([
            'ok' => 'Uredno',
            'recommendation' => 'Preporuka',
            'noncompliance' => 'Nepravilnost',
            'critical' => 'Kritična nepravilnost',
        ]),
            ])
            ->headerActions([
    // postojeći Izvoz u PDF
    // postojeći Izvoz u Excel
            CreateAction::make()
                ->label('Dodaj nalaz')
                ->modalHeading('Napravi novi nalaz')
                ->modalSubmitActionLabel('Napravi')
                ->modalCancelActionLabel('Odustani')
                ->mutateDataUsing(
                    fn (array $data): array => $this->mutateFindingData($data)
                ),

            Action::make('inspectionReports')
                ->label('Izvještaji')
                ->icon('heroicon-o-chart-bar-square')
                ->color('info')
                ->url(function (): string {
                    $inspection =
                        $this->getOwnerRecord();

                    return InspectionResource::getUrl(
                        'reports',
                        [
                            'inspection' =>
                                $inspection->getKey(),
                        ]
                    );
                }),

            Action::make('export_findings_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    $inspection = $this->getOwnerRecord();

                    $findings = $this->getFilteredSortedTableQuery()
                        ->get();

                    $pdf = Pdf::loadView('pdf.inspection-findings', [
                        'inspection' => $inspection,
                        'findings' => $findings,
            ])
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => true,
                    'dpi' => 96,
                    'defaultFont' => 'DejaVu Sans',
                ]);

            return response()->streamDownload(
                fn () => print($pdf->output()),
                'nalazi-nadzora-' . now()->format('Y-m-d') . '.pdf'
            );
        }),

            Action::make('export_findings_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $findings = $this->getFilteredSortedTableQuery()
                        ->get();

                    return Excel::download(
                        new InspectionFindingsExport($findings),
                        'nalazi-nadzora-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),

        ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaz'),

                    EditAction::make()
                        ->label('Uredi')
                        ->mutateDataUsing(fn (array $data): array => $this->mutateFindingData($data)),

                    Action::make('createObservation')
                        ->label('Napravi negativno zapažanje')
                        ->icon('heroicon-o-exclamation-circle')
                        ->color('warning')
                        ->visible(fn (InspectionFinding $record) => blank($record->observation_id))
                        ->url(fn (InspectionFinding $record): string => $this->getObservationCreateUrl($record))
                        ->openUrlInNewTab(false),

                    Action::make('markResolvedNoAction')
                        ->label('Označi zatvoreno')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (InspectionFinding $record) => in_array($record->workflow_status, ['open', 'in_progress', 'rejected', 'resolved_no_action']))
                        ->requiresConfirmation()
                        ->action(function (InspectionFinding $record) {
                            $record->update([
                                'workflow_status' => 'closed',
                                'resolved_at' => now(),
                            ]);

                            ActivityLogger::status(
                                module: 'Nalazi nadzora',
                                title: 'Nalaz nadzora zatvoren',
                                description: 'Zatvoren je nalaz: ' . str($record->description)->limit(120),
                                record: $record,
                            );

                            Notification::make()
                                ->title('Nalaz je označen kao zatvoren.')
                                ->success()
                                ->send();
                        }),

                    Action::make('markInProgress')
                        ->label('U tijeku')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (InspectionFinding $record) => in_array($record->workflow_status, ['open', 'resolved_no_action', 'closed']))
                        ->action(function (InspectionFinding $record) {
                            $record->update([
                                'workflow_status' => 'in_progress',
                                'resolved_at' => null,
                            ]);

                            ActivityLogger::status(
                                module: 'Nalazi nadzora',
                                title: 'Nalaz nadzora označen kao u tijeku',
                                description: 'Nalaz je označen kao u tijeku: ' . str($record->description)->limit(120),
                                record: $record,
                            );

                            Notification::make()
                                ->title('Nalaz je označen kao u tijeku.')
                                ->success()
                                ->send();
                        }),

                    Action::make('markRejected')
                        ->label('Odbaci')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (InspectionFinding $record) => in_array($record->workflow_status, ['open', 'in_progress']))
                        ->requiresConfirmation()
                        ->action(function (InspectionFinding $record) {
                            $record->update([
                                'workflow_status' => 'rejected',
                                'resolved_at' => null,
                            ]);

                            ActivityLogger::status(
                                module: 'Nalazi nadzora',
                                title: 'Nalaz nadzora odbačen',
                                description: 'Odbačen je nalaz: ' . str($record->description)->limit(120),
                                record: $record,
                            );

                            Notification::make()
                                ->title('Nalaz je odbačen.')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()->label('Obriši'),
                ])
                    ->label('')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Izbriši odabrano')
                    ->requiresConfirmation()
                    ->modalHeading('Izbriši odabrane nalaze')
                    ->modalDescription('Jesi li siguran/a da želiš izbrisati odabrane nalaze?')
                    ->modalSubmitActionLabel('Izbriši')
                    ->modalCancelActionLabel('Odustani'),

                BulkAction::make('createObservationBulk')
                    ->label('Napravi negativno zapažanje')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Napravi negativno zapažanje')
                    ->modalDescription('Za otvaranje obrasca zapažanja označi točno jedan nalaz.')
                    ->modalSubmitActionLabel('Nastavi')
                    ->modalCancelActionLabel('Odustani')
                    ->action(function (Collection $records) {
                        if ($records->count() !== 1) {
                            Notification::make()
                                ->title('Označi točno jedan nalaz.')
                                ->warning()
                                ->send();

                            return;
                        }

                        /** @var InspectionFinding $record */
                        $record = $records->first();

                        $this->redirect($this->getObservationCreateUrl($record));
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('id', 'desc');
    }
}