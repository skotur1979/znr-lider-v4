<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWasteTrackingForm extends CreateRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        WasteTrackingFormResource::class;

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
        return WasteTrackingFormResource::fillOwnershipData(
            $data
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl(
            'index'
        );
    }
}
