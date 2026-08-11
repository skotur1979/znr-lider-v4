<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasModulePermissions;
use App\Filament\Resources\Concerns\HasUserTableColumn;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

abstract class BaseResource extends Resource
{
    use HasUserTableColumn;
    use HasModulePermissions;

    /**
     * Koristi li Resource SoftDeletes.
     */
    protected static bool $usesSoftDeletes = false;

    /**
     * Koristi li Resource standardni ownership preko user_id.
     *
     * Standardni poslovni modul:
     * user_id = ownerId()
     *
     * Posebni moduli poput Testova imaju vlastitu logiku
     * i postavljaju $hasOwnership = false.
     */
    protected static bool $hasOwnership = true;

    /**
     * Smije li superadmin kreirati zapise ovog Resourcea.
     *
     * Standardno NE.
     *
     * Superadmin vidi i administrira postojeće poslovne zapise,
     * ali ih ne kreira u ime organizacija.
     *
     * Iznimke poput Testova / Pitanja / Odgovora mogu postaviti:
     *
     * protected static bool $superAdminCanCreate = true;
     */
    protected static bool $superAdminCanCreate = false;

    /**
     * Ključ modula.
     */
    protected static function getModuleKey(): ?string
    {
        return null;
    }

    /**
     * Javno dostupan ključ modula.
     */
    public static function moduleKey(): ?string
    {
        return static::getModuleKey();
    }

    /**
     * Trenutni korisnik.
     */
    protected static function user(): ?User
    {
        return filament()->auth()->user()
            ?? Auth::user();
    }

    /**
     * Superadmin provjera.
     */
    protected static function isSuperAdmin(): bool
    {
        return static::user()?->isSuperAdmin() === true;
    }

    /**
     * ID vlasnika organizacije.
     *
     * Glavni korisnik:
     * ownerId() = njegov ID
     *
     * Podkorisnik:
     * ownerId() = ID glavnog korisnika
     */
    protected static function ownerId(): ?int
    {
        return static::user()?->ownerId();
    }

    /**
     * Provjera postoji li kolona na model tablici.
     */
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

    /**
     * Standardni multi-tenant scope.
     *
     * Superadmin vidi sve.
     * Organizacijski korisnici vide samo svoju organizaciju.
     */
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
     * Navigacija.
     *
     * Superadmin vidi sve.
     *
     * Ostali:
     * - organizacija mora imati modul
     * - kontrolirani modul mora imati view dozvolu
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

    /**
     * Pristup listi zapisa.
     */
    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    /**
     * Pregled pojedinačnog zapisa.
     *
     * Superadmin smije pregledavati sve postojeće zapise.
     */
    public static function canView(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canView($record);
    }


    /**
     * Uređivanje postojećeg zapisa.
     *
     * Superadmin smije administrirati postojeće zapise,
     * ali ownership se time ne mijenja.
     */
    public static function canEdit(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canEdit($record);
    }


    /**
     * Brisanje / deaktiviranje postojećeg zapisa.
     */
    public static function canDelete(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canDelete($record);
    }


    /**
     * Bulk brisanje.
     */
    public static function canDeleteAny(): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canDeleteAny();
    }


    /**
     * Vraćanje soft-deleted zapisa.
     */
    public static function canRestore(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canRestore($record);
    }


    /**
     * Bulk vraćanje.
     */
    public static function canRestoreAny(): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canRestoreAny();
    }


    /**
     * Trajno brisanje soft-deleted zapisa.
     */
    public static function canForceDelete(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canForceDelete($record);
    }


    /**
     * Bulk trajno brisanje.
     */
    public static function canForceDeleteAny(): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canForceDeleteAny();
    }
    public static function canCreate(): bool
    {
        $user = static::user();

        if (! $user) {
            return false;
        }

        /**
         * Superadmin.
         */
        if ($user->isSuperAdmin()) {
            if (! static::$superAdminCanCreate) {
                return false;
            }

            return parent::canCreate();
        }

        /**
         * Organizacija mora imati modul.
         */
        $moduleKey = static::getModuleKey();

        if (
            $moduleKey
            && ! $user->canAccessModule($moduleKey)
        ) {
            return false;
        }

        /**
         * Granularne create dozvole provjeravamo
         * samo za kontrolirane module.
         */
        if (
            $moduleKey
            && User::isControlledModule($moduleKey)
            && ! static::canCreateModuleRecord()
        ) {
            return false;
        }

        return parent::canCreate();
    }

    /**
     * Glavni Resource query.
     */
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

    /**
     * Direktni URL recorda mora koristiti isti tenant scope.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    /**
     * Global search također koristi isti tenant scope.
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        if (! static::canViewModule()) {
            return parent::getGlobalSearchEloquentQuery()
                ->whereRaw('1 = 0');
        }

        return static::getEloquentQuery();
    }

    /**
     * Navigation badge.
     */
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

                if (
                    Schema::hasColumn(
                        $table,
                        'active'
                    )
                ) {
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

    /**
     * Standardni user_id za novi poslovni zapis.
     *
     * Organizacijski korisnik:
     * ownerId()
     *
     * Superadmin:
     * null, jer standardno ne kreira poslovne zapise.
     */
    public static function defaultUserId(): ?int
    {
        if (! static::$hasOwnership) {
            return null;
        }

        if (static::isSuperAdmin()) {
            return null;
        }

        return static::ownerId();
    }

    /**
     * Standardno popunjavanje ownershipa.
     */
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
