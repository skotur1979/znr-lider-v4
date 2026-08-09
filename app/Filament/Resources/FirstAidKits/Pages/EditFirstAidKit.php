<?php

namespace App\Filament\Resources\FirstAidKits\Pages;

use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use Filament\Resources\Pages\EditRecord;

class EditFirstAidKit extends EditRecord
{
    protected static string $resource = FirstAidKitResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = $this->record->user_id;

        return $data;
    }

    public function getTitle(): string
    {
        return 'Uredi Prva pomoć';
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }
}