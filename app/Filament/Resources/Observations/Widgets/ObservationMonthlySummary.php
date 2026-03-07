<?php

namespace App\Filament\Resources\Observations\Widgets;

use App\Filament\Resources\Observations\Pages\ListObservations;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ObservationMonthlySummary extends Widget
{
    use InteractsWithPageTable;

    protected string $view = 'filament.resources.observations.widgets.observation-monthly-summary';

    protected int|string|array $columnSpan = 'full';

    protected function getTablePage(): string
    {
        return ListObservations::class;
    }

    public function getViewData(): array
    {
        $year = $this->getSelectedYear();

        $rows = $this->getPageTableQuery()
            ->whereYear('incident_date', $year)
            ->selectRaw('MONTH(incident_date) as month_number')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN observation_type = 'Near Miss' THEN 1 ELSE 0 END) as nm_total")
            ->selectRaw("SUM(CASE WHEN observation_type = 'Negative Observation' THEN 1 ELSE 0 END) as negative_total")
            ->selectRaw("SUM(CASE WHEN observation_type = 'Positive Observation' THEN 1 ELSE 0 END) as positive_total")
            ->selectRaw("SUM(CASE WHEN status = 'Not started' THEN 1 ELSE 0 END) as not_started_total")
            ->selectRaw("SUM(CASE WHEN status = 'In progress' THEN 1 ELSE 0 END) as in_progress_total")
            ->selectRaw("SUM(CASE WHEN status = 'Complete' THEN 1 ELSE 0 END) as complete_total")
            ->groupBy(DB::raw('MONTH(incident_date)'))
            ->orderBy(DB::raw('MONTH(incident_date)'))
            ->get()
            ->keyBy('month_number');

        $months = [
            1 => 'Siječanj',
            2 => 'Veljača',
            3 => 'Ožujak',
            4 => 'Travanj',
            5 => 'Svibanj',
            6 => 'Lipanj',
            7 => 'Srpanj',
            8 => 'Kolovoz',
            9 => 'Rujan',
            10 => 'Listopad',
            11 => 'Studeni',
            12 => 'Prosinac',
        ];

        $data = [];

        foreach ($months as $number => $label) {
            $row = $rows->get($number);

            $data[] = [
                'month' => $label,
                'total' => (int) ($row->total ?? 0),
                'nm_total' => (int) ($row->nm_total ?? 0),
                'negative_total' => (int) ($row->negative_total ?? 0),
                'positive_total' => (int) ($row->positive_total ?? 0),
                'not_started_total' => (int) ($row->not_started_total ?? 0),
                'in_progress_total' => (int) ($row->in_progress_total ?? 0),
                'complete_total' => (int) ($row->complete_total ?? 0),
            ];
        }

        return [
            'year' => $year,
            'rows' => $data,
        ];
    }

    protected function getSelectedYear(): int
    {
        $page = $this->getTablePageInstance();

        $year = data_get($page, 'tableFilters.year.value')
            ?? data_get($page, 'filters.year.value')
            ?? data_get($page, 'mountedTableFilters.year.value')
            ?? now()->year;

        return (int) $year;
    }
}