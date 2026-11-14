<?php

namespace App\Filament\Resources\Kpis;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Kpis\Pages;
use App\Filament\Resources\Kpis\RelationManagers\KpiValuesRelationManager;
use App\Models\Kpi;
use App\Models\KpiValue;
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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class KpiResource extends BaseResource
{
    protected static ?string $model = Kpi::class;

    protected static bool $usesSoftDeletes = true;

    /**
     * KPI je iznimka:
     * superadmin smije kreirati globalne KPI definicije.
     */
    protected static bool $superAdminCanCreate = true;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup =
        'Upravljanje';

    protected static ?string $navigationLabel = 'KPI';

    protected static ?string $pluralModelLabel = 'KPI';

    protected static ?string $modelLabel = 'KPI';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    protected static function getModuleKey(): ?string
    {
        return 'kpis';
    }

    public static function resolveOwnerId(): ?int
    {
        return static::ownerId()
            ?: static::defaultUserId();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('slug')
                ->dehydrateStateUsing(
                    fn ($state, callable $get) =>
                        filled($state)
                            ? $state
                            : Str::slug(
                                (string) $get('name')
                            )
                ),

            Section::make('Osnovni podaci')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextInput::make('name')
                        ->label('Naziv KPI-a')
                        ->required()
                        ->maxLength(255),

                    Select::make('category')
                        ->label('Kategorija')
                        ->options([
                            'ZNR' => 'ZNR',
                            'Okoliš' => 'Okoliš',
                            'Energija' => 'Energija',
                            'Kvaliteta' => 'Kvaliteta',
                            '5S' => '5S',
                            'Ostalo' => 'Ostalo',
                        ])
                        ->searchable()
                        ->required(),

                    TextInput::make('unit')
                        ->label('Jedinica')
                        ->placeholder(
                            'broj, kg, kWh, sati...'
                        )
                        ->maxLength(50),

                    TextInput::make('sort_order')
                        ->label('Redoslijed')
                        ->numeric()
                        ->default(0),

                    Toggle::make('is_active')
                        ->label('Aktivan')
                        ->default(true),

                    Toggle::make('show_on_dashboard')
                        ->label(
                            'Prikazuj na KPI dashboardu'
                        )
                        ->default(true),
                ]),

            Section::make('Cilj i izračun')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextInput::make('target_value')
                        ->label('Cilj')
                        ->numeric()
                        ->step('0.0001'),

                    TextInput::make('warning_offset')
                        ->label(
                            'Tolerancija upozorenja'
                        )
                        ->numeric()
                        ->step('0.0001')
                        ->helperText(
                            'Za upozorenje prije prelaska cilja.'
                        ),

                    Select::make('direction')
                        ->label('Ocjena')
                        ->options([
                            'lower_better' =>
                                'Manje je bolje',

                            'higher_better' =>
                                'Više je bolje',

                            'target_value' =>
                                'Ciljana vrijednost',
                        ])
                        ->required(),

                    Select::make(
                        'calculation_type'
                    )
                        ->label('Tip')
                        ->options([
                            'manual' => 'Ručno',
                            'automatic' =>
                                'Automatski',
                            'formula' => 'Formula',
                        ])
                        ->live()
                        ->required(),

                    Select::make('source_key')
                        ->label(
                            'Automatski izvor / formula'
                        )
                        ->options([
                            'days_without_lta' =>
                                'Broj dana bez LTA',

                            'lta_count' =>
                                'Broj ozljeda LTA',

                            'lta_lost_days' =>
                                'Dani izgubljeni zbog LTA',

                            'near_miss_count' =>
                                'Near Miss',

                            'negative_observation_count' =>
                                'Negativna zapažanja',

                            'inspection_count' =>
                                'Interni nadzori',

                            'corrective_actions_open' =>
                                'Otvorene korektivne radnje',

                            'corrective_actions_closed' =>
                                'Zatvorene korektivne radnje',

                            'corrective_actions_in_progress' =>
                                'Korektivne radnje u tijeku',

                            'corrective_actions_delay_days' =>
                                'Dani kašnjenja korektivnih radnji',

                            'non_hazardous_waste_kg' =>
                                'Neopasni otpad',

                            'hazardous_waste_kg' =>
                                'Opasni otpad',

                            'municipal_waste_kg' =>
                                'Miješani komunalni otpad',

                            'afr' =>
                                'AFR formula',

                            'asr' =>
                                'ASR formula',
                        ])
                        ->searchable()
                        ->visible(
                            fn (callable $get): bool =>
                                in_array(
                                    $get(
                                        'calculation_type'
                                    ),
                                    [
                                        'automatic',
                                        'formula',
                                    ],
                                    true
                                )
                        ),

                    Textarea::make('formula_text')
                        ->label(
                            'Opis formule / napomena'
                        )
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Opis')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('description')
                        ->label('Opis')
                        ->rows(4),
                ]),
        ]);
    }

    public static function infolist(
        Schema $schema
    ): Schema {
        return $schema->components([]);
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
                TextColumn::make('name')
                    ->label('Naziv KPI-a')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap()
                    ->formatStateUsing(
                        function (
                            string $state,
                            Kpi $record
                        ): string {
                            return match (
                                $record->source_key
                            ) {
                                'afr' =>
                                    'AFR – Stopa učestalosti ozljeda',

                                'asr' =>
                                    'ASR – Stopa težine ozljeda',

                                default => $state,
                            };
                        }
                    )
                    ->tooltip(
                        function (
                            Kpi $record
                        ): ?string {
                            return match (
                                $record->source_key
                            ) {
                                'afr' =>
                                    'Accident Frequency Rate',

                                'asr' =>
                                    'Accident Severity Rate',

                                default => null,
                            };
                        }
                    )
                    ->toggleable(),

                static::userTableColumn(),

                TextColumn::make('category')
                    ->label('Kategorija')
                    ->badge()
                    ->sortable()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make('unit')
                    ->label('Jedinica')
                    ->badge()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make(
                    'effective_target'
                )
                    ->label('Cilj')
                    ->state(
                        fn (Kpi $record) =>
                            $record
                                ->effectiveTargetValue(
                                    static::resolveOwnerId()
                                )
                    )
                    ->formatStateUsing(
                        fn (
                            $state,
                            Kpi $record
                        ) =>
                            $record
                                ->formatNumberOnly(
                                    $state
                                )
                    )
                    ->toggleable(),

                TextColumn::make(
                    'calculation_type'
                )
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            string $state
                        ): string =>
                            match ($state) {
                                'manual' =>
                                    'Ručno',

                                'automatic' =>
                                    'Automatski',

                                'formula' =>
                                    'Formula',

                                default =>
                                    $state,
                            }
                    )
                    ->color(
                        fn (
                            string $state
                        ): string =>
                            match ($state) {
                                'manual' =>
                                    'gray',

                                'automatic' =>
                                    'success',

                                'formula' =>
                                    'warning',

                                default =>
                                    'gray',
                            }
                    )
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktivan')
                    ->boolean()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                IconColumn::make(
                    'show_on_dashboard'
                )
                    ->label('Dashboard')
                    ->boolean()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),

                TextColumn::make(
                    'latest_value'
                )
                    ->label(
                        'Zadnja vrijednost'
                    )
                    ->state(
                        fn (Kpi $record) =>
                            static::latestVisibleKpiValue(
                                $record
                            )?->value
                    )
                    ->formatStateUsing(
                        fn (
                            $state,
                            Kpi $record
                        ) =>
                            $record
                                ->formatNumberOnly(
                                    $state
                                )
                    )
                    ->toggleable(),

                TextColumn::make(
                    'current_status'
                )
                    ->label('Status')
                    ->badge()
                    ->state(
                        function (
                            Kpi $record
                        ): string {
                            $value =
                                static::latestVisibleKpiValue(
                                    $record
                                )?->value;

                            return $record
                                ->evaluateStatus(
                                    $value,
                                    static::resolveOwnerId()
                                );
                        }
                    )
                    ->formatStateUsing(
                        fn (
                            string $state
                        ): string =>
                            match ($state) {
                                'success' =>
                                    'U cilju',

                                'warning' =>
                                    'Upozorenje',

                                'danger' =>
                                    'Izvan cilja',

                                default =>
                                    'Bez cilja',
                            }
                    )
                    ->color(
                        fn (
                            string $state
                        ): string =>
                            match ($state) {
                                'success' =>
                                    'success',

                                'warning' =>
                                    'warning',

                                'danger' =>
                                    'danger',

                                default =>
                                    'gray',
                            }
                    )
                    ->toggleable(),
            ])
            ->defaultSort(
                'sort_order'
            )
            ->recordUrl(
                fn (
                    Kpi $record
                ): string =>
                    static::getUrl(
                        'view',
                        [
                            'record' =>
                                $record,
                        ]
                    )
            )
            ->filters([
                SelectFilter::make('status')
                    ->label('Status zapisa')
                    ->placeholder(
                        'Odaberi status'
                    )
                    ->options([
                        'active' =>
                            'Aktivni zapisi',

                        'trashed' =>
                            'Obrisani zapisi',

                        'all' =>
                            'Svi zapisi',
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ) {
                            $value =
                                $data['value']
                                ?? null;

                            return match (
                                $value
                            ) {
                                'trashed' =>
                                    $query
                                        ->onlyTrashed(),

                                'all' =>
                                    $query
                                        ->withTrashed(),

                                default =>
                                    $query
                                        ->withoutTrashed(),
                            };
                        }
                    ),

                SelectFilter::make(
                    'category'
                )
                    ->label('Kategorija')
                    ->options([
                        'ZNR' => 'ZNR',
                        'Okoliš' => 'Okoliš',
                        'Energija' => 'Energija',
                        'Kvaliteta' =>
                            'Kvaliteta',
                        '5S' => '5S',
                        'Ostalo' => 'Ostalo',
                    ]),

                SelectFilter::make(
                    'calculation_type'
                )
                    ->label('Tip izračuna')
                    ->options([
                        'manual' => 'Ručno',
                        'automatic' =>
                            'Automatski',
                        'formula' =>
                            'Formula',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(
                            function (
                                Kpi $record
                            ): bool {
                                if (
                                    auth()
                                        ->user()
                                        ?->isSuperAdmin()
                                ) {
                                    return true;
                                }

                                /*
                                 * Organizacija može
                                 * uređivati samo svoje KPI-je.
                                 */
                                return filled(
                                    $record->user_id
                                );
                            }
                        ),

                    DeleteAction::make()
                        ->label('Obriši')
                        ->icon(
                            'heroicon-o-trash'
                        )
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(
                            'Obriši KPI'
                        )
                        ->modalDescription(
                            'Jesi li siguran/a da želiš obrisati ovaj KPI? Obrisani ručno dodani KPI možeš kasnije vratiti kroz filter obrisanih zapisa.'
                        )
                        ->modalSubmitActionLabel(
                            'Obriši'
                        )
                        ->visible(
                            function (Kpi $record): bool {
                                /*
                                * Superadmin smije upravljati
                                * globalnim KPI definicijama.
                                */
                                if (
                                    auth()
                                        ->user()
                                        ?->isSuperAdmin()
                                ) {
                                    return true;
                                }

                                /*
                                * Organizacija nikada ne smije
                                * obrisati globalni KPI.
                                */
                                if (blank($record->user_id)) {
                                    return false;
                                }

                                /*
                                * Smiju se brisati samo
                                * ručno dodani KPI-jevi.
                                */
                                if (
                                    $record->calculation_type
                                    !== 'manual'
                                ) {
                                    return false;
                                }

                                /*
                                * Sistemski KPI mora ostati zaštićen.
                                *
                                * Posebno:
                                * Ukupan broj odrađenih radnih sati
                                * potreban je za AFR i ASR.
                                */
                                if (
                                    static::isProtectedSystemKpi(
                                        $record
                                    )
                                ) {
                                    return false;
                                }

                                /*
                                * KPI mora pripadati trenutnoj
                                * organizaciji.
                                */
                                return (int) $record->user_id
                                    === (int) static::resolveOwnerId();
                            }
                        ),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(
                            function (
                                Kpi $record
                            ): bool {
                                if (
                                    ! $record
                                        ->trashed()
                                ) {
                                    return false;
                                }

                                if (
                                    auth()
                                        ->user()
                                        ?->isSuperAdmin()
                                ) {
                                    return true;
                                }

                                if (
                                    blank(
                                        $record->user_id
                                    )
                                ) {
                                    return false;
                                }

                                return ! static::
                                    isProtectedSystemKpi(
                                        $record
                                    );
                            }
                        ),

                    ForceDeleteAction::make()
                        ->label(
                            'Trajno obriši'
                        )
                        ->requiresConfirmation()
                        ->modalHeading(
                            'Trajno obriši KPI'
                        )
                        ->modalDescription(
                            'Jesi li siguran/a da želiš trajno obrisati ovaj KPI? Ova radnja se ne može poništiti.'
                        )
                        ->modalSubmitActionLabel(
                            'Trajno obriši'
                        )
                        ->visible(
                            function (
                                Kpi $record
                            ): bool {
                                if (
                                    ! $record
                                        ->trashed()
                                ) {
                                    return false;
                                }

                                if (
                                    auth()
                                        ->user()
                                        ?->isSuperAdmin()
                                ) {
                                    return true;
                                }

                                if (
                                    blank(
                                        $record->user_id
                                    )
                                ) {
                                    return false;
                                }

                                if (
                                    static::isProtectedSystemKpi(
                                        $record
                                    )
                                ) {
                                    return false;
                                }

                                return true;
                            }
                        ),
                ])
                    ->icon(
                        Heroicon::EllipsisVertical
                    )
                    ->label(''),
            ])
            ->bulkActions([
                /*
                 * Bulk brisanje ostavljamo samo
                 * superadminu kako korisnik slučajno
                 * ne bi označio sistemske KPI-je.
                 */
                DeleteBulkAction::make()
                    ->label(
                        'Obriši označeno'
                    )
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                            auth()
                                ->user()
                                ?->isSuperAdmin()
                            === true
                    ),

                RestoreBulkAction::make()
                    ->label(
                        'Vrati označeno'
                    )
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                            auth()
                                ->user()
                                ?->isSuperAdmin()
                            === true
                    ),

                ForceDeleteBulkAction::make()
                    ->label(
                        'Trajno obriši označeno'
                    )
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                            auth()
                                ->user()
                                ?->isSuperAdmin()
                            === true
                    ),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            KpiValuesRelationManager::class,
        ];
    }

    public static function canView(
        Model $record
    ): bool {
        if (
            ! static::hasModuleActionPermission(
                'view'
            )
        ) {
            return false;
        }

        if (static::isSuperAdmin()) {
            return true;
        }

        /*
        * Zapis mora biti dio KPI skupa koji
        * je vidljiv trenutnoj organizaciji.
        */
        if (
            ! static::getEloquentQuery()
                ->whereKey(
                    $record->getKey()
                )
                ->exists()
        ) {
            return false;
        }

        /*
        * Vidljivi globalni KPI je organizaciji
        * dopušten samo za pregled.
        */
        if (blank($record->user_id)) {
            return true;
        }

        return parent::canView(
            $record
        );
    }

    public static function canEdit(
        Model $record
    ): bool {
        if (static::isSuperAdmin()) {
            return true;
        }

        /*
        * Organizacija nikada ne uređuje
        * globalnu KPI definiciju.
        */
        if (blank($record->user_id)) {
            return false;
        }

        if (
            (int) $record->user_id
                !==
            (int) static::resolveOwnerId()
        ) {
            return false;
        }

        return parent::canEdit(
            $record
        );
    }

    public static function canDelete(
        Model $record
    ): bool {
        if (static::isSuperAdmin()) {
            return true;
        }

        if (
            blank($record->user_id)
            || (int) $record->user_id
                !==
            (int) static::resolveOwnerId()
        ) {
            return false;
        }

        /*
        * Organizacija smije obrisati samo
        * vlastiti ručno dodani KPI.
        */
        if (
            $record->calculation_type
                !== 'manual'
        ) {
            return false;
        }

        if (
            static::isProtectedSystemKpi(
                $record
            )
        ) {
            return false;
        }

        return parent::canDelete(
            $record
        );
    }

    public static function canRestore(
        Model $record
    ): bool {
        if (static::isSuperAdmin()) {
            return true;
        }

        if (
            blank($record->user_id)
            || (int) $record->user_id
                !==
            (int) static::resolveOwnerId()
        ) {
            return false;
        }

        if (
            $record->calculation_type
                !== 'manual'
            || static::isProtectedSystemKpi(
                $record
            )
        ) {
            return false;
        }

        return parent::canRestore(
            $record
        );
    }

    public static function canForceDelete(
        Model $record
    ): bool {
        if (static::isSuperAdmin()) {
            return true;
        }

        if (
            blank($record->user_id)
            || (int) $record->user_id
                !==
            (int) static::resolveOwnerId()
        ) {
            return false;
        }

        if (
            $record->calculation_type
                !== 'manual'
            || static::isProtectedSystemKpi(
                $record
            )
        ) {
            return false;
        }

        return parent::canForceDelete(
            $record
        );
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListKpis::route('/'),

            'create' =>
                Pages\CreateKpi::route(
                    '/create'
                ),

            'dashboard' =>
                Pages\KpiDashboard::route(
                    '/dashboard'
                ),

            'reports' =>
                Pages\KpiReports::route(
                    '/reports'
                ),

            'bulk-entry' =>
                Pages\BulkKpiEntry::route(
                    '/bulk-entry'
                ),

            'view' =>
                Pages\ViewKpi::route(
                    '/{record}'
                ),

            'edit' =>
                Pages\EditKpi::route(
                    '/{record}/edit'
                ),
        ];
    }

    /**
     * Sistemski KPI source_key-evi.
     *
     * Ove KPI-je korisnik organizacije ne smije
     * slučajno obrisati jer ih aplikacija koristi
     * za automatske izračune, Dashboard i izvještaje.
     */
    protected static function protectedSystemSourceKeys(): array
    {
        return [
            'days_without_lta',
            'lta_count',
            'lta_lost_days',

            'near_miss_count',
            'negative_observation_count',

            'inspection_count',

            'corrective_actions_delay_days',
            'corrective_actions_open',
            'corrective_actions_closed',
            'corrective_actions_in_progress',

            'non_hazardous_waste_kg',
            'hazardous_waste_kg',
            'municipal_waste_kg',

            'afr',
            'asr',
        ];
    }

    /**
     * KPI koji aplikacija smatra sistemskim.
     *
     * Uz automatske/formula KPI-je štitimo i
     * "Ukupan broj odrađenih radnih sati" jer je
     * potreban za AFR i ASR.
     */
    protected static function isProtectedSystemKpi(
        Kpi $record
    ): bool {
        if (
            filled($record->source_key)
            && in_array(
                $record->source_key,
                static::protectedSystemSourceKeys(),
                true
            )
        ) {
            return true;
        }

        return in_array(
            trim((string) $record->name),
            [
                'Ukupan broj odrađenih radnih sati',
                'Ukupno odrađenih radnih sati',
            ],
            true
        );
    }

    /**
     * Source key-evi za koje organizacija koristi
     * vlastitu kopiju KPI definicije.
     *
     * Globalnu kopiju tih KPI-ja ne prikazujemo
     * organizacijskom korisniku.
     */
    protected static function protectedAutomaticSourceKeys(): array
    {
        return [
            'near_miss_count',
            'negative_observation_count',

            'inspection_count',

            'corrective_actions_delay_days',
            'corrective_actions_open',
            'corrective_actions_closed',
            'corrective_actions_in_progress',

            'non_hazardous_waste_kg',
            'hazardous_waste_kg',
            'municipal_waste_kg',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $model = static::getModel();

        $query = $model::query()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        if (static::isSuperAdmin()) {
            return $query;
        }

        $ownerId = static::ownerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            function (Builder $query) use ($ownerId): void {
                $query
                    ->where(
                        'user_id',
                        $ownerId
                    )
                    ->orWhere(
                        function (Builder $global) use ($ownerId): void {
                            $global
                                ->whereNull('user_id')
                                ->whereNotExists(
                                    function ($sub) use ($ownerId): void {
                                        $sub
                                            ->selectRaw('1')
                                            ->from('kpis as org_kpis')
                                            ->where(
                                                'org_kpis.user_id',
                                                $ownerId
                                            )
                                            ->whereNull(
                                                'org_kpis.deleted_at'
                                            )
                                            ->where(
                                                function ($match): void {
                                                    $match
                                                        ->where(
                                                            function ($bySource): void {
                                                                $bySource
                                                                    ->whereNotNull(
                                                                        'kpis.source_key'
                                                                    )
                                                                    ->whereColumn(
                                                                        'org_kpis.source_key',
                                                                        'kpis.source_key'
                                                                    );
                                                            }
                                                        )
                                                        ->orWhere(
                                                            function ($byName): void {
                                                                $byName
                                                                    ->whereNull(
                                                                        'kpis.source_key'
                                                                    )
                                                                    ->whereColumn(
                                                                        'org_kpis.name',
                                                                        'kpis.name'
                                                                    );
                                                            }
                                                        );
                                                }
                                            );
                                    }
                                );
                        }
                    );
            }
        );
    }

    protected static function latestVisibleKpiValue(
        Kpi $record
    ): ?KpiValue {
        /*
        * Superadmin nema vlastitu organizacijsku
        * KPI vrijednost.
        *
        * Sve organizacijske vrijednosti može
        * pregledati kroz relation manager.
        */
        if (static::isSuperAdmin()) {
            return null;
        }

        $ownerId =
            static::resolveOwnerId();

        if (! $ownerId) {
            return null;
        }

        return KpiValue::query()
            ->where(
                'kpi_id',
                $record->id
            )
            ->where(
                'user_id',
                $ownerId
            )
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withoutTrashed();

        if (static::isSuperAdmin()) {
            return (string) $query->count();
        }

        $ownerId = static::ownerId();

        if (! $ownerId) {
            return '0';
        }

        $query->where(
            function (Builder $query) use ($ownerId): void {
                $query
                    ->where(
                        'user_id',
                        $ownerId
                    )
                    ->orWhere(
                        function (Builder $global) use ($ownerId): void {
                            $global
                                ->whereNull('user_id')
                                ->whereNotExists(
                                    function ($sub) use ($ownerId): void {
                                        $sub
                                            ->selectRaw('1')
                                            ->from('kpis as org_kpis')
                                            ->where(
                                                'org_kpis.user_id',
                                                $ownerId
                                            )
                                            ->whereNull(
                                                'org_kpis.deleted_at'
                                            )
                                            ->where(
                                                function ($match): void {
                                                    $match
                                                        ->where(
                                                            function ($bySource): void {
                                                                $bySource
                                                                    ->whereNotNull(
                                                                        'kpis.source_key'
                                                                    )
                                                                    ->whereColumn(
                                                                        'org_kpis.source_key',
                                                                        'kpis.source_key'
                                                                    );
                                                            }
                                                        )
                                                        ->orWhere(
                                                            function ($byName): void {
                                                                $byName
                                                                    ->whereNull(
                                                                        'kpis.source_key'
                                                                    )
                                                                    ->whereColumn(
                                                                        'org_kpis.name',
                                                                        'kpis.name'
                                                                    );
                                                            }
                                                        );
                                                }
                                            );
                                    }
                                );
                        }
                    );
            }
        );

        return (string) $query->count();
    }
}