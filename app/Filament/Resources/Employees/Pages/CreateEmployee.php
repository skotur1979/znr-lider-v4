<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        EmployeeResource::class;

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
        return EmployeeResource::fillOwnershipData(
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