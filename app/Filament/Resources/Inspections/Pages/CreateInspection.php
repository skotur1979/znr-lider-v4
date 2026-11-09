<?php

namespace App\Filament\Resources\Inspections\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Inspections\InspectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInspection extends CreateRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        InspectionResource::class;

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
        return InspectionResource::fillOwnershipData(
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