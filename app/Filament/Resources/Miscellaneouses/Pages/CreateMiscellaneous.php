<?php

namespace App\Filament\Resources\Miscellaneouses\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMiscellaneous extends CreateRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        MiscellaneousResource::class;

    public function mount(): void
    {
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
        $this->haltIfMissingModulePermission(
            'create'
        );
    }

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        return MiscellaneousResource::fillOwnershipData(
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
