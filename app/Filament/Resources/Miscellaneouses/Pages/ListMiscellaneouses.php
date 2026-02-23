<?php

namespace App\Filament\Resources\Miscellaneouses\MiscellaneousResource\Pages;

use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMiscellaneouses extends ListRecords
{
    protected static string $resource = MiscellaneousResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
