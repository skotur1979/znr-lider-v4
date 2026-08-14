<?php

namespace App\Filament\Resources\FirstAidKits\Pages;

use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFirstAidKit extends CreateRecord
{
    protected static string $resource =
        FirstAidKitResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        return FirstAidKitResource::fillOwnershipData(
            $data
        );
    }

    public function getTitle(): string
    {
        return 'Nova Prva pomoć';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl(
            'index'
        );
    }
}