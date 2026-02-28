<?php

namespace App\Filament\Resources\Chemicals\Pages;

use App\Filament\Resources\Chemicals\ChemicalResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

use Maatwebsite\Excel\Facades\Excel;

class ListChemicals extends ListRecords
{
    protected static string $resource = ChemicalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova kemikalija'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    // ✅ koristi isti query kao tablica (user filter + soft delete scope uklonjen u resource)
                    $chemicals = ChemicalResource::getEloquentQuery()
                        ->orderBy('product_name')
                        ->get();

                    $pdf = Pdf::loadView('pdf.chemicals', compact('chemicals'))
                        ->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'kemikalije-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => \Maatwebsite\Excel\Facades\Excel::download(
    new \App\Exports\ChemicalsExport(),
    'kemikalije-' . now()->format('Y-m-d') . '.xlsx'
)),

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

                    // ✅ Filament može vratiti string path ili UploadedFile objekt
                    if ($file instanceof TemporaryUploadedFile) {
                        $path = $file->store('imports', 'local');
                    } else {
                        $path = (string) $file; // npr. "imports/ime.xlsx"
                    }

                    $fullPath = Storage::disk('local')->path($path);

                    Excel::import(new \App\Imports\ChemicalsImport(), $fullPath);

                    Notification::make()
                        ->title('Uvoz uspješan!')
                        ->success()
                        ->send();
                }),
        ];
    }
}