<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Exports\ActivityLogsExport;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new ActivityLogsExport(),
                        'zadnje_aktivnosti_' . now()->format('Y_m_d_H_i') . '.xlsx'
                    );
                }),
        ];
    }
}