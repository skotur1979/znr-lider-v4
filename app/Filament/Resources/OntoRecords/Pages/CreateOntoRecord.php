<?php

namespace App\Filament\Resources\OntoRecords\Pages;

use App\Filament\Resources\OntoRecords\OntoRecordResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOntoRecord extends CreateRecord
{
    protected static string $resource = OntoRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if (! $user || $user->isSuperAdmin()) {
            abort(403);
        }

        $data['user_id'] = $user->ownerId();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}