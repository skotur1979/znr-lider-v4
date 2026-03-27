<?php

namespace App\Filament\Resources\InspectionZones\Pages;

use App\Filament\Resources\InspectionZones\InspectionZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInspectionZone extends ViewRecord
{
    protected static string $resource = InspectionZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}