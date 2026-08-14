<?php

namespace App\Filament\Resources\OperationalLogs;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\OperationalLogs\Pages;
use App\Models\OperationalLog;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OperationalLogResource extends BaseResource
{
    protected static ?string $model = OperationalLog::class;

    /*
     * NAMJERNA IZNIMKA:
     *
     * Operativni dnevnik nije organizacijski zapis.
     * Svaki korisnik ima svoj privatni dnevnik.
     */
    protected static bool $hasOwnership = false;

    /*
     * OperationalLog model koristi SoftDeletes.
     */
    protected static bool $usesSoftDeletes = true;

    protected static function getModuleKey(): ?string
    {
        return 'operational_logs';
    }

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $slug =
        'operativni-dnevnik';

    protected static ?string $navigationLabel =
        'Operativni dnevnik';

    protected static ?string $modelLabel =
        'zapis dnevnika';

    protected static ?string $pluralModelLabel =
        'operativni dnevnik';

    protected static \UnitEnum|string|null $navigationGroup =
        'Upravljanje';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            /*
             * Dnevnik pripada TOČNO prijavljenom korisniku.
             * Ne koristimo ownerId().
             */
            Hidden::make('user_id')
                ->default(fn () => Auth::id())
                ->dehydrated(
                    fn (string $operation): bool =>
                        $operation === 'create'
                        && ! static::isSuperAdmin()
                ),

            Section::make('Operativni dnevnik')
                ->description(
                    'Jedan dnevni unos s više natuknica. '
                    . 'Označene natuknice automatski se spremaju kao radni zadaci.'
                )
                ->columns(1)
                ->schema([
                    DatePicker::make('log_date')
                        ->label('Datum')
                        ->default(now())
                        ->required()
                        ->native(false)
                        ->displayFormat('d.m.Y.')
                        ->format('Y-m-d')
                        ->columnSpanFull(),

                    Repeater::make('items')
                        ->label('Bilješke / natuknice')
                        ->schema([
                            Textarea::make('note')
                                ->label('Bilješka')
                                ->rows(3)
                                ->required()
                                ->placeholder(
                                    'Npr. vagan otpad, obaviješten radnik za rukavice, nazvati dr. medicine rada...'
                                )
                                ->columnSpanFull(),

                            Checkbox::make('create_task')
                                ->label('Radni zadatak')
                                ->helperText(
                                    'Označi ako ova bilješka treba ići u Radne zadatke.'
                                )
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
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
            ->paginated([
                10,
                25,
                50,
                'all',
            ])
            ->defaultSort(
                'log_date',
                'desc'
            )
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
                    ->getStateUsing(
                        fn (
                            OperationalLog $record
                        ): string =>
                            (string) $record->itemsCount()
                    )
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('items_preview')
                    ->label('Sažetak')
                    ->getStateUsing(
                        function (
                            OperationalLog $record
                        ): string {
                            $items = collect(
                                $record->items ?? []
                            )
                                ->pluck('note')
                                ->filter()
                                ->take(3)
                                ->implode(' • ');

                            return $items ?: '-';
                        }
                    )
                    ->limit(160)
                    ->wrap()
                    ->searchable(
                        query:
                            function (
                                Builder $query,
                                string $search
                            ): Builder {
                                return $query->where(
                                    'items',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                    )
                    ->toggleable(),

                TextColumn::make('tasks_count')
                    ->label('Radni zadaci')
                    ->badge()
                    ->getStateUsing(
                        fn (
                            OperationalLog $record
                        ): string =>
                            (string) $record->tasksCount()
                    )
                    ->color(
                        fn (
                            OperationalLog $record
                        ): string =>
                            $record->tasksCount() > 0
                                ? 'warning'
                                : 'gray'
                    )
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Uneseno')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([
                Filter::make('log_date')
                    ->label('Datum')
                    ->form([
                        DatePicker::make('from')
                            ->label('Od')
                            ->native(false)
                            ->displayFormat('d.m.Y.')
                            ->format('Y-m-d'),

                        DatePicker::make('until')
                            ->label('Do')
                            ->native(false)
                            ->displayFormat('d.m.Y.')
                            ->format('Y-m-d'),
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            return $query
                                ->when(
                                    $data['from'] ?? null,
                                    fn (
                                        Builder $query,
                                        $date
                                    ): Builder =>
                                        $query->whereDate(
                                            'log_date',
                                            '>=',
                                            $date
                                        )
                                )
                                ->when(
                                    $data['until'] ?? null,
                                    fn (
                                        Builder $query,
                                        $date
                                    ): Builder =>
                                        $query->whereDate(
                                            'log_date',
                                            '<=',
                                            $date
                                        )
                                );
                        }
                    ),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Prikaži'),

                EditAction::make()
                    ->label('Uredi')
                    ->visible(
                        fn (
                            OperationalLog $record
                        ): bool =>
                            static::canEdit($record)
                            && ! $record->trashed()
                    ),

                DeleteAction::make()
                    ->label('Obriši')
                    ->requiresConfirmation()
                    ->visible(
                        fn (
                            OperationalLog $record
                        ): bool =>
                            static::canDelete($record)
                            && ! $record->trashed()
                    ),

                RestoreAction::make()
                    ->label('Vrati')
                    ->requiresConfirmation()
                    ->visible(
                        fn (OperationalLog $record): bool =>
                            $record->trashed()
                            && static::canRestore($record)
                    ),

                ForceDeleteAction::make()
                    ->label('Trajno izbriši')
                    ->requiresConfirmation()
                    ->visible(
                        fn (OperationalLog $record): bool =>
                            $record->trashed()
                            && static::canForceDelete($record)
                    ),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Obriši označeno')
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                            ! static::isSuperAdmin()
                    )
                    ->deselectRecordsAfterCompletion(),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->visible(
                        fn (): bool =>
                            ! static::isSuperAdmin()
                    ),

                ForceDeleteBulkAction::make()
                    ->label('Trajno izbriši označeno')
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                            ! static::isSuperAdmin()
                    ),
            ]);
    }

    /**
     * Posebna ownership logika:
     *
     * Superadmin:
     * vidi sve osobne dnevnike.
     *
     * Ostali korisnici:
     * vide isključivo dnevnik čiji je user_id
     * jednak njihovom vlastitom Auth::id().
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::isSuperAdmin()) {
            return $query;
        }

        $userId = Auth::id();

        if (! $userId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            'user_id',
            $userId
        );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        if (! static::canViewModule()) {
            return parent::getGlobalSearchEloquentQuery()
                ->whereRaw('1 = 0');
        }

        return static::getEloquentQuery();
    }

    /**
     * Operativni dnevnik može kreirati samo
     * obični prijavljeni korisnik za sebe.
     *
     * Superadmin ne kreira osobne dnevnike
     * u ime drugih korisnika.
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return false;
        }

        return parent::canCreate();
    }

    /**
     * Uređivanje:
     *
     * - superadmin NE uređuje tuđe osobne dnevnike
     * - korisnik uređuje samo vlastiti dnevnik
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return false;
        }

        return (int) $record->user_id === (int) $user->id
            && parent::canEdit($record);
    }

    /**
     * Brisanje:
     *
     * - superadmin nema pravo brisanja
     * - korisnik briše samo vlastiti dnevnik
     */
    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return false;
        }

        return (int) $record->user_id === (int) $user->id
            && parent::canDelete($record);
    }

    /**
     * Vraćanje soft-deleted zapisa:
     *
     * samo vlasnik vlastitog dnevnika.
     */
    public static function canRestore(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return false;
        }

        return (int) $record->user_id === (int) $user->id
            && parent::canRestore($record);
    }

    /**
     * Trajno brisanje:
     *
     * samo vlasnik vlastitog dnevnika.
     */
    public static function canForceDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return false;
        }

        return (int) $record->user_id === (int) $user->id
            && parent::canForceDelete($record);
    }

    /**
     * Bulk zaštite.
     *
     * Superadmin može pregledavati sve,
     * ali ne smije bulk brisati/vraćati/trajno brisati.
     */
    public static function canDeleteAny(): bool
    {
        return ! static::isSuperAdmin()
            && parent::canDeleteAny();
    }

    public static function canRestoreAny(): bool
    {
        return ! static::isSuperAdmin()
            && parent::canRestoreAny();
    }

    public static function canForceDeleteAny(): bool
    {
        return ! static::isSuperAdmin()
            && parent::canForceDeleteAny();
    }

    /**
     * Badge prati isti osobni scope.
     *
     * Superadmin vidi broj svih aktivnih osobnih dnevnika.
     * Korisnik samo svojih.
     */
    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()
            ->whereNull('deleted_at')
            ->count();
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListOperationalLogs::route('/'),

            'create' =>
                Pages\CreateOperationalLog::route('/create'),

            'edit' =>
                Pages\EditOperationalLog::route(
                    '/{record}/edit'
                ),

            'view' =>
                Pages\ViewOperationalLog::route(
                    '/{record}'
                ),
        ];
    }
}