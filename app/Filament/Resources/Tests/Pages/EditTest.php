<?php

namespace App\Filament\Resources\Tests\Pages;

use App\Filament\Resources\Tests\TestResource;
use Filament\Resources\Pages\EditRecord;

class EditTest extends EditRecord
{
    protected static string $resource = TestResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ownership testa se nikada ne mijenja kroz edit formu.
        $data['user_id'] = $this->record->user_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }
}