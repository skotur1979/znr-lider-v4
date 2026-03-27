<?php

namespace App\Filament\Resources\Inspections\Pages;

use App\Filament\Resources\Inspections\InspectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInspections extends ListRecords
{
    protected static string $resource = InspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    
    Action::make('create_five_s')
    ->label('Novi 5S nadzor')
    ->icon('heroicon-o-squares-2x2')
    ->color('success')
    ->url(fn () => static::getResource()::getUrl('create', ['inspection_type' => 'five_s']));
}
}