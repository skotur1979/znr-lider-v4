<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\SystemTaskMonitor;
use Illuminate\Console\Command;
use Throwable;

class CleanupActivityLogs extends Command
{
    protected $signature = 'activitylogs:cleanup';

    protected $description = 'Briše zapise aktivnosti starije od 30 dana';

    public function handle(SystemTaskMonitor $monitor): int
    {
        $taskKey = 'activity_cleanup';
        $taskName = 'Čišćenje aktivnosti';

        $monitor->start($taskKey, $taskName);

        try {
            $deleted = ActivityLog::query()
                ->where('created_at', '<', now()->subDays(30))
                ->delete();

            $message = "Obrisano starih zapisa aktivnosti: {$deleted}.";

            $monitor->success(
                taskKey: $taskKey,
                taskName: $taskName,
                message: $message,
                processedCount: $deleted,
                metadata: [
                    'retention_days' => 30,
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