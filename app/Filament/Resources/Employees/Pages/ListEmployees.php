<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

// prilagodi ako su ti klase drugačije imenovane
use App\Exports\EmployeesExport;
use App\Imports\EmployeesImport;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novi Zaposlenik'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    // ✅ isti query kao tablica (admin/user + without softdelete + with certificates)
                    $employees = EmployeeResource::getEloquentQuery()
                        ->orderBy('name')
                        ->get();

                    $pdf = Pdf::loadView('pdf.employees', compact('employees'))
                        ->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'zaposlenici-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => Excel::download(
                    new EmployeesExport(),
                    'zaposlenici-' . now()->format('Y-m-d') . '.xlsx'
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

                    // ✅ string path ili TemporaryUploadedFile
                    if ($file instanceof TemporaryUploadedFile) {
                        $path = $file->store('imports', 'local');
                    } else {
                        $path = (string) $file;
                    }

                    $fullPath = Storage::disk('local')->path($path);

                    Excel::import(new EmployeesImport(), $fullPath);

                    Notification::make()
                        ->title('Uvoz uspješan!')
                        ->success()
                        ->send();
                }),
        ];
    }
}