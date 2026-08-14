<?php

namespace App\Console\Commands;

use App\Services\SystemTaskMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class CleanupTempFiles extends Command
{
    protected $signature = 'temp:cleanup';

    protected $description = 'Briše privremene datoteke starije od 7 dana';

    public function handle(SystemTaskMonitor $monitor): int
    {
        $taskKey = 'temp_files_cleanup';
        $taskName = 'Čišćenje privremenih datoteka';

        $monitor->start($taskKey, $taskName);

        try {
            $folders = [
                storage_path('app/temp'),
                storage_path('app/private/temp'),
                storage_path('app/public/temp'),
            ];

            $deleted = 0;
            $checkedFolders = 0;

            foreach ($folders as $folder) {
                if (! File::exists($folder)) {
                    continue;
                }

                $checkedFolders++;

                foreach (File::allFiles($folder) as $file) {
                    if ($file->getMTime() < now()->subDays(7)->timestamp) {
                        if (File::delete($file->getPathname())) {
                            $deleted++;
                        }
                    }
                }

                /*
                 * Nakon brisanja starih datoteka pokušaj ukloniti
                 * prazne poddirektorije, ali ne i glavni temp folder.
                 */
                $directories = collect(File::directories($folder))
                    ->sortByDesc(fn (string $directory) => substr_count($directory, DIRECTORY_SEPARATOR));

                foreach ($directories as $directory) {
                    if (
                        File::exists($directory)
                        && empty(File::allFiles($directory))
                        && empty(File::directories($directory))
                    ) {
                        File::deleteDirectory($directory);
                    }
                }
            }

            $message = "Obrisano privremenih datoteka: {$deleted}.";

            $monitor->success(
                taskKey: $taskKey,
                taskName: $taskName,
                message: $message,
                processedCount: $deleted,
                metadata: [
                    'retention_days' => 7,
                    'checked_folders' => $checkedFolders,
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