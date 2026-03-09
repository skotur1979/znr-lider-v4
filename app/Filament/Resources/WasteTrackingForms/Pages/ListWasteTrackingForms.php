<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWasteTrackingForms extends ListRecords
{
    protected static string $resource = WasteTrackingFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novi prateći list'),
        ];
    }
}