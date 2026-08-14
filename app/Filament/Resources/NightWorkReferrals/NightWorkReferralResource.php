<?php

namespace App\Filament\Resources\NightWorkReferrals;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\NightWorkReferrals\Pages;
use App\Models\Employee;
use App\Models\NightWorkReferral;
use App\Services\FormVersionService;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class NightWorkReferralResource extends BaseResource
{
    protected static ?string $model = NightWorkReferral::class;

    protected static bool $usesSoftDeletes = true;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::DocumentText;

    protected static string|UnitEnum|null $navigationGroup =
        'Zaposlenici';

    protected static ?string $navigationLabel =
        'NR-1 Uputnice';

    protected static ?string $pluralModelLabel =
        'NR-1 Uputnice';

    protected static ?string $modelLabel =
        'NR-1 Uputnica';

    protected static ?int $navigationSort = 3;

    protected static function getModuleKey(): ?string
    {
        return 'medical_referrals_nr1';
    }

    protected static function ownerId(): ?int
    {
        return Auth::user()?->ownerId();
    }

    protected static function isSuperAdmin(): bool
    {
        return Auth::user()?->isSuperAdmin() === true;
    }

    /**
     * NR-1 je organizacijski poslovni zapis.
     *
     * Superadmin smije pregledavati zapise svih organizacija,
     * ali ne kreira niti mijenja poslovne NR-1 zapise.
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

    /**
     * Zaposlenici koji se smiju ponuditi u NR-1 formi.
     * Organizacijski korisnik vidi samo zaposlenike
     * svoje organizacije.
     */
    protected static function getEmployeeOptions(
        ?NightWorkReferral $record = null
    ): array {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        /*
        * Kod superadmin uređivanja zaposlenike
        * ograničavamo na organizaciju postojećeg
        * NR-1 zapisa.
        */
        if ($user->isSuperAdmin()) {
            $ownerId = (int) ($record?->user_id ?? 0);
        } else {
            $ownerId = (int) $user->ownerId();
        }

        if ($ownerId <= 0) {
            return [];
        }

        return Employee::query()
            ->where('user_id', $ownerId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Dodatna serverska provjera zaposlenika.
     */
    protected static function getAllowedEmployee(
        int|string|null $employeeId,
        ?NightWorkReferral $record = null
    ): ?Employee {
        if (! $employeeId) {
            return null;
        }

        $user = Auth::user();

        if (! $user) {
            return null;
        }

        /*
        * Superadmin kod uređivanja smije birati
        * samo zaposlenike organizacije kojoj
        * postojeća NR-1 uputnica pripada.
        */
        if ($user->isSuperAdmin()) {
            $ownerId = (int) ($record?->user_id ?? 0);
        } else {
            $ownerId = (int) $user->ownerId();
        }

        if ($ownerId <= 0) {
            return null;
        }

        return Employee::query()
            ->whereKey($employeeId)
            ->where('user_id', $ownerId)
            ->first();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(fn () => static::ownerId())
                ->dehydrated(),

            Select::make('form_version')
                ->label('Verzija NR-1 obrasca')
                ->options(
                    NightWorkReferral::formVersions()
                )
                ->default(
                    FormVersionService::currentNr1()
                )
                ->required()
                ->helperText(
                    'Verzija se sprema uz uputnicu. '
                    . 'Stare uputnice ostaju na staroj verziji obrasca.'
                ),

            Section::make(
                'Povezivanje sa zaposlenikom'
            )
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    Toggle::make('manual_entry')
                        ->label(
                            'Novi radnik (još nije u bazi)'
                        )
                        ->helperText(
                            'Ako uključiš, podatke upiši ručno.'
                        )
                        ->live(),

                    Select::make('employee_id')
                        ->label('Zaposlenik')
                        ->options(
                            fn (
                                ?NightWorkReferral $record
                            ): array =>
                                static::getEmployeeOptions(
                                    $record
                                )
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(
                            fn (Get $get): bool =>
                                ! $get('manual_entry')
                        )
                        ->hidden(
                            fn (Get $get): bool =>
                                (bool) $get(
                                    'manual_entry'
                                )
                        )
                        ->afterStateUpdated(
                            function (
                                $state,
                                Set $set,
                                Get $get,
                                ?NightWorkReferral $record
                            ): void {
                                if (
                                    $get('manual_entry')
                                    || ! $state
                                ) {
                                    return;
                                }

                                $employee =
                                    static::getAllowedEmployee(
                                        $state,
                                        $record
                                    );

                                if (! $employee) {
                                    $set(
                                        'employee_id',
                                        null
                                    );

                                    return;
                                }

                                $set(
                                    'full_name',
                                    $employee->name ?? ''
                                );

                                $set(
                                    'oib',
                                    $employee->OIB ?? ''
                                );

                                $set(
                                    'job_title',
                                    $employee->workplace ?? ''
                                );

                                $set(
                                    'education',
                                    $employee->education ?? ''
                                );

                                $set(
                                    'name_of_parents',
                                    $employee->name_of_parents
                                    ?? ''
                                );

                                $set(
                                    'place_of_birth',
                                    $employee->place_of_birth
                                    ?? ''
                                );
                            }
                        ),
                ]),

            Section::make('Podaci o zaposleniku')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make(
                        'referral_number'
                    )
                        ->label('Broj'),

                    DatePicker::make(
                        'referral_date'
                    )
                        ->label('Datum'),

                    TextInput::make(
                        'employer_name'
                    )
                        ->label(
                            'Naziv poslodavca'
                        ),

                    TextInput::make(
                        'employer_address'
                    )
                        ->label(
                            'Adresa poslodavca'
                        ),

                    TextInput::make(
                        'employer_oib'
                    )
                        ->label(
                            'OIB poslodavca'
                        ),

                    TextInput::make(
                        'full_name'
                    )
                        ->label(
                            'Ime i prezime'
                        )
                        ->required(
                            fn (Get $get): bool =>
                                (bool) $get(
                                    'manual_entry'
                                )
                        )
                        ->afterStateHydrated(
                            function (
                                Set $set,
                                $state,
                                ?NightWorkReferral $record
                            ): void {
                                if (
                                    $record?->employee
                                    && blank($state)
                                    && ! (
                                        $record
                                            ->manual_entry
                                        ?? false
                                    )
                                ) {
                                    $set(
                                        'full_name',
                                        $record
                                            ->employee
                                            ->name
                                        ?? ''
                                    );
                                }
                            }
                        ),

                    TextInput::make(
                        'name_of_parents'
                    )
                        ->label(
                            'Ime oca – majke'
                        )
                        ->afterStateHydrated(
                            function (
                                Set $set,
                                $state,
                                ?NightWorkReferral $record
                            ): void {
                                if (
                                    $record?->employee
                                    && blank($state)
                                    && ! (
                                        $record
                                            ->manual_entry
                                        ?? false
                                    )
                                ) {
                                    $set(
                                        'name_of_parents',
                                        $record
                                            ->employee
                                            ->name_of_parents
                                        ?? ''
                                    );
                                }
                            }
                        ),

                    TextInput::make(
                        'place_of_birth'
                    )
                        ->label(
                            'Datum i mjesto rođenja'
                        )
                        ->afterStateHydrated(
                            function (
                                Set $set,
                                $state,
                                ?NightWorkReferral $record
                            ): void {
                                if (
                                    $record?->employee
                                    && blank($state)
                                    && ! (
                                        $record
                                            ->manual_entry
                                        ?? false
                                    )
                                ) {
                                    $set(
                                        'place_of_birth',
                                        $record
                                            ->employee
                                            ->place_of_birth
                                        ?? ''
                                    );
                                }
                            }
                        ),

                    TextInput::make('oib')
                        ->label('OIB')
                        ->required(
                            fn (Get $get): bool =>
                                (bool) $get(
                                    'manual_entry'
                                )
                        )
                        ->afterStateHydrated(
                            function (
                                Set $set,
                                $state,
                                ?NightWorkReferral $record
                            ): void {
                                if (
                                    $record?->employee
                                    && blank($state)
                                ) {
                                    $set(
                                        'oib',
                                        $record
                                            ->employee
                                            ->OIB
                                        ?? ''
                                    );
                                }
                            }
                        ),

                    TextInput::make(
                        'job_title'
                    )
                        ->label(
                            'Noćni rad za koje se utvrđuje radna sposobnost'
                        )
                        ->afterStateHydrated(
                            function (
                                Set $set,
                                $state,
                                ?NightWorkReferral $record
                            ): void {
                                if (
                                    $record?->employee
                                    && blank($state)
                                    && ! (
                                        $record
                                            ->manual_entry
                                        ?? false
                                    )
                                ) {
                                    $set(
                                        'job_title',
                                        $record
                                            ->employee
                                            ->workplace
                                        ?? ''
                                    );
                                }
                            }
                        )
                        ->maxLength(110)
                        ->extraAttributes([
                            'maxlength' => 110,
                        ])
                        ->rule('max:110')
                        ->live(onBlur: true)
                        ->helperText(
                            fn (Get $get) =>
                                mb_strlen(
                                    (string) $get(
                                        'job_title'
                                    )
                                )
                                . '/110'
                        ),

                    TextInput::make(
                        'education'
                    )
                        ->label(
                            'Školska sprema'
                        )
                        ->afterStateHydrated(
                            function (
                                Set $set,
                                $state,
                                ?NightWorkReferral $record
                            ): void {
                                if (
                                    $record?->employee
                                    && blank($state)
                                    && ! (
                                        $record
                                            ->manual_entry
                                        ?? false
                                    )
                                ) {
                                    $set(
                                        'education',
                                        $record
                                            ->employee
                                            ->education
                                        ?? ''
                                    );
                                }
                            }
                        ),
                ]),

            Section::make(
                'Zdravstveni pregled'
            )
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Grid::make(1)
                        ->columnSpanFull()
                        ->schema([
                            CheckboxList::make(
                                'exam_type'
                            )
                                ->label(
                                    'Vrsta pregleda'
                                )
                                ->options([
                                    'prethodni'
                                        => 'Prethodni',

                                    'kontrolni'
                                        => 'Kontrolni',
                                ])
                                ->columns(2),
                        ]),

                    DatePicker::make(
                        'last_exam_date'
                    )
                        ->label(
                            'Posljednji zdravstveni pregled je učinjen'
                        ),

                    TextInput::make(
                        'last_exam_reference3'
                    )
                        ->label(
                            'S ocjenom zdravstvene sposobnosti'
                        )
                        ->columnSpanFull(),
                ]),

            Section::make(
                'Opis radnog mjesta'
            )
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    Textarea::make(
                        'short_description'
                    )
                        ->label(
                            'Kratak opis noćnog rada, poslova i trajanje noćnog rada'
                        )
                        ->rows(2)
                        ->maxLength(250)
                        ->extraAttributes([
                            'maxlength' => 250,
                        ])
                        ->rule('max:250')
                        ->live(onBlur: true)
                        ->helperText(
                            fn (Get $get) =>
                                mb_strlen(
                                    (string) $get(
                                        'short_description'
                                    )
                                )
                                . '/250'
                        ),

                    Textarea::make('tools')
                        ->label(
                            'Strojevi, alati, uređaji¹'
                        )
                        ->rows(1)
                        ->maxLength(150)
                        ->extraAttributes([
                            'maxlength' => 150,
                        ])
                        ->rule('max:150')
                        ->live(onBlur: true)
                        ->helperText(
                            fn (Get $get) =>
                                mb_strlen(
                                    (string) $get(
                                        'tools'
                                    )
                                )
                                . '/150'
                        ),

                    Textarea::make(
                        'job_tasks'
                    )
                        ->label(
                            'Predmet rada²'
                        )
                        ->rows(1)
                        ->maxLength(150)
                        ->extraAttributes([
                            'maxlength' => 150,
                        ])
                        ->rule('max:150')
                        ->live(onBlur: true)
                        ->helperText(
                            fn (Get $get) =>
                                mb_strlen(
                                    (string) $get(
                                        'job_tasks'
                                    )
                                )
                                . '/150'
                        ),
                ]),

            Section::make(
                'Radni uvjeti – lokacija, organizacija i položaj'
            )
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    CheckboxList::make(
                        'workplace_location'
                    )
                        ->label('Mjesto rada:')
                        ->options([
                            'zatvorenom'
                                => 'u zatvorenom',

                            'otvorenom'
                                => 'na otvorenom',

                            'na_visini'
                                => 'na visini',

                            'u_dubini'
                                => 'u dubini',

                            'u_vodi'
                                => 'u vodi',

                            'mokrim_uvjetima'
                                => 'u mokrom',
                        ])
                        ->columns(6),

                    CheckboxList::make(
                        'organization'
                    )
                        ->label(
                            'Organizacija rada'
                        )
                        ->options([
                            'smjena'
                                => 'u smjenama',

                            'terenski'
                                => 'na terenu',

                            'samostalni'
                                => 'radi sam',

                            'rad_s_grupom'
                                => 'radi u grupi',

                            'rad_sa_strankama'
                                => 'radi sa strankama',

                            'rad_na_traci'
                                => 'rad na traci',

                            'brzi_tempo'
                                => 'brzi tempo rada',

                            'ritam_određen'
                                => 'rad sa nametnutim ritmom',

                            'monotonija'
                                => 'monoton rad',
                        ])
                        ->columns(5),

                    CheckboxList::make(
                        'body_position'
                    )
                        ->label(
                            'Položaj tijela i aktivnosti³:'
                        )
                        ->options([
                            'stojeći'
                                => 'rad stojeći',

                            'sagibanje'
                                => 'učestalo sagibanje',

                            'podvlačenje'
                                => 'podvlačenje',

                            'sjedeći'
                                => 'rad sjedeći',

                            'zakretanje'
                                => 'zaokretanje trupa',

                            'balansiranje'
                                => 'balansiranje',

                            'u_pokretu'
                                => 'u pokretu',

                            'klečanje'
                                => 'klečanje',

                            'uspinjanje'
                                => 'uspinjanje ljestvama',

                            'kombinirano'
                                => 'kombinirano',

                            'čučanje'
                                => 'čučanje',

                            'uspinjanje_stepenicama'
                                => 'uspinjanje stepenicama',
                        ])
                        ->columns(6),

                    Grid::make(3)
                        ->schema([
                            Group::make([
                                Grid::make(2)
                                    ->schema([
                                        Checkbox::make(
                                            'lifting_enabled'
                                        )
                                            ->label(
                                                'Dizanje tereta kg'
                                            )
                                            ->live(),

                                        TextInput::make(
                                            'lifting_weight'
                                        )
                                            ->hiddenLabel()
                                            ->placeholder('')
                                            ->numeric()
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    (bool) $get(
                                                        'lifting_enabled'
                                                    )
                                            ),
                                    ]),
                            ]),

                            Group::make([
                                Grid::make(2)
                                    ->schema([
                                        Checkbox::make(
                                            'carrying_enabled'
                                        )
                                            ->label(
                                                'Prenošenje tereta kg'
                                            )
                                            ->live(),

                                        TextInput::make(
                                            'carrying_weight'
                                        )
                                            ->hiddenLabel()
                                            ->placeholder('')
                                            ->numeric()
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    (bool) $get(
                                                        'carrying_enabled'
                                                    )
                                            ),
                                    ]),
                            ]),

                            Group::make([
                                Grid::make(2)
                                    ->schema([
                                        Checkbox::make(
                                            'pushing_enabled'
                                        )
                                            ->label(
                                                'Guranje tereta kg'
                                            )
                                            ->live(),

                                        TextInput::make(
                                            'pushing_weight'
                                        )
                                            ->hiddenLabel()
                                            ->placeholder('')
                                            ->numeric()
                                            ->visible(
                                                fn (
                                                    Get $get
                                                ): bool =>
                                                    (bool) $get(
                                                        'pushing_enabled'
                                                    )
                                            ),
                                    ]),
                            ]),
                        ]),

                    CheckboxList::make(
                        'job_characteristics'
                    )
                        ->label(
                            'Pri radu je važan⁴:'
                        )
                        ->options([
                            'vid_na_daljinu'
                                => 'vid na daljinu',

                            'vid_na_blizinu'
                                => 'vid na blizinu',

                            'raspoznavanje'
                                => 'raspoznavanje boja',

                            'sluh'
                                => 'dobar sluh',

                            'govor'
                                => 'jasan govor',
                        ])
                        ->columns(5),

                    CheckboxList::make(
                        'hazards'
                    )
                        ->label('Uvjeti rada:')
                        ->options([
                            'toplina'
                                => 'visoka temperatura',

                            'vlažnost'
                                => 'visoka vlažnost',

                            'hladnoća'
                                => 'niska temperatura',

                            'buka'
                                => 'buka',

                            'vibracije'
                                => 'vibracije',

                            'ozljede'
                                => 'povećana izloženost ozljedama',

                            'tlak'
                                => 'povišeni atmosferski tlak',

                            'prašina'
                                => 'prašina',

                            'zračenja'
                                => 'ionizacijska zračenja',

                            'zračenja1'
                                => 'neionizacijska zračenja',
                        ])
                        ->columns(5),
                ]),

            Section::make(
                'Kemijske tvari i biološke štetnosti'
            )
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    Textarea::make(
                        'chemcial_substances'
                    )
                        ->label(
                            'Kemijske tvari'
                        )
                        ->rows(1)
                        ->maxLength(90)
                        ->extraAttributes([
                            'maxlength' => 90,
                        ])
                        ->rule('max:90')
                        ->live(onBlur: true)
                        ->helperText(
                            fn (Get $get) =>
                                mb_strlen(
                                    (string) $get(
                                        'chemcial_substances'
                                    )
                                )
                                . '/90'
                        ),

                    Textarea::make(
                        'biological_hazards'
                    )
                        ->label(
                            'Biološke štetnosti'
                        )
                        ->rows(1)
                        ->maxLength(90)
                        ->extraAttributes([
                            'maxlength' => 90,
                        ])
                        ->rule('max:90')
                        ->live(onBlur: true)
                        ->helperText(
                            fn (Get $get) =>
                                mb_strlen(
                                    (string) $get(
                                        'biological_hazards'
                                    )
                                )
                                . '/90'
                        ),
                ]),
        ]);
    }

    public static function table(
        Table $table
    ): Table {
        return $table
            ->paginated([
                10,
                25,
                50,
                'all',
            ])
            ->columns([
                TextColumn::make(
                    'display_name'
                )
                    ->label('Zaposlenik')
                    ->state(
                        fn (
                            NightWorkReferral $record
                        ) =>
                            $record->employee->name
                            ?? $record->full_name
                    )
                    ->searchable(
                        query: function (
                            Builder $query,
                            string $search
                        ): Builder {
                            return $query->where(
                                function (
                                    Builder $q
                                ) use ($search): void {
                                    $q->where(
                                        'full_name',
                                        'like',
                                        "%{$search}%"
                                    )
                                        ->orWhereHas(
                                            'employee',
                                            fn (
                                                Builder $employeeQuery
                                            ) =>
                                                $employeeQuery
                                                    ->where(
                                                        'name',
                                                        'like',
                                                        "%{$search}%"
                                                    )
                                        );
                                }
                            );
                        }
                    )
                    ->sortable()
                    ->weight('bold')
                    ->wrap()
                    ->toggleable(),

                static::userTableColumn()
                    ->toggleable(),

                TextColumn::make(
                    'form_version_label'
                )
                    ->label(
                        'Verzija obrasca'
                    )
                    ->alignCenter()
                    ->badge()
                    ->wrap()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make(
                    'referral_number'
                )
                    ->label(
                        'Broj uputnice'
                    )
                    ->sortable()
                    ->searchable()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make(
                    'referral_date'
                )
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make(
                    'job_title'
                )
                    ->label(
                        'Noćni rad za koji se utvrđuje zdr. sposobnost'
                    )
                    ->wrap()
                    ->limit(150)
                    ->tooltip(
                        fn (
                            NightWorkReferral $record
                        ) =>
                            $record->job_title
                    )
                    ->toggleable(),

                TextColumn::make(
                    'manual_entry'
                )
                    ->label('Unos')
                    ->badge()
                    ->alignment(
                        Alignment::Center
                    )
                    ->formatStateUsing(
                        fn ($state) =>
                            $state
                                ? 'Ručno'
                                : 'Zaposlenik'
                    )
                    ->color(
                        fn ($state) =>
                            $state
                                ? 'warning'
                                : 'success'
                    )
                    ->toggleable(),
            ])
            ->defaultSort(
                'referral_date',
                'desc'
            )
            ->recordUrl(
                fn (
                    NightWorkReferral $record
                ): string =>
                    static::getUrl(
                        'view',
                        [
                            'record' => $record,
                        ]
                    )
            )
            ->filters([
                SelectFilter::make('status')
                    ->label(
                        'Status zapisa'
                    )
                    ->placeholder(
                        'Odaberi status'
                    )
                    ->options([
                        'active'
                            => 'Aktivni zapisi',

                        'trashed'
                            => 'Deaktivirani zapisi',

                        'all'
                            => 'Svi zapisi',
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            $value =
                                $data['value']
                                ?? null;

                            return match (
                                $value
                            ) {
                                'trashed'
                                    => $query
                                        ->onlyTrashed(),

                                'all'
                                    => $query
                                        ->withTrashed(),

                                default
                                    => $query
                                        ->withoutTrashed(),
                            };
                        }
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(
                        fn (
                            NightWorkReferral $record
                        ): bool =>
                            static::canEdit($record)
                            && ! $record->trashed()
                    ),

                    DeleteAction::make()
                        ->label(
                            'Deaktiviraj'
                        )
                        ->requiresConfirmation()
                        ->visible(
                        fn (
                            NightWorkReferral $record
                        ): bool =>
                            static::canDelete($record)
                            && ! $record->trashed()
                        ),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(
                        fn (
                            NightWorkReferral $record
                        ): bool =>
                            static::canRestore($record)
                            && $record->trashed()
                        ),

                    ForceDeleteAction::make()
                        ->label(
                            'Trajno obriši'
                        )
                        ->requiresConfirmation()
                        ->visible(
                        fn (
                            NightWorkReferral $record
                        ): bool =>
                            static::canForceDelete($record)
                            && $record->trashed()
                        ),
                ])
                    ->icon(
                        Heroicon::EllipsisVertical
                    )
                    ->label(''),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label(
                        'Deaktiviraj označeno'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Deaktiviraj odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    )
                    ->modalSubmitActionLabel(
                        'Deaktiviraj'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                    fn (
                        HasTable $livewire
                    ): bool =>
                        static::canDeleteAny()
                        && ! static::isOnlyTrashed(
                            $livewire
                            )
                    ),

                RestoreBulkAction::make()
                    ->label(
                        'Vrati označeno'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Vrati odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    )
                    ->modalSubmitActionLabel(
                        'Vrati'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                    fn (
                        HasTable $livewire
                    ): bool =>
                        static::canRestoreAny()
                        && static::isOnlyTrashed(
                            $livewire
                            )
                    ),

                /**
                 * Kopiranje je poslovna CREATE akcija.
                 *
                 * Dostupno je samo organizacijskim
                 * korisnicima, ne superadminu.
                 */
                BulkAction::make(
                    'copyAndCreateNew'
                )
                    ->label(
                        'Kopiraj i napravi novi'
                    )
                    ->icon(
                        Heroicon::DocumentDuplicate
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Kopiraj NR-1 uputnicu'
                    )
                    ->modalDescription(
                        'Kopirat će se odabrana uputnica i otvoriti nova za uređivanje. '
                        . 'Broj uputnice će ostati prazan, a datum će biti današnji.'
                    )
                    ->modalSubmitActionLabel(
                        'Kopiraj i otvori'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        fn (): bool =>
                            static::canForceDeleteAny()
                    )
                    ->action(
                        function (
                            EloquentCollection $records
                        ) {
                            if (
                                static::isSuperAdmin()
                            ) {
                                abort(403);
                            }

                            $ownerId =
                                static::ownerId();

                            if (! $ownerId) {
                                abort(403);
                            }

                            if (
                                $records->count()
                                !== 1
                            ) {
                                Notification::make()
                                    ->title(
                                        'Odaberi samo jednu uputnicu'
                                    )
                                    ->body(
                                        'Za kopiranje može biti označena samo jedna NR-1 uputnica.'
                                    )
                                    ->danger()
                                    ->send();

                                return;
                            }

                            /** @var NightWorkReferral $record */
                            $record =
                                $records->first();

                            /**
                             * Dodatna tenant zaštita.
                             */
                            if (
                                (int) $record
                                    ->user_id
                                !==
                                (int) $ownerId
                            ) {
                                abort(403);
                            }

                            $newRecord =
                                $record->replicate([
                                    'referral_number',
                                    'referral_date',
                                    'created_at',
                                    'updated_at',
                                    'deleted_at',
                                ]);

                            $newRecord
                                ->referral_number =
                                null;

                            $newRecord
                                ->referral_date =
                                now()
                                    ->toDateString();

                            $newRecord->user_id =
                                $ownerId;

                            $newRecord
                                ->form_version =
                                $record
                                    ->form_version
                                ?: FormVersionService::
                                    currentNr1();

                            $newRecord->save();

                            Notification::make()
                                ->title(
                                    'NR-1 uputnica je kopirana'
                                )
                                ->success()
                                ->send();

                            return redirect(
                                static::getUrl(
                                    'edit',
                                    [
                                        'record'
                                            => $newRecord,
                                    ]
                                )
                            );
                        }
                    ),

                ForceDeleteBulkAction::make()
                    ->label(
                        'Trajno obriši označeno'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Trajno obriši odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti? '
                        . 'Ova radnja se ne može poništiti.'
                    )
                    ->modalSubmitActionLabel(
                        'Trajno obriši'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        fn (): bool =>
                            ! static::isSuperAdmin()
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListNightWorkReferrals::route(
                    '/'
                ),

            'create' =>
                Pages\CreateNightWorkReferral::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditNightWorkReferral::route(
                    '/{record}/edit'
                ),

            'view' =>
                Pages\ViewNightWorkReferral::route(
                    '/{record}'
                ),
        ];
    }

    /**
     * BaseResource već rješava:
     *
     * - superadmin vidi sve zapise
     * - organizacija vidi user_id = ownerId()
     * - SoftDeletingScope se uklanja jer je
     *   $usesSoftDeletes = true
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::
            getRecordRouteBindingEloquentQuery();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    /**
     * BaseResource već pravilno broji samo
     * zapise organizacije i izostavlja
     * deaktivirane zapise iz badgea.
     */
    public static function getNavigationBadge(): ?string
    {
        return parent::getNavigationBadge();
    }

    private static function isOnlyTrashed(
        HasTable $livewire
    ): bool {
        $state =
            $livewire->getTableFilterState(
                'status'
            );

        $value =
            data_get(
                $state,
                'value'
            );

        return $value === 'trashed';
    }
}