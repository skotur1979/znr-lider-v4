<?php

namespace App\Filament\Resources\Fires\Pages;

use App\Filament\Resources\Fires\FireResource;
use App\Imports\FiresImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use App\Exports\FiresExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListFires extends ListRecords
{
    protected static string $resource = FireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Dodaj Vatrogasni aparat'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    $fires = FireResource::getEloquentQuery()
                        ->orderBy('place')
                        ->get();

                    $pdf = Pdf::loadView('pdf.fires', compact('fires'))
                        ->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'vatrogasni-aparati-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => Excel::download(new FiresExport(), 'vatrogasni-aparati-' . now()->format('Y-m-d') . '.xlsx')),

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
                        $path = (string) $file;
                    }

                    $fullPath = Storage::disk('local')->path($path);

                    Excel::import(new FiresImport, $fullPath);
                    // ✅ opcionalno obriši upload nakon importa
                    Storage::disk('local')->delete($path);

                    Notification::make()
                        ->title('Uvoz uspješan!')
                        ->success()
                        ->send();
                }),
        ];
    }
}