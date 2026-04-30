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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
        $employees = $this->getFilteredSortedTableQuery()
            ->with('certificates')
            ->get();

        $pdf = Pdf::loadView('pdf.employees', compact('employees'))
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
    ->action(function (array $data): void {
        $path = $data['excel_file'];

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

        $import = new EmployeesImport();

        Excel::import($import, $fullPath);

        $total = $import->created + $import->updated + $import->unchanged + $import->skipped;

        Notification::make()
            ->title('Uvoz zaposlenika je završen')
            ->body(
                "Ukupno obrađeno zaposlenika: {$total}\n" .
                "Novi zaposlenici: {$import->created}\n" .
                "Ažurirani zaposlenici: {$import->updated}\n" .
                "Bez promjene: {$import->unchanged}\n" .
                "Preskočeni redovi: {$import->skipped}\n\n" .
                "Novi certifikati: {$import->certificatesCreated}\n" .
                "Ažurirani certifikati: {$import->certificatesUpdated}\n" .
                "Certifikati bez promjene: {$import->certificatesUnchanged}"
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
        $pregled = request()->query('pregled');

        return match ($pregled) {
            'medical_expiring' => $query
                ->whereDate('medical_examination_valid_until', '>=', Carbon::today())
                ->whereDate('medical_examination_valid_until', '<=', Carbon::today()->addDays(30)),

            'medical_expired' => $query
                ->whereDate('medical_examination_valid_until', '<', Carbon::today()),

            'certificates_expiring' => $query->whereHas('certificates', function (Builder $q) {
                $q->whereDate('valid_until', '>=', Carbon::today())
                    ->whereDate('valid_until', '<=', Carbon::today()->addDays(30));
            }),

            'certificates_expired' => $query->whereHas('certificates', function (Builder $q) {
                $q->whereDate('valid_until', '<', Carbon::today());
            }),

            default => $query,
        };
    }
}