<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Filament\Resources\PPELogs\PPELogResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePPELog extends CreateRecord
{
    protected static string $resource = PPELogResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Auth::user()?->isAdmin()) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
