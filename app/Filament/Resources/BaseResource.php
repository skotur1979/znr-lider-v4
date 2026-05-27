<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasUserTableColumn;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

abstract class BaseResource extends Resource
{
    use HasUserTableColumn;

    protected static bool $usesSoftDeletes = false;

    protected static bool $hasOwnership = true;

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
        $userId = Auth::id() ?? 'guest';

        $cacheKey = 'navigation_badge_' . str_replace('\\', '_', static::class)
    . '_' . $userId
    . '_' . now()->format('Y-m-d-H-i');

        return Cache::remember($cacheKey, now()->addMinutes(2), function (): string {
            $model = static::getModel();

            if (! $model) {
                return '0';
            }

            $query = $model::query();

            if (static::$usesSoftDeletes) {
                $query->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]);
            }

            return (string) static::scopeToOwner($query)->count();
        });
    }

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
