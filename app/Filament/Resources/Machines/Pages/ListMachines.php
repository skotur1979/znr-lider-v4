<?php

namespace App\Filament\Resources\Machines\Pages;

use App\Filament\Resources\Machines\MachineResource;
use App\Imports\MachinesImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use App\Exports\MachinesExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                ->color('warning')
                ->action(function () {
                    // ✅ koristi isti query kao tablica (user filter + soft delete scope uklonjen u resource)
                    $machines = MachineResource::getEloquentQuery()
                        ->orderBy('name')
                        ->get();

                    $pdf = Pdf::loadView('pdf.machines', compact('machines'))
    ->setPaper('a4', 'landscape');



                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'radna-oprema-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),

            Actions\Action::make('export_excel')
    ->label('Izvoz u Excel')
    ->icon('heroicon-o-document-arrow-down')
    ->color('success')
    ->action(fn () => Excel::download(new MachinesExport(), 'radna-oprema-' . now()->format('Y-m-d') . '.xlsx')),

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

        // ✅ Filament može vratiti string path ili UploadedFile objekt (ovisno o konfiguraciji)
        if ($file instanceof TemporaryUploadedFile) {
            $path = $file->store('imports', 'local');
        } else {
            // najčešće: već je string tipa "imports/ime.xlsx"
            $path = (string) $file;
        }

        $fullPath = Storage::disk('local')->path($path);

        Excel::import(new \App\Imports\MachinesImport, $fullPath);

        Notification::make()
            ->title('Uvoz uspješan!')
            ->success()
            ->send();
    
    }),
        ];
    }
}

