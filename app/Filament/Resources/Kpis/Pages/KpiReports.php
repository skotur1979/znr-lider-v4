<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use App\Services\KpiCalculationService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\KpiReportsExport;
use Maatwebsite\Excel\Facades\Excel;

class KpiReports extends Page
{
    protected static string $resource = KpiResource::class;

    protected string $view = 'filament.resources.kpis.pages.kpi-reports';

    public int $year;

    public array $groups = [];

    public function mount(): void
    {
        $this->year = now()->year;
        $this->loadData();
    }

    public function updatedYear(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->groups = app(KpiCalculationService::class)
            ->yearlyReportGrouped($this->year)
            ->toArray();
    }

    protected function getHeaderActions(): array
{
    return [
        Action::make('export_pdf')
            ->label('Izvoz u PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('warning')
            ->action(function () {
                $groups = app(KpiCalculationService::class)
                    ->yearlyReportGrouped($this->year)
                    ->toArray();

                $pdf = Pdf::loadView('pdf.kpi-reports', [
                    'groups' => $groups,
                    'year' => $this->year,
                ])
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
                    'kpi-izvjestaji-' . $this->year . '-' . now()->format('Y-m-d') . '.pdf'
                );
            }),

            Action::make('export_excel')
            ->label('Izvoz u Excel')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->action(function () {
                return Excel::download(
                    new KpiReportsExport($this->year),
                    'kpi-izvjestaji-' . $this->year . '-' . now()->format('Y-m-d') . '.xlsx'
                );
            }),

        Action::make('dashboard')
            ->label('KPI Dashboard')
            ->icon('heroicon-o-chart-bar-square')
            ->color('info')
            ->url(KpiResource::getUrl('dashboard')),

        Action::make('bulk_entry')
            ->label('Bulk unos')
            ->icon('heroicon-o-table-cells')
            ->color('success')
            ->visible(
                fn (): bool =>
                    auth()->user()?->isSuperAdmin() !== true
            )
            ->url(KpiResource::getUrl('bulk-entry')),
    ];
}

    public function getTitle(): string
    {
        return 'KPI Izvještaji';
    }
}