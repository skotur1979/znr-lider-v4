<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Filament\Resources\PPELogs\PPELogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPPELog extends ViewRecord
{
    protected static string $resource = PPELogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
