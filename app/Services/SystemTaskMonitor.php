<?php

namespace App\Services;

use App\Models\SystemTaskRun;
use Carbon\Carbon;
use Throwable;

class SystemTaskMonitor
{
    public function start(string $taskKey, string $taskName): SystemTaskRun
    {
        return SystemTaskRun::updateOrCreate(
            [
                'task_key' => $taskKey,
            ],
            [
                'task_name' => $taskName,
                'status' => 'running',
                'last_started_at' => now(),
                'message' => 'Zadatak je pokrenut.',
            ],
        );
    }

    public function success(
        string $taskKey,
        string $taskName,
        ?string $message = null,
        ?int $processedCount = null,
        array $metadata = [],
    ): void {
        $run = SystemTaskRun::query()
            ->where('task_key', $taskKey)
            ->first();

        if (! $run) {
            $run = new SystemTaskRun([
                'task_key' => $taskKey,
                'task_name' => $taskName,
                'last_started_at' => now(),
            ]);
        }

        $finishedAt = now();

        $run->fill([
            'task_name' => $taskName,
            'status' => 'success',
            'last_finished_at' => $finishedAt,
            'last_success_at' => $finishedAt,
            'processed_count' => $processedCount,
            'duration_ms' => $this->duration(
                $run->last_started_at,
                $finishedAt,
            ),
            'message' => $message ?: 'Zadatak je uspješno izvršen.',
            'metadata' => $metadata,
        ]);

        $run->save();
    }

    public function failure(
        string $taskKey,
        string $taskName,
        Throwable|string $error,
        array $metadata = [],
    ): void {
        $run = SystemTaskRun::query()
            ->where('task_key', $taskKey)
            ->first();

        if (! $run) {
            $run = new SystemTaskRun([
                'task_key' => $taskKey,
                'task_name' => $taskName,
                'last_started_at' => now(),
            ]);
        }

        $finishedAt = now();

        $errorMessage = $error instanceof Throwable
            ? $error->getMessage()
            : $error;

        $run->fill([
            'task_name' => $taskName,
            'status' => 'failed',
            'last_finished_at' => $finishedAt,
            'last_failed_at' => $finishedAt,
            'duration_ms' => $this->duration(
                $run->last_started_at,
                $finishedAt,
            ),
            'message' => $errorMessage,
            'metadata' => $metadata,
        ]);

        $run->save();
    }

    public function heartbeat(): void
    {
        $now = now();

        SystemTaskRun::updateOrCreate(
            [
                'task_key' => 'scheduler_heartbeat',
            ],
            [
                'task_name' => 'Laravel scheduler',
                'status' => 'success',
                'last_started_at' => $now,
                'last_finished_at' => $now,
                'last_success_at' => $now,
                'processed_count' => null,
                'duration_ms' => 0,
                'message' => 'Scheduler je aktivan i uredno se izvršava.',
                'metadata' => [],
            ],
        );
    }

    protected function duration(
        Carbon|string|null $startedAt,
        Carbon $finishedAt,
    ): int {
        if (! $startedAt) {
            return 0;
        }

        return Carbon::parse($startedAt)
            ->diffInMilliseconds($finishedAt);
    }
}