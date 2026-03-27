<?php

namespace App\Filament\Resources\InspectionZones\Pages;

use App\Filament\Resources\InspectionZones\InspectionZoneResource;
use Filament\Resources\Pages\ListRecords;

class ListInspectionZones extends ListRecords
{
    protected static string $resource = InspectionZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
