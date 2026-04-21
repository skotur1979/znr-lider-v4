<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

abstract class BaseResource extends Resource
{
    /**
     * Uključi na true ako resource koristi SoftDeletes
     */
    protected static bool $usesSoftDeletes = false;

    /**
     * Uključi na false ako tablica nema user_id i nije owner scoped
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

    /**
     * Default user_id koji se sprema na zapis
     */
    protected static function defaultUserId(): ?int
    {
        if (! static::$hasOwnership) {
            return null;
        }

        if (static::isSuperAdmin()) {
            return Auth::id();
        }

        return static::ownerId();
    }

    /**
     * Scope za organizacijske podatke
     */
    protected static function scopeToOwner(Builder $query, string $column = 'user_id'): Builder
    {
        if (! static::$hasOwnership) {
            return $query;
        }

        if (static::isSuperAdmin()) {
            return $query;
        }

        $ownerId = static::ownerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($column, $ownerId);
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

        $query = static::scopeToOwner($query);

        return (string) $query->count();
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (static::$hasOwnership && ! static::isSuperAdmin()) {
            $data['user_id'] = static::defaultUserId();
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (
            static::$hasOwnership &&
            ! static::isSuperAdmin() &&
            array_key_exists('user_id', $data)
        ) {
            $data['user_id'] = static::defaultUserId();
        }

        return $data;
    }
}
