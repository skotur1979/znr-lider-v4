<?php

namespace App\Filament\Resources\WasteTypes\Pages;

use App\Exports\WasteTypesExport;
use App\Filament\Resources\WasteTypes\WasteTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListWasteTypes extends ListRecords
{
    protected static string $resource = WasteTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova vrsta otpada')
                ->icon('heroicon-o-plus')
                ->visible(
                    fn (): bool =>
                        Auth::user() !== null
                        && ! Auth::user()->isSuperAdmin()
                ),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $ids = $this
                        ->getFilteredSortedTableQuery()
                        ->pluck('waste_types.id')
                        ->toArray();

                    return Excel::download(
                        new WasteTypesExport($ids),
                        'vrste-otpada-'
                            . now()->format('Y-m-d')
                            . '.xlsx'
                    );
                }),
        ];
    }
}