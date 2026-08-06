<?php

namespace App\Filament\Resources\Concerns;

trait InteractsWithModulePagePermissions
{
    /**
     * Vraća Resource koji pripada trenutačnoj Filament stranici.
     */
    protected function modulePermissionResource(): string
    {
        return static::getResource();
    }

    /**
     * Provjerava dozvolu i vraća korisnika na popis ako
     * je nema.
     *
     * Vraća true kada je korisnik preusmjeren.
     */
    protected function redirectIfMissingModulePermission(
        string $permission
    ): bool {
        $resource = $this->modulePermissionResource();

        if (
            $resource::ensureModulePermission(
                $permission
            )
        ) {
            return false;
        }

        $this->redirect(
            $resource::getUrl('index'),
            navigate: true
        );

        return true;
    }

    /**
     * Zaustavlja spremanje CreateRecord/EditRecord stranice.
     */
    protected function haltIfMissingModulePermission(
        string $permission
    ): void {
        $resource = $this->modulePermissionResource();

        if (
            ! $resource::ensureModulePermission(
                $permission
            )
        ) {
            $this->halt();
        }
    }

    /**
     * Provjera bez preusmjeravanja i bez halt().
     *
     * Koristi se za OCR, posebne gumbe i druge metode.
     */
    protected function hasRequiredModulePermission(
        string $permission
    ): bool {
        $resource = $this->modulePermissionResource();

        return $resource::ensureModulePermission(
            $permission
        );
    }
}