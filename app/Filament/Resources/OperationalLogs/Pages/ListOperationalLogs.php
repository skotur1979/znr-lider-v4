<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListOperationalLogs extends ListRecords
{
    protected static string $resource = OperationalLogResource::class;

    protected Width|string|null $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi dnevni unos')
                ->icon('heroicon-o-plus'),
        ];
    }
}