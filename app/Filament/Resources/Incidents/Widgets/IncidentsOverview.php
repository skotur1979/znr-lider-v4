<?php

namespace App\Filament\Resources\Incidents\Widgets;

use App\Models\Incident;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class IncidentsOverview extends StatsOverviewWidget
{
    protected function getColumns(): int
    {
        return 6; // 6 kartica u redu
    }

    protected function getStats(): array
    {
        $year = (int) (request()->input('tableFilters.godina_filter.value') ?: now()->year);

        $base = Incident::query()->withoutTrashed();

        if (!Auth::user()?->isAdmin()) {
            $base->where('user_id', Auth::id());
        }

        $qYear = (clone $base)->whereYear('date_occurred', $year);

        $total = (clone $qYear)->count();
        $lta   = (clone $qYear)->where('type_of_incident', 'LTA')->count();
        $mta   = (clone $qYear)->where('type_of_incident', 'MTA')->count();
        $faa   = (clone $qYear)->where('type_of_incident', 'FAA')->count();

        $lastLta = (clone $base)
            ->where('type_of_incident', 'LTA')
            ->whereNotNull('date_occurred')
            ->orderByDesc('date_occurred')
            ->value('date_occurred');

        if ($lastLta) {
            $last = Carbon::parse($lastLta)->startOfDay();
            $daysWithout = $last->diffInDays(Carbon::today());
            $sinceText = 'od ' . $last->translatedFormat('d. M. Y');
        } else {
            $daysWithout = 0;
            $sinceText = 'nema LTA zapisa';
        }

        return [

            Stat::make('GODINA', $year)
                ->extraAttributes(['class' => 'text-center']),

            Stat::make('UKUPNO', $total)
                ->extraAttributes(['class' => 'text-center']),

            Stat::make('LTA', $lta)
                ->extraAttributes(['class' => 'text-center']),

            Stat::make('MTA', $mta)
                ->extraAttributes(['class' => 'text-center']),

            Stat::make('FAA', $faa)
                ->extraAttributes(['class' => 'text-center']),

            Stat::make('DANA BEZ OZLJEDE (LTA)', $daysWithout)
                ->description($sinceText)
                ->extraAttributes([
                    'class' => 'text-center znr-days-card'
                ]),
        ];
    }
}