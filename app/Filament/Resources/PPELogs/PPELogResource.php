<?php

namespace App\Filament\Resources\PPELogs;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\PPELogs\Pages;
use App\Filament\Resources\PPELogs\RelationManagers\ItemsRelationManager;
use App\Models\Employee;
use App\Models\PPELog;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;
use UnitEnum;
use Filament\Support\Enums\MaxWidth;

class PPELogResource extends BaseResource
{
    protected static ?string $model = PPELog::class;

    protected static bool $usesSoftDeletes = true;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';
    protected static string|UnitEnum|null $navigationGroup = 'Zaposlenici';
    protected static ?string $navigationLabel = 'Upisnik OZO';
    protected static ?string $modelLabel = 'OZO';
    protected static ?string $pluralModelLabel = 'Osobna zaštitna oprema';
    protected static ?int $navigationSort = 3;

    protected static function getModuleKey(): ?string
    {
        return 'ppe_logs';
    }
    public static function getMaxContentWidth(): MaxWidth|string|null
{
    return MaxWidth::Full;
}
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'items' => fn ($query) => $query
                    ->whereNull('return_date')
                    ->orderBy('end_date'),
            ]);
    }

    public static function form(Schema $schema): Schema
{
    return $schema
        ->schema([
            Section::make('Podaci o zaposleniku')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('employee_lookup')
                        ->label('Zaposlenik')
                        ->searchable()
                        ->live()
                        ->getSearchResultsUsing(function (string $search): array {
                            $query = Employee::query()
                                ->where(function ($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%")
                                        ->orWhere('OIB', 'like', "%{$search}%")
                                        ->orWhere('oib', 'like', "%{$search}%");
                                })
                                ->orderBy('name')
                                ->limit(50);

                            if (! static::isSuperAdmin()) {
                                $query->where('user_id', static::ownerId());
                            }

                            return $query->get()
                                ->mapWithKeys(fn (Employee $employee) => [
                                    $employee->id => "{$employee->name} ({$employee->oib})",
                                ])
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            if (! $value) {
                                return null;
                            }

                            $employee = Employee::find($value);

                            return $employee ? "{$employee->name} ({$employee->oib})" : null;
                        })
                        ->afterStateUpdated(function ($state, $set) {
                            if (! $state) {
                                $set('user_id', null);
                                $set('user_last_name', null);
                                $set('user_oib', null);
                                $set('workplace', null);
                                $set('organization_unit', null);

                                return;
                            }

                            $employee = Employee::find($state);

                            if (! $employee) {
                                return;
                            }

                            $set('user_id', $employee->id);
                            $set('user_last_name', $employee->name);
                            $set('user_oib', $employee->oib);
                            $set('workplace', $employee->workplace);
                            $set('organization_unit', $employee->organization_unit);
                        })
                        ->dehydrated(false),

                    TextInput::make('user_last_name')
                        ->label('Prezime i ime')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('user_oib')
                        ->label('OIB')
                        ->required()
                        ->maxLength(11)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (! $state || strlen($state) < 3) {
                                return;
                            }

                            $table = (new Employee())->getTable();
                            $hasOIBUpper = DbSchema::hasColumn($table, 'OIB');
                            $hasOIBLower = DbSchema::hasColumn($table, 'oib');

                            $employee = Employee::query()
                                ->when($hasOIBUpper, fn ($query) => $query->orWhere('OIB', $state))
                                ->when($hasOIBLower, fn ($query) => $query->orWhere('oib', $state))
                                ->first();

                            if (! $employee) {
                                return;
                            }

                            if (! $get('user_last_name')) {
                                $set('user_last_name', $employee->name ?? '');
                            }

                            if (! $get('workplace')) {
                                $set('workplace', $employee->workplace ?? null);
                            }

                            if (! $get('organization_unit')) {
                                $set('organization_unit', $employee->organization_unit ?? null);
                            }
                        }),

                    TextInput::make('workplace')
                        ->label('Radno mjesto')
                        ->maxLength(255),

                    TextInput::make('organization_unit')
                        ->label('Organizacijska jedinica')
                        ->maxLength(255),
                ]),
        ])
        ->columns(1);
}

    public static function table(Table $table): Table
    {
        return $table
        ->paginated([10, 25, 50, 100,'all'])
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'items' => fn ($subQuery) => $subQuery
                    ->whereNull('return_date')
                    ->orderBy('end_date'),
            ]))
            ->columns([
    TextColumn::make('user_last_name')
        ->label('Ime i prezime')
        ->searchable()
        ->extraAttributes([
            'style' => 'vertical-align: top;',
        ])
        ->toggleable(),

    static::userTableColumn()
        ->toggleable(),

    TextColumn::make('user_oib')
        ->label('OIB')
        ->alignCenter()
        ->extraAttributes([
            'style' => 'vertical-align: top;',
        ])
        ->toggleable(),

    ViewColumn::make('nazivi')
        ->label('Naziv OZO')
        ->view('filament.columns.ozo-nazivi')
        ->extraAttributes([
            'style' => 'vertical-align: top; min-width: 260px;',
        ])
        ->toggleable(),

    ViewColumn::make('izdano')
        ->label('Izdano')
        ->view('filament.columns.ozo-izdano')
        ->extraAttributes([
            'style' => 'vertical-align: top; min-width: 120px;',
        ])
        ->toggleable(),

    ViewColumn::make('istek')
        ->label('Istek')
        ->view('filament.columns.ozo-items-expiring')
        ->extraAttributes([
            'style' => 'vertical-align: top; min-width: 150px;',
        ])
        ->toggleable(),
])
            ->filters([
                SelectFilter::make('pregled')
                    ->label('Prikaz')
                    ->options([
                        'svi' => 'Svi zaposlenici',
                        'isteklo' => 'Samo istekli OZO',
                        'istek' => 'Samo OZO s istekom u 30 dana',
                        'deaktivirani' => 'Deaktivirani',
                    ])
                    ->placeholder('')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'svi') {
                            'isteklo' => $query
                                ->withoutTrashed()
                                ->whereHas('items', function ($subQuery) {
                                                                       $subQuery->whereNull('return_date')
                                        ->whereNotNull('end_date')
                                        ->whereDate('end_date', '<', now()->startOfDay());
                                }),
                            'istek' => $query
                                ->withoutTrashed()
                                ->whereHas('items', function ($subQuery) {
                                        $subQuery->whereNull('return_date')
                                        ->whereNotNull('end_date')
                                        ->whereBetween('end_date', [now()->startOfDay(), now()->copy()->addDays(30)->endOfDay()]);
                                }),
                            'deaktivirani' => $query->onlyTrashed(),
                            default => $query->withoutTrashed(),
                        };
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn ($record) => ! $record->trashed()),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn ($record) => ! $record->trashed()),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->visible(fn ($record) => $record->trashed()),

                    ForceDeleteAction::make()
                        ->label('Trajno izbriši')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn ($record) => $record->trashed()),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno'),

                RestoreBulkAction::make()
                    ->label('Vrati označeno'),

                ForceDeleteBulkAction::make()
                    ->label('Trajno izbriši označeno'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPPELogs::route('/'),
            'create' => Pages\CreatePPELog::route('/create'),
            'edit' => Pages\EditPPELog::route('/{record}/edit'),
        ];
    }
}
