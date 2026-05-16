<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Exports\WasteTrackingFormsExport;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListWasteTrackingForms extends ListRecords
{
    protected static string $resource = WasteTrackingFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi prateći list')
                ->icon('heroicon-o-plus'),

            Action::make('exportExcel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new WasteTrackingFormsExport(),
                        'prateci-listovi-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }
}