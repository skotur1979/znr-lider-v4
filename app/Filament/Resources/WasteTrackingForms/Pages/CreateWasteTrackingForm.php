<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWasteTrackingForm extends CreateRecord
{
    protected static string $resource = WasteTrackingFormResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
