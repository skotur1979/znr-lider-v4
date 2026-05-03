<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOperationalLogs extends ListRecords
{
    protected static string $resource = OperationalLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi zapis')
                ->icon('heroicon-o-plus'),
        ];
    }
}