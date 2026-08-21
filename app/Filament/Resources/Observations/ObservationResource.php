<?php

namespace App\Filament\Resources\Observations;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Observations\Pages;
use App\Mail\ObservationNotificationMail;
use App\Models\ActivityLog;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\Observation;
use App\Services\StorageQuotaService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\ExpiryBadge;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\MaxWidth;

class ObservationResource extends BaseResource
{
    protected static ?string $model = Observation::class;

    protected static bool $usesSoftDeletes = true;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedExclamationCircle;

    protected static ?string $navigationLabel = 'Zapažanja';
    protected static ?string $modelLabel = 'Zapažanje';
    protected static ?string $pluralModelLabel = 'Zapažanja';

    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 4;

    protected static function getModuleKey(): ?string
    {
        return 'observations';
    }

    /*
    |--------------------------------------------------------------------------
    | AUTORIZACIJA POSLOVNIH ZAPISA
    |--------------------------------------------------------------------------
    |
    | Zapažanja pripadaju organizaciji.
    |
    | Superadmin:
    | - može pregledavati sve postojeće zapise
    | - može uređivati postojeće zapise
    | - može deaktivirati, vratiti i trajno brisati
    | - ne može kreirati novo zapažanje u ime organizacije
    |
    | Organizacijski korisnici:
    | - rade samo nad zapisima svoje organizacije
    | - poštuju granularne module permissions
    |
    */

    public static function canCreate(): bool
    {
        return parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canDelete($record);
    }

    public static function canRestore(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canRestore($record);
    }

    public static function canForceDelete(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canForceDelete($record);
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
{
    return MaxWidth::Full;
}

    protected static function observationTypeOptions(): array
    {
        return [
            'Near Miss' => 'Near Miss - Skoro nezgoda',
            'Negative Observation' => 'Negativno zapažanje',
            'Positive Observation' => 'Pozitivno zapažanje',
        ];
    }

    protected static function observationTypeLabel(?string $state): ?string
    {
        return match ($state) {
            'Near Miss' => 'NM - Skoro nezgoda',
            'Negative Observation' => 'Negativno zapažanje',
            'Positive Observation' => 'Pozitivno zapažanje',
            default => $state,
        };
    }

    protected static function priorityOptions(): array
{
    return [
        'low' => 'Nisko',
        'medium' => 'Srednje',
        'high' => 'Visoko',
        'critical' => 'Kritično',
    ];
}

    protected static function priorityColor(?string $state): string
{
    return match ($state) {
        'low' => 'gray',
        'medium' => 'info',
        'high' => 'warning',
        'critical' => 'danger',
        default => 'gray',
    };
}

protected static function priorityIcon(?string $state): ?string
{
    return match ($state) {
        'low' => 'heroicon-o-minus-circle',
        'medium' => 'heroicon-o-exclamation-circle',
        'high' => 'heroicon-o-exclamation-triangle',
        'critical' => 'heroicon-o-fire',
        default => null,
    };
}

    protected static function statusOptions(): array
    {
        return [
            'Not started' => 'Nije započeto',
            'In progress' => 'U tijeku',
            'Complete' => 'Završeno',
        ];
    }

    protected static function statusColor(?string $state): string
    {
        return match ($state) {
            'Not started' => 'danger',
            'In progress' => 'warning',
            'Complete' => 'success',
            default => 'gray',
        };
    }

    protected static function potentialIncidentTypes(): array
    {
        return [
            'Kontakt s pokretnim dijelovima strojeva',
            'Utapanje ili gušenje',
            'Izloženost struji',
            'Izloženost ekstremnim temperaturama',
            'Izloženost vatri',
            'Pad s visine',
            'Pad na istoj razini',
            'Udarac pokretnim vozilom',
            'Udarac pokretnim, letećim ili padajućim predmetom',
            'Udarac u nešto nepomično',
            'Ručno rukovanje, podizanje ili nošenje',
            'Profesionalna bolest/bolest',
            'Fizički napad',
            'Padovi, spoticanje ili pokliznuće',
            'Incident s trećom stranom',
            'Zarobljenost nečim što se ruši',
            'Ostalo',
            'Porezotine, ogrebotine ili abrazije',
            'Blokirana protupožarna oprema',
            'Blokirani evakuacijski putevi',
            'Nedostatak odgovarajuće rasvjete',
            'Nedostatak čistoće',
            'Nepravilno skladištenje',
        ];
    }

    protected static function responsiblePersonOptions(): array
    {
        return Employee::query()
            ->when(
                ! auth()->user()?->isSuperAdmin(),
                fn ($query) => $query->where('user_id', auth()->user()?->ownerId())
            )
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    public static function form(Schema $schema): Schema
{
    return $schema
        ->schema([
            Hidden::make('user_id')
                ->default(fn () => static::defaultUserId())
                ->dehydrated(),

            Tabs::make('ObservationTabs')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Zapažanje')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Section::make('Osnovni podatci')
                                        ->columns(2)
                                        ->columnSpan(1)
                                        ->schema([
                                            DatePicker::make('incident_date')
                                                ->label('Datum')
                                                ->required()
                                                ->displayFormat('d.m.Y.')
                                                ->weekStartsOnMonday()
                                                ->timezone('Europe/Zagreb'),

                                            Select::make('observation_type')
                                                ->label('Vrsta zapažanja')
                                                ->options(static::observationTypeOptions())
                                                ->required(),

                                            Select::make('priority')
                                                ->label('Prioritet')
                                                ->options(static::priorityOptions())
                                                ->default('medium')
                                                ->required()
                                                ->native(false)
                                                ->helperText('Kritično označi samo za zapažanja koja zahtijevaju hitnu reakciju.')
                                                ->extraAttributes(fn ($state) => [
                                                    'style' => match ($state) {
                                                        'critical' => 'border:2px solid #ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.20); border-radius:10px;',
                                                        'high' => 'border:2px solid #f59e0b; box-shadow:0 0 0 3px rgba(245,158,11,.18); border-radius:10px;',
                                                        'medium' => 'border:2px solid #0ea5e9; box-shadow:0 0 0 3px rgba(14,165,233,.14); border-radius:10px;',
                                                        'low' => 'border:2px solid #6b7280; border-radius:10px;',
                                                        default => '',
                                                    },
                                                ]),

                                            TextInput::make('location')
                                                ->label('Lokacija')
                                                ->required()
                                                ->maxLength(255),

                                            TextInput::make('potential_incident_type')
                                                ->label('Vrsta opasnosti')
                                                ->datalist(static::potentialIncidentTypes())
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpanFull(),
                                        ]),

                                    Section::make('Odgovornost i rok')
                                        ->columns(2)
                                        ->columnSpan(1)
                                        ->schema([
                                            TextInput::make('responsible')
                                                ->label('Odgovorna osoba')
                                                ->datalist(fn () => static::responsiblePersonOptions())
                                                ->placeholder('Upiši ime')
                                                ->maxLength(255),

                                            DatePicker::make('target_date')
                                                ->label('Rok za provedbu')
                                                ->displayFormat('d.m.Y.')
                                                ->weekStartsOnMonday()
                                                ->timezone('Europe/Zagreb'),

                                            Select::make('status')
                                                ->label('Status')
                                                ->options(static::statusOptions())
                                                ->default('Not started')
                                                ->required()
                                                ->live()
                                                ->columnSpanFull(),

                                            DatePicker::make('completed_at')
                                                ->label('Datum zatvaranja')
                                                ->displayFormat('d.m.Y.')
                                                ->weekStartsOnMonday()
                                                ->timezone('Europe/Zagreb')
                                                ->native(false)
                                                ->required(fn (callable $get): bool =>
                                                    $get('status') === 'Complete'
                                                )
                                                ->visible(fn (callable $get): bool =>
                                                    $get('status') === 'Complete'
                                                )
                                                ->rules([
                                                    'nullable',
                                                    'date',
                                                    'after_or_equal:incident_date',
                                                    'before_or_equal:today',
                                                ])
                                                ->helperText(
                                                    'Upiši stvarni datum kada je radnja završena.'
                                                ),

                                            TextInput::make('closing_days')
                                                ->label('Broj dana do zatvaranja')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->visible(fn (callable $get): bool =>
                                                    $get('status') === 'Complete'
                                                )
                                                ->formatStateUsing(function ($state, ?Observation $record): string {
                                                    if (! $record?->incident_date || ! $record?->completed_at) {
                                                        return '-';
                                                    }

                                                    $days = $record->incident_date
                                                        ->copy()
                                                        ->startOfDay()
                                                        ->diffInDays(
                                                            $record->completed_at
                                                                ->copy()
                                                                ->startOfDay()
                                                        );

                                                    return match ($days) {
                                                        0 => '0 dana',
                                                        1 => '1 dan',
                                                        default => $days . ' dana',
                                                    };
                                                })
                                                ->helperText(
                                                    'Broj kalendarskih dana od datuma zapažanja do datuma zatvaranja.'
                                                ),

                                            TagsInput::make('notification_emails')
                                                ->label('E-mail primatelji')
                                                ->placeholder('Upiši e-mail i pritisni Enter')
                                                ->helperText('Možeš upisati više adresa: direktor, voditelj, odgovorna osoba...')
                                                ->columnSpanFull(),
                                        ]),
                                ]),

                            Section::make('Opis i potrebna radnja')
                                ->columns(2)
                                ->schema([
                                    Textarea::make('item')
                                        ->label('Opis zapažanja')
                                        ->required()
                                        ->rows(4)
                                        ->maxLength(2000)
                                        ->extraAttributes([
                                            'data-voice-target' => 'observation-item',
                                        ]),

                                    Textarea::make('action')
                                        ->label('Potrebna radnja')
                                        ->rows(4)
                                        ->extraAttributes([
                                            'data-voice-target' => 'observation-action',
                                        ]),

                                    View::make('filament.components.observation-voice-button')
                                        ->viewData([
                                            'target' => 'observation-item',
                                            'label' => 'Govori opis zapažanja',
                                        ]),

                                    View::make('filament.components.observation-voice-button')
                                        ->viewData([
                                            'target' => 'observation-action',
                                            'label' => 'Govori potrebnu radnju',
                                        ]),
                                ]),

                            Section::make('Slika i komentar')
                                ->columns(2)
                                ->schema([
                            FileUpload::make('camera_picture')
                                ->label('📷 Fotografiraj')
                                ->image()
                                ->disk('public')
                                ->directory('observations')
                                ->visibility('public')
                                ->preserveFilenames()
                                ->maxSize(30720)
                                ->extraInputAttributes([
                                    'accept' => 'image/*',
                                    'capture' => 'environment',
                                ])
                                ->live()
                                ->afterStateUpdated(
                                    function ($state, Set $set): void {
                                        if (filled($state)) {
                                            /*
                                            * Ako je snimljena nova fotografija,
                                            * poništavamo eventualni izbor
                                            * iz galerije.
                                            */
                                            $set('picture_path', null);
                                        }
                                    }
                                )
                                ->helperText(
                                    '📷 Odaberi fotoaparat i snimi novu fotografiju.'
                                ),

                            FileUpload::make('picture_path')
                                ->label('🖼️ Odaberi iz galerije')
                                ->image()
                                ->disk('public')
                                ->directory('observations')
                                ->visibility('public')
                                ->preserveFilenames()
                                ->openable()
                                ->downloadable()
                                ->maxSize(30720)
                                ->extraInputAttributes([
                                    'accept' => 'image/*',
                                ])
                                ->live()
                                ->afterStateUpdated(
                                    function ($state, Set $set): void {
                                        if (filled($state)) {
                                            /*
                                            * Ako je odabrana slika iz galerije,
                                            * poništavamo fotografiju iz kamere.
                                            */
                                            $set('camera_picture', null);
                                        }
                                    }
                                )
                                ->helperText(function () {
                                    $ownerId =
                                        auth()->user()?->ownerId();

                                    $text =
                                        '🖼️ Odaberi ranije spremljenu fotografiju iz galerije.';

                                    if (! $ownerId) {
                                        return $text;
                                    }

                                    return $text
                                        . ' Iskorištenost prostora organizacije: '
                                        . app(StorageQuotaService::class)
                                            ->usageText($ownerId);
                                })
                                ->rules([
                                    function () {
                                        return function (
                                            string $attribute,
                                            mixed $value,
                                            \Closure $fail
                                        ) {
                                            $ownerId =
                                                auth()->user()?->ownerId();

                                            if (! $ownerId) {
                                                return;
                                            }

                                            if (
                                                ! app(StorageQuotaService::class)
                                                    ->canUpload(
                                                        $value,
                                                        $ownerId
                                                    )
                                            ) {
                                                $fail(
                                                    'Dosegnut je maksimalni prostor za pohranu dokumenata organizacije. '
                                                    . 'Obrišite nepotrebne priloge ili kontaktirajte administratora.'
                                                );
                                            }
                                        };
                                    },
                                ]),

                                    Textarea::make('comments')
                                        ->label('Komentar')
                                        ->rows(4),
                                ]),
                        ]),
                ]),
        ])
        ->columns(1);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
    TextColumn::make('incident_date')
        ->label('Datum')
        ->date('d.m.Y.')
        ->sortable()
        ->alignment(Alignment::Center)
        ->wrap()
        ->toggleable(),

    static::userTableColumn()
        ->toggleable(),

    TextColumn::make('observation_type')
        ->label('Vrsta zapažanja')
        ->alignment(Alignment::Center)
        ->wrap()
        ->formatStateUsing(fn (?string $state) => static::observationTypeLabel($state))
        ->toggleable(),

    TextColumn::make('priority')
        ->label('Prioritet')
        ->badge()
        ->icon(fn (?string $state) => static::priorityIcon($state))
        ->alignment(Alignment::Center)
        ->color(fn (?string $state) => static::priorityColor($state))
        ->formatStateUsing(fn (?string $state) => static::priorityOptions()[$state] ?? $state)
        ->sortable()
        ->extraAttributes(fn (Observation $record) => [
            'style' => $record->priority === 'critical'
                ? 'font-weight:900; text-transform:uppercase;'
                : '',
        ])
        ->toggleable(),

    TextColumn::make('location')
        ->label('Lokacija')
        ->alignment(Alignment::Center)
        ->wrap()
        ->toggleable(),

    TextColumn::make('item')
        ->label('Opis')
        ->searchable()
        ->html()
        ->formatStateUsing(function (?string $state) {
            $text = trim((string) $state);

            $text = mb_strlen($text) > 55
                ? mb_substr($text, 0, 55) . '...'
                : $text;

            return nl2br(e(wordwrap($text, 18, "\n", true)));
        })
        ->tooltip(fn ($record) => $record->item)
        ->alignment(Alignment::Center)
        ->toggleable(),

    TextColumn::make('potential_incident_type')
        ->label('Vrsta opasnosti')
        ->alignment(Alignment::Center)
        ->wrap()
        ->toggleable(),

    ImageColumn::make('picture_path')
        ->label('Slika')
        ->disk('public')
        ->visibility('public')
        ->alignment(Alignment::Center)
        ->height(60)
        ->width(90)
        ->extraImgAttributes([
            'style' => '
                width: 90px;
                height: 60px;
                object-fit: cover;
                border-radius: 8px;
            ',
        ])
        ->getStateUsing(function (Observation $record): ?string {
            if (blank($record->picture_path)) {
                return null;
            }

            return Str::of($record->picture_path)
                ->replaceFirst('/storage/', '')
                ->replaceFirst('storage/', '')
                ->ltrim('/')
                ->toString();
        })
        ->url(function (Observation $record): ?string {
            if (blank($record->picture_path)) {
                return null;
            }

            $path = Str::of($record->picture_path)
                ->replaceFirst('/storage/', '')
                ->replaceFirst('storage/', '')
                ->ltrim('/')
                ->toString();

            return Storage::disk('public')->url($path);
        })
        ->openUrlInNewTab()
        ->toggleable(),

    TextColumn::make('action')
        ->label('Potrebna radnja')
        ->searchable()
        ->html()
        ->formatStateUsing(function (?string $state) {
            $text = trim((string) $state);

            $text = mb_strlen($text) > 75
                ? mb_substr($text, 0, 75) . '...'
                : $text;

            return nl2br(e(wordwrap($text, 22, "\n", true)));
        })
        ->tooltip(fn ($record) => $record->action)
        ->toggleable(),

    TextColumn::make('responsible')
        ->label('Odgovorna osoba')
        ->alignment(Alignment::Center)
        ->wrap()
        ->toggleable(),

    TextColumn::make('target_date')
        ->label('Rok za provedbu')
        ->alignment(Alignment::Center)
        ->sortable()
        ->date('d.m.Y.')
        ->badge()
        ->color(fn (Observation $record) =>
            $record->status === 'Complete'
                ? 'success'
                : ExpiryBadge::color($record->target_date)
        )
        ->icon(fn (Observation $record) =>
            $record->status === 'Complete'
                ? 'heroicon-o-check-circle'
                : ExpiryBadge::icon($record->target_date)
        )
        ->iconPosition('before')
        ->tooltip(fn (Observation $record) =>
            $record->status === 'Complete'
                ? 'Zapažanje je završeno'
                : ExpiryBadge::tooltip($record->target_date)
        )
        ->toggleable(),

    TextColumn::make('status')
        ->label('Status')
        ->alignment(Alignment::Center)
        ->badge()
        ->color(fn (?string $state) => static::statusColor($state))
        ->formatStateUsing(fn (?string $state) => static::statusOptions()[$state] ?? $state)
        ->toggleable(),

    TextColumn::make('sent_at')
        ->label('Poslano')
        ->dateTime('d.m.Y. H:i')
        ->alignment(Alignment::Center)
        ->toggleable(isToggledHiddenByDefault: true),

    TextColumn::make('comments')
        ->label('Komentar')
        ->limit(20)
        ->wrap()
        ->toggleable(isToggledHiddenByDefault: true),
])
            ->filters([
                SelectFilter::make('record_state')
                    ->label('Status zapisa')
                    ->placeholder('Odaberi status')
                    ->options([
                        'active'  => 'Aktivni zapisi',
                        'trashed' => 'Deaktivirani zapisi',
                        'all'     => 'Svi zapisi',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'trashed' => $query->onlyTrashed(),
                            'all'     => $query->withTrashed(),
                            default   => $query->withoutTrashed(),
                        };
                    }),

                    SelectFilter::make('responsible')
                    ->label('Odgovorna osoba')
                    ->placeholder('Sve odgovorne osobe')
                    ->options(function (): array {
                        return static::getEloquentQuery()
                            ->whereNotNull('responsible')
                            ->where('responsible', '<>', '')
                            ->distinct()
                            ->orderBy('responsible')
                            ->pluck('responsible', 'responsible')
                            ->toArray();
                    })
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        $responsible = $data['value'] ?? null;

                        if (filled($responsible)) {
                            $query->where('responsible', $responsible);
                        }

                        return $query;
                    }),

                SelectFilter::make('status_action')
                    ->label('Zahtijeva radnju')
                    ->placeholder('Sve')
                    ->options([
                        'Not started' => 'Nije započeto',
                        'In progress' => 'U tijeku',
                        'Complete' => 'Završeno',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        if (filled($status)) {
                            $query->where('status', $status);
                        }

                        return $query;
                    }),

                    SelectFilter::make('action_deadline')
                    ->label('Rok radnje')
                    ->placeholder('Svi rokovi')
                    ->options([
                        'expired' => 'Isteklo',
                        'expiring' => 'Ističe u sljedećih 30 dana',
                        'today' => 'Ističe danas',
                        'without_deadline' => 'Bez upisanog roka',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'expired' => $query
                                ->whereIn('status', ['Not started', 'In progress'])
                                ->whereNotNull('target_date')
                                ->whereDate('target_date', '<', Carbon::today()),

                            'expiring' => $query
                                ->whereIn('status', ['Not started', 'In progress'])
                                ->whereNotNull('target_date')
                                ->whereDate('target_date', '>=', Carbon::today())
                                ->whereDate(
                                    'target_date',
                                    '<=',
                                    Carbon::today()->addDays(30)
                                ),

                            'today' => $query
                                ->whereIn('status', ['Not started', 'In progress'])
                                ->whereDate('target_date', Carbon::today()),

                            'without_deadline' => $query
                                ->whereIn('status', ['Not started', 'In progress'])
                                ->whereNull('target_date'),

                            default => $query,
                        };
                    }),

                SelectFilter::make('observation_type')
                    ->label('Vrsta zapažanja')
                    ->placeholder('Sve')
                    ->options(static::observationTypeOptions())
                    ->query(fn (Builder $query, array $data) =>
                        filled($data['value'] ?? null)
                            ? $query->where('observation_type', $data['value'])
                            : $query
                    ),

                SelectFilter::make('priority')
                    ->label('Prioritet')
                    ->placeholder('Svi prioriteti')
                    ->options(static::priorityOptions()),

                SelectFilter::make('year')
                    ->label('Godina nastanka')
                    ->placeholder('Sve godine')
                    ->options(fn () => static::getYearOptions())
                    ->query(function (Builder $query, array $data) {
                        $year = $data['value'] ?? null;

                        if (filled($year)) {
                            $query->whereYear('incident_date', (int) $year);
                        }

                        return $query;
                    }),
            ])
            ->paginated([10, 25, 50, 100, 'all'])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),

                    Action::make('editObservation')
                        ->label('Uredi')
                        ->icon(Heroicon::PencilSquare)
                        ->visible(
                            fn (Observation $record): bool =>
                                ! $record->trashed()
                                && static::canEdit($record)
                        )
                        ->action(function (Observation $record) {
                            if (
                                ! static::isSuperAdmin()
                                && ! static::allowsModulePermission('update')
                            ) {
                                return;
                            }

                            return redirect(
                                static::getUrl('edit', [
                                    'record' => $record,
                                ])
                            );
                        }),

                    Action::make('send_observation')
                        ->label('Pošalji zapažanje / podsjetnik')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->modalHeading('Pošalji obavijest odgovornoj osobi')
                        ->modalDescription(
                            'Upiši e-mail odgovorne osobe ili druge osobe koju želiš obavijestiti o zapažanju, potrebnoj radnji i roku za provedbu.'
                        )
                        ->modalSubmitActionLabel('Pošalji e-mail')
                        ->modalCancelActionLabel('Odustani')
                        ->visible(
                            fn (Observation $record): bool =>
                                ! $record->trashed()
                                && static::canEdit($record)
                        )
                        ->form([
                            TagsInput::make('emails')
                                ->label('Primatelji')
                                ->placeholder('Upiši e-mail i pritisni Enter')
                                ->helperText('Možeš upisati jednu ili više e-mail adresa.')
                                ->default(fn (Observation $record): array =>
                                    is_array($record->notification_emails)
                                        ? $record->notification_emails
                                        : []
                                )
                                ->required(),
                        ])
                        ->action(function (Observation $record, array $data): void {

                            if (
                                ! static::isSuperAdmin()
                                && ! static::allowsModulePermission('update')
                            ) {
                                return;
                            }
                            $emails = collect($data['emails'] ?? [])
                                ->map(fn ($email): string => trim((string) $email))
                                ->filter(fn ($email): bool =>
                                    filled($email)
                                    && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
                                )
                                ->unique()
                                ->values()
                                ->all();

                            if (empty($emails)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Zapažanje nije poslano')
                                    ->body('Upišite barem jednu ispravnu e-mail adresu.')
                                    ->warning()
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            try {
                                foreach ($emails as $email) {
                                    Mail::to($email)->send(
                                        new ObservationNotificationMail($record)
                                    );
                                }

                                $record->updateQuietly([
                                    'notification_emails' => $emails,
                                    'sent_at' => now(),
                                ]);

                                $user = Auth::user();

                                ActivityLog::create([
                                    'user_id' => $user?->id,
                                    'owner_id' => $user?->ownerId(),
                                    'module' => 'Zapažanja',
                                    'action' => 'sent',
                                    'record_type' => $record::class,
                                    'record_id' => $record->getKey(),
                                    'title' => 'Zapažanje poslano e-mailom',
                                    'description' =>
                                        'Zapažanje na lokaciji „'
                                        . ($record->location ?: 'Nije navedena')
                                        . '” poslano je na: '
                                        . implode(', ', $emails),
                                    'url' => ObservationResource::getUrl('view', [
                                        'record' => $record,
                                    ]),
                                    'ip_address' => request()->ip(),
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Zapažanje je poslano')
                                    ->body(
                                        count($emails) === 1
                                            ? 'Poruka je poslana na jednu e-mail adresu.'
                                            : 'Poruka je poslana na ' . count($emails) . ' e-mail adrese.'
                                    )
                                    ->success()
                                    ->send();
                            } catch (\Throwable $exception) {
                                report($exception);

                                \Filament\Notifications\Notification::make()
                                    ->title('Slanje nije uspjelo')
                                    ->body('Došlo je do pogreške pri slanju e-maila. Pokušajte ponovno.')
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),
                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->before(
                            static::beforeModulePermission('delete')
                        )
                        ->visible(
                            fn (Observation $record): bool =>
                                ! $record->trashed()
                                && static::canDelete($record)
                        ),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->before(
                            static::beforeModulePermission('delete')
                        )
                        ->visible(
                            fn (Observation $record): bool =>
                                $record->trashed()
                                && static::canRestore($record)
                        ),
                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->before(
                            static::beforeModulePermission('delete')
                        )
                        ->visible(
                            fn (Observation $record): bool =>
                                $record->trashed()
                                && static::canForceDelete($record)
                        ),
                ])
                    ->icon(Heroicon::EllipsisVertical)
                    ->label(''),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
                    ->requiresConfirmation()
                    ->before(
                        static::beforeModulePermission('delete')
                    )
                    ->modalHeading('Deaktiviraj odabrana zapažanja')
                    ->modalDescription(
                        'Jesi li siguran/a da želiš deaktivirati odabrana zapažanja?'
                    )
                    ->modalSubmitActionLabel('Deaktiviraj')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(
                        fn (HasTable $livewire): bool =>
                            ! self::isOnlyTrashed($livewire)
                            && static::canDeleteAny()
                    )
                    ->deselectRecordsAfterCompletion(),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->requiresConfirmation()
                    ->before(
                        static::beforeModulePermission('delete')
                    )
                    ->modalHeading('Vrati odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
                    ->modalSubmitActionLabel('Vrati')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(
                        fn (HasTable $livewire): bool =>
                            self::isOnlyTrashed($livewire)
                            && static::canRestoreAny()
                    ),

                ForceDeleteBulkAction::make()
                    ->label('Trajno obriši označeno')
                    ->requiresConfirmation()
                    ->before(
                        static::beforeModulePermission('delete')
                    )
                    ->modalHeading('Trajno obriši odabrana zapažanja')
                    ->modalDescription(
                        'Jesi li siguran/a da želiš trajno obrisati odabrana zapažanja? Ova radnja se ne može poništiti.'
                    )
                    ->modalSubmitActionLabel('Trajno obriši')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(
                        fn (HasTable $livewire): bool =>
                            static::canForceDeleteAny()
                    )
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('incident_date', 'desc');
    }

    private static function isOnlyTrashed(HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('record_state');
        $value = data_get($state, 'value');

        return $value === 'trashed';
    }

    protected static function getYearOptions(): array
    {
        return static::getEloquentQuery()
            ->selectRaw('YEAR(incident_date) as year')
            ->whereNotNull('incident_date')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year', 'year')
            ->toArray();
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\Observations\Widgets\ObservationStatsTopRow::class,
            \App\Filament\Resources\Observations\Widgets\ObservationStatsBottomRow::class,
            \App\Filament\Resources\Observations\Widgets\ObservationMonthlySummary::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListObservations::route('/'),
            'create' => Pages\CreateObservation::route('/create'),
            'reports' => Pages\ObservationReports::route('/reports'),
            'edit' => Pages\EditObservation::route('/{record}/edit'),
            'view' => Pages\ViewObservation::route('/{record}'),
        ];
    }
}
