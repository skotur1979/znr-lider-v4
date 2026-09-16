<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Exports\PpeLogItemsExport;
use App\Filament\Resources\PPELogs\PPELogResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Maatwebsite\Excel\Facades\Excel;

class EditPPELog extends EditRecord
{
    protected static string $resource =
        PPELogResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo kontekst dashboard pregleda
         * prije parent::mount().
         */
        $pregled = request()->query('pregled');

        if (
            in_array(
                $pregled,
                [
                    'isteklo',
                    'uskoro',
                ],
                true
            )
        ) {
            $this->pregled = $pregled;
        }

        parent::mount($record);
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership Upisnika OZO ne može se
         * promijeniti prilikom uređivanja.
         */
        $data['user_id'] =
            $this->record->user_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon(
                    'heroicon-o-arrow-down-tray'
                )
                ->color('warning')
                ->action(function () {
                    $record =
                        $this->record->load(
                            'items'
                        );

                    $pdf = Pdf::loadView(
                        'pdf.ozo-pdf',
                        [
                            'record' =>
                                $record,
                        ]
                    );

                    $filename =
                        'OZO-'
                        . str_replace(
                            ' ',
                            '-',
                            $record
                                ->user_last_name
                        )
                        . '-'
                        . now()->format(
                            'd-m-Y'
                        )
                        . '.pdf';

                    return response()
                        ->streamDownload(
                            fn () =>
                                print(
                                    $pdf->output()
                                ),
                            $filename
                        );
                }),

            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon(
                    'heroicon-o-document-arrow-down'
                )
                ->color('success')
                ->action(function () {
                    $record =
                        $this->record->load(
                            'items'
                        );

                    $filename =
                        'OZO-'
                        . str_replace(
                            ' ',
                            '-',
                            $record
                                ->user_last_name
                        )
                        . '-'
                        . now()->format(
                            'd-m-Y'
                        )
                        . '.xlsx';

                    return Excel::download(
                        new PpeLogItemsExport(
                            $record
                        ),
                        $filename
                    );
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        /*
         * Ako smo došli iz dashboard
         * pregleda OZO, nakon spremanja
         * vraćamo se na isti pregled.
         */
        if (
            in_array(
                $this->pregled,
                [
                    'isteklo',
                    'uskoro',
                ],
                true
            )
        ) {
            return static::getResource()::getUrl(
                'index',
                [
                    'pregled' =>
                        $this->pregled,
                ]
            );
        }

        /*
         * Kod normalnog ulaska u modul
         * zadržavamo postojeće ponašanje.
         */
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}