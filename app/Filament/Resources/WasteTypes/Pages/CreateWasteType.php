<?php

namespace App\Filament\Resources\WasteTypes\Pages;

use App\Filament\Resources\WasteTypes\WasteTypeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWasteType extends CreateRecord
{
    protected static string $resource = WasteTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = WasteTypeResource::resolveOwnerId() ?: Auth::id();

        return $data;
    }
}