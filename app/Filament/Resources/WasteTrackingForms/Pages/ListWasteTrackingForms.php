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
    protected static string $resource =
        WasteTrackingFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi prateći list')
                ->icon('heroicon-o-plus')
                ->before(
                    WasteTrackingFormResource::beforeModulePermission(
                        'create'
                    )
                ),

            Action::make('exportExcel')
                ->label('Izvoz u Excel')
                ->icon(
                    'heroicon-o-document-arrow-down'
                )
                ->color('success')
                ->action(function () {
                    if (
                        ! WasteTrackingFormResource::allowsModulePermission(
                            'view'
                        )
                    ) {
                        return null;
                    }

                    $ids = $this
                        ->getFilteredSortedTableQuery()
                        ->pluck('waste_tracking_forms.id')
                        ->toArray();

                    return Excel::download(
                        new WasteTrackingFormsExport($ids),
                        'prateci-listovi-'
                            . now()->format('Y-m-d')
                            . '.xlsx'
                    );
                }),
        ];
    }
}