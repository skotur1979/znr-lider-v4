<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Korisnici';
    protected static ?string $modelLabel = 'Korisnik';
    protected static ?string $pluralModelLabel = 'Korisnici';
    protected static string|\UnitEnum|null $navigationGroup = 'Administracija';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user?->isSuperAdmin() || $user?->canCreateSubusers();
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    protected static function getModuleGroups(): array
    {
        return [
            'upravljanje' => [
                'inspections',
                'operational_logs',
                'risk_assessments',
                'documentation',
                'chemicals',
                'observations',
                'incidents',
                'expenses',
                'budgets',
                'work_permits',
                'kpis',
            ],
            'zaposlenici' => [
                'employees',
                'medical_referrals_ra1',
                'medical_referrals_nr1',
                'ppe_logs',
            ],
            'edukacija' => [
                'education_categories',
                'education_center',
            ],
            'ispitivanja' => [
                'machines',
                'fires',
                'first_aid',
                'miscellaneous',
                'categories',
            ],
            'okolis' => [
                'waste_organizations',
                'waste_types',
                'onto_records',
                'waste_tracking_forms',
                'monthly_reports',
            ],
            'testiranje' => [
                'tests',
                'questions',
                'answers',
                'test_attempts',
            ],
            'zadaci' => [
                'work_tasks',
            ],
        ];
    }

    protected static function groupedSelectedModules(?User $record): array
    {
        $selected = $record?->quick_actions ?? [];

        if (! is_array($selected)) {
            $selected = [];
        }

        $groups = static::getModuleGroups();

        return [
            'upravljanje' => array_values(array_intersect($selected, $groups['upravljanje'])),
            'zaposlenici' => array_values(array_intersect($selected, $groups['zaposlenici'])),
            'edukacija' => array_values(array_intersect($selected, $groups['edukacija'])),
            'ispitivanja' => array_values(array_intersect($selected, $groups['ispitivanja'])),
            'okolis' => array_values(array_intersect($selected, $groups['okolis'])),
            'testiranje' => array_values(array_intersect($selected, $groups['testiranje'])),
            'zadaci' => array_values(array_intersect($selected, $groups['zadaci'])),
        ];
    }

    public static function mergeQuickActions(array $data): array
    {
        $merged = array_merge(
            $data['quick_actions_upravljanje'] ?? [],
            $data['quick_actions_zaposlenici'] ?? [],
            $data['quick_actions_edukacija'] ?? [],
            $data['quick_actions_ispitivanja'] ?? [],
            $data['quick_actions_okolis'] ?? [],
            $data['quick_actions_testiranje'] ?? [],
            $data['quick_actions_zadaci'] ?? [],
        );

        $data['quick_actions'] = array_values(array_unique($merged));

        unset(
            $data['quick_actions_upravljanje'],
            $data['quick_actions_zaposlenici'],
            $data['quick_actions_edukacija'],
            $data['quick_actions_ispitivanja'],
            $data['quick_actions_okolis'],
            $data['quick_actions_testiranje'],
            $data['quick_actions_zadaci'],
        );

        return $data;
    }

    protected static function resetLegalAcceptance(array $data): array
    {
        $data['accepted_terms_at'] = null;
        $data['accepted_privacy_at'] = null;
        $data['terms_version'] = null;
        $data['privacy_version'] = null;
        $data['newsletter_opt_in'] = false;

        return $data;
    }

    protected static function moduleCheckboxList(
        string $field,
        array $options,
        string $groupKey,
        int $columns
    ): CheckboxList {
        return CheckboxList::make($field)
            ->label('')
            ->options($options)
            ->afterStateHydrated(function ($component, ?User $record) use ($groupKey) {
                $grouped = static::groupedSelectedModules($record);
                $component->state($grouped[$groupKey] ?? []);
            })
            ->dehydrated(true)
            ->columns($columns)
            ->bulkToggleable();
    }

    public static function form(Schema $schema): Schema
    {
        $authUser = Auth::user();

        return $schema->schema([
            TextInput::make('name')
                ->label('Ime i prezime')
                ->required()
                ->maxLength(255),

            TextInput::make('organization_name')
                ->label('Naziv organizacije')
                ->maxLength(255)
                ->visible(fn () => $authUser?->isSuperAdmin()),

            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('password')
                ->label('Lozinka')
                ->password()
                ->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state)),

            Select::make('role')
                ->label('Uloga')
                ->required()
                ->options(function () use ($authUser) {
                    if ($authUser?->isSuperAdmin()) {
                        return [
                            'org_admin' => 'Glavni korisnik organizacije',
                            'org_user' => 'Podkorisnik organizacije',
                        ];
                    }

                    return [
                        'org_user' => 'Podkorisnik organizacije',
                    ];
                })
                ->default(fn () => $authUser?->isSuperAdmin() ? 'org_admin' : 'org_user'),

            Select::make('parent_user_id')
                ->label('Glavni korisnik organizacije')
                ->options(function () {
                    return User::query()
                        ->whereIn('role', ['org_admin'])
                        ->orWhere(function ($query) {
                            $query->whereNull('role')
                                ->where('is_admin', false);
                        })
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->visible(fn () => Auth::user()?->isSuperAdmin())
                ->helperText('Prazno = glavni korisnik organizacije.'),

            Hidden::make('parent_user_id')
                ->default(function () {
                    $authUser = Auth::user();

                    if (! $authUser || $authUser->isSuperAdmin()) {
                        return null;
                    }

                    return $authUser->ownerId();
                })
                ->visible(fn () => ! Auth::user()?->isSuperAdmin()),

            Toggle::make('can_manage_subusers')
                ->label('Može dodavati podkorisnike')
                ->default(false)
                ->visible(fn () => Auth::user()?->isSuperAdmin()),

            Toggle::make('is_active')
                ->label('Aktivan korisnik')
                ->default(true)
                ->visible(fn () => Auth::user()?->isSuperAdmin()),

            Toggle::make('daily_status_email_enabled')
                ->label('Prima dnevni izvještaj na e-mail')
                ->default(true),

            Toggle::make('weekly_status_email_enabled')
                ->label('Prima tjedni izvještaj na e-mail')
                ->default(false),

            Section::make('GDPR / prihvaćanje uvjeta')
                ->visible(fn () => Auth::user()?->isSuperAdmin())
                ->columns(2)
                ->schema([
                    Placeholder::make('gdpr_status')
                        ->label('Status')
                        ->content(fn (?User $record): string => $record?->hasAcceptedCurrentLegalTerms()
                            ? 'Prihvaćeno'
                            : 'Nije prihvaćeno'),

                    Placeholder::make('newsletter_status')
                        ->label('Newsletter')
                        ->content(fn (?User $record): string => $record?->newsletter_opt_in ? 'Da' : 'Ne'),

                    Placeholder::make('accepted_terms_at_info')
                        ->label('Uvjeti korištenja')
                        ->content(fn (?User $record): string => $record?->accepted_terms_at
                            ? $record->accepted_terms_at->format('d.m.Y. H:i') . ' / verzija ' . ($record->terms_version ?? '-')
                            : '-'),

                    Placeholder::make('accepted_privacy_at_info')
                        ->label('Pravila privatnosti')
                        ->content(fn (?User $record): string => $record?->accepted_privacy_at
                            ? $record->accepted_privacy_at->format('d.m.Y. H:i') . ' / verzija ' . ($record->privacy_version ?? '-')
                            : '-'),
                ])
                ->columnSpanFull(),

            Section::make('Uključeni moduli')
                ->visible(fn () => Auth::user()?->isSuperAdmin())
                ->schema([
                    Section::make('Upravljanje')
                        ->compact()
                        ->schema([
                            static::moduleCheckboxList('quick_actions_upravljanje', [
                                'inspections' => 'Nadzori',
                                'operational_logs' => 'Operativni dnevnik',
                                'risk_assessments' => 'Procjene rizika',
                                'documentation' => 'Dokumentacija',
                                'chemicals' => 'Kemikalije',
                                'observations' => 'Zapažanja',
                                'incidents' => 'Incidenti',
                                'expenses' => 'Troškovi',
                                'budgets' => 'Budžet',
                                'work_permits' => 'Dozvole za rad',
                                'kpis' => 'KPI',
                            ], 'upravljanje', 5),
                        ]),

                    Section::make('Zaposlenici')
                        ->compact()
                        ->schema([
                            static::moduleCheckboxList('quick_actions_zaposlenici', [
                                'employees' => 'Zaposlenici',
                                'medical_referrals_ra1' => 'RA-1 uputnice',
                                'medical_referrals_nr1' => 'NR-1 uputnice',
                                'ppe_logs' => 'Upisnik OZO',
                            ], 'zaposlenici', 4),
                        ]),

                    Section::make('Edukacija')
                        ->compact()
                        ->schema([
                            static::moduleCheckboxList('quick_actions_edukacija', [
                                'education_categories' => 'Kategorije edukacije',
                                'education_center' => 'Edukacijski centar',
                            ], 'edukacija', 2),
                        ]),

                    Section::make('Ispitivanja')
                        ->compact()
                        ->schema([
                            static::moduleCheckboxList('quick_actions_ispitivanja', [
                                'machines' => 'Radna oprema',
                                'fires' => 'Vatrogasni aparati',
                                'first_aid' => 'Prva pomoć - ormarići',
                                'miscellaneous' => 'Ostala ispitivanja',
                                'categories' => 'Kategorije ispitivanja',
                            ], 'ispitivanja', 5),
                        ]),

                    Section::make('Zaštita okoliša')
                        ->compact()
                        ->schema([
                            static::moduleCheckboxList('quick_actions_okolis', [
                                'waste_organizations' => 'Organizacije otpada',
                                'waste_types' => 'Vrste otpada',
                                'onto_records' => 'ONTO obrasci',
                                'waste_tracking_forms' => 'Prateći listovi',
                                'monthly_reports' => 'Mjesečni izvještaj',
                            ], 'okolis', 5),
                        ]),

                    Section::make('Testiranje')
                        ->compact()
                        ->schema([
                            static::moduleCheckboxList('quick_actions_testiranje', [
                                'tests' => 'Testovi',
                                'questions' => 'Pitanja',
                                'answers' => 'Odgovori',
                                'test_attempts' => 'Riješeni testovi',
                            ], 'testiranje', 4),
                        ]),

                    Section::make('Radni zadaci')
                        ->compact()
                        ->schema([
                            static::moduleCheckboxList('quick_actions_zadaci', [
                                'work_tasks' => 'Radni zadaci',
                            ], 'zadaci', 1),
                        ]),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Ime')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('organization_name')
                    ->label('Organizacija')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Uloga')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'super_admin', 'admin' => 'Super admin',
                        'org_admin' => 'Glavni korisnik',
                        'org_user' => 'Podkorisnik',
                        default => 'Korisnik',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'super_admin', 'admin' => 'danger',
                        'org_admin' => 'warning',
                        'org_user' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('can_manage_subusers')
                    ->label('Podkorisnici')
                    ->boolean(),

                Tables\Columns\IconColumn::make('daily_status_email_enabled')
                    ->label('Dnevni')
                    ->boolean(),

                Tables\Columns\IconColumn::make('weekly_status_email_enabled')
                    ->label('Tjedni')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktivan')
                    ->boolean(),

                Tables\Columns\IconColumn::make('legal_accepted')
                    ->label('GDPR')
                    ->state(fn (User $record): bool => $record->hasAcceptedCurrentLegalTerms())
                    ->boolean()
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('accepted_terms_at')
                    ->label('Uvjeti prihvaćeni')
                    ->dateTime('d.m.Y. H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('accepted_privacy_at')
                    ->label('Privatnost prihvaćena')
                    ->dateTime('d.m.Y. H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('terms_version')
                    ->label('Verzija uvjeta')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('privacy_version')
                    ->label('Verzija privatnosti')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\IconColumn::make('newsletter_opt_in')
                    ->label('Newsletter')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),
            ])
            ->actions([
                ViewAction::make()->label('Prikaži'),
                EditAction::make()->label('Uredi'),
                DeleteAction::make()
                    ->label('Obriši')
                    ->visible(fn (User $record) => ! $record->isSuperAdmin()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Obriši označeno'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $authUser = Auth::user();
        $query = parent::getEloquentQuery();

        if ($authUser?->isSuperAdmin()) {
            return $query;
        }

        if ($authUser?->canCreateSubusers()) {
            return $query->where(function (Builder $query) use ($authUser) {
                $query->where('id', $authUser->id)
                    ->orWhere('parent_user_id', $authUser->ownerId());
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data = static::mergeQuickActions($data);
        $data = static::resetLegalAcceptance($data);

        $authUser = Auth::user();

        if ($authUser?->isSuperAdmin()) {
            $data['is_admin'] = false;
            $data['role'] = $data['role'] ?? 'org_admin';
            $data['is_active'] = $data['is_active'] ?? true;

            if (($data['role'] ?? null) === 'org_admin') {
                $data['parent_user_id'] = null;
            }

            return $data;
        }

        $data['parent_user_id'] = $authUser->ownerId();
        $data['organization_name'] = $authUser->owner()?->organization_name;
        $data['role'] = 'org_user';
        $data['is_admin'] = false;
        $data['can_manage_subusers'] = false;
        $data['is_active'] = true;
        $data['quick_actions'] = null;

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data = static::mergeQuickActions($data);

        $authUser = Auth::user();

        if (! $authUser?->isSuperAdmin()) {
            unset($data['quick_actions']);
            unset($data['can_manage_subusers']);
            unset($data['is_active']);
            unset($data['organization_name']);
            unset($data['role']);
            unset($data['parent_user_id']);
            unset($data['accepted_terms_at']);
            unset($data['accepted_privacy_at']);
            unset($data['terms_version']);
            unset($data['privacy_version']);
            unset($data['newsletter_opt_in']);
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}