<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Exports\PpeItemsAllExport;
use App\Filament\Resources\Pages\BaseListRecords;
use App\Filament\Resources\PPELogs\PPELogResource;
use App\Imports\PPELogsImport;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ListPPELogs extends BaseListRecords
{
    protected static string $resource =
        PPELogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi OZO')
                ->icon(
                    'heroicon-o-plus'
                )
                ->color(
                    'warning'
                ),

            /*
            |--------------------------------------------------------------------------
            | IZVOZ SVIH OZO ZADUŽENJA
            |--------------------------------------------------------------------------
            */

            Action::make(
                'export_excel'
            )
                ->label(
                    'Izvoz u Excel'
                )
                ->icon(
                    'heroicon-o-document-arrow-down'
                )
                ->color(
                    'success'
                )
                ->action(
                    fn () =>
                        Excel::download(
                            new PpeItemsAllExport(),
                            'OZO-SVI-'
                            . now()->format(
                                'd-m-Y'
                            )
                            . '.xlsx'
                        )
                ),

            /*
            |--------------------------------------------------------------------------
            | UVOZ ZADUŽENJA IZ EXCELA
            |--------------------------------------------------------------------------
            */

            Action::make(
                'import_excel'
            )
                ->label(
                    'Uvoz iz Excela'
                )
                ->icon(
                    'heroicon-o-document-arrow-up'
                )
                ->color(
                    'warning'
                )
                ->visible(
                    fn (): bool =>
                        PPELogResource::canCreate()
                )
                ->modalHeading(
                    'Uvoz zaduženja OZO iz Excela'
                )
                ->modalDescription(
                    'Svaki red predstavlja jedno zaduženje OZO jednom zaposleniku.'
                )
                ->modalSubmitActionLabel(
                    'Uvezi'
                )
                ->form([
                    FileUpload::make(
                        'excel_file'
                    )
                        ->label(
                            'Excel datoteka'
                        )
                        ->helperText(
                            'Obavezno: OIB, Naziv OZO, Datum izdavanja. '
                            . 'Opcionalno: HRN EN / Norma, Veličina, '
                            . 'Rok uporabe (mjeseci), Datum vraćanja.'
                        )
                        ->disk(
                            'local'
                        )
                        ->directory(
                            'imports'
                        )
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required(),
                ])
                ->action(
                    function (
                        array $data
                    ): void {
                        $user =
                            auth()->user();

                        /*
                         * Superadmin ne uvozi
                         * podatke u ime organizacije.
                         */
                        if (
                            ! $user
                            || $user->isSuperAdmin()
                        ) {
                            abort(403);
                        }

                        $ownerId =
                            (int)
                            $user->ownerId();

                        if ($ownerId <= 0) {
                            abort(403);
                        }

                        $file =
                            $data[
                                'excel_file'
                            ];

                        if (is_array($file)) {
                            $file =
                                collect(
                                    $file
                                )->first();
                        }

                        if (
                            $file instanceof
                            TemporaryUploadedFile
                        ) {
                            $path =
                                $file->store(
                                    'imports',
                                    'local'
                                );
                        } else {
                            $path =
                                (string) $file;
                        }

                        if (
                            ! Storage::disk(
                                'local'
                            )->exists(
                                $path
                            )
                        ) {
                            Notification::make()
                                ->title(
                                    'Excel datoteka nije pronađena'
                                )
                                ->danger()
                                ->send();

                            return;
                        }

                        $import =
                            new PPELogsImport(
                                $ownerId
                            );

                        try {
                            Excel::import(
                                $import,
                                Storage::disk(
                                    'local'
                                )->path(
                                    $path
                                )
                            );

                            $total =
                                $import->created
                                + $import->updated
                                + $import->unchanged
                                + $import->skipped;

                            Notification::make()
                                ->title(
                                    'Uvoz Upisnika OZO je završen'
                                )
                                ->body(
                                    "Ukupno obrađeno: {$total}\n"
                                    . "Nova zaduženja: {$import->created}\n"
                                    . "Ažurirana zaduženja: {$import->updated}\n"
                                    . "Bez promjene: {$import->unchanged}\n"
                                    . "Preskočeno: {$import->skipped}\n"
                                    . "Nije pronađen zaposlenik: {$import->missingEmployees}\n"
                                    . "OZO nije u Registru, ali je uvezen iz Excela: {$import->notInRegistry}"
                                )
                                ->success()
                                ->send();

                            $this->resetTable();
                        } catch (
                            \Throwable $e
                        ) {
                            report(
                                $e
                            );

                            Notification::make()
                                ->title(
                                    'Uvoz nije uspio'
                                )
                                ->body(
                                    $e->getMessage()
                                )
                                ->danger()
                                ->send();
                        } finally {
                            if (
                                filled(
                                    $path
                                )
                                && Storage::disk(
                                    'local'
                                )->exists(
                                    $path
                                )
                            ) {
                                Storage::disk(
                                    'local'
                                )->delete(
                                    $path
                                );
                            }
                        }
                    }
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD FILTERI
    |--------------------------------------------------------------------------
    */

    protected function getTableQuery(): Builder
    {
        $query =
            parent::getTableQuery();

        $pregled =
            request()->query(
                'pregled'
            )
            ?? data_get(
                request()->query(),
                'tableFilters.pregled.value'
            )
            ?? data_get(
                request()->query(),
                'filters.pregled.value'
            );

        return match ($pregled) {
            'uskoro' =>
                $query->whereHas(
                    'items',
                    function (
                        Builder $q
                    ): void {
                        $q
                            ->whereNull(
                                'return_date'
                            )
                            ->whereNotNull(
                                'end_date'
                            )
                            ->where(
                                'end_date',
                                '>=',
                                Carbon::today()
                                    ->startOfDay()
                            )
                            ->where(
                                'end_date',
                                '<=',
                                Carbon::today()
                                    ->addDays(
                                        30
                                    )
                                    ->endOfDay()
                            );
                    }
                ),

            'isteklo' =>
                $query->whereHas(
                    'items',
                    function (
                        Builder $q
                    ): void {
                        $q
                            ->whereNull(
                                'return_date'
                            )
                            ->whereNotNull(
                                'end_date'
                            )
                            ->where(
                                'end_date',
                                '<',
                                Carbon::today()
                                    ->startOfDay()
                            );
                    }
                ),

            default =>
                $query,
        };
    }
}