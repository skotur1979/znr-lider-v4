<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        CategoryResource::class;

    public function mount(): void
    {
        /*
         * Kod CreateRecord stranice provjera ide prije
         * parent::mount(), jer još ne postoji zapis.
         */
        if (
            $this->redirectIfMissingModulePermission(
                'create'
            )
        ) {
            return;
        }

        parent::mount();
    }

    protected function beforeCreate(): void
    {
        /*
         * Serverska provjera neposredno prije spremanja.
         */
        $this->haltIfMissingModulePermission(
            'create'
        );
    }

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        /*
         * Glavni korisnik i podkorisnik spremaju zapis
         * na ownerId organizacije.
         */
        return CategoryResource::fillOwnershipData(
            $data
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }
}