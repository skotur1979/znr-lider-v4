<?php

namespace App\Console\Commands;

use App\Models\User;
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
            if ($month < 1 || $month > 12) {
                throw new \InvalidArgumentException(
                    'Mjesec mora biti između 1 i 12.'
                );
            }

            if ($year < 2000 || $year > 2100) {
                throw new \InvalidArgumentException(
                    'Godina mora biti između 2000 i 2100.'
                );
            }

            $totals = [
                'organizations' => 0,
                'generated' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];

            /*
             * KPI se generiraju zasebno za svaku aktivnu organizaciju.
             *
             * ID glavnog korisnika (org_admin) predstavlja ownerId
             * cijele organizacije.
             */
            User::query()
                ->where('role', 'org_admin')
                ->where('is_active', true)
                ->withoutTrashed()
                ->orderBy('id')
                ->chunkById(
                    100,
                    function ($users) use (
                        $service,
                        $month,
                        $year,
                        &$totals
                    ): void {
                        foreach ($users as $user) {
                            $result = $service->generateForOwner(
                                (int) $user->id,
                                $month,
                                $year
                            );

                            $totals['organizations']++;

                            $totals['generated'] += (int) (
                                $result['generated'] ?? 0
                            );

                            $totals['updated'] += (int) (
                                $result['updated'] ?? 0
                            );

                            $totals['skipped'] += (int) (
                                $result['skipped'] ?? 0
                            );
                        }
                    }
                );

            $message =
                "KPI vrijednosti generirane su za {$month}/{$year}. "
                . "Organizacije: {$totals['organizations']} | "
                . "Kreirano: {$totals['generated']} | "
                . "Ažurirano: {$totals['updated']} | "
                . "Preskočeno: {$totals['skipped']}.";

            $monitor->success(
                taskKey: $taskKey,
                taskName: $taskName,
                message: $message,
                processedCount: $totals['organizations'],
                metadata: [
                    'month' => $month,
                    'year' => $year,
                    'organizations' => $totals['organizations'],
                    'generated' => $totals['generated'],
                    'updated' => $totals['updated'],
                    'skipped' => $totals['skipped'],
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