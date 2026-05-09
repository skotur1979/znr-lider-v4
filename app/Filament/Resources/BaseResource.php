<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasUserTableColumn;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

abstract class BaseResource extends Resource
{
    use HasUserTableColumn;

    /**
     * Uključi na true ako resource koristi SoftDeletes.
     */
    protected static bool $usesSoftDeletes = false;

    /**
     * Uključi na false ako tablica nema user_id i nije owner scoped.
     */
    protected static bool $hasOwnership = true;

    /**
     * Ako resource ima module access ključ, ovdje ga vrati.
     * Primjer: 'incidents', 'employees', 'machines'
     */
    protected static function getModuleKey(): ?string
    {
        return null;
    }

    protected static function user()
    {
        return Auth::user();
    }

    protected static function isSuperAdmin(): bool
    {
        return static::user()?->isSuperAdmin() === true;
    }

    protected static function ownerId(): ?int
    {
        return static::user()?->ownerId();
    }

    protected static function modelHasColumn(string $column): bool
    {
        $model = static::getModel();

        if (! $model) {
            return false;
        }

        return Schema::hasColumn((new $model)->getTable(), $column);
    }

    /**
     * Scope za organizacijske podatke.
     *
     * Super admin vidi sve.
     * Glavni korisnik i podkorisnik vide sve zapise svoje organizacije.
     */
    protected static function scopeToOwner(Builder $query, string $column = 'user_id'): Builder
    {
        if (! static::$hasOwnership) {
            return $query;
        }

        if (! static::modelHasColumn($column)) {
            return $query;
        }

        if (static::isSuperAdmin()) {
            return $query;
        }

        $ownerId = static::ownerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        $model = static::getModel();
        $table = (new $model)->getTable();

        return $query->where($table . '.' . $column, $ownerId);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = static::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $moduleKey = static::getModuleKey();

        if (! $moduleKey) {
            return true;
        }

        return $user->canAccessModule($moduleKey);
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::$usesSoftDeletes) {
            $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        return static::scopeToOwner($query);
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
        $query = static::getModel()::query();

        if (static::$usesSoftDeletes) {
            $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        return (string) static::scopeToOwner($query)->count();
    }

    /**
     * Pomoćna metoda ako ju negdje želiš ručno pozvati.
     * Glavno automatsko spremanje user_id sada radi preko AppServiceProvider-a.
     */
    public static function defaultUserId(): ?int
    {
        if (! static::$hasOwnership) {
            return null;
        }

        if (static::isSuperAdmin()) {
            return Auth::id();
        }

        return static::ownerId();
    }

    public static function fillOwnershipData(array $data): array
    {
        if (! static::$hasOwnership) {
            return $data;
        }

        if (! static::modelHasColumn('user_id')) {
            return $data;
        }

        if (! static::isSuperAdmin()) {
            $data['user_id'] = static::defaultUserId();
        }

        return $data;
    }
}
