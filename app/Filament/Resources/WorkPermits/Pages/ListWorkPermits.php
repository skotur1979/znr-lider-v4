<?php

namespace App\Filament\Resources\WorkPermits\Pages;

use App\Filament\Resources\WorkPermits\WorkPermitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkPermits extends ListRecords
{
    protected static string $resource = WorkPermitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova dozvola za rad'),
        ];
    }
}