<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages;
use App\Models\User;
use App\Services\StorageQuotaService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;


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

    public static function canCreate(): bool
    {
    $authUser = Auth::user();

    if (! $authUser) {
        return false;
    }

    if ($authUser->isSuperAdmin()) {
        return true;
    }

    if (! $authUser->canCreateSubusers()) {
        return false;
    }

    return $authUser->owner()->canAddMoreSubusers();
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

    protected static function modulePermissionCheckbox(
        string $moduleKey,
        string $label
    ): CheckboxList {
        return CheckboxList::make('module_permissions.' . $moduleKey)
            ->label($label)
            ->options(User::MODULE_PERMISSION_ACTIONS)
            ->default(User::fullModulePermissionSet())
            ->afterStateHydrated(function ($component, ?User $record) use ($moduleKey): void {
                if (! $record) {
                    $component->state(User::fullModulePermissionSet());

                    return;
                }

                $component->state($record->permissionsForModule($moduleKey));
            })
            ->columns(4)
            ->bulkToggleable()
            ->visible(function () use ($moduleKey): bool {
                $authUser = Auth::user();

                return $authUser?->isOrgAdmin() === true
                    && $authUser->canAccessModule($moduleKey);
            });
    }

    protected static function normalizeModulePermissions(
        mixed $permissions,
        User $owner
    ): array {
        $permissions = is_array($permissions) ? $permissions : [];
        $normalized = [];

        foreach (User::CONTROLLED_MODULES as $moduleKey => $label) {
            if (! $owner->canAccessModule($moduleKey)) {
                continue;
            }

            $selected = $permissions[$moduleKey] ?? [];
            $selected = is_array($selected) ? $selected : [];

            $normalized[$moduleKey] = array_values(array_intersect(
                $selected,
                array_keys(User::MODULE_PERMISSION_ACTIONS)
            ));
        }

        return $normalized;
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
                ->label(fn (string $operation): string =>
                    $operation === 'create' ? 'Lozinka' : 'Nova lozinka'
                )
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText(fn (string $operation): ?string =>
                    $operation === 'edit'
                        ? 'Ostavite prazno ako ne želite promijeniti postojeću lozinku.'
                        : null
                ),

            Select::make('role')
                ->label('Uloga')
                ->options(function (?User $record): array {
                    if ($record?->isSuperAdmin()) {
                        return [
                            'super_admin' => 'Super admin',
                        ];
                    }

                    return [
                        'org_admin' => 'Glavni korisnik organizacije',
                        'org_user' => 'Podkorisnik organizacije',
                    ];
                })
                ->default(fn () => $authUser?->isSuperAdmin() ? 'org_admin' : 'org_user')
                ->required()
                ->native(false)
                ->disabled(function (?User $record): bool {
                    if ($record?->isSuperAdmin()) {
                        return true;
                    }

                    return ! Auth::user()?->isSuperAdmin();
                })
                ->dehydrated(function (?User $record): bool {
                    return Auth::user()?->isSuperAdmin()
                        && ! $record?->isSuperAdmin();
                })
                ->helperText(function (?User $record): ?string {
                    if ($record?->isSuperAdmin()) {
                        return 'Uloga superadmina ne može se promijeniti.';
                    }

                    return Auth::user()?->isSuperAdmin()
                        ? 'Superadmin može promijeniti ulogu korisnika.'
                        : 'Ulogu korisnika može promijeniti samo superadmin.';
                }),

            Placeholder::make('subusers_limit_info')
                ->label('Podkorisnici organizacije')
                ->content(function () use ($authUser): string {

                    if (! $authUser || $authUser->isSuperAdmin()) {
                        return 'Superadmin nema ograničenje broja korisnika.';
                    }

                    $owner = $authUser->owner();

                    return
                        'Iskorišteno: '
                        . $owner->subusersCountForLimit()
                        . ' od '
                        . User::MAX_SUBUSERS_PER_ORGANIZATION
                        . PHP_EOL
                        . 'Preostalo mjesta: '
                        . $owner->remainingSubusers();
                })
                ->visible(fn () => ! Auth::user()?->isSuperAdmin()),

            Select::make('parent_user_id')
                ->label('Glavni korisnik organizacije')
                ->options(function (): array {
                    return User::query()
                        ->where('role', 'org_admin')
                        ->where('is_active', true)
                        ->withoutTrashed()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->visible(
                    fn (): bool =>
                        Auth::user()?->isSuperAdmin()
                        === true
                )
                ->helperText(
                    'Za glavnog korisnika ostavite prazno. '
                    . 'Za podkorisnika obavezno odaberite glavnog korisnika organizacije.'
                ),

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

            Section::make('Prostor organizacije')
                ->visible(fn () => Auth::user()?->isSuperAdmin())
                ->columns(2)
                ->schema([
                    TextInput::make('storage_quota_mb')
                        ->label('Limit prostora organizacije (GB)')
                        ->numeric()
                        ->default(20)
                        ->formatStateUsing(function ($state, ?User $record) {
                            if (! $record) {
                                return 20;
                            }

                            $owner = $record->owner();

                            return round((($owner->storage_quota_mb ?? 20480) / 1024), 0);
                        })
                        ->dehydrateStateUsing(fn ($state) => (int) $state * 1024)
                        ->disabled(fn (?User $record): bool => $record?->parent_user_id !== null)
                        ->dehydrated(fn (?User $record): bool => $record?->parent_user_id === null)
                        ->helperText(function (?User $record) {
                            if (! $record) {
                                return 'Zadani limit je 20 GB.';
                            }

                            if ($record->parent_user_id) {
                                return 'Limit prostora određuje superadmin na glavnom korisniku organizacije. Svi korisnici organizacije dijele isti prostor.';
                            }

                            return 'Ovdje superadmin povećava ili smanjuje prostor cijeloj organizaciji.';
                        }),

                    Placeholder::make('storage_usage_info')
                        ->label('Trenutna iskorištenost')
                        ->content(function (?User $record): string {
                            if (! $record) {
                                return '-';
                            }

                            $owner = $record->owner();
                            $ownerId = $owner->id;

                            $quotaGb = round(($owner->storage_quota_mb ?? 20480) / 1024, 0);

                            return 'Organizacija '
                                . ($owner->organization_name ?: $owner->name)
                                . ' ima '
                                . $quotaGb
                                . ' GB, a svi korisnici organizacije dijele taj prostor. '
                                . app(StorageQuotaService::class)->usageText($ownerId)
                                . ' ('
                                . app(StorageQuotaService::class)->usagePercent($ownerId)
                                . '%)';
                        }),
                ])
                ->columnSpanFull(),

            Section::make('Dozvole podkorisnika po modulima')
                ->description(
                    'Odredi što podkorisnik smije raditi u najvažnijim modulima. '
                    . 'Pregled dopušta otvaranje modula i izvoz, Dodavanje dopušta novi zapis, uvoz i kopiranje, '
                    . 'Uređivanje dopušta izmjene, a Brisanje dopušta deaktiviranje, vraćanje i trajno brisanje.'
                )
                ->visible(function (?User $record): bool {
                    $authUser = Auth::user();

                    if (! $authUser?->isOrgAdmin()) {
                        return false;
                    }

                    if (! $record) {
                        return true;
                    }

                    return $record->isOrgUser()
                        && (int) $record->parent_user_id === (int) $authUser->ownerId();
                })
                ->schema([
                    static::modulePermissionCheckbox('observations', 'Zapažanja'),
                    static::modulePermissionCheckbox('employees', 'Zaposlenici'),
                    static::modulePermissionCheckbox('machines', 'Radna oprema'),
                    static::modulePermissionCheckbox('waste_tracking_forms', 'Prateći listovi'),
                    static::modulePermissionCheckbox('miscellaneous', 'Ostala ispitivanja'),
                    static::modulePermissionCheckbox('categories', 'Kategorije ispitivanja'),
                ])
                ->columns(1)
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

                Tables\Columns\TextColumn::make('storage_quota_mb')
                    ->label('Limit prostora')
                    ->formatStateUsing(function ($state, User $record): string {
                        $owner = $record->owner();

                        return round((($owner->storage_quota_mb ?? 20480) / 1024), 0) . ' GB';
                    })
                    ->description(function (User $record): ?string {
                        if (! $record->parent_user_id) {
                            return 'Organizacija';
                        }

                        return 'Dijeli prostor organizacije';
                    })
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Uloga')
                    ->alignment('center')
                    ->badge()
                    ->formatStateUsing(function (?string $state, User $record): string {
                        if ($record->isSuperAdmin()) {
                            return 'Super admin';
                        }

                        return match ($state) {
                            'org_admin' => 'Glavni korisnik',
                            'org_user' => 'Podkorisnik',
                            default => 'Korisnik',
                        };
                    })
                    ->color(function (?string $state, User $record): string {
                        if ($record->isSuperAdmin()) {
                            return 'danger';
                        }

                        return match ($state) {
                            'org_admin' => 'warning',
                            'org_user' => 'info',
                            default => 'gray',
                        };
                    }),

                Tables\Columns\TextColumn::make('subusers_usage')
                    ->label('Podkorisnici')
                    ->alignment('center')
                    ->state(function (User $record) {

                        if ($record->parent_user_id) {
                            return null;
                        }

                        return $record->subusersCountForLimit()
                            . ' / '
                            . User::MAX_SUBUSERS_PER_ORGANIZATION;
                    })
                    ->placeholder('')
                    ->badge()
                    ->color(function (User $record) {

                        if ($record->parent_user_id) {
                            return 'gray';
                        }

                        $count = $record->subusersCountForLimit();

                        if ($count >= User::MAX_SUBUSERS_PER_ORGANIZATION) {
                            return 'danger';
                        }

                        if ($count >= 4) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                Tables\Columns\IconColumn::make('can_manage_subusers')
                    ->label('Može dodavati')
                    ->alignment('center')
                    ->boolean(),

                Tables\Columns\IconColumn::make('daily_status_email_enabled')
                    ->label('Dnevni')
                    ->alignment('center')
                    ->boolean(),

                Tables\Columns\IconColumn::make('weekly_status_email_enabled')
                    ->label('Tjedni')
                    ->alignment('center')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktivan')
                    ->alignment('center')
                    ->boolean(),

                Tables\Columns\TextColumn::make('account_status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'deactivated' => 'warning',
                        'anonymized' => 'danger',
                        'archived' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'Aktivan',
                        'deactivated' => 'Deaktiviran',
                        'anonymized' => 'Anonimiziran',
                        'archived' => 'Arhiviran',
                        default => '-',
                    }),

                Tables\Columns\TextColumn::make('gdpr_request_status')
                    ->label('GDPR zahtjev')
                    ->badge()
                    ->toggleable()
                    ->color(fn (?string $state): string => match ($state) {
                        'requested' => 'danger',
                        'processing' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'requested' => 'Zaprimljen',
                        'processing' => 'U obradi',
                        'completed' => 'Riješen',
                        default => '-',
                    }),

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

                Tables\Columns\IconColumn::make('legal_consent_withdrawn_status')
                    ->label('Privola povučena')
                    ->state(fn (User $record): bool => (bool) $record->legal_consent_withdrawn_at)
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('legal_consent_withdrawn_at')
                    ->label('Privola povučena datum')
                    ->dateTime('d.m.Y. H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('legal_consent_withdrawn_reason')
                    ->label('Razlog povlačenja')
                    ->limit(40)
                    ->tooltip(fn (User $record) => $record->legal_consent_withdrawn_reason)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\IconColumn::make('account_deletion_requested_status')
                    ->label('Brisanje računa')
                    ->state(fn (User $record): bool => (bool) $record->account_deletion_requested_at)
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->toggleable()
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('account_deletion_requested_at')
                    ->label('Zahtjev za brisanje')
                    ->dateTime('d.m.Y. H:i')
                    ->placeholder('-')
                    ->toggleable()
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),

                Tables\Columns\TextColumn::make('account_deletion_reason')
                    ->label('Razlog brisanja')
                    ->limit(40)
                    ->tooltip(fn (User $record) => $record->account_deletion_reason)
                    ->placeholder('-')
                    ->toggleable()
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),
            ])
            ->filters([
                TernaryFilter::make('deleted_at')
                    ->label('Arhivirani korisnici')
                    ->placeholder('Svi korisnici')
                    ->trueLabel('Samo arhivirani')
                    ->falseLabel('Bez arhiviranih')
                    ->queries(
                        true: fn (Builder $query) => $query->onlyTrashed(),
                        false: fn (Builder $query) => $query->withoutTrashed(),
                        blank: fn (Builder $query) => $query->withTrashed(),
                    ),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Prikaži'),

                EditAction::make()
                    ->label('Uredi'),

                Action::make('deactivate_user')
                    ->label('Deaktiviraj')
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Deaktivirati korisnika?')
                    ->modalDescription('Korisnik se neće moći prijaviti, ali njegovi zapisi i audit tragovi ostaju sačuvani.')
                    ->visible(fn (User $record): bool =>
                        ! $record->isSuperAdmin()
                        && (bool) $record->is_active
                        && ! $record->trashed()
                        && (
                            Auth::user()?->isSuperAdmin()
                            || (
                                Auth::user()?->canCreateSubusers()
                                && (int) $record->parent_user_id === (int) Auth::user()?->ownerId()
                            )
                        )
                    )
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'is_active' => false,
                            'account_status' => 'deactivated',
                        ])->save();

                        Notification::make()
                            ->title('Korisnik je deaktiviran.')
                            ->success()
                            ->send();
                    }),

                Action::make('activate_user')
                    ->label('Aktiviraj')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool =>
                        Auth::user()?->isSuperAdmin()
                        && ! $record->isSuperAdmin()
                        && ! (bool) $record->is_active
                        && ! $record->trashed()
                    )
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'is_active' => true,
                            'account_status' => 'active',
                        ])->save();

                        Notification::make()
                            ->title('Korisnik je ponovno aktiviran.')
                            ->success()
                            ->send();
                    }),

                Action::make('mark_gdpr_processing')
                    ->label('GDPR u obradi')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool =>
                        Auth::user()?->isSuperAdmin()
                        && ! $record->isSuperAdmin()
                        && ! $record->trashed()
                        && $record->gdpr_request_status === 'requested'
                    )
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'gdpr_request_status' => 'processing',
                        ])->save();

                        Notification::make()
                            ->title('GDPR zahtjev je označen kao u obradi.')
                            ->success()
                            ->send();
                    }),

                Action::make('anonymize_user')
                    ->label('Anonimiziraj')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anonimizirati korisnika?')
                    ->modalDescription('Osobni podaci korisnika bit će zamijenjeni anonimnim vrijednostima. Audit zapisi i zakonske evidencije ostaju sačuvani.')
                    ->visible(fn (User $record): bool =>
                        Auth::user()?->isSuperAdmin()
                        && ! $record->isSuperAdmin()
                        && (int) $record->id !== (int) Auth::id()
                        && ! $record->trashed()
                        && $record->account_status !== 'anonymized'
                    )
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'name' => 'Anonimizirani korisnik #' . $record->id,
                            'email' => 'deleted-user-' . $record->id . '@example.invalid',
                            'organization_name' => null,
                            'password' => Hash::make(Str::random(64)),
                            'is_active' => false,
                            'account_status' => 'anonymized',
                            'gdpr_request_status' => 'completed',
                            'gdpr_request_processed_at' => now(),
                            'daily_status_email_enabled' => false,
                            'weekly_status_email_enabled' => false,
                            'newsletter_opt_in' => false,
                            'legal_consent_withdrawn_at' => $record->legal_consent_withdrawn_at ?? now(),
                            'legal_consent_withdrawn_reason' => $record->legal_consent_withdrawn_reason ?: 'Korisnik anonimiziran nakon GDPR zahtjeva.',
                        ])->save();

                        Notification::make()
                            ->title('Korisnik je anonimiziran.')
                            ->success()
                            ->send();
                    }),

                Action::make('archive_user')
                    ->label('Arhiviraj')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Arhivirati korisnika?')
                    ->modalDescription('Korisnik će biti soft-delete arhiviran. Podaci se neće fizički brisati iz baze.')
                    ->visible(fn (User $record): bool =>
                        Auth::user()?->isSuperAdmin()
                        && ! $record->isSuperAdmin()
                        && (int) $record->id !== (int) Auth::id()
                        && ! $record->trashed()
                    )
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'account_status' => 'archived',
                            'is_active' => false,
                        ])->save();

                        $record->delete();

                        Notification::make()
                            ->title('Korisnik je arhiviran.')
                            ->success()
                            ->send();
                    }),
                    Action::make('permanently_delete_user')
                            ->label('Trajno izbriši')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('Trajno izbrisati korisnika?')
                            ->modalDescription(
                                'Sustav će prije brisanja provjeriti postoje li podkorisnici, '
                                . 'zapisi, dokumenti, audit tragovi ili drugi povezani podaci. '
                                . 'Ova se radnja ne može poništiti.'
                            )
                            ->modalSubmitActionLabel('Provjeri i trajno izbriši')
                            ->visible(fn (User $record): bool =>
                                Auth::user()?->isSuperAdmin()
                                && ! $record->isSuperAdmin()
                                && (int) $record->id !== (int) Auth::id()
                            )
                            ->action(function (User $record): void {
                                $blockReason = static::userDeletionBlockReason($record);

                                if ($blockReason !== null) {
                                    Notification::make()
                                        ->title('Korisnik nije izbrisan.')
                                        ->body($blockReason)
                                        ->danger()
                                        ->persistent()
                                        ->send();

                                    return;
                                }

                                DB::transaction(function () use ($record): void {
                                    $record->forceDelete();
                                });

                                Notification::make()
                                    ->title('Korisnik je trajno izbrisan.')
                                    ->body('Provjera nije pronašla povezane podatke.')
                                    ->success()
                                    ->send();
                            }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $authUser = Auth::user();

        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withTrashed();

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

    if (! $authUser) {
        throw ValidationException::withMessages([
            'email' => 'Korisnik nije prijavljen.',
        ]);
    }

    /*
     * ============================================================
     * SUPERADMIN
     * ============================================================
     *
     * Superadmin može kreirati:
     *
     * org_admin
     * parent_user_id = NULL
     *
     * org_user
     * parent_user_id = ID stvarnog org_admin korisnika
     *
     * Nikada ne stvaramo poslovni superadmin zapis
     * kroz ovaj obrazac.
     */
    if ($authUser->isSuperAdmin()) {
        $data['is_admin'] = false;

        $role = $data['role'] ?? 'org_admin';

        if (! in_array(
            $role,
            [
                'org_admin',
                'org_user',
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'role' => 'Odabrana uloga nije dopuštena.',
            ]);
        }

        $data['role'] = $role;

        /*
         * GLAVNI KORISNIK ORGANIZACIJE
         */
        if ($role === 'org_admin') {
            /*
             * Glavni korisnik nema parent_user_id.
             */
            $data['parent_user_id'] = null;

            /*
             * Glavni korisnik može dobiti mogućnost
             * upravljanja podkorisnicima.
             */
            $data['can_manage_subusers'] =
                (bool) (
                    $data['can_manage_subusers']
                    ?? false
                );

            /*
             * Granularne dozvole vrijede za
             * podkorisnike, ne za org_admina.
             */
            unset(
                $data['module_permissions']
            );

            $data['is_active'] =
                $data['is_active']
                ?? true;

            $data['account_status'] = 'active';
            $data['gdpr_request_status'] = null;

            return $data;
        }

        /*
         * ========================================================
         * PODKORISNIK KOJEG KREIRA SUPERADMIN
         * ========================================================
         */
        $parentId = isset(
            $data['parent_user_id']
        )
            ? (int) $data['parent_user_id']
            : 0;

        if ($parentId <= 0) {
            throw ValidationException::withMessages([
                'parent_user_id' =>
                    'Za podkorisnika morate odabrati glavnog korisnika organizacije.',
            ]);
        }

        $owner = User::query()
            ->whereKey($parentId)
            ->where(
                'role',
                'org_admin'
            )
            ->where(
                'is_active',
                true
            )
            ->withoutTrashed()
            ->first();

        if (! $owner) {
            throw ValidationException::withMessages([
                'parent_user_id' =>
                    'Odabrani glavni korisnik organizacije nije valjan ili nije aktivan.',
            ]);
        }

        if (! $owner->canAddMoreSubusers()) {
            throw ValidationException::withMessages([
                'parent_user_id' =>
                    'Odabrana organizacija već ima maksimalan broj podkorisnika.',
            ]);
        }

        $data['parent_user_id'] =
            $owner->id;

        $data['organization_name'] =
            $owner->organization_name;

        $data['role'] = 'org_user';
        $data['is_admin'] = false;

        /*
         * Podkorisnik nikada ne upravlja drugim
         * podkorisnicima.
         */
        $data['can_manage_subusers'] =
            false;

        $data['is_active'] =
            $data['is_active']
            ?? true;

        $data['account_status'] =
            'active';

        $data['gdpr_request_status'] =
            null;

        /*
         * Podkorisnik nema vlastiti storage limit.
         * Dijeli storage organizacije.
         */
        unset(
            $data['storage_quota_mb']
        );

        /*
         * Superadmin ne određuje granularna prava
         * podkorisnika na ovom mjestu.
         *
         * NULL znači puna prava dok ih glavni
         * korisnik kasnije ne definira.
         */
        unset(
            $data['module_permissions']
        );

        /*
         * quick_actions podkorisnika se ne koriste kao
         * njegov vlastiti tenant modul-popis.
         *
         * Module dobiva preko owner()->quick_actions.
         */
        $data['quick_actions'] = null;

        return $data;
    }

    /*
     * ============================================================
     * GLAVNI KORISNIK ORGANIZACIJE
     * ============================================================
     *
     * Može kreirati isključivo podkorisnike
     * vlastite organizacije.
     */
    if (! $authUser->isOrgAdmin()) {
        throw ValidationException::withMessages([
            'email' =>
                'Nemate ovlasti za kreiranje korisnika.',
        ]);
    }

    if (! $authUser->canCreateSubusers()) {
        throw ValidationException::withMessages([
            'email' =>
                'Nemate ovlasti za dodavanje podkorisnika.',
        ]);
    }

    $owner = $authUser->owner();

    if (! $owner->canAddMoreSubusers()) {
        Notification::make()
            ->title(
                'Dosegnut je limit podkorisnika.'
            )
            ->body(
                'Organizacija može imati najviše '
                . User::MAX_SUBUSERS_PER_ORGANIZATION
                . ' podkorisnika.'
            )
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'email' =>
                'Dosegnut je maksimalan broj podkorisnika za ovu organizaciju.',
        ]);
    }

    /*
     * Ownership se postavlja SERVER-SIDE.
     *
     * Vrijednosti poslane iz forme ne mogu
     * promijeniti organizaciju podkorisnika.
     */
    $data['parent_user_id'] =
        $owner->id;

    $data['organization_name'] =
        $owner->organization_name;

    $data['role'] = 'org_user';
    $data['is_admin'] = false;

    $data['can_manage_subusers'] =
        false;

    $data['is_active'] = true;

    $data['account_status'] =
        'active';

    $data['gdpr_request_status'] =
        null;

    /*
     * Podkorisnik module dobiva od organizacije.
     */
    $data['quick_actions'] = null;

    /*
     * Glavni korisnik određuje granularne dozvole
     * samo za šest CONTROLLED_MODULES modula.
     */
    $data['module_permissions'] =
        static::normalizeModulePermissions(
            $data['module_permissions']
                ?? User::defaultModulePermissions(),
            $owner
        );

    /*
     * Podkorisnik ne smije imati svoj storage limit.
     */
    unset(
        $data['storage_quota_mb']
    );

    return $data;
}

    public static function mutateFormDataBeforeSave(array $data): array
{
    $data = static::mergeQuickActions($data);

    $authUser = Auth::user();

    if (! $authUser) {
        throw ValidationException::withMessages([
            'email' => 'Korisnik nije prijavljen.',
        ]);
    }

    /*
     * ============================================================
     * GLAVNI KORISNIK ORGANIZACIJE
     * ============================================================
     *
     * On smije mijenjati granularne dozvole
     * svojih podkorisnika.
     *
     * Ne smije mijenjati:
     * - ownership
     * - role
     * - organization_name
     * - aktivnost računa
     * - storage
     * - pravne/admin podatke.
     */
    if ($authUser->isOrgAdmin()) {
        $data['module_permissions'] =
            static::normalizeModulePermissions(
                $data['module_permissions']
                    ?? [],
                $authUser->owner()
            );

        unset(
            $data['storage_quota_mb'],
            $data['quick_actions'],
            $data['can_manage_subusers'],
            $data['is_active'],
            $data['account_status'],
            $data['gdpr_request_status'],
            $data['gdpr_request_processed_at'],
            $data['organization_name'],
            $data['role'],
            $data['parent_user_id'],
            $data['accepted_terms_at'],
            $data['accepted_privacy_at'],
            $data['terms_version'],
            $data['privacy_version'],
            $data['newsletter_opt_in']
        );

        return $data;
    }

    /*
     * ============================================================
     * SUPERADMIN
     * ============================================================
     */
    if ($authUser->isSuperAdmin()) {
        /*
         * Superadmin račun se kroz obični User obrazac
         * ne pretvara u drugu ulogu.
         *
         * Za ostale korisnike dopuštene su samo
         * org_admin i org_user uloge.
         */
        $role =
            $data['role']
            ?? null;

        if (
            $role !== null
            && ! in_array(
                $role,
                [
                    'org_admin',
                    'org_user',
                ],
                true
            )
        ) {
            /*
             * Postojeći superadmin može imati svoj
             * zaključani role select koji nije dehydrated.
             * Zato provjeravamo samo ako je role stvarno
             * stigao iz forme.
             */
            throw ValidationException::withMessages([
                'role' =>
                    'Odabrana uloga nije dopuštena.',
            ]);
        }

        /*
         * Ako uređujemo običnog korisnika i
         * odabran je org_admin:
         *
         * parent mora biti NULL.
         */
        if ($role === 'org_admin') {
            $data['parent_user_id'] = null;

            /*
             * Org admin ne koristi granularne
             * module_permissions.
             */
            unset(
                $data['module_permissions']
            );

            return $data;
        }

        /*
         * Ako je odabran podkorisnik,
         * mora imati stvarnog glavnog korisnika.
         */
        if ($role === 'org_user') {
            $parentId = isset(
                $data['parent_user_id']
            )
                ? (int) $data['parent_user_id']
                : 0;

            if ($parentId <= 0) {
                throw ValidationException::withMessages([
                    'parent_user_id' =>
                        'Za podkorisnika morate odabrati glavnog korisnika organizacije.',
                ]);
            }

            $owner = User::query()
                ->whereKey($parentId)
                ->where(
                    'role',
                    'org_admin'
                )
                ->where(
                    'is_active',
                    true
                )
                ->withoutTrashed()
                ->first();

            if (! $owner) {
                throw ValidationException::withMessages([
                    'parent_user_id' =>
                        'Odabrani glavni korisnik organizacije nije valjan ili nije aktivan.',
                ]);
            }

            $data['parent_user_id'] =
                $owner->id;

            /*
             * Naziv organizacije uvijek dolazi
             * od stvarnog ownera.
             */
            $data['organization_name'] =
                $owner->organization_name;

            /*
             * Podkorisnik nikada ne smije
             * upravljati drugim podkorisnicima.
             */
            $data['can_manage_subusers'] =
                false;

            /*
             * Podkorisnik dijeli storage
             * glavnog korisnika.
             */
            unset(
                $data['storage_quota_mb']
            );

            /*
             * Njegove granularne dozvole ne uređuje
             * superadmin kroz ovaj dio sustava.
             */
            unset(
                $data['module_permissions']
            );

            $data['quick_actions'] = null;
        } else {
            /*
             * Ako role nije stigao iz forme,
             * ne diramo postojeći ownership.
             *
             * To je važno za zaključane role forme.
             */
            unset(
                $data['module_permissions']
            );
        }

        return $data;
    }

    /*
     * Nepoznata uloga nema pravo uređivati Users modul.
     * Dodatna fail-closed zaštita.
     */
    throw ValidationException::withMessages([
        'email' =>
            'Nemate ovlasti za uređivanje korisnika.',
    ]);
}
protected static function getUserRelatedRecords(User $record): array
{
    $database = DB::getDatabaseName();

    /*
     * Pronalazi stvarne strane ključeve koji pokazuju na users.id.
     */
    $foreignKeys = DB::select(
        '
        SELECT TABLE_NAME, COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = ?
          AND REFERENCED_TABLE_NAME = ?
          AND REFERENCED_COLUMN_NAME = ?
        ',
        [$database, 'users', 'id']
    );

    /*
     * Dodatno provjerava stupce koji se u aplikaciji koriste
     * i kada nije postavljen pravi foreign key.
     */
    $conventionalColumns = DB::select(
        '
        SELECT TABLE_NAME, COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ?
          AND COLUMN_NAME IN (
              "user_id",
              "owner_id",
              "parent_user_id",
              "created_by",
              "updated_by",
              "assigned_to",
              "responsible_user_id"
          )
        ',
        [$database]
    );

    $references = collect($foreignKeys)
        ->merge($conventionalColumns)
        ->unique(fn ($item) => $item->TABLE_NAME . '.' . $item->COLUMN_NAME);

    $relatedRecords = [];

    foreach ($references as $reference) {
        $table = $reference->TABLE_NAME;
        $column = $reference->COLUMN_NAME;

        /*
         * Ne provjeravamo users.id jer je to sam korisnik.
         * users.parent_user_id se normalno provjerava zbog podkorisnika.
         */
        if ($table === 'users' && $column === 'id') {
            continue;
        }

        try {
            $count = DB::table($table)
                ->where($column, $record->id)
                ->count();

            if ($count > 0) {
                $relatedRecords[] = [
                    'table' => $table,
                    'column' => $column,
                    'count' => $count,
                ];
            }
        } catch (\Throwable $exception) {
            report($exception);

            /*
             * Ako provjera neke tablice ne uspije, iz sigurnosnih razloga
             * tretiramo je kao zapreku za brisanje.
             */
            $relatedRecords[] = [
                'table' => $table,
                'column' => $column,
                'count' => null,
            ];
        }
    }

    return $relatedRecords;
}

    protected static function userDeletionBlockReason(User $record): ?string
{
    if ($record->isSuperAdmin()) {
        return 'Superadmin se ne može trajno izbrisati.';
    }

    if ((int) $record->id === (int) Auth::id()) {
        return 'Ne možete trajno izbrisati korisnički račun s kojim ste trenutačno prijavljeni.';
    }

    $relatedRecords = static::getUserRelatedRecords($record);

    if (empty($relatedRecords)) {
        return null;
    }

    $details = collect($relatedRecords)
        ->take(5)
        ->map(function (array $item): string {
            if ($item['count'] === null) {
                return $item['table'] . '.' . $item['column'];
            }

            return $item['table']
                . '.'
                . $item['column']
                . ' (' . $item['count'] . ')';
        })
        ->implode(', ');

    return 'Korisnik ima povezane podatke i ne može se trajno izbrisati. '
        . 'Pronađene veze: '
        . $details
        . (count($relatedRecords) > 5 ? ' i druge.' : '.');
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