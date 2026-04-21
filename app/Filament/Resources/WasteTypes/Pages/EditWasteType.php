<?php

namespace App\Filament\Resources\WasteTypes\Pages;

use App\Filament\Resources\WasteTypes\WasteTypeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditWasteType extends EditRecord
{
    protected static string $resource = WasteTypeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($this->record->user_id)) {
            $data['user_id'] = WasteTypeResource::resolveOwnerId() ?: Auth::id();
        }

        return $data;
    }
}