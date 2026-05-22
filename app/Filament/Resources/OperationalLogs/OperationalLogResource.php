<?php

namespace App\Filament\Resources\OperationalLogs;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\OperationalLogs\Pages;
use App\Models\OperationalLog;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class OperationalLogResource extends BaseResource
{
    protected static ?string $model = OperationalLog::class;

    protected static bool $hasOwnership = false;

    protected static bool $usesSoftDeletes = false;

    protected static function getModuleKey(): ?string
    {
        return 'operational_logs';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static ?string $slug = 'operativni-dnevnik';
    protected static ?string $navigationLabel = 'Operativni dnevnik';
    protected static ?string $modelLabel = 'zapis dnevnika';
    protected static ?string $pluralModelLabel = 'operativni dnevnik';
    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(fn () => Auth::id())
                ->dehydrated(),

            Section::make('Operativni dnevnik')
                ->description('Jedan dnevni unos s više natuknica. Označene natuknice automatski se spremaju kao radni zadaci.')
                ->columns(12)
                ->schema([
                    DatePicker::make('log_date')
                        ->label('Datum')
                        ->default(now())
                        ->required()
                        ->native(false)
                        ->displayFormat('d.m.Y.')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 4,
                            'xl' => 3,
                        ]),

                    Repeater::make('items')
                        ->label('Bilješke / natuknice')
                        ->schema([
                            Textarea::make('note')
                                ->label('Bilješka')
                                ->rows(2)
                                ->required()
                                ->placeholder('Npr. vagan otpad, obaviješten radnik za rukavice, nazvati dr. medicine rada...')
                                ->columnSpan([
                                    'default' => 12,
                                    'md' => 9,
                                ]),

                            Checkbox::make('create_task')
                                ->label('Radni zadatak')
                                ->helperText('Označi ako ova bilješka treba ići u Radne zadatke.')
                                ->columnSpan([
                                    'default' => 12,
                                    'md' => 3,
                                ]),
                        ])
                        ->columns(12)
                        ->defaultItems(3)
                        ->minItems(1)
                        ->addActionLabel('Dodaj još bilješku')
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 'all'])
            ->defaultSort('log_date', 'desc')
            ->groups([
                Group::make('log_date')
                    ->label('Dan')
                    ->date(),
            ])
            ->columns([
    TextColumn::make('log_date')
        ->label('Datum')
        ->date('d.m.Y.')
        ->sortable()
        ->toggleable(),

    static::userTableColumn()
        ->toggleable(),

    TextColumn::make('items_count')
        ->label('Bilješke')
        ->badge()
        ->getStateUsing(fn (OperationalLog $record): string => (string) $record->itemsCount())
        ->color('info')
        ->toggleable(),

    TextColumn::make('items_preview')
        ->label('Sažetak')
        ->getStateUsing(function (OperationalLog $record): string {
            $items = collect($record->items ?? [])
                ->pluck('note')
                ->filter()
                ->take(3)
                ->implode(' • ');

            return $items ?: '-';
        })
        ->limit(160)
        ->wrap()
        ->searchable(query: function (Builder $query, string $search): Builder {
            return $query->where('items', 'like', "%{$search}%");
        })
        ->toggleable(),

    TextColumn::make('tasks_count')
        ->label('Radni zadaci')
        ->badge()
        ->getStateUsing(fn (OperationalLog $record): string => (string) $record->tasksCount())
        ->color(fn (OperationalLog $record): string => $record->tasksCount() > 0 ? 'warning' : 'gray')
        ->toggleable(),

    TextColumn::make('created_at')
        ->label('Uneseno')
        ->dateTime('d.m.Y. H:i')
        ->sortable()
        ->toggleable(isToggledHiddenByDefault: true),
])
            ->filters([
                Filter::make('log_date')
                    ->label('Datum')
                    ->form([
                        DatePicker::make('from')
                            ->label('Od')
                            ->native(false)
                            ->displayFormat('d.m.Y.'),

                        DatePicker::make('until')
                            ->label('Do')
                            ->native(false)
                            ->displayFormat('d.m.Y.'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('log_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('log_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Prikaži'),

                EditAction::make()
                    ->label('Uredi'),

                DeleteAction::make()
                    ->label('Obriši'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Obriši označeno')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    if (Auth::user()?->isSuperAdmin()) {
        return $query;
    }

    return $query->where('user_id', Auth::id());
}

public static function getRecordRouteBindingEloquentQuery(): Builder
{
    return static::getEloquentQuery();
}

public static function getGlobalSearchEloquentQuery(): Builder
{
    return static::getEloquentQuery();
}

public static function getNavigationBadge(): ?string
{
    $userId = auth()->id() ?? 'guest';

    $cacheKey = 'operational_logs_badge_'
        . $userId
        . '_'
        . now()->format('Y-m-d-H');

    return cache()->remember($cacheKey, now()->addMinutes(5), function () {
        return (string) static::getEloquentQuery()->count();
    });
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperationalLogs::route('/'),
            'create' => Pages\CreateOperationalLog::route('/create'),
            'edit' => Pages\EditOperationalLog::route('/{record}/edit'),
            'view' => Pages\ViewOperationalLog::route('/{record}'),
        ];
    }
}