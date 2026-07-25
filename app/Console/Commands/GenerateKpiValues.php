<?php

namespace App\Console\Commands;

use App\Services\KpiCalculationService;
use App\Services\SystemTaskMonitor;
use Illuminate\Console\Command;
use Throwable;

class GenerateKpiValues extends Command
{
    protected $signature = 'kpi:generate {month?} {year?}';

    protected $description = 'Generira automatske KPI vrijednosti za zadani mjesec i godinu';

    public function handle(
        KpiCalculationService $service,
        SystemTaskMonitor $monitor,
    ): int {
        $taskKey = 'kpi_generation';
        $taskName = 'Automatsko generiranje KPI vrijednosti';

        $monitor->start($taskKey, $taskName);

        try {
            $month = (int) ($this->argument('month') ?: now()->month);
            $year = (int) ($this->argument('year') ?: now()->year);

            $service->generateForMonth($month, $year);

            $message = "KPI vrijednosti generirane su za {$month}/{$year}.";

            $monitor->success(
                taskKey: $taskKey,
                taskName: $taskName,
                message: $message,
                metadata: [
                    'month' => $month,
                    'year' => $year,
                ],
            );

            $this->info($message);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $monitor->failure(
                taskKey: $taskKey,
                taskName: $taskName,
                error: $exception,
            );

            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}