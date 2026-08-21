<?php

namespace App\Filament\Resources\InspectionZones\Pages;

use App\Filament\Resources\InspectionZones\InspectionZoneResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditInspectionZone extends EditRecord
{
    protected static string $resource =
        InspectionZoneResource::class;

    public function getTitle(): string
    {
        return 'Ocjenjivanje zone';
    }

    protected function getReturnUrl(): string
    {
        return request()->query('return_url')
            ?: '/admin/inspections';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Povratak')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(
                    $this->getReturnUrl()
                )
                ->extraAttributes([
                    'type' => 'button',
                ]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getReturnUrl();
    }
}