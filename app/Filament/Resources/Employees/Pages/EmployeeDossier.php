<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Services\EmployeeDossierService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class EmployeeDossier extends ViewRecord
{
    protected static string $resource =
        EmployeeResource::class;

    protected string $view =
        'filament.resources.employees.pages.employee-dossier';

    public function mount(
        int|string $record
    ): void {
        if (
            ! EmployeeResource::allowsModulePermission(
                'view'
            )
        ) {
            abort(403);
        }

        parent::mount($record);

        if (
            method_exists(
                $this->record,
                'trashed'
            )
            && $this->record->trashed()
        ) {
            abort(404);
        }
    }

    public function getTitle(): string
    {
        return 'ZNR dosje zaposlenika';
    }

    public function getSubheading(): ?string
    {
        return (string) $this->record->name;
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Izvoz dosjea u PDF')
                ->icon(
                    'heroicon-o-arrow-down-tray'
                )
                ->color('warning')
                ->action(function () {
                    $dossier = app(
                        EmployeeDossierService::class
                    )->build(
                        $this->record
                    );

                    $pdf = Pdf::loadView(
                        'pdf.employee-dossier',
                        $dossier
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

                            'dpi' => 96,

                            'defaultFont' =>
                                'DejaVu Sans',
                        ]);

                    $fileName =
                        'znr-dosje-'
                        . Str::slug(
                            (string)
                            $this->record->name
                        )
                        . '-'
                        . now()->format(
                            'Y-m-d'
                        )
                        . '.pdf';

                    return response()
                        ->streamDownload(
                            fn () =>
                                print(
                                    $pdf->output()
                                ),
                            $fileName
                        );
                }),

            Action::make('back')
                ->label(
                    'Natrag na zaposlenike'
                )
                ->icon(
                    'heroicon-o-arrow-left'
                )
                ->color('gray')
                ->url(
                    EmployeeResource::getUrl(
                        'index'
                    )
                ),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'dossier' => app(
                EmployeeDossierService::class
            )->build(
                $this->record
            ),
        ];
    }
}