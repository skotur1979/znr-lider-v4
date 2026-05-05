<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Exports\OperationalLogsExport;
use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Maatwebsite\Excel\Facades\Excel;

class ListOperationalLogs extends ListRecords
{
    protected static string $resource = OperationalLogResource::class;

    protected Width|string|null $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novi dnevni unos')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => Excel::download(
                    new OperationalLogsExport(),
                    'operativni-dnevnik-' . now()->format('Y-m-d') . '.xlsx'
                )),
        ];
    }
}