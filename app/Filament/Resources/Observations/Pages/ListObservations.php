<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Resources\Observations\ObservationResource;
use App\Exports\ObservationsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

// (Opcionalno) za import:
// use Filament\Forms\Components\FileUpload;
// use Filament\Notifications\Notification;
// use Illuminate\Support\Facades\Storage;
// use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
// use App\Imports\ObservationsImport;

class ListObservations extends ListRecords
{
    protected static string $resource = ObservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo zapažanje'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    // ✅ koristi isti query kao tablica (user scope + soft delete scope uklonjen u resource)
                    $observations = ObservationResource::getEloquentQuery()
                        ->orderByDesc('incident_date')
                        ->get();

                    $pdf = Pdf::loadView('pdf.observations', compact('observations'))
                        ->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'zapazanja-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => Excel::download(
                    new ObservationsExport(),
                    'zapazanja-' . now()->format('Y-m-d') . '.xlsx'
                )),

            /*
            // ✅ OPCIONALNO: uvoz iz Excela (ako ćeš raditi import kao Machines)
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
                    $file = $data['excel_file'];

                    if ($file instanceof TemporaryUploadedFile) {
                        $path = $file->store('imports', 'local');
                    } else {
                        $path = (string) $file; // npr. "imports/ime.xlsx"
                    }

                    $fullPath = Storage::disk('local')->path($path);

                    Excel::import(new ObservationsImport(), $fullPath);

                    Notification::make()
                        ->title('Uvoz uspješan!')
                        ->success()
                        ->send();
                }),
            */
        ];
    }
}