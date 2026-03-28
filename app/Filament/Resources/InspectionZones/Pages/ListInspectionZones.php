<?php

namespace App\Filament\Resources\InspectionZones\Pages;

use App\Filament\Resources\InspectionZones\InspectionZoneResource;
use Filament\Resources\Pages\ListRecords;

class ListInspectionZones extends ListRecords
{
    protected static string $resource = InspectionZoneResource::class;

    public function mount(): void
    {
        $this->redirect('/admin/inspections', navigate: true);
    }

    public function getTitle(): string
    {
        return 'Zone nadzora';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
