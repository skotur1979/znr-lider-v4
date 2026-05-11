<?php

namespace App\Filament\Resources\PPEEquipment\Pages;

use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
use App\Imports\PPEEquipmentImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ListPPEEquipment extends ListRecords
{
    protected static string $resource = PPEEquipmentResource::class;

    protected function getHeaderActions(): array
{
    return [
        Action::make('import_excel')
            ->label('Import Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalHeading('Import OZO opreme')
            ->form([
                FileUpload::make('excel_file')
                    ->label('Excel datoteka')
                    ->acceptedFileTypes([
    'application/*',
])
                    ->required(),
            ])
            ->action(function (array $data) {
                $file = $data['excel_file'];

                if ($file instanceof TemporaryUploadedFile) {
                    $path = $file->store('imports', 'local');
                    $fullPath = Storage::disk('local')->path($path);
                } else {
                    $fullPath = Storage::disk('local')->path($file);
                }

                $import = new PPEEquipmentImport();

Excel::import($import, $fullPath);

Notification::make()
    ->title('Import uspješno završen')
    ->body("Dodano: {$import->created}, ažurirano: {$import->updated}, preskočeno: {$import->skipped}.")
    ->success()
    ->send();
            }),

        CreateAction::make()
            ->label('Nova OZO oprema')
            ->icon('heroicon-o-plus')
            ->color('warning'),
    ];
}
}