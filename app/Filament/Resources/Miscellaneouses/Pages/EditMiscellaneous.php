<?php

namespace App\Filament\Resources\Miscellaneouses\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use Filament\Resources\Pages\EditRecord;

class EditMiscellaneous extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        MiscellaneousResource::class;

    public function mount(int|string $record): void
    {
        /*
         * Filament prvo mora učitati stvarni model.
         */
        parent::mount($record);

        $this->redirectIfMissingModulePermission(
            'update'
        );
    }

    protected function beforeSave(): void
    {
        $this->haltIfMissingModulePermission(
            'update'
        );
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Podkorisnik ne smije promijeniti vlasnika zapisa.
         * Postojeći user_id ostaje nepromijenjen.
         */
        if (! auth()->user()?->isSuperAdmin()) {
            unset($data['user_id']);
        }

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
