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
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
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

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|UnitEnum|null $navigationGroup = 'Upravljanje';
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
        return static::ownerId() ?: static::defaultUserId();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('slug')
                ->dehydrateStateUsing(fn ($state, callable $get) => filled($state) ? $state : Str::slug((string) $get('name'))),

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
                        ->placeholder('broj, kg, kWh, sati...')
                        ->maxLength(50),

                    TextInput::make('sort_order')
                        ->label('Redoslijed')
                        ->numeric()
                        ->default(0),

                    Toggle::make('is_active')
                        ->label('Aktivan')
                        ->default(true),

                    Toggle::make('show_on_dashboard')
                        ->label('Prikazuj na KPI dashboardu')
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
                        ->label('Tolerancija upozorenja')
                        ->numeric()
                        ->step('0.0001')
                        ->helperText('Za upozorenje prije prelaska cilja.'),

                    Select::make('direction')
                        ->label('Ocjena')
                        ->options([
                            'lower_better' => 'Manje je bolje',
                            'higher_better' => 'Više je bolje',
                            'target_value' => 'Ciljana vrijednost',
                        ])
                        ->required(),

                    Select::make('calculation_type')
                        ->label('Tip')
                        ->options([
                            'manual' => 'Ručno',
                            'automatic' => 'Automatski',
                            'formula' => 'Formula',
                        ])
                        ->live()
                        ->required(),

                    Select::make('source_key')
                        ->label('Automatski izvor / formula')
                        ->options([
                            'days_without_lta' => 'Broj dana bez LTA',
                            'lta_count' => 'Broj ozljeda LTA',
                            'lta_lost_days' => 'Dani izgubljeni zbog LTA',
                            'near_miss_count' => 'Near Miss',
                            'negative_observation_count' => 'Negativna zapažanja',
                            'inspection_count' => 'Interni nadzori',
                            'corrective_actions_open' => 'Otvorene korektivne radnje',
                            'corrective_actions_closed' => 'Zatvorene korektivne radnje',
                            'corrective_actions_in_progress' => 'Korektivne radnje u tijeku',
                            'corrective_actions_delay_days' => 'Dani kašnjenja korektivnih radnji',
                            'non_hazardous_waste_kg' => 'Neopasni otpad',
                            'hazardous_waste_kg' => 'Opasni otpad',
                            'municipal_waste_kg' => 'Miješani komunalni otpad',
                            'afr' => 'AFR formula',
                            'asr' => 'ASR formula',
                        ])
                        ->searchable()
                        ->visible(fn (callable $get) => in_array($get('calculation_type'), ['automatic', 'formula'], true)),

                    Textarea::make('formula_text')
                        ->label('Opis formule / napomena')
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->paginated([10, 25, 50,'all'])
            ->columns([
            TextColumn::make('name')
            ->label('Naziv KPI-a')
            ->searchable()
            ->sortable()
            ->weight('bold')
            ->wrap()
            ->formatStateUsing(function (string $state, Kpi $record): string {
                return match ($record->source_key) {
                    'afr' => 'AFR – Stopa učestalosti ozljeda',
                    'asr' => 'ASR – Stopa težine ozljeda',
                    default => $state,
                };
            })
            ->tooltip(function (Kpi $record): ?string {
                return match ($record->source_key) {
                    'afr' => 'Accident Frequency Rate',
                    'asr' => 'Accident Severity Rate',
                    default => null,
                };
            })
            ->toggleable(),

       static::userTableColumn(),

    TextColumn::make('category')
        ->label('Kategorija')
        ->badge()
        ->sortable()
        ->alignment(Alignment::Center)
        ->toggleable(),

    TextColumn::make('unit')
        ->label('Jedinica')
        ->badge()
        ->alignment(Alignment::Center)
        ->toggleable(),

    TextColumn::make('effective_target')
        ->label('Cilj')
        ->state(fn (Kpi $record) => $record->effectiveTargetValue(static::resolveOwnerId()))
        ->formatStateUsing(fn ($state, Kpi $record) => $record->formatNumberOnly($state))
        ->toggleable(),

    TextColumn::make('calculation_type')
        ->label('Tip')
        ->badge()
        ->formatStateUsing(fn (string $state): string => match ($state) {
            'manual' => 'Ručno',
            'automatic' => 'Automatski',
            'formula' => 'Formula',
            default => $state,
        })
        ->color(fn (string $state): string => match ($state) {
            'manual' => 'gray',
            'automatic' => 'success',
            'formula' => 'warning',
            default => 'gray',
        })
        ->alignment(Alignment::Center)
        ->toggleable(),

    IconColumn::make('is_active')
        ->label('Aktivan')
        ->boolean()
        ->alignment(Alignment::Center)
        ->toggleable(),

    IconColumn::make('show_on_dashboard')
        ->label('Dashboard')
        ->boolean()
        ->alignment(Alignment::Center)
        ->toggleable(isToggledHiddenByDefault: true),

        TextColumn::make('latest_value')
        ->label('Zadnja vrijednost')
        ->state(fn (Kpi $record) => static::latestVisibleKpiValue($record)?->value)
        ->formatStateUsing(fn ($state, Kpi $record) => $record->formatNumberOnly($state))
        ->toggleable(),

        TextColumn::make('current_status')
        ->label('Status')
        ->badge()
        ->state(function (Kpi $record): string {
            $value = static::latestVisibleKpiValue($record)?->value;

            return $record->evaluateStatus($value, static::resolveOwnerId());
        })
        ->formatStateUsing(fn (string $state): string => match ($state) {
            'success' => 'U cilju',
            'warning' => 'Upozorenje',
            'danger' => 'Izvan cilja',
            default => 'Bez cilja',
        })
        ->color(fn (string $state): string => match ($state) {
            'success' => 'success',
            'warning' => 'warning',
            'danger' => 'danger',
            default => 'gray',
        })
        ->toggleable(),
    ])
            ->defaultSort('sort_order')
            ->recordUrl(fn (Kpi $record): string => static::getUrl('view', ['record' => $record]))
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

                SelectFilter::make('category')
                    ->label('Kategorija')
                    ->options([
                        'ZNR' => 'ZNR',
                        'Okoliš' => 'Okoliš',
                        'Energija' => 'Energija',
                        'Kvaliteta' => 'Kvaliteta',
                        '5S' => '5S',
                        'Ostalo' => 'Ostalo',
                    ]),

                SelectFilter::make('calculation_type')
                    ->label('Tip izračuna')
                    ->options([
                        'manual' => 'Ručno',
                        'automatic' => 'Automatski',
                        'formula' => 'Formula',
                    ]),
            ])
            ->actions([
        ActionGroup::make([
            ViewAction::make()
                ->label('Prikaži'),

            EditAction::make()
                ->label('Uredi')
                ->visible(function (Kpi $record): bool {
                    if (auth()->user()?->isSuperAdmin()) {
                        return true;
                    }

                    // Organizacijske KPI-e korisnici mogu uređivati
                    return filled($record->user_id);
                }),

            DeleteAction::make()
                ->label('Deaktiviraj')
                ->requiresConfirmation()
                ->visible(function (Kpi $record): bool {
                    if (auth()->user()?->isSuperAdmin()) {
                        return true;
                    }

                    // Organizacijski automatski KPI-i se ne smiju deaktivirati
                    if (static::isProtectedAutomaticKpi($record)) {
                        return false;
                    }

                    // Organizacijski ručni KPI-i se mogu deaktivirati
                    return filled($record->user_id);
                }),

            RestoreAction::make()
                ->label('Vrati')
                ->requiresConfirmation()
                ->visible(function (Kpi $record): bool {
                    if (! $record->trashed()) {
                        return false;
                    }

                    if (auth()->user()?->isSuperAdmin()) {
                        return true;
                    }

                    return filled($record->user_id);
                }),

            ForceDeleteAction::make()
                ->label('Trajno obriši')
                ->requiresConfirmation()
                ->visible(function (Kpi $record): bool {
                    if (! $record->trashed()) {
                        return false;
                    }

                    if (auth()->user()?->isSuperAdmin()) {
                        return true;
                    }

                    // Zaštićeni automatski KPI-i se nikada ne brišu
                    if (static::isProtectedAutomaticKpi($record)) {
                        return false;
                    }

                    return filled($record->user_id);
                }),
        ])
            ->icon(Heroicon::EllipsisVertical)
            ->label(''),
    ])
                ->bulkActions([
                    DeleteBulkAction::make()
                        ->label('Deaktiviraj označeno')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()?->isSuperAdmin()),

                    RestoreBulkAction::make()
                        ->label('Vrati označeno')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()?->isSuperAdmin()),

                    ForceDeleteBulkAction::make()
                        ->label('Trajno obriši označeno')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()?->isSuperAdmin()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            KpiValuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKpis::route('/'),
            'create' => Pages\CreateKpi::route('/create'),
            'dashboard' => Pages\KpiDashboard::route('/dashboard'),
            'reports' => Pages\KpiReports::route('/reports'),
            'bulk-entry' => Pages\BulkKpiEntry::route('/bulk-entry'),
            'view' => Pages\ViewKpi::route('/{record}'),
            'edit' => Pages\EditKpi::route('/{record}/edit'),
        ];
    }
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
    protected static function isProtectedAutomaticKpi(Kpi $record): bool
{
    return $record->calculation_type === 'automatic'
        && in_array(
            $record->source_key,
            static::protectedAutomaticSourceKeys(),
            true
        );
}
    public static function getEloquentQuery(): Builder
{
    $model = static::getModel();

    $query = $model::query();

    $query->withoutGlobalScopes([
        \Illuminate\Database\Eloquent\SoftDeletingScope::class,
    ]);

    if (static::isSuperAdmin()) {
        return $query;
    }

    $ownerId = static::ownerId();

    if (! $ownerId) {
        return $query->whereRaw('1 = 0');
    }

    return $query->where(function (Builder $q) use ($ownerId): void {
        $q->where('user_id', $ownerId)
            ->orWhere(function (Builder $global): void {
                $global
                    ->whereNull('user_id')
                    ->where(function (Builder $source): void {
                        $source
                            ->whereNull('source_key')
                            ->orWhereNotIn(
                                'source_key',
                                static::protectedAutomaticSourceKeys()
                            );
                    });
            });
    });
}
protected static function latestVisibleKpiValue(Kpi $record): ?KpiValue
{
    $query = KpiValue::query()
        ->where('kpi_id', $record->id)
        ->orderByDesc('year')
        ->orderByDesc('month');

    if (! static::isSuperAdmin()) {
        $ownerId = static::resolveOwnerId();

        if (! $ownerId) {
            return null;
        }

        $query->where('user_id', $ownerId);
    }

    return $query->first();
}
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
{
    $query = static::getModel()::query()
        ->withoutGlobalScopes([
            \Illuminate\Database\Eloquent\SoftDeletingScope::class,
        ])
        ->withoutTrashed();

    if (! static::isSuperAdmin()) {
        $ownerId = static::ownerId();

        if (! $ownerId) {
            return '0';
        }

        $query->where(function (Builder $q) use ($ownerId): void {
            $q->where('user_id', $ownerId)
                ->orWhere(function (Builder $global): void {
                    $global
                        ->whereNull('user_id')
                        ->where(function (Builder $source): void {
                            $source
                                ->whereNull('source_key')
                                ->orWhereNotIn(
                                    'source_key',
                                    static::protectedAutomaticSourceKeys()
                                );
                        });
                });
        });
    }

    return (string) $query->count();
}
}