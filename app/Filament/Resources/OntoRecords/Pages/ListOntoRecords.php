<?php

namespace App\Filament\Resources\OntoRecords\Pages;

use App\Filament\Resources\OntoRecords\OntoRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOntoRecords extends ListRecords
{
    protected static string $resource = OntoRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novi ONTO obrazac'),
        ];
    }
}