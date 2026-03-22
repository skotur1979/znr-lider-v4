<?php

namespace App\Filament\Resources\Fires\Pages;

use App\Filament\Resources\Fires\FireResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFire extends CreateRecord
{
    protected static string $resource = FireResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}