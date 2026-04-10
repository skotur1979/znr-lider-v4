<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateKpi extends CreateRecord
{
    protected static string $resource = KpiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}
