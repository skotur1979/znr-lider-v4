<?php

namespace App\Filament\Resources\PpeLogs;

use App\Filament\Resources\PpeLogs\PPELogResource\Pages;
use App\Filament\Resources\PpeLogs\PPELogResource\RelationManagers\ItemsRelationManager;
use App\Models\Employee;
use App\Models\PPELog;

use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DbSchema;

use UnitEnum;
use BackedEnum;

// ✅ ACTIONS (kao u tvom TestResource stilu)
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;

class PPELogResource extends Resource
{
    protected static ?string $model = PPELog::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';
    protected static string|UnitEnum|null $navigationGroup = 'Zaposlenici';
    protected static ?string $navigationLabel = 'Upisnik OZO';

    protected static ?string $modelLabel = 'OZO';
    protected static ?string $pluralModelLabel = 'Osobna zaštitna oprema';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
{
    $q = parent::getEloquentQuery()->with(['items']);

    if (Auth::user()?->isAdmin()) return $q;

    if (DbSchema::hasColumn((new PPELog)->getTable(), 'user_id')) {
        $q->where('user_id', Auth::id());
    }

    return $q;
}

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Podaci o zaposleniku')
                ->schema([
                    Select::make('employee_lookup')
                        ->label('Pronađi zaposlenika (ime ili OIB)')
                        ->placeholder('')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            $table = (new Employee)->getTable();
                            $hasOIBUpper = DbSchema::hasColumn($table, 'OIB');
                            $hasOIBLower = DbSchema::hasColumn($table, 'oib');

                            return Employee::query()
                                ->when(strlen($search) > 0, function ($q) use ($search, $hasOIBUpper, $hasOIBLower) {
                                    $q->where(function ($qq) use ($search, $hasOIBUpper, $hasOIBLower) {
                                        $qq->where('name', 'like', "%{$search}%");
                                        if ($hasOIBUpper) $qq->orWhere('OIB', 'like', "%{$search}%");
                                        if ($hasOIBLower) $qq->orWhere('oib', 'like', "%{$search}%");
                                    });
                                })
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(function ($e) {
                                    $fullName = $e->name ?? 'Zaposlenik';
                                    $oib = $e->OIB ?? $e->oib ?? '';
                                    return [$e->id => $fullName . ($oib ? " ({$oib})" : '')];
                                })
                                ->toArray();
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) return;

                            $emp = Employee::find($state);
                            if (! $emp) return;

                            $set('user_last_name', $emp->name ?? '');
                            $set('user_oib', $emp->OIB ?? $emp->oib ?? null);
                            $set('workplace', $emp->workplace ?? null);
                            $set('organization_unit', $emp->organization_unit ?? null);
                        })
                        ->dehydrated(false)
                        ->columnSpanFull(),

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
                            if (! $state || strlen($state) < 3) return;

                            $table = (new Employee)->getTable();
                            $hasOIBUpper = DbSchema::hasColumn($table, 'OIB');
                            $hasOIBLower = DbSchema::hasColumn($table, 'oib');

                            $emp = Employee::query()
                                ->when($hasOIBUpper, fn ($q) => $q->orWhere('OIB', $state))
                                ->when($hasOIBLower, fn ($q) => $q->orWhere('oib', $state))
                                ->first();

                            if (! $emp) return;

                            if (! $get('user_last_name')) $set('user_last_name', $emp->name ?? '');
                            if (! $get('workplace')) $set('workplace', $emp->workplace ?? null);
                            if (! $get('organization_unit')) $set('organization_unit', $emp->organization_unit ?? null);
                        }),

                    TextInput::make('workplace')
                        ->label('Radno mjesto')
                        ->maxLength(255),

                    TextInput::make('organization_unit')
                        ->label('Organizacijska jedinica')
                        ->maxLength(255),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->with('items'))
        ->columns([
    TextColumn::make('user_last_name')->label('Ime i prezime')->searchable(),
    TextColumn::make('user_oib')->label('OIB')->alignCenter(),

    ViewColumn::make('nazivi')
        ->label('Naziv OZO')
        ->alignCenter()
        ->view('filament.columns.ozo-nazivi'),

    ViewColumn::make('izdano')
        ->label('Izdano')
        ->view('filament.columns.ozo-izdano')
        ->extraAttributes(['class' => 'whitespace-nowrap align-middle']),

    ViewColumn::make('istek')
        ->label('Istek')
        ->view('filament.columns.ozo-items-expiring')
        ->extraAttributes(['class' => 'whitespace-nowrap align-middle']),
])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('pregled')
                    ->label('Prikaz')
                    ->options([
                        'svi' => 'Svi zaposlenici',
                        'istek' => 'Samo OZO s istekom u 30 dana',
                        'deaktivirani' => 'Deaktivirani',
                    ])
                    ->placeholder('')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'svi') {
                            'istek' => $query
                                ->withoutTrashed()
                                ->whereHas('items', function ($subQuery) {
                                    $subQuery->whereNotNull('end_date')
                                        ->whereBetween('end_date', [now(), now()->addDays(30)]);
                                }),
                            'deaktivirani' => $query->onlyTrashed(),
                            'svi' => $query->withoutTrashed(),
                            default => $query->whereRaw('0=1'),
                        };
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

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
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
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

    public static function getNavigationBadge(): ?string
    {
        try {
            $q = static::getModel()::query();

            if (! Auth::user()?->isAdmin() && DbSchema::hasColumn((new PPELog)->getTable(), 'user_id')) {
                $q->where('user_id', Auth::id());
            }

            return (string) $q->count();
        } catch (\Throwable) {
            return null;
        }
    }
    
}