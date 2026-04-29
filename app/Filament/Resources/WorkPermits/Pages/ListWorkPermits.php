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
                ->label('Nova dozvola za rad'),

            Actions\Action::make('exportExcel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new WorkPermitsExport(),
                        'dozvole-za-rad-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }
}