<?php

namespace App\Filament\Resources\PpeLogs\PPELogResource\Pages;

use App\Exports\PersonalProtectiveEquipmentExport;
use App\Filament\Resources\PpeLogs\PPELogResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PpeLogItemsExport;

class EditPPELog extends EditRecord
{
    protected static string $resource = PPELogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-document')
                ->color('warning')
                ->action(function () {
                    $record = $this->record->load('items');

                    $pdf = Pdf::loadView('pdf.ozo-pdf', [
                        'record' => $record,
                    ]);

                    $filename = 'OZO-' . str_replace(' ', '-', $record->user_last_name) . '-' . now()->format('d-m-Y') . '.pdf';

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        $filename
                    );
                }),

           Action::make('export_excel')
    ->label('Izvoz u Excel')
    ->icon('heroicon-o-document-text')
    ->color('success')
    ->action(function () {
        $record = $this->record->load('items');

        $filename = 'OZO-' . str_replace(' ', '-', $record->user_last_name) . '-' . now()->format('d-m-Y') . '.xlsx';

        return Excel::download(new PpeLogItemsExport($record), $filename);
    
                }),
        ];
    }
}