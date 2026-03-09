<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWasteTrackingForm extends EditRecord
{
    protected static string $resource = WasteTrackingFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}