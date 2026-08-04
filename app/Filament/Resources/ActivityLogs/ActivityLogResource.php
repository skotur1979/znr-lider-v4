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

    protected static bool $hasOwnership = false;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|UnitEnum|null $navigationGroup = 'Upravljanje';

    protected static ?string $navigationLabel = 'Zadnje aktivnosti';

    protected static ?string $modelLabel = 'Aktivnost';

    protected static ?string $pluralModelLabel = 'Zadnje aktivnosti';

    protected static ?int $navigationSort = 99;

    protected static function getModuleKey(): ?string
    {
        return null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = ActivityLog::query()
            ->with(['user', 'owner'])
            ->latest();

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // Superadmin vidi sve aktivnosti.
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // Glavni korisnik vidi sve aktivnosti svoje organizacije.
        if ($user->canCreateSubusers()) {
            return $query->where('owner_id', $user->ownerId());
        }

        // Podkorisnik vidi samo svoje aktivnosti.
        return $query->where('user_id', $user->id);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $cacheKey = 'activity_log_badge_'
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

                if ($user->canCreateSubusers()) {
                    return (string) $query
                        ->where('owner_id', $user->ownerId())
                        ->count();
                }

                return (string) $query
                    ->where('user_id', $user->id)
                    ->count();
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
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
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
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => 'Kreirano',
                        'updated' => 'Uređeno',
                        'deleted' => 'Obrisano',
                        'import' => 'Import',
                        'export' => 'Export',
                        'status' => 'Status',
                        'sent' => 'Poslano',
                        'login' => 'Prijava',
                        'logout' => 'Odjava',
                        'failed_login' => 'Neuspješna prijava',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'import' => 'info',
                        'export' => 'gray',
                        'status' => 'primary',
                        'sent' => 'info',
                        'login' => 'success',
                        'logout' => 'gray',
                        'failed_login' => 'danger',
                        default => 'gray',
                    })
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
                    ->tooltip(fn (ActivityLog $record): ?string =>
                        filled($record->description)
                            ? $record->description
                            : null
                    )
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->label('Modul')
                    ->placeholder('Svi moduli')
                    ->options(fn (): array => ActivityLog::query()
                        ->whereNotNull('module')
                        ->where('module', '<>', '')
                        ->distinct()
                        ->orderBy('module')
                        ->limit(100)
                        ->pluck('module', 'module')
                        ->toArray()),

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
                        'failed_login' => 'Neuspješna prijava',
                    ]),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('Izbriši')
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                        auth()->user()?->isSuperAdmin() === true
                    ),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Izbriši označeno')
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                        auth()->user()?->isSuperAdmin() === true
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}