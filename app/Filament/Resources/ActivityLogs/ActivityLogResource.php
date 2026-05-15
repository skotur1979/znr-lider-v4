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
        $query = parent::getEloquentQuery()->latest();

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->canCreateSubusers()) {
            return $query->where('owner_id', $user->ownerId());
        }

        return $query->where('user_id', $user->id);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100, 200])
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
        ->formatStateUsing(fn (?string $state) => match ($state) {
            'created' => 'Kreirano',
            'updated' => 'Uređeno',
            'deleted' => 'Obrisano',
            'import' => 'Import',
            'export' => 'Export',
            'status' => 'Status',
            'login' => 'Prijava',
            'logout' => 'Odjava',
            'failed_login' => 'Neuspješna prijava',
            default => $state ?? '-',
        })
        ->color(fn (?string $state) => match ($state) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'import' => 'info',
            'export' => 'gray',
            'status' => 'primary',
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
        ->wrap()
        ->toggleable(isToggledHiddenByDefault: true),

    TextColumn::make('ip_address')
        ->label('IP')
        ->toggleable(isToggledHiddenByDefault: true),
])
            ->filters([
                SelectFilter::make('module')
                    ->label('Modul')
                    ->options(fn () => ActivityLog::query()
                        ->whereNotNull('module')
                        ->distinct()
                        ->orderBy('module')
                        ->pluck('module', 'module')
                        ->toArray()),

                SelectFilter::make('action')
                    ->label('Radnja')
                    ->options([
                        'created' => 'Kreirano',
                        'updated' => 'Uređeno',
                        'deleted' => 'Obrisano',
                        'import' => 'Import',
                        'export' => 'Export',
                        'status' => 'Status',
                        'login' => 'Prijava',
                        'logout' => 'Odjava',
                        'failed_login' => 'Neuspješna prijava',
                    ]),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('Izbriši')
                    ->visible(fn () => auth()->user()?->isSuperAdmin() === true),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Izbriši označeno')
                    ->visible(fn () => auth()->user()?->isSuperAdmin() === true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}