<?php

namespace App\Filament\Pages;

use App\Models\OntoEntry;
use App\Models\OntoRecord;
use App\Models\WasteOrganizationLocation;
use App\Models\WasteType;
use App\Support\WasteCodeFormatter;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class WasteMonthlyReport extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Mjesečni izvještaj';
    protected static ?string $title = 'Mjesečni izvještaj';
    protected static string | \UnitEnum | null $navigationGroup = 'Zaštita okoliša';
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.waste-monthly-report';

    public ?int $selectedYear = null;
    public ?int $selectedLocationId = null;

    public function mount(): void
    {
        $this->selectedYear = (int) now()->year;
        $this->selectedLocationId = null;
    }

    public function getHeading(): string
    {
        return 'Mjesečni izvještaj';
    }

    public function getMonthLabels(): array
    {
        return [
            1 => 'Sij',
            2 => 'Velj',
            3 => 'Ožu',
            4 => 'Tra',
            5 => 'Svi',
            6 => 'Lip',
            7 => 'Srp',
            8 => 'Kol',
            9 => 'Ruj',
            10 => 'Lis',
            11 => 'Stu',
            12 => 'Pro',
        ];
    }

    public function getYearOptions(): array
    {
        $years = OntoRecord::query()
            ->when(
                ! Auth::user()?->isAdmin(),
                fn ($query) => $query->where('user_id', Auth::id())
            )
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values()
            ->all();

        if (empty($years)) {
            $years = [(int) now()->year];
        }

        return array_combine($years, $years);
    }

    public function getLocationOptions(): array
    {
        return WasteOrganizationLocation::query()
            ->when(
                ! Auth::user()?->isAdmin(),
                fn ($query) => $query->whereHas('organization', fn ($q) => $q->where('user_id', Auth::id()))
            )
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($location) => [
                $location->id => $location->display_name ?? $location->name,
            ])
            ->toArray();
    }

    public function getRowsProperty(): array
    {
        $baseWasteTypeIds = OntoRecord::query()
            ->when(
                ! Auth::user()?->isAdmin(),
                fn ($query) => $query->where('user_id', Auth::id())
            )
            ->when(
                filled($this->selectedYear),
                fn ($query) => $query->where('year', $this->selectedYear)
            )
            ->when(
                filled($this->selectedLocationId),
                fn ($query) => $query->where('waste_organization_location_id', $this->selectedLocationId)
            )
            ->distinct()
            ->pluck('waste_type_id')
            ->filter()
            ->values()
            ->all();

        if (empty($baseWasteTypeIds)) {
            return [];
        }

        $wasteTypes = WasteType::query()
            ->whereIn('id', $baseWasteTypeIds)
            ->get()
            ->keyBy('id');

        $outputSums = OntoEntry::query()
            ->selectRaw('onto_records.waste_type_id as waste_type_id, MONTH(onto_entries.entry_date) as month_no, SUM(onto_entries.output_kg) as total_kg')
            ->join('onto_records', 'onto_records.id', '=', 'onto_entries.onto_record_id')
            ->where('onto_entries.entry_type', 'output')
            ->whereYear('onto_entries.entry_date', $this->selectedYear)
            ->when(
                ! Auth::user()?->isAdmin(),
                fn ($query) => $query->where('onto_records.user_id', Auth::id())
            )
            ->when(
                filled($this->selectedLocationId),
                fn ($query) => $query->where('onto_records.waste_organization_location_id', $this->selectedLocationId)
            )
            ->groupByRaw('onto_records.waste_type_id, MONTH(onto_entries.entry_date)')
            ->get();

        $matrix = [];

        foreach ($baseWasteTypeIds as $wasteTypeId) {
            $matrix[$wasteTypeId] = array_fill(1, 12, 0.0);
        }

        foreach ($outputSums as $sum) {
            $wasteTypeId = (int) $sum->waste_type_id;
            $monthNo = (int) $sum->month_no;
            $matrix[$wasteTypeId][$monthNo] = (float) $sum->total_kg;
        }

        $rows = [];

        foreach ($baseWasteTypeIds as $wasteTypeId) {
            $wasteType = $wasteTypes->get($wasteTypeId);

            if (! $wasteType) {
                continue;
            }

            $months = $matrix[$wasteTypeId] ?? array_fill(1, 12, 0.0);

            $rows[] = [
                'waste_type_id' => $wasteTypeId,
                'waste_code' => $wasteType->waste_code,
                'formatted_waste_code' => WasteCodeFormatter::plain($wasteType->waste_code),
                'name' => $wasteType->name,
                'is_hazardous' => (bool) $wasteType->is_hazardous,
                'months' => $months,
                'total' => array_sum($months),
            ];
        }

        usort($rows, function (array $a, array $b): int {
            $codeA = preg_replace('/\D+/', '', (string) ($a['waste_code'] ?? ''));
            $codeB = preg_replace('/\D+/', '', (string) ($b['waste_code'] ?? ''));

            return strcmp(
                str_pad($codeA, 10, '0', STR_PAD_RIGHT),
                str_pad($codeB, 10, '0', STR_PAD_RIGHT)
            );
        });

        return $rows;
    }

    public function getTotalsProperty(): array
    {
        $totals = array_fill(1, 12, 0.0);

        foreach ($this->rows as $row) {
            foreach ($row['months'] as $monthNo => $value) {
                $totals[$monthNo] += (float) $value;
            }
        }

        return [
            'months' => $totals,
            'grand_total' => array_sum($totals),
        ];
    }

    public function formatKg(float|int|string|null $value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }
}
