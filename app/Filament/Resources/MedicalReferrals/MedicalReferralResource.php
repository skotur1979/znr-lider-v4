<?php

namespace App\Filament\Resources\MedicalReferrals;

use App\Filament\Resources\MedicalReferrals\Pages\CreateMedicalReferral;
use App\Filament\Resources\MedicalReferrals\Pages\EditMedicalReferral;
use App\Filament\Resources\MedicalReferrals\Pages\ListMedicalReferrals;
use App\Filament\Resources\MedicalReferrals\Pages\ViewMedicalReferral;
use App\Models\Employee;
use App\Models\MedicalReferral;
use BackedEnum;
use Closure;
use Filament\Actions\ActionGroup;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

class MedicalReferralResource extends Resource
{
    protected static ?string $model = MedicalReferral::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|UnitEnum|null $navigationGroup = 'Zaposlenici';
    protected static ?string $navigationLabel = 'RA-1 Uputnice';
    protected static ?string $pluralModelLabel = 'RA-1 Uputnice';
    protected static ?string $modelLabel = 'RA-1 Uputnica';
    protected static ?int $navigationSort = 2;

    private static function isAdminUser($user): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $user->id === 1) {
            return true;
        }

        try {
            if (method_exists($user, 'getRoleNames')) {
                $roles = $user->getRoleNames()->toArray();

                foreach ($roles as $role) {
                    $name = trim((string) $role);

                    if (
                        Str::contains(Str::lower($name), 'admin') ||
                        in_array(Str::lower($name), ['administrator', 'super-admin', 'super admin', 'owner', 'root'])
                    ) {
                        return true;
                    }
                }
            }

            if (method_exists($user, 'hasAnyRole')) {
                if ($user->hasAnyRole([
                    'admin', 'Admin', 'administrator', 'Administrator',
                    'super-admin', 'Super Admin', 'owner', 'Owner', 'root', 'Root',
                ])) {
                    return true;
                }
            }

            if (method_exists($user, 'hasRole')) {
                foreach (['admin', 'Admin', 'administrator', 'Administrator', 'super-admin', 'Super Admin', 'owner', 'Owner', 'root', 'Root'] as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        if (isset($user->is_admin) && (bool) $user->is_admin) {
            return true;
        }

        try {
            if (method_exists($user, 'can') && $user->can('viewAny', \App\Models\MedicalReferral::class)) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return false;
    }

    protected static function getEmployeeOptions(): array
    {
        $user = auth()->user();

        $query = Employee::query();

        if (! self::isAdminUser($user)) {
            $query->where('user_id', $user?->id);
        }

        return $query
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
           Section::make('Povezivanje sa zaposlenikom')
    ->columnSpanFull()
    ->columns(1)
    ->schema([
        Toggle::make('manual_entry')
            ->label('Novi radnik (još nije u bazi)')
            ->helperText('Ako uključiš, podatke upiši ručno')
            ->live(),

        Select::make('employee_id')
            ->label('Zaposlenik')
            ->options(fn () => self::getEmployeeOptions())
            ->searchable()
            ->preload()
            ->live()
            ->required(fn (Get $get): bool => ! $get('manual_entry'))
            ->hidden(fn (Get $get): bool => (bool) $get('manual_entry'))
            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                if ($get('manual_entry')) {
                    return;
                }

                $emp = Employee::find($state);

                if (! $emp) {
                    return;
                }

                $set('full_name', $emp->name ?? '');
                $set('oib', $emp->OIB ?? '');
                $set('job_title', $emp->job_title ?? '');
                $set('education', $emp->education ?? '');
                $set('name_of_parents', $emp->name_of_parents ?? '');
                $set('place_of_birth', $emp->place_of_birth ?? '');
            }),
    ]),

Section::make('Podaci o zaposleniku')
    ->columnSpanFull()
    ->columns(2)
    ->schema([
        TextInput::make('referral_number')
            ->label('Broj'),

        DatePicker::make('referral_date')
            ->label('Datum'),

        TextInput::make('employer_name')
            ->label('Naziv poslodavca'),

        TextInput::make('employer_address')
            ->label('Adresa poslodavca'),

        TextInput::make('employer_oib')
            ->label('OIB poslodavca'),

        TextInput::make('full_name')
            ->label('Ime i prezime')
            ->required(fn (Get $get): bool => (bool) $get('manual_entry'))
            ->afterStateHydrated(function (Set $set, $state, ?MedicalReferral $record) {
                if ($record?->employee && blank($state) && ! ($record->manual_entry ?? false)) {
                    $set('full_name', $record->employee->name ?? '');
                }
            }),

        TextInput::make('name_of_parents')
            ->label('Ime oca – majke')
            ->afterStateHydrated(function (Set $set, $state, ?MedicalReferral $record) {
                if ($record?->employee && blank($state) && ! ($record->manual_entry ?? false)) {
                    $set('name_of_parents', $record->employee->name_of_parents ?? '');
                }
            }),

        TextInput::make('place_of_birth')
            ->label('Datum i mjesto rođenja')
            ->afterStateHydrated(function (Set $set, $state, ?MedicalReferral $record) {
                if ($record?->employee && blank($state) && ! ($record->manual_entry ?? false)) {
                    $set('place_of_birth', $record->employee->place_of_birth ?? '');
                }
            }),

        TextInput::make('oib')
            ->label('OIB')
            ->required(fn (Get $get): bool => (bool) $get('manual_entry'))
            ->maxLength(11)
            ->minLength(11)
            ->afterStateHydrated(function (Set $set, $state, ?MedicalReferral $record) {
                if ($record?->employee && blank($state)) {
                    $set('oib', $record->employee->OIB ?? '');
                }
            }),

        TextInput::make('job_title')
            ->label('Zanimanje')
            ->afterStateHydrated(function (Set $set, $state, ?MedicalReferral $record) {
                if ($record?->employee && blank($state) && ! ($record->manual_entry ?? false)) {
                    $set('job_title', $record->employee->job_title ?? '');
                }
            }),

        TextInput::make('education')
            ->label('Školska sprema')
            ->afterStateHydrated(function (Set $set, $state, ?MedicalReferral $record) {
                if ($record?->employee && blank($state) && ! ($record->manual_entry ?? false)) {
                    $set('education', $record->employee->education ?? '');
                }
            }),
    ]),

            Section::make('Opis poslova i uvjeti')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('health_jobs_description')
                        ->label('Poslovi za koje se utvrđuje zdravstvena sposobnost')
                        ->maxLength(110)
                        ->extraAttributes(['maxlength' => 110])
                        ->rule('max:110')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('health_jobs_description')) . '/110'),

                    TextInput::make('law_reference')
                        ->label('Poslovi su prema članku'),

                    TextInput::make('law_reference1')
                        ->label('točka Pravilnika o poslovima s posebnim uvjetima rada')
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $s = (string) $state;
                            $s = preg_replace('/[^0-9,()]/u', '', $s);
                            $s = preg_replace('/\s*,\s*/u', ',', $s);
                            $set('law_reference1', $s);
                        })
                        ->maxLength(28)
                        ->extraAttributes(['maxlength' => 28])
                        ->rule('max:28')
                        ->rule('regex:/^[0-9(),]+$/u')
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('law_reference1')) . '/28'),

                    TextInput::make('special_conditions')
                        ->label('Poslovi prema drugim zakonima, propisima ili kolektivom')
                        ->maxLength(110)
                        ->extraAttributes(['maxlength' => 110])
                        ->rule('max:110')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('special_conditions')) . '/110'),
                ]),

            Section::make('Radni staž')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('total_years')
                        ->label('Ukupni radni staž'),

                    TextInput::make('work_years_in_job')
                        ->label('Radni staž na poslovima za koje se utvrđuje zdravstvena sposobnost'),
                ]),

            Section::make('Zdravstveni pregled')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Grid::make(1)
                        ->columnSpanFull()
                        ->schema([
                            CheckboxList::make('exam_type')
                                ->label('Vrsta pregleda')
                                ->options([
                                    'prethodni' => 'Prethodni',
                                    'periodični' => 'Periodički',
                                    'izvanredni' => 'Izvanredni',
                                ])
                                ->columns(3),
                        ]),

                    DatePicker::make('last_exam_date')
                        ->label('Posljednji zdravstveni pregled je učinjen'),

                    TextInput::make('last_exam_reference')
                        ->label('Prema članku'),

                    TextInput::make('last_exam_reference1')
                        ->label('točki Pravilnika o poslovima s posebnim uvjetima rada'),

                    TextInput::make('last_exam_reference2')
                        ->label('ili')
                        ->maxLength(170)
                        ->extraAttributes(['maxlength' => 170])
                        ->rule('max:170')
                        ->live(onBlur: true)
                        ->helperText(function (Get $get) {
                            $count = mb_strlen((string) $get('last_exam_reference2'));

                            return new HtmlString(
                                '<div class="text-xs space-y-1">'
                                . '<div>(navesti zakon, propis ili kolektivni ugovor iz članka 2. stavka 1. podstavka 2. ili 3. Pravilnika)</div>'
                                . '<div><strong>' . $count . '/170</strong></div>'
                                . '</div>'
                            );
                        }),

                    TextInput::make('last_exam_reference3')
                        ->label('sa ocjenom zdravstvene sposobnosti')
                        ->columnSpanFull(),
                ]),

            Section::make('Opis radnog mjesta')
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    Textarea::make('short_description')
                        ->label('Kratak opis poslova')
                        ->rows(2)
                        ->maxLength(190)
                        ->extraAttributes(['maxlength' => 190])
                        ->rule('max:190')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('short_description')) . '/190'),

                    Textarea::make('tools')
                        ->label('Strojevi, alati, aparati¹')
                        ->rows(1)
                        ->maxLength(95)
                        ->extraAttributes(['maxlength' => 95])
                        ->rule('max:95')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('tools')) . '/95'),

                    Textarea::make('job_tasks')
                        ->label('Predmet rada²')
                        ->rows(1)
                        ->maxLength(95)
                        ->extraAttributes(['maxlength' => 95])
                        ->rule('max:95')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('job_tasks')) . '/95'),
                ]),

            Section::make('Radni uvjeti – lokacija, organizacija i položaj')
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    CheckboxList::make('workplace_location')
                        ->label('Mjesto rada:')
                        ->options([
                            'zatvorenom' => 'u zatvorenom',
                            'otvorenom' => 'na otvorenom',
                            'na_visini' => 'na visini',
                            'u_jami' => 'u jami',
                            'u_vodi' => 'u vodi',
                            'pod_vodom' => 'pod vodom',
                            'mokrim_uvjetima' => 'u mokrom',
                        ])
                        ->columns(7),

                    CheckboxList::make('organization')
                        ->label('Organizacija')
                        ->options([
                            'smjena' => 'u smjenama',
                            'rad_na_traci' => 'radi na traci',
                            'noćni' => 'noćni rad',
                            'brzi_tempo' => 'brzi tempo rada',
                            'terenski' => 'terenski rad',
                            'ritam_određen' => 'ritam određen',
                            'samostalni' => 'radi sam',
                            'rad_sa_strankama' => 'radi sa strankama',
                            'rad_s_grupom' => 'radi s grupom',
                            'monotonija' => 'monotonija',
                        ])
                        ->columns(5),

                    CheckboxList::make('body_position')
                        ->label('Položaj tijela i aktivnosti³:')
                        ->options([
                            'stojeći' => 'rad stojeći',
                            'u_pokretu' => 'u pokretu',
                            'sagibanje' => 'učestalo sagibanje',
                            'klečanje' => 'klečanje',
                            'podvlačenje' => 'podvlačenje',
                            'uspinjanje' => 'uspinjanje ljestvama',
                            'sjedeći' => 'rad sjedeći',
                            'kombinirano' => 'kombinirano',
                            'zakretanje' => 'zakretanje trupa',
                            'čučanje' => 'čučanje',
                            'balansiranje' => 'balansiranje',
                            'uspinjanje_stepenicama' => 'uspinjanje stepenicama',
                        ])
                        ->columns(6),

                    Grid::make(3)->schema([
                        Group::make([
                            Checkbox::make('lifting_enabled')
                                ->label('Dizanje tereta kg')
                                ->live(),

                            TextInput::make('lifting_weight')
                                ->label('')
                                ->placeholder('kg')
                                ->visible(fn (Get $get): bool => (bool) $get('lifting_enabled')),
                        ]),

                        Group::make([
                            Checkbox::make('carrying_enabled')
                                ->label('Prenošenje tereta kg')
                                ->live(),

                            TextInput::make('carrying_weight')
                                ->label('')
                                ->placeholder('kg')
                                ->visible(fn (Get $get): bool => (bool) $get('carrying_enabled')),
                        ]),

                        Group::make([
                            Checkbox::make('pushing_enabled')
                                ->label('Guranje tereta kg')
                                ->live(),

                            TextInput::make('pushing_weight')
                                ->label('')
                                ->placeholder('kg')
                                ->visible(fn (Get $get): bool => (bool) $get('pushing_enabled')),
                        ]),
                    ]),

                    CheckboxList::make('job_characteristics')
                        ->label('U poslu je važan⁴:')
                        ->options([
                            'vid_na_daljinu' => 'vid na daljinu',
                            'vid_na_blizinu' => 'vid na blizinu',
                            'raspoznavanje' => 'raspoznavanje boja',
                            'sluh' => 'dobar sluh',
                            'govor' => 'jasan govor',
                        ])
                        ->columns(5),

                    CheckboxList::make('hazards')
                        ->label('Uvjeti rada:')
                        ->options([
                            'toplina' => 'visoka temperatura',
                            'vibracije' => 'vibracije poda',
                            'vlažnost' => 'visoka vlažnost',
                            'hladnoća' => 'niska temperatura',
                            'vibracije1' => 'vibracije stroja ili alata',
                            'zračenja' => 'ionizirajuća zračenja',
                            'buka' => 'buka',
                            'tlak' => 'povišeni atmosferski tlak',
                            'ozljede' => 'povećana izloženost ozljedama',
                            'zračenja1' => 'neionizirajuća zračenja',
                            'prašina' => 'prašina',
                        ])
                        ->columns(5),
                ]),

            Section::make('Kemijske tvari i biološke štetnosti')
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    Textarea::make('chemcial_substances')
                        ->label('Kemijske tvari')
                        ->rows(1)
                        ->maxLength(90)
                        ->extraAttributes(['maxlength' => 90])
                        ->rule('max:90')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('chemcial_substances')) . '/90'),

                    Textarea::make('biological_hazards')
                        ->label('Biološke štetnosti')
                        ->rows(1)
                        ->maxLength(90)
                        ->extraAttributes(['maxlength' => 90])
                        ->rule('max:90')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('biological_hazards')) . '/90'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Zaposlenik')
                    ->state(fn (MedicalReferral $record) => $record->employee->name ?? $record->full_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where('full_name', 'like', "%{$search}%")
                                ->orWhereHas('employee', fn (Builder $employeeQuery) => $employeeQuery->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('referral_number')
                    ->label('Broj uputnice')
                    ->sortable()
                    ->searchable()
                    ->alignment(Alignment::Center),

                TextColumn::make('referral_date')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('health_jobs_description')
                    ->label('Poslovi za koje se utvrđuje zdr. sposobnost')
                    ->wrap()
                    ->limit(150)
                    ->tooltip(fn (MedicalReferral $record) => $record->health_jobs_description),

                TextColumn::make('manual_entry')
                    ->label('Unos')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->formatStateUsing(fn ($state) => $state ? 'Ručno' : 'Zaposlenik')
                    ->color(fn ($state) => $state ? 'warning' : 'success'),
            ])
            ->defaultSort('referral_date', 'desc')
            ->recordUrl(fn (MedicalReferral $record): string => static::getUrl('view', ['record' => $record]))
            ->filters([
                SelectFilter::make('status')
                    ->label('Status zapisa')
                    ->placeholder('Odaberi status')
                    ->options([
                        'active' => 'Aktivni zapisi',
                        'trashed' => 'Deaktivirani zapisi',
                        'all' => 'Svi zapisi',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'trashed' => $query->onlyTrashed(),
                            'all' => $query->withTrashed(),
                            default => $query->withoutTrashed(),
                        };
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (MedicalReferral $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(fn (MedicalReferral $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (MedicalReferral $record) => method_exists($record, 'trashed') && $record->trashed()),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (MedicalReferral $record) => method_exists($record, 'trashed') && $record->trashed()),
                ])
                    ->icon(Heroicon::EllipsisVertical)
                    ->label(''),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Deaktiviraj odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
                    ->modalSubmitActionLabel('Deaktiviraj')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(fn (HasTable $livewire) => ! self::isOnlyTrashed($livewire)),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Vrati odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
                    ->modalSubmitActionLabel('Vrati')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(fn (HasTable $livewire) => self::isOnlyTrashed($livewire)),

                ForceDeleteBulkAction::make()
                    ->label('Trajno obriši označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Trajno obriši odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti? Ova radnja se ne može poništiti.')
                    ->modalSubmitActionLabel('Trajno obriši')
                    ->modalCancelActionLabel('Odustani'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedicalReferrals::route('/'),
            'create' => CreateMedicalReferral::route('/create'),
            'edit' => EditMedicalReferral::route('/{record}/edit'),
            'view' => ViewMedicalReferral::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        return self::isAdminUser($user)
            ? $query
            : $query->where('user_id', $user->id);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return '0';
        }

        $query = static::getModel()::query();

        if (! self::isAdminUser($user)) {
            $query->where('user_id', $user->id);
        }

        return (string) $query->count();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }
    public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();

    return $user?->isSuperAdmin() || $user?->canAccessModule('medical_referrals_ra1');
}

public static function canViewAny(): bool
{
    return static::shouldRegisterNavigation();
}

    private static function isOnlyTrashed(HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('status');
        $value = data_get($state, 'value');

        return $value === 'trashed';
    }
}