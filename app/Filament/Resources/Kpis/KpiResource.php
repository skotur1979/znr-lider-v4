<?php

namespace App\Filament\Resources\Kpis;

use App\Filament\Resources\Kpis\Pages;
use App\Filament\Resources\Kpis\RelationManagers\KpiValuesRelationManager;
use App\Models\Kpi;
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
use Filament\Resources\Resource;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Str;
use UnitEnum;

class KpiResource extends Resource
{
    protected static ?string $model = Kpi::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|UnitEnum|null $navigationGroup = 'Upravljanje';
    protected static ?string $navigationLabel = 'KPI';
    protected static ?string $pluralModelLabel = 'KPI';
    protected static ?string $modelLabel = 'KPI';
    protected static ?int $navigationSort = 30;
    protected static ?string $recordTitleAttribute = 'name';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    private static function isAdminUser($user): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $user->id === 1) {
            return true;
        }

        try {
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        if (isset($user->is_admin) && (bool) $user->is_admin) {
            return true;
        }

        return false;
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

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv KPI-a')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('category')
                    ->label('Kategorija')
                    ->badge()
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('unit')
                    ->label('Jedinica')
                    ->badge()
                    ->alignment(Alignment::Center),

                TextColumn::make('target_value')
                    ->label('Cilj')
                    ->formatStateUsing(fn ($state, Kpi $record) => $record->formatNumberOnly($state))
                    ->sortable(),

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
                    ->alignment(Alignment::Center),

                IconColumn::make('is_active')
                    ->label('Aktivan')
                    ->boolean()
                    ->alignment(Alignment::Center),

                IconColumn::make('show_on_dashboard')
                    ->label('Dashboard')
                    ->boolean()
                    ->alignment(Alignment::Center),

                TextColumn::make('latest_value')
                    ->label('Zadnja vrijednost')
                    ->state(fn (Kpi $record) => $record->latestValue()?->value)
                    ->formatStateUsing(fn ($state, Kpi $record) => $record->formatNumberOnly($state)),

                TextColumn::make('current_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Kpi $record) => $record->current_status)
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
                    }),
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
                    ViewAction::make()->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (Kpi $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(fn (Kpi $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (Kpi $record) => method_exists($record, 'trashed') && $record->trashed()),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (Kpi $record) => method_exists($record, 'trashed') && $record->trashed()),
                ])
                    ->icon(Heroicon::EllipsisVertical)
                    ->label(''),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
                    ->requiresConfirmation()
                    ->visible(fn (HasTable $livewire) => ! self::isOnlyTrashed($livewire)),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->requiresConfirmation()
                    ->visible(fn (HasTable $livewire) => self::isOnlyTrashed($livewire)),

                ForceDeleteBulkAction::make()
                    ->label('Trajno obriši označeno')
                    ->requiresConfirmation(),
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        if (self::isAdminUser($user)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereNull('user_id');
        });
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            if (! DbSchema::hasTable('kpis')) {
                return null;
            }

            $user = auth()->user();

            if (! $user) {
                return '0';
            }

            $query = static::getModel()::query();

            if (! self::isAdminUser($user)) {
                $query->where(function (Builder $q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereNull('user_id');
                });
            }

            return (string) $query->count();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    private static function isOnlyTrashed(HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('status');
        $value = data_get($state, 'value');

        return $value === 'trashed';
    }
}