<?php

namespace App\Filament\Resources\Miscellaneouses\Pages;

use App\Exports\MiscellaneousesExport;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Imports\MiscellaneousImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

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
    ->action(function () {
        $miscellaneouses = $this->getFilteredSortedTableQuery()
            ->with('category')
            ->get();

        $pdf = Pdf::loadView('pdf.miscellaneouses', compact('miscellaneouses'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'dpi' => 96,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'ostala-ispitivanja-' . now()->format('Y-m-d') . '.pdf'
        );
    }),

            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => Excel::download(
                    new MiscellaneousesExport(),
                    'ostala-ispitivanja-' . now()->format('Y-m-d') . '.xlsx'
                )),

            Action::make('import_excel')
                ->label('Uvoz iz Excela')
                ->icon('heroicon-o-document-arrow-up')
                ->color('warning')
                ->form([
                    FileUpload::make('file')
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
                ->action(function (array $data): void {
                    $path = $data['file'];

                    if (is_array($path)) {
                        $path = collect($path)->first();
                    }

                    if ($path instanceof TemporaryUploadedFile) {
                        $path = $path->store('imports', 'local');
                    }

                    if (! Storage::disk('local')->exists($path)) {
                        Notification::make()
                            ->title('Excel datoteka nije pronađena')
                            ->danger()
                            ->send();

                        return;
                    }

                    $fullPath = Storage::disk('local')->path($path);

                    $import = new MiscellaneousImport();

                    Excel::import($import, $fullPath);

                    $total = $import->created + $import->updated + $import->unchanged + $import->skipped;

                    Notification::make()
                        ->title('Uvoz ostalih ispitivanja je završen')
                        ->body(
                            "Ukupno obrađeno: {$total}\n" .
                            "Novi zapisi: {$import->created}\n" .
                            "Ažurirani zapisi: {$import->updated}\n" .
                            "Bez promjene: {$import->unchanged}\n" .
                            "Preskočeni redovi: {$import->skipped}"
                        )
                        ->success()
                        ->send();

                    $this->resetTable();
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $pregled =
            request()->query('pregled')
            ?? data_get(request()->query(), 'tableFilters.pregled.value')
            ?? data_get(request()->query(), 'filters.pregled.value');

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