<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        CategoryResource::class;

    public function mount(int|string $record): void
    {
        /*
         * Parent mora prvi učitati Category model.
         */
        parent::mount($record);

        /*
         * Nakon učitavanja zapisa provjerava se pravo
         * uređivanja.
         */
        $this->redirectIfMissingModulePermission(
            'update'
        );
    }

    protected function beforeSave(): void
    {
        /*
         * Dodatna serverska zaštita neposredno prije
         * spremanja promjena.
         */
        $this->haltIfMissingModulePermission(
            'update'
        );
    }
    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        unset($data['user_id']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}