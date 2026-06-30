<?php

namespace App\Filament\Resources\PPEEquipment\Pages;

use App\Exports\PPEEquipmentExport;
use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
use App\Imports\PPEEquipmentImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ListPPEEquipment extends ListRecords
{
    protected static string $resource = PPEEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova OZO oprema')
                ->icon('heroicon-o-plus')
                ->color('warning'),

            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $ids = $this->getFilteredSortedTableQuery()
                        ->pluck('ppe_equipments.id')
                        ->toArray();

                    return Excel::download(
                        new PPEEquipmentExport($ids),
                        'registar-ozo-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),

            Action::make('import_excel')
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

                    if (is_array($file)) {
                        $file = collect($file)->first();
                    }

                    if ($file instanceof TemporaryUploadedFile) {
                        $path = $file->store('imports', 'local');
                    } else {
                        $path = (string) $file;
                    }

                    if (! Storage::disk('local')->exists($path)) {
                        Notification::make()
                            ->title('Excel datoteka nije pronađena')
                            ->danger()
                            ->send();

                        return;
                    }

                    $import = new PPEEquipmentImport();

                    Excel::import($import, Storage::disk('local')->path($path));

                    Notification::make()
                        ->title('Import uspješno završen')
                        ->body("Dodano: {$import->created}, ažurirano: {$import->updated}, preskočeno: {$import->skipped}.")
                        ->success()
                        ->send();

                    $this->resetTable();
                }),
        ];
    }
}