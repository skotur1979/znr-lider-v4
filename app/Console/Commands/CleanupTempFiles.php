<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupTempFiles extends Command
{
    protected $signature = 'temp:cleanup';

    protected $description = 'Delete temporary files older than 7 days';

    public function handle(): int
    {
        $folders = [
            storage_path('app/temp'),
            storage_path('app/private/temp'),
            storage_path('app/public/temp'),
        ];

        $deleted = 0;

        foreach ($folders as $folder) {
            if (! File::exists($folder)) {
                continue;
            }

            foreach (File::files($folder) as $file) {
                if ($file->getMTime() < now()->subDays(7)->timestamp) {
                    File::delete($file->getPathname());
                    $deleted++;
                }
            }
        }

        $this->info("Deleted temporary files: {$deleted}");

        return self::SUCCESS;
    }
}