<?php

namespace App\Filament\Resources\WasteTypes\Pages;

use App\Exports\WasteTypesExport;
use App\Filament\Resources\WasteTypes\WasteTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListWasteTypes extends ListRecords
{
    protected static string $resource = WasteTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova vrsta otpada'),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $filters = $this->getTableFiltersForm()->getState();

                    return Excel::download(
                        new WasteTypesExport($filters),
                        'vrste-otpada-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }
}