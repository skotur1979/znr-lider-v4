<?php

namespace App\Filament\Resources\PpeLogs\PPELogResource\Pages;

use App\Exports\PpeItemsAllExport;
use App\Filament\Resources\PpeLogs\PPELogResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPPELogs extends ListRecords
{
    protected static string $resource = PPELogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi OZO'),

            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->action(fn () => Excel::download(
                    new PpeItemsAllExport(),
                    'OZO-SVI-' . now()->format('d-m-Y') . '.xlsx'
                )),
        ];
    }
}