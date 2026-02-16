<?php

namespace App\Filament\Resources\Machines\Pages;

use App\Filament\Resources\Machines\MachineResource;
use App\Imports\MachinesImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListMachines extends ListRecords
{
    protected static string $resource = MachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Radna Oprema'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    $machines = MachineResource::getEloquentQuery()->get();

                    $pdf = Pdf::loadView('pdf.machines', compact('machines'));

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'radna-oprema.pdf'
                    );
                }),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => Excel::download(new \App\Exports\MachinesExport, 'radna-oprema.xlsx')),

            Actions\Action::make('import_excel')
                ->label('Uvoz iz Excela')
                ->icon('heroicon-o-document-arrow-up')
                ->color('warning')
                ->form([
                    FileUpload::make('excel_file')
                        ->label('Excel datoteka')
                        ->disk('local')
                        ->directory('imports')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $path = $data['excel_file'];
                    $fullPath = storage_path('app/' . $path);

                    Excel::import(new MachinesImport, $fullPath);

                    Notification::make()
                        ->title('Uvoz uspješan!')
                        ->success()
                        ->send();
                }),
        ];
    }
}
