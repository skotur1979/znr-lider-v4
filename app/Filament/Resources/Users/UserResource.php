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
                ->options([
                    'org_admin' => 'Glavni korisnik organizacije',
                    'org_user' => 'Podkorisnik organizacije',
                ])
                ->default(fn () => $authUser?->isSuperAdmin() ? 'org_admin' : 'org_user')
                ->required()
                ->native(false)
                ->disabled(fn (): bool => ! Auth::user()?->isSuperAdmin())
                ->dehydrated(fn (): bool => Auth::user()?->isSuperAdmin())
                ->helperText(fn (): ?string =>
                    Auth::user()?->isSuperAdmin()
                        ? 'Superadmin može promijeniti ulogu korisnika.'
                        : 'Ulogu korisnika može promijeniti samo superadmin.'
                ),

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
        if (! $authUser?->isSuperAdmin()) {
        $owner = $authUser->owner();

        if (! $owner->canAddMoreSubusers()) {
            Notification::make()
                ->title('Dosegnut je limit podkorisnika.')
                ->body('Organizacija može imati najviše ' . User::MAX_SUBUSERS_PER_ORGANIZATION . ' podkorisnika.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'email' => 'Dosegnut je maksimalan broj podkorisnika za ovu organizaciju.',
            ]);
        }
    }

        if ($authUser?->isSuperAdmin()) {
            $data['is_admin'] = false;
            $data['role'] = $data['role'] ?? 'org_admin';
            $data['is_active'] = $data['is_active'] ?? true;
            $data['account_status'] = 'active';
            $data['gdpr_request_status'] = null;

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
        $data['account_status'] = 'active';
        $data['gdpr_request_status'] = null;
        $data['quick_actions'] = null;

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data = static::mergeQuickActions($data);

        $authUser = Auth::user();

        if (! $authUser?->isSuperAdmin()) {
            unset($data['storage_quota_mb']);
            unset($data['quick_actions']);
            unset($data['can_manage_subusers']);
            unset($data['is_active']);
            unset($data['account_status']);
            unset($data['gdpr_request_status']);
            unset($data['gdpr_request_processed_at']);
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