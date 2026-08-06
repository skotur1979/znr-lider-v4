<?php

namespace App\Filament\Concerns;

use Closure;
use Filament\Notifications\Notification;

trait HasModulePermissions
{
    /**
     * Provjerava smije li trenutačni korisnik izvršiti
     * određenu akciju u modulu ovog Resourcea.
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
         * Resourcei koji nemaju ključ modula ne koriste
         * ovaj sustav pojedinačnih dozvola.
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
     * Standardna poruka kada korisnik nema ovlasti.
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
     * Provjerava dozvolu i prikazuje obavijest ako
     * korisnik nema pravo.
     */
    public static function ensureModulePermission(
        string $permission,
        ?string $customBody = null
    ): bool {
        if (static::canUseModuleAction($permission)) {
            return true;
        }

        static::notifyMissingModulePermission(
            $customBody
        );

        return false;
    }

    /**
     * Zajednička provjera za Filament akcije koje podržavaju
     * before() i halt().
     *
     * Primjer:
     *
     * ->before(static::beforeModulePermission('delete'))
     */
    public static function beforeModulePermission(
        string $permission
    ): Closure {
        $resource = static::class;

        return function ($action) use (
            $resource,
            $permission
        ): void {
            if (
                ! $resource::ensureModulePermission(
                    $permission
                )
            ) {
                $action->halt();
            }
        };
    }

    /**
     * Provjera za obične action() callbackove.
     *
     * Primjer:
     *
     * if (! static::allowsModulePermission('create')) {
     *     return;
     * }
     */
    public static function allowsModulePermission(
        string $permission
    ): bool {
        return static::ensureModulePermission(
            $permission
        );
    }
}