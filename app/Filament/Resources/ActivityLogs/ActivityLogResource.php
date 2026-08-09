<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages;
use App\Filament\Resources\BaseResource;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityLogResource extends BaseResource
{
    protected static ?string $model = ActivityLog::class;

    /**
     * Activity log nema standardni poslovni ownership preko user_id.
     *
     * Koristi:
     * user_id  = korisnik koji je napravio aktivnost
     * owner_id = organizacija kojoj aktivnost pripada
     *
     * Zato ima vlastiti tenant query.
     */
    protected static bool $hasOwnership = false;

    protected static BackedEnum|string|null $navigationIcon =
        'heroicon-o-bell-alert';

    protected static string|UnitEnum|null $navigationGroup =
        'Upravljanje';

    protected static ?string $navigationLabel =
        'Zadnje aktivnosti';

    protected static ?string $modelLabel =
        'Aktivnost';

    protected static ?string $pluralModelLabel =
        'Zadnje aktivnosti';

    protected static ?int $navigationSort = 99;

    /**
     * Activity log nije jedan od šest kontroliranih
     * poslovnih modula.
     */
    protected static function getModuleKey(): ?string
    {
        return null;
    }

    /**
     * Svaki prijavljeni korisnik može otvoriti
     * modul aktivnosti.
     *
     * Query ispod određuje koje zapise smije vidjeti.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    /**
     * Activity log se nikada ne kreira ručno.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Activity log se nikada ne uređuje ručno.
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Samo superadmin smije pojedinačno brisati
     * zapise aktivnosti.
     */
    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    /**
     * Dodatna zaštita bulk brisanja.
     */
    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    /**
     * POSEBNA MULTI-TENANT LOGIKA ACTIVITY LOGA.
     *
     * Superadmin:
     * - vidi sve aktivnosti.
     *
     * Glavni korisnik:
     * - vidi sve aktivnosti svoje organizacije,
     *   bez obzira smije li dodavati podkorisnike.
     *
     * Podkorisnik:
     * - vidi samo vlastite aktivnosti.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = ActivityLog::query()
            ->with([
                'user',
                'owner',
            ])
            ->latest();

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isOrgAdmin()) {
            return $query->where(
                'owner_id',
                $user->ownerId()
            );
        }

        if ($user->isOrgUser()) {
            return $query->where(
                'user_id',
                $user->id
            );
        }

        /**
         * Fail closed za nepoznatu ulogu.
         */
        return $query->whereRaw('1 = 0');
    }

    /**
     * Direktni pristup zapisima mora koristiti
     * isti tenant-safe query.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $cacheKey =
            'activity_log_badge_'
            . $user->id
            . '_'
            . now()->format('Y-m-d-H');

        return cache()->remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($user): string {
                $query = ActivityLog::query();

                if ($user->isSuperAdmin()) {
                    return (string) $query->count();
                }

                if ($user->isOrgAdmin()) {
                    return (string) $query
                        ->where(
                            'owner_id',
                            $user->ownerId()
                        )
                        ->count();
                }

                if ($user->isOrgUser()) {
                    return (string) $query
                        ->where(
                            'user_id',
                            $user->id
                        )
                        ->count();
                }

                return '0';
            }
        );
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(
                'created_at',
                'desc'
            )
            ->paginated([
                10,
                25,
                50,
                100,
            ])
            ->columns([
                TextColumn::make('created_at')
                    ->label('Vrijeme')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Korisnik')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('module')
                    ->label('Modul')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('action')
                    ->label('Radnja')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            match ($state) {
                                'created' =>
                                    'Kreirano',

                                'updated' =>
                                    'Uređeno',

                                'deleted' =>
                                    'Obrisano',

                                'import' =>
                                    'Import',

                                'export' =>
                                    'Export',

                                'status' =>
                                    'Status',

                                'sent' =>
                                    'Poslano',

                                'login' =>
                                    'Prijava',

                                'logout' =>
                                    'Odjava',

                                'failed_login' =>
                                    'Neuspješna prijava',

                                default =>
                                    $state ?? '-',
                            }
                    )
                    ->color(
                        fn (?string $state): string =>
                            match ($state) {
                                'created' =>
                                    'success',

                                'updated' =>
                                    'warning',

                                'deleted' =>
                                    'danger',

                                'import' =>
                                    'info',

                                'export' =>
                                    'gray',

                                'status' =>
                                    'primary',

                                'sent' =>
                                    'info',

                                'login' =>
                                    'success',

                                'logout' =>
                                    'gray',

                                'failed_login' =>
                                    'danger',

                                default =>
                                    'gray',
                            }
                    )
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Opis aktivnosti')
                    ->searchable()
                    ->wrap()
                    ->weight('semibold')
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Detalji')
                    ->searchable()
                    ->wrap()
                    ->placeholder('-')
                    ->formatStateUsing(
                        function (
                            ?string $state,
                            ActivityLog $record
                        ): string {
                            $user = auth()->user();

                            if (! $user) {
                                return '-';
                            }

                            /**
                             * Superadmin vidi sve detalje.
                             */
                            if ($user->isSuperAdmin()) {
                                return filled($state)
                                    ? $state
                                    : '-';
                            }

                            /**
                             * Glavni korisnik vidi detalje
                             * aktivnosti svoje organizacije.
                             */
                            if (
                                $user->isOrgAdmin()
                                && (int) $record->owner_id
                                    === (int) $user->ownerId()
                            ) {
                                return filled($state)
                                    ? $state
                                    : '-';
                            }

                            /**
                             * Podkorisnik vidi detalje
                             * samo svojih aktivnosti.
                             *
                             * To uključuje i primatelje
                             * njegovih poslanih podsjetnika.
                             */
                            if (
                                $user->isOrgUser()
                                && (int) $record->user_id
                                    === (int) $user->id
                            ) {
                                return filled($state)
                                    ? $state
                                    : '-';
                            }

                            return '-';
                        }
                    )
                    ->tooltip(
                        function (
                            ActivityLog $record
                        ): ?string {
                            $user = auth()->user();

                            if (! $user) {
                                return null;
                            }

                            $canSeeDetails =
                                $user->isSuperAdmin()
                                || (
                                    $user->isOrgAdmin()
                                    && (int) $record->owner_id
                                        === (int) $user->ownerId()
                                )
                                || (
                                    $user->isOrgUser()
                                    && (int) $record->user_id
                                        === (int) $user->id
                                );

                            return $canSeeDetails
                                && filled(
                                    $record->description
                                )
                                    ? $record->description
                                    : null;
                        }
                    )
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('-')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->label('Modul')
                    ->placeholder('Svi moduli')
                    ->options(
                        fn (): array =>
                            static::getEloquentQuery()
                                ->whereNotNull('module')
                                ->where(
                                    'module',
                                    '<>',
                                    ''
                                )
                                ->distinct()
                                ->orderBy('module')
                                ->limit(100)
                                ->pluck(
                                    'module',
                                    'module'
                                )
                                ->toArray()
                    ),

                SelectFilter::make('action')
                    ->label('Radnja')
                    ->placeholder('Sve radnje')
                    ->options([
                        'created' => 'Kreirano',
                        'updated' => 'Uređeno',
                        'deleted' => 'Obrisano',
                        'import' => 'Import',
                        'export' => 'Export',
                        'status' => 'Status',
                        'sent' => 'Poslano',
                        'login' => 'Prijava',
                        'logout' => 'Odjava',
                        'failed_login' =>
                            'Neuspješna prijava',
                    ]),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('Izbriši')
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                            auth()->user()
                                ?->isSuperAdmin()
                            === true
                    ),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Izbriši označeno')
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                            auth()->user()
                                ?->isSuperAdmin()
                            === true
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListActivityLogs::route('/'),
        ];
    }
}