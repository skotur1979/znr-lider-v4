<?php

namespace App\Filament\Resources\Expenses\Expenses\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Expenses\Expenses\ExpenseResource;
use App\Services\ExpenseReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;

class ExpenseReports extends Page
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        ExpenseResource::class;

    protected string $view =
        'filament.resources.expenses.expenses.pages.expense-reports';

    protected Width|string|null $maxContentWidth =
        '7xl';

    public string $year = 'all';

    public string $comparison_year = 'none';

    public string $month = 'all';

    public ?string $category_id = null;

    public string $realized = 'all';

    public ?string $supplier = null;

    public array $report = [];

    public function mount(): void
    {
        if (
            $this->redirectIfMissingModulePermission(
                'view'
            )
        ) {
            return;
        }

        $this->year =
            (string) now(
                'Europe/Zagreb'
            )->year;

        $previousYear =
            (string) (
                now(
                    'Europe/Zagreb'
                )->year - 1
            );

        /*
         * Ako prethodna godina postoji,
         * nudimo ju kao početnu usporedbu.
         */
        $years =
            app(
                ExpenseReportService::class
            )->report([
                'year' =>
                    $this->year,
            ])['options']['years']
            ?? [];

        $this->comparison_year =
            array_key_exists(
                $previousYear,
                $years
            )
                ? $previousYear
                : 'none';

        $this->loadData();
    }

    public function getTitle(): string
    {
        return 'Izvještaji troškova';
    }

    public function getHeading(): string
    {
        return 'Izvještaji troškova';
    }

    public function updatedYear(): void
    {
        if (
            $this->comparison_year
            === $this->year
        ) {
            $this->comparison_year =
                'none';
        }

        $this->loadData();
    }

    public function updatedComparisonYear(): void
    {
        $this->loadData();
    }

    public function updatedMonth(): void
    {
        $this->loadData();
    }

    public function updatedCategoryId(): void
    {
        $this->loadData();
    }

    public function updatedRealized(): void
    {
        $this->loadData();
    }

    public function updatedSupplier(): void
    {
        $this->loadData();
    }

    public function resetFilters(): void
    {
        $this->year =
            (string) now(
                'Europe/Zagreb'
            )->year;

        $this->comparison_year =
            'none';

        $this->month =
            'all';

        $this->category_id =
            null;

        $this->realized =
            'all';

        $this->supplier =
            null;

        $this->loadData();
    }

    public function loadData(): void
    {
        if (
            ! ExpenseResource
                ::canViewModule()
        ) {
            return;
        }

        $this->report =
            app(
                ExpenseReportService::class
            )->report(
                $this->filters()
            );
    }

    protected function filters(): array
    {
        return [
            'year' =>
                $this->year,

            'comparison_year' =>
                $this->comparison_year,

            'month' =>
                $this->month,

            'category_id' =>
                $this->category_id,

            'realized' =>
                $this->realized,

            'supplier' =>
                $this->supplier,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make(
                'export_report_pdf'
            )
                ->label(
                    'Izvoz izvještaja u PDF'
                )
                ->icon(
                    'heroicon-o-arrow-down-tray'
                )
                ->color('warning')
                ->action(
                    function () {
                        if (
                            ! ExpenseResource
                                ::allowsModulePermission(
                                    'view'
                                )
                        ) {
                            return null;
                        }

                        $report =
                            app(
                                ExpenseReportService::class
                            )->report(
                                $this->filters()
                            );

                        $pdf =
                            Pdf::loadView(
                                'pdf.expense-reports',
                                [
                                    'report' =>
                                        $report,

                                    'filters' =>
                                        $this->filters(),
                                ]
                            )
                                ->setPaper(
                                    'a4',
                                    'landscape'
                                )
                                ->setOptions([
                                    'isHtml5ParserEnabled' =>
                                        true,

                                    'isRemoteEnabled' =>
                                        true,

                                    'isPhpEnabled' =>
                                        true,

                                    'dpi' =>
                                        96,

                                    'defaultFont' =>
                                        'DejaVu Sans',
                                ]);

                        $yearLabel =
                            $this->year
                                === 'all'
                                ? 'sve-godine'
                                : $this->year;

                        return response()
                            ->streamDownload(
                                fn () =>
                                    print(
                                        $pdf->output()
                                    ),
                                'izvjestaj-troskova-'
                                    . $yearLabel
                                    . '-'
                                    . now()->format(
                                        'Y-m-d'
                                    )
                                    . '.pdf'
                            );
                    }
                ),

            Action::make('back')
                ->label(
                    'Popis troškova'
                )
                ->icon(
                    'heroicon-o-arrow-left'
                )
                ->color('gray')
                ->url(
                    ExpenseResource::getUrl(
                        'index'
                    )
                ),
        ];
    }
}