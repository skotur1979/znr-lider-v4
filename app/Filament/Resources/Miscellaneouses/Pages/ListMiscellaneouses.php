<?php

namespace App\Filament\Resources\Miscellaneouses\Pages;

use App\Exports\MiscellaneousesExport;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Imports\MiscellaneousesImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
                ->action(fn () => null),

            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new MiscellaneousesExport(),
                        'ostala-ispitivanja-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),

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
                    $path = $data['file'];
                    $fullPath = Storage::disk('local')->path($path);

                    Excel::import(new MiscellaneousesImport(), $fullPath);

                    Notification::make()
                        ->title('Uvoz završen.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $pregled = request()->query('pregled');

        return match ($pregled) {
            'uskoro' => $query
                ->whereDate('examination_valid_until', '>=', Carbon::today())
                ->whereDate('examination_valid_until', '<=', Carbon::today()->addDays(30)),

            'isteklo' => $query
                ->whereDate('examination_valid_until', '<', Carbon::today()),

            default => $query,
        };
    }
}