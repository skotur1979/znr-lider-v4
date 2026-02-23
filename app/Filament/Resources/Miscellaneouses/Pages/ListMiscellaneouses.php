<?php

namespace App\Filament\Resources\Miscellaneouses\Pages;

use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListMiscellaneouses extends ListRecords
{
    protected static string $resource = MiscellaneousResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Dodaj ispitivanje')
                ->icon('heroicon-o-plus')
                ->color('warning'),

            Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(fn () => null), // TODO

            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => null), // TODO

            Action::make('import_excel')
                ->label('Uvoz iz Excela')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->modalHeading('Uvoz ispitivanja iz Excela')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel datoteka')
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ]),
                ])
                ->action(function (array $data): void {
                    $path = $data['file']; // npr. imports/nesto.xlsx
                    $fullPath = Storage::disk('local')->path($path);

                    Excel::import(new \App\Imports\MiscellaneousesImport(), $fullPath);

                    $this->notify('success', 'Uvoz završen.');
                }),
        ];
    }
}