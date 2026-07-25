<?php

namespace App\Services;

use App\Models\SystemTaskRun;
use Carbon\Carbon;
use Throwable;

class SystemTaskMonitor
{
    public function start(string $taskKey, string $taskName): SystemTaskRun
    {
        return SystemTaskRun::create([
            'task_key' => $taskKey,
            'task_name' => $taskName,
            'started_at' => now(),
            'status' => 'running',
        ]);
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
            ->where('status', 'running')
            ->latest('id')
            ->first();

        if (! $run) {
            $run = new SystemTaskRun([
                'task_key' => $taskKey,
                'task_name' => $taskName,
                'started_at' => now(),
            ]);
        }

        $finishedAt = now();

        $run->fill([
            'task_name'       => $taskName,
            'status'          => 'success',
            'finished_at'     => $finishedAt,
            'duration_ms'     => $this->duration($run->started_at, $finishedAt),
            'processed_count' => $processedCount,
            'message'         => $message,
            'metadata'        => $metadata,
            'error_message'   => null,
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
            ->where('status', 'running')
            ->latest('id')
            ->first();

        if (! $run) {
            $run = new SystemTaskRun([
                'task_key' => $taskKey,
                'task_name' => $taskName,
                'started_at' => now(),
            ]);
        }

        $finishedAt = now();

        $run->fill([
            'task_name'     => $taskName,
            'status'        => 'failed',
            'finished_at'   => $finishedAt,
            'duration_ms'   => $this->duration($run->started_at, $finishedAt),
            'metadata'      => $metadata,
            'error_message' => $error instanceof Throwable
                ? $error->getMessage()
                : $error,
        ]);

        $run->save();
    }

    protected function duration($startedAt, Carbon $finishedAt): int
    {
        if (! $startedAt) {
            return 0;
        }

        return Carbon::parse($startedAt)->diffInMilliseconds($finishedAt);
    }
}