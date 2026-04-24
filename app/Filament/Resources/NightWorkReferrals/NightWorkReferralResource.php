<?php

namespace App\Filament\Resources\NightWorkReferrals;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\NightWorkReferrals\Pages;
use App\Models\Employee;
use App\Models\NightWorkReferral;
use BackedEnum;
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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class NightWorkReferralResource extends BaseResource
{
    protected static ?string $model = NightWorkReferral::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;
    protected static string|UnitEnum|null $navigationGroup = 'Zaposlenici';
    protected static ?string $navigationLabel = 'NR-1 Uputnice';
    protected static ?string $pluralModelLabel = 'NR-1 Uputnice';
    protected static ?string $modelLabel = 'NR-1 Uputnica';
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
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    protected static function getEmployeeOptions(): array
    {
        $query = Employee::query()->orderBy('name');

        if (! static::isSuperAdmin()) {
            $query->where('user_id', static::ownerId());
        }

        return $query->pluck('name', 'id')->toArray();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(fn () => static::ownerId())
                ->dehydrated(),

            Section::make('Povezivanje sa zaposlenikom')
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    Toggle::make('manual_entry')
                        ->label('Novi radnik (još nije u bazi)')
                        ->helperText('Ako uključiš, podatke upiši ručno.')
                        ->live(),

                    Select::make('employee_id')
                        ->label('Zaposlenik')
                        ->options(fn () => static::getEmployeeOptions())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(fn (Get $get): bool => ! $get('manual_entry'))
                        ->hidden(fn (Get $get): bool => (bool) $get('manual_entry'))
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if ($get('manual_entry') || ! $state) {
                                return;
                            }

                            $employee = Employee::find($state);

                            if (! $employee) {
                                return;
                            }

                            $set('full_name', $employee->name ?? '');
                            $set('oib', $employee->OIB ?? '');
                            $set('job_title', $employee->workplace ?? '');
                            $set('education', $employee->education ?? '');
                            $set('name_of_parents', $employee->name_of_parents ?? '');
                            $set('place_of_birth', $employee->place_of_birth ?? '');
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
                        ->afterStateHydrated(function (Set $set, $state, ?NightWorkReferral $record) {
                            if ($record?->employee && blank($state) && ! ($record->manual_entry ?? false)) {
                                $set('full_name', $record->employee->name ?? '');
                            }
                        }),

                    TextInput::make('name_of_parents')
                        ->label('Ime oca – majke')
                        ->afterStateHydrated(function (Set $set, $state, ?NightWorkReferral $record) {
                            if ($record?->employee && blank($state) && ! ($record->manual_entry ?? false)) {
                                $set('name_of_parents', $record->employee->name_of_parents ?? '');
                            }
                        }),

                    TextInput::make('place_of_birth')
                        ->label('Datum i mjesto rođenja')
                        ->afterStateHydrated(function (Set $set, $state, ?NightWorkReferral $record) {
                            if ($record?->employee && blank($state) && ! ($record->manual_entry ?? false)) {
                                $set('place_of_birth', $record->employee->place_of_birth ?? '');
                            }
                        }),

                    TextInput::make('oib')
                        ->label('OIB')
                        ->required(fn (Get $get): bool => (bool) $get('manual_entry'))
                        ->afterStateHydrated(function (Set $set, $state, ?NightWorkReferral $record) {
                            if ($record?->employee && blank($state)) {
                                $set('oib', $record->employee->OIB ?? '');
                            }
                        }),

                    TextInput::make('job_title')
                        ->label('Noćni rad za koje se utvrđuje radna sposobnost')
                        ->afterStateHydrated(function (Set $set, $state, ?NightWorkReferral $record) {
                            if ($record?->employee && blank($state) && ! ($record->manual_entry ?? false)) {
                                $set('job_title', $record->employee->workplace ?? '');
                            }
                        })
                        ->maxLength(110)
                        ->extraAttributes(['maxlength' => 110])
                        ->rule('max:110')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('job_title')) . '/110'),

                    TextInput::make('education')
                        ->label('Školska sprema')
                        ->afterStateHydrated(function (Set $set, $state, ?NightWorkReferral $record) {
                            if ($record?->employee && blank($state) && ! ($record->manual_entry ?? false)) {
                                $set('education', $record->employee->education ?? '');
                            }
                        }),
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
                                    'kontrolni' => 'Kontrolni',
                                ])
                                ->columns(2),
                        ]),

                    DatePicker::make('last_exam_date')
                        ->label('Posljednji zdravstveni pregled je učinjen'),

                    TextInput::make('last_exam_reference3')
                        ->label('S ocjenom zdravstvene sposobnosti')
                        ->columnSpanFull(),
                ]),

            Section::make('Opis radnog mjesta')
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    Textarea::make('short_description')
                        ->label('Kratak opis noćnog rada, poslova i trajanje noćnog rada')
                        ->rows(2)
                        ->maxLength(250)
                        ->extraAttributes(['maxlength' => 250])
                        ->rule('max:250')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('short_description')) . '/250'),

                    Textarea::make('tools')
                        ->label('Strojevi, alati, uređaji¹')
                        ->rows(1)
                        ->maxLength(150)
                        ->extraAttributes(['maxlength' => 150])
                        ->rule('max:150')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('tools')) . '/150'),

                    Textarea::make('job_tasks')
                        ->label('Predmet rada²')
                        ->rows(1)
                        ->maxLength(150)
                        ->extraAttributes(['maxlength' => 150])
                        ->rule('max:150')
                        ->live(onBlur: true)
                        ->helperText(fn (Get $get) => mb_strlen((string) $get('job_tasks')) . '/150'),
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
                            'u_dubini' => 'u dubini',
                            'u_vodi' => 'u vodi',
                            'mokrim_uvjetima' => 'u mokrom',
                        ])
                        ->columns(6),

                    CheckboxList::make('organization')
                        ->label('Organizacija rada')
                        ->options([
                            'smjena' => 'u smjenama',
                            'terenski' => 'na terenu',
                            'samostalni' => 'radi sam',
                            'rad_s_grupom' => 'radi u grupi',
                            'rad_sa_strankama' => 'radi sa strankama',
                            'rad_na_traci' => 'rad na traci',
                            'brzi_tempo' => 'brzi tempo rada',
                            'ritam_određen' => 'rad sa nametnutim ritmom',
                            'monotonija' => 'monoton rad',
                        ])
                        ->columns(5),

                    CheckboxList::make('body_position')
                        ->label('Položaj tijela i aktivnosti³:')
                        ->options([
                            'stojeći' => 'rad stojeći',
                            'sagibanje' => 'učestalo sagibanje',
                            'podvlačenje' => 'podvlačenje',
                            'sjedeći' => 'rad sjedeći',
                            'zakretanje' => 'zaokretanje trupa',
                            'balansiranje' => 'balansiranje',
                            'u_pokretu' => 'u pokretu',
                            'klečanje' => 'klečanje',
                            'uspinjanje' => 'uspinjanje ljestvama',
                            'kombinirano' => 'kombinirano',
                            'čučanje' => 'čučanje',
                            'uspinjanje_stepenicama' => 'uspinjanje stepenicama',
                        ])
                        ->columns(6),

                    Grid::make(3)->schema([
                        Group::make([
                            Grid::make(2)
                                ->schema([
                                    Checkbox::make('lifting_enabled')
                                        ->label('Dizanje tereta kg')
                                        ->live(),

                                    TextInput::make('lifting_weight')
                                        ->hiddenLabel()
                                        ->placeholder('')
                                        ->numeric()
                                        ->visible(fn (Get $get): bool => (bool) $get('lifting_enabled')),
                                ]),
                        ]),

                        Group::make([
                            Grid::make(2)
                                ->schema([
                                    Checkbox::make('carrying_enabled')
                                        ->label('Prenošenje tereta kg')
                                        ->live(),

                                    TextInput::make('carrying_weight')
                                        ->hiddenLabel()
                                        ->placeholder('')
                                        ->numeric()
                                        ->visible(fn (Get $get): bool => (bool) $get('carrying_enabled')),
                                ]),
                        ]),

                        Group::make([
                            Grid::make(2)
                                ->schema([
                                    Checkbox::make('pushing_enabled')
                                        ->label('Guranje tereta kg')
                                        ->live(),

                                    TextInput::make('pushing_weight')
                                        ->hiddenLabel()
                                        ->placeholder('')
                                        ->numeric()
                                        ->visible(fn (Get $get): bool => (bool) $get('pushing_enabled')),
                                ]),
                        ]),
                    ]),

                    CheckboxList::make('job_characteristics')
                        ->label('Pri radu je važan⁴:')
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
                            'vlažnost' => 'visoka vlažnost',
                            'hladnoća' => 'niska temperatura',
                            'buka' => 'buka',
                            'vibracije' => 'vibracije',
                            'ozljede' => 'povećana izloženost ozljedama',
                            'tlak' => 'povišeni atmosferski tlak',
                            'prašina' => 'prašina',
                            'zračenja' => 'ionizacijska zračenja',
                            'zračenja1' => 'neionizacijska zračenja',
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
                    ->state(fn (NightWorkReferral $record) => $record->employee->name ?? $record->full_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where('full_name', 'like', "%{$search}%")
                                ->orWhereHas('employee', fn (Builder $employeeQuery) => $employeeQuery->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),
static::userTableColumn(),
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

                TextColumn::make('job_title')
                    ->label('Noćni rad za koji se utvrđuje zdr. sposobnost')
                    ->wrap()
                    ->limit(150)
                    ->tooltip(fn (NightWorkReferral $record) => $record->job_title),

                TextColumn::make('manual_entry')
                    ->label('Unos')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->formatStateUsing(fn ($state) => $state ? 'Ručno' : 'Zaposlenik')
                    ->color(fn ($state) => $state ? 'warning' : 'success'),
            ])
            ->defaultSort('referral_date', 'desc')
            ->recordUrl(fn (NightWorkReferral $record): string => static::getUrl('view', ['record' => $record]))
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
                        ->visible(fn (NightWorkReferral $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(fn (NightWorkReferral $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (NightWorkReferral $record) => method_exists($record, 'trashed') && $record->trashed()),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (NightWorkReferral $record) => method_exists($record, 'trashed') && $record->trashed()),
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
                    ->visible(fn (HasTable $livewire) => ! static::isOnlyTrashed($livewire)),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Vrati odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
                    ->modalSubmitActionLabel('Vrati')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(fn (HasTable $livewire) => static::isOnlyTrashed($livewire)),

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
            'index' => Pages\ListNightWorkReferrals::route('/'),
            'create' => Pages\CreateNightWorkReferral::route('/create'),
            'edit' => Pages\EditNightWorkReferral::route('/{record}/edit'),
            'view' => Pages\ViewNightWorkReferral::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        if (static::isSuperAdmin()) {
            return $query;
        }

        return $query->where('user_id', static::ownerId());
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        if (static::isSuperAdmin()) {
            return $query;
        }

        return $query->where('user_id', static::ownerId());
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (! static::isSuperAdmin()) {
            $query->where('user_id', static::ownerId());
        }

        return (string) $query->count();
    }

    private static function isOnlyTrashed(HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('status');
        $value = data_get($state, 'value');

        return $value === 'trashed';
    }
}
