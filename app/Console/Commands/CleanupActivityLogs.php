<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class CleanupActivityLogs extends Command
{
    protected $signature = 'activitylogs:cleanup';

    protected $description = 'Delete activity logs older than 30 days';

    public function handle(): int
    {
        $deleted = ActivityLog::query()
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $this->info("Old activity logs deleted: {$deleted}");

        return self::SUCCESS;
    }
}