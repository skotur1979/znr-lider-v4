<?php

namespace App\Filament\Resources\WorkPermits\Pages;

use App\Exports\WorkPermitsExport;
use App\Filament\Resources\WorkPermits\WorkPermitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListWorkPermits extends ListRecords
{
    protected static string $resource = WorkPermitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova dozvola za rad')
                ->icon('heroicon-o-plus')
                ->visible(
                    fn (): bool =>
                        WorkPermitResource::canCreate()
                ),

            Actions\Action::make('exportExcel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    /*
                     * Izvozimo upravo zapise koji su trenutno
                     * vidljivi kroz filtre, pretragu i sortiranje.
                     */
                    $permitIds = $this
                        ->getFilteredSortedTableQuery()
                        ->pluck('work_permits.id')
                        ->toArray();

                    return Excel::download(
                        new WorkPermitsExport($permitIds),
                        'dozvole-za-rad-'
                            . now()->format('Y-m-d')
                            . '.xlsx'
                    );
                }),
        ];
    }
}