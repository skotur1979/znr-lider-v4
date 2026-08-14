<?php

namespace App\Filament\Resources\WasteOrganizations\Pages;

use App\Filament\Resources\WasteOrganizations\WasteOrganizationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListWasteOrganizations extends ListRecords
{
    protected static string $resource = WasteOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova organizacija')
                ->icon('heroicon-o-plus')
                ->visible(
                    fn (): bool =>
                        Auth::user() !== null
                        && ! Auth::user()->isSuperAdmin()
                ),
        ];
    }
}