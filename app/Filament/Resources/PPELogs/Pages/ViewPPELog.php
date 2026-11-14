<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Exports\PpeLogItemsExport;
use App\Filament\Resources\PPELogs\PPELogResource;
use App\Models\PPELog;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ViewPPELog extends ViewRecord
{
    protected static string $resource =
        PPELogResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
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

    protected function authorizedRecord(): PPELog
    {
        $record =
            PPELogResource::getEloquentQuery()
                ->whereKey(
                    $this
                        ->getRecord()
                        ->getKey()
                )
                ->firstOrFail();

        abort_unless(
            PPELogResource::canView(
                $record
            ),
            403
        );

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
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
                ->visible(
                    fn (): bool =>
                        PPELogResource::canView(
                            $this->getRecord()
                        )
                )
                ->action(
                    function () {
                        $record =
                            $this
                                ->authorizedRecord()
                                ->load([
                                    'items',
                                    'user',
                                ]);

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
                                        false,

                                    'dpi' =>
                                        96,

                                    'defaultFont' =>
                                        'DejaVu Sans',
                                ]);

                        $name =
                            Str::slug(
                                (string) (
                                    $record->user_last_name
                                    ?: 'ozo'
                                )
                            );

                        $filename =
                            'OZO-'
                            . ($name ?: 'zapis')
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
                ->visible(
                    fn (): bool =>
                        PPELogResource::canView(
                            $this->getRecord()
                        )
                )
                ->action(
                    function () {
                        $record =
                            $this
                                ->authorizedRecord()
                                ->load([
                                    'items',
                                    'user',
                                ]);

                        $name =
                            Str::slug(
                                (string) (
                                    $record->user_last_name
                                    ?: 'ozo'
                                )
                            );

                        $filename =
                            'OZO-'
                            . ($name ?: 'zapis')
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
