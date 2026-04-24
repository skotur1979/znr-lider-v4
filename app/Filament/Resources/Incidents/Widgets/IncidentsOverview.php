<?php

namespace App\Filament\Resources\Incidents\Widgets;

use App\Filament\Resources\Incidents\Pages\ListIncidents;
use App\Models\Incident;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class IncidentsOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListIncidents::class;
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $year = $this->getSelectedYearRaw();
$yearLabel = $year ?? 'SVE';

$base = $this->getPageTableQuery();

$qYear = clone $base;

if (filled($year)) {
    $qYear->whereYear('date_occurred', $year);
}

        $total = (clone $qYear)->count();
        $lta = (clone $qYear)->where('type_of_incident', 'LTA')->count();
        $mta = (clone $qYear)->where('type_of_incident', 'MTA')->count();
        $faa = (clone $qYear)->where('type_of_incident', 'FAA')->count();

        $lastLtaDate = Incident::query()
            ->withoutTrashed()
            ->when(! Auth::user()?->isAdmin(), fn ($query) => $query->where('user_id', Auth::id()))
            ->where('type_of_incident', 'LTA')
            ->whereNotNull('date_occurred')
            ->orderByDesc('date_occurred')
            ->value('date_occurred');

        if ($lastLtaDate) {
            $lastLta = Carbon::parse($lastLtaDate)->startOfDay();
            $daysWithoutLta = $lastLta->diffInDays(Carbon::today());
            $lastLtaText = 'od ' . $lastLta->translatedFormat('d. M. Y');
        } else {
            $daysWithoutLta = 0;
            $lastLtaText = 'nema LTA zapisa';
        }

        return [
            Stat::make('GODINA', $yearLabel)
                ->description('Odabrana godina')
                ->extraAttributes(['class' => 'text-center']),

            Stat::make('UKUPNO', (string) $total)
                ->description('Ukupno prijavljeno')
                ->color('info')
                ->extraAttributes(['class' => 'text-center']),

            Stat::make('LTA', (string) $lta)
                ->description('Ozljeda na radu')
                ->color('danger')
                ->extraAttributes(['class' => 'text-center']),

            Stat::make('MTA', (string) $mta)
                ->description('Medicinski tretman')
                ->color('warning')
                ->extraAttributes(['class' => 'text-center']),

            Stat::make('FAA', (string) $faa)
                ->description('Prva pomoć')
                ->color('warning')
                ->extraAttributes(['class' => 'text-center']),

            Stat::make('DANA BEZ OZLJEDE', (string) $daysWithoutLta)
                ->description($lastLtaText)
                ->color('success')
                ->extraAttributes(['class' => 'text-center']),
        ];
    }

    protected function getSelectedYearRaw(): ?string
{
    $page = $this->getTablePageInstance();

    if (method_exists($page, 'getSelectedYearRaw')) {
        return $page->getSelectedYearRaw();
    }

    return (string) now()->year;
}
}