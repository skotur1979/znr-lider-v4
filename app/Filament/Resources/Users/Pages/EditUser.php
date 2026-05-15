<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return UserResource::mutateFormDataBeforeSave($data);
    }
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? static::getResource()::getUrl('index');
    }
}