<?php

namespace App\Filament\Resources\Miscellaneouses\Pages;

use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMiscellaneous extends ViewRecord
{
    protected static string $resource = MiscellaneousResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
