<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKpi extends CreateRecord
{
    protected static string $resource = KpiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()?->isSuperAdmin()) {
            $data['user_id'] = null;
        } else {
            $data['user_id'] = KpiResource::defaultUserId();
        }

        return $data;
    }
}