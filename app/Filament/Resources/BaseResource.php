<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasUserTableColumn;
use App\Models\User;
use Filament\Notifications\Notification;
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

    /**
     * Svaki Resource koji koristi module organizacije
     * vraća vlastiti ključ modula.
     */
    protected static function getModuleKey(): ?string
    {
        return null;
    }

    /**
     * Javno dostupan ključ modula za Page klase
     * i ostale dijelove aplikacije.
     */
    public static function moduleKey(): ?string
    {
        return static::getModuleKey();
    }

    protected static function user(): ?User
    {
        return filament()->auth()->user()
            ?? Auth::user();
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
     * Provjera dozvole za trenutačni Resource.
     */
    public static function canUseModuleAction(
        string $permission
    ): bool {
        $user = static::user();

        if (! $user) {
            return false;
        }

        $moduleKey = static::getModuleKey();

        /*
         * Resourcei bez ključa modula ne koriste
         * ovaj sustav dozvola.
         */
        if (! $moduleKey) {
            return true;
        }

        return $user->hasModulePermission(
            $moduleKey,
            $permission
        );
    }

    public static function canViewModule(): bool
    {
        return static::canUseModuleAction('view');
    }

    public static function canCreateModuleRecord(): bool
    {
        return static::canUseModuleAction('create');
    }

    public static function canUpdateModuleRecord(): bool
    {
        return static::canUseModuleAction('update');
    }

    public static function canDeleteModuleRecord(): bool
    {
        return static::canUseModuleAction('delete');
    }

    /**
     * Zajednička obavijest kada korisnik nema dozvolu.
     */
    public static function notifyMissingModulePermission(
        ?string $customBody = null
    ): void {
        Notification::make()
            ->title('Nemate ovlasti za ovu akciju')
            ->body(
                $customBody
                    ?: 'Obratite se glavnom korisniku svoje organizacije.'
            )
            ->danger()
            ->send();
    }

    /**
     * Provjera dozvole uz automatsko prikazivanje obavijesti.
     *
     * Koristit ćemo je na vidljivim gumbima kako gumbi ne bi
     * nestali, nego bi nakon klika prikazali poruku.
     */
    public static function ensureModulePermission(
        string $permission,
        ?string $customBody = null
    ): bool {
        if (static::canUseModuleAction($permission)) {
            return true;
        }

        static::notifyMissingModulePermission($customBody);

        return false;
    }

    protected static function modelHasColumn(
        string $column
    ): bool {
        $model = static::getModel();

        if (! $model) {
            return false;
        }

        $instance = new $model();

        return Schema::hasColumn(
            $instance->getTable(),
            $column
        );
    }

    protected static function scopeToOwner(
        Builder $query,
        string $column = 'user_id'
    ): Builder {
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
        $table = (new $model())->getTable();

        return $query->where(
            $table . '.' . $column,
            $ownerId
        );
    }

    /**
     * Modul se prikazuje u izborniku samo ako:
     *
     * 1. organizacija ima omogućen modul;
     * 2. podkorisnik ima pravo pregleda.
     *
     * Ostale akcije kasnije ostaju vidljive i prikazuju
     * poruku ako korisnik nema dozvolu.
     */
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

        if (! $user->canAccessModule($moduleKey)) {
            return false;
        }

        if (User::isControlledModule($moduleKey)) {
            return $user->hasModulePermission(
                $moduleKey,
                'view'
            );
        }

        return true;
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
        /*
         * Korisnik bez prava pregleda ne smije dobivati
         * rezultate ovog modula u globalnoj pretrazi.
         */
        if (! static::canViewModule()) {
            return parent::getGlobalSearchEloquentQuery()
                ->whereRaw('1 = 0');
        }

        return static::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        $userId = Auth::id() ?? 'guest';

        $cacheKey =
            'navigation_badge_'
            . str_replace('\\', '_', static::class)
            . '_'
            . $userId
            . '_'
            . now()->format('Y-m-d-H-i');

        return Cache::remember(
            $cacheKey,
            now()->addSeconds(30),
            function (): string {
                $model = static::getModel();

                if (! $model) {
                    return '0';
                }

                $instance = new $model();
                $table = $instance->getTable();

                $query = $model::query();

                if (static::$usesSoftDeletes) {
                    $query->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ]);

                    if (
                        Schema::hasColumn(
                            $table,
                            'deleted_at'
                        )
                    ) {
                        $query->whereNull(
                            $table . '.deleted_at'
                        );
                    }
                }

                if (Schema::hasColumn($table, 'active')) {
                    $query->where(
                        $table . '.active',
                        true
                    );
                }

                return (string) static::scopeToOwner(
                    $query
                )->count();
            }
        );
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

    public static function fillOwnershipData(
        array $data
    ): array {
        if (! static::$hasOwnership) {
            return $data;
        }

        if (! static::modelHasColumn('user_id')) {
            return $data;
        }

        if (! static::isSuperAdmin()) {
            $data['user_id'] =
                static::defaultUserId();
        }

        return $data;
    }
}
