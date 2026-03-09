<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWasteTrackingForm extends ViewRecord
{
    protected static string $resource = WasteTrackingFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}