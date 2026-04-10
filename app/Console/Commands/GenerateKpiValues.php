<?php

namespace App\Console\Commands;

use App\Services\KpiCalculationService;
use Illuminate\Console\Command;

class GenerateKpiValues extends Command
{
    protected $signature = 'kpi:generate {month?} {year?}';
    protected $description = 'Generira automatske KPI vrijednosti za zadani mjesec i godinu';

    public function handle(KpiCalculationService $service): int
    {
        $month = (int) ($this->argument('month') ?: now()->month);
        $year = (int) ($this->argument('year') ?: now()->year);

        $service->generateForMonth($month, $year);

        $this->info("KPI vrijednosti su generirane za {$month}/{$year}.");

        return self::SUCCESS;
    }
}