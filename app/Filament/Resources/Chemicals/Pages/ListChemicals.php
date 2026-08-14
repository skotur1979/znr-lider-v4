<?php

namespace App\Filament\Resources\Chemicals\Pages;

use App\Filament\Resources\Chemicals\ChemicalResource;
use App\Imports\ChemicalsImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ListChemicals extends ListRecords
{
    protected static string $resource = ChemicalResource::class;

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova kemikalija')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    $chemicals = $this
                        ->getFilteredSortedTableQuery()
                        ->get();

                    $pdf = Pdf::loadView(
                        'pdf.chemicals',
                        compact('chemicals')
                    )
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
                        'kemikalije-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $chemicalIds = $this
                        ->getFilteredSortedTableQuery()
                        ->pluck('chemicals.id')
                        ->toArray();

                    return Excel::download(
                        new \App\Exports\ChemicalsExport(
                            $chemicalIds
                        ),
                        'kemikalije-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),

            Actions\Action::make('import_excel')
                ->label('Uvoz iz Excela')
                ->icon('heroicon-o-document-arrow-up')
                ->color('warning')
                ->visible(
                    fn (): bool =>
                        auth()->user()?->isSuperAdmin() !== true
                )
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
                    $user = auth()->user();

                    if (! $user || $user->isSuperAdmin()) {
                        abort(403);
                    }

                    $path = $data['excel_file'];

                    if (is_array($path)) {
                        $path = collect($path)->first();
                    }

                    if ($path instanceof TemporaryUploadedFile) {
                        $path = $path->store(
                            'imports',
                            'local'
                        );
                    }

                    if (
                        ! is_string($path)
                        || $path === ''
                        || ! Storage::disk('local')->exists($path)
                    ) {
                        Notification::make()
                            ->title('Excel datoteka nije pronađena')
                            ->danger()
                            ->send();

                        return;
                    }

                    $fullPath = Storage::disk('local')
                        ->path($path);

                    $import = new ChemicalsImport();

                    try {
                        Excel::import(
                            $import,
                            $fullPath
                        );

                        $total =
                            $import->created
                            + $import->updated
                            + $import->unchanged
                            + $import->skipped;

                        Notification::make()
                            ->title('Uvoz kemikalija je završen')
                            ->body(
                                "Ukupno obrađeno: {$total}\n"
                                . "Novi zapisi: {$import->created}\n"
                                . "Ažurirani zapisi: {$import->updated}\n"
                                . "Bez promjene: {$import->unchanged}\n"
                                . "Preskočeni redovi: {$import->skipped}"
                            )
                            ->success()
                            ->send();

                        $this->resetTable();
                    } finally {
                        if (
                            Storage::disk('local')->exists($path)
                        ) {
                            Storage::disk('local')->delete($path);
                        }
                    }
                                    }),
        ];
    }
}