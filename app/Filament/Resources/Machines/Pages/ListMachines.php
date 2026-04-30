<?php

namespace App\Filament\Resources\Machines\Pages;

use App\Exports\MachinesExport;
use App\Filament\Resources\Machines\MachineResource;
use App\Imports\MachinesImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

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
        $machines = $this->getFilteredSortedTableQuery()
            ->get();

        $pdf = Pdf::loadView('pdf.machines', compact('machines'))
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
            'radna-oprema-' . now()->format('Y-m-d') . '.pdf'
        );
    }),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => Excel::download(
                    new MachinesExport(),
                    'radna-oprema-' . now()->format('Y-m-d') . '.xlsx'
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
                ->body('Datoteka nije spremljena ili putanja nije ispravna.')
                ->danger()
                ->send();

            return;
        }

        $fullPath = Storage::disk('local')->path($path);

        $import = new MachinesImport();

        Excel::import($import, $fullPath);

        $total = $import->created + $import->updated + $import->unchanged + $import->skipped;

        Notification::make()
            ->title('Uvoz radne opreme je završen')
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