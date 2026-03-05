<?php

namespace App\Filament\Resources\PpeLogs\PPELogResource\Pages;

use App\Filament\Resources\PpeLogs\PPELogResource;
use Filament\Resources\Pages\EditRecord;

class EditPPELog extends EditRecord
{
    protected static string $resource = PPELogResource::class;

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}