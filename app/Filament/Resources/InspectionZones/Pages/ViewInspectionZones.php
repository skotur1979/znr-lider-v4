<?php

namespace App\Filament\Resources\InspectionZones\Pages;

use App\Filament\Resources\InspectionZones\InspectionZoneResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInspectionZone extends ViewRecord
{
    protected static string $resource = InspectionZoneResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $targetUrl = request()->query('return_url')
            ?: request()->headers->get('referer')
            ?: '/admin/inspections';

        $this->redirect($targetUrl, navigate: true);
    }

    public function getTitle(): string
    {
        return 'Pregled ocjena zone';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}