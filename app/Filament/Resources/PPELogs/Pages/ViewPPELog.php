<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Exports\PpeLogItemsExport;
use App\Filament\Resources\PPELogs\PPELogResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Maatwebsite\Excel\Facades\Excel;

class ViewPPELog extends ViewRecord
{
    protected static string $resource =
        PPELogResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo dashboard kontekst.
         */
        $pregled =
            request()->query(
                'pregled'
            );

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
            $this->pregled =
                $pregled;
        }

        parent::mount(
            $record
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | PDF
            |--------------------------------------------------------------------------
            */

            Action::make(
                'export_pdf'
            )
                ->label(
                    'Izvoz u PDF'
                )
                ->icon(
                    'heroicon-o-arrow-down-tray'
                )
                ->color(
                    'warning'
                )
                ->action(
                    function () {
                        $record =
                            $this
                                ->record
                                ->load(
                                    'items'
                                );

                        $pdf =
                            Pdf::loadView(
                                'pdf.ozo-pdf',
                                [
                                    'record' =>
                                        $record,
                                ]
                            )
                                ->setPaper(
                                    'a4',
                                    'portrait'
                                )
                                ->setOptions([
                                    'isHtml5ParserEnabled' =>
                                        true,

                                    'isRemoteEnabled' =>
                                        false,

                                    'isPhpEnabled' =>
                                        true,

                                    'dpi' =>
                                        96,

                                    'defaultFont' =>
                                        'DejaVu Sans',
                                ]);

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
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | EXCEL
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
                    function () {
                        $record =
                            $this
                                ->record
                                ->load(
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
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | UREDI
            |--------------------------------------------------------------------------
            */

            Action::make(
                'editPPELog'
            )
                ->label(
                    'Uredi'
                )
                ->icon(
                    'heroicon-o-pencil-square'
                )
                ->color(
                    'warning'
                )
                ->visible(
                    fn (): bool =>
                        ! $this
                            ->record
                            ->trashed()
                        && PPELogResource::canEdit(
                            $this->record
                        )
                )
                ->url(
                    function (): string {
                        $parameters = [
                            'record' =>
                                $this
                                    ->getRecord(),
                        ];

                        /*
                         * Ako je View otvoren iz
                         * dashboard pregleda,
                         * kontekst prenosimo dalje.
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
                            $parameters[
                                'pregled'
                            ] =
                                $this->pregled;
                        }

                        return PPELogResource::getUrl(
                            'edit',
                            $parameters
                        );
                    }
                ),
        ];
    }
}