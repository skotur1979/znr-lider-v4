<?php

namespace App\Filament\Resources\PpeLogs\PPELogResource\Pages;

use App\Filament\Resources\PpeLogs\PPELogResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;

class ListPPELogs extends ListRecords
{
    protected static string $resource = PPELogResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with('items');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi OZO'),
        ];
    }
}