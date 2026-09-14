<?php

namespace App\Console\Commands;

use App\Services\SystemTaskMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class CleanupTempFiles extends Command
{
    protected $signature = 'temp:cleanup';

    protected $description = 'Briše stare privremene datoteke i OCR datoteke starije od 24 sata';

    public function handle(SystemTaskMonitor $monitor): int
    {
        $taskKey = 'temp_files_cleanup';
        $taskName = 'Čišćenje privremenih datoteka';

        $monitor->start($taskKey, $taskName);

        try {
            /*
             * Standardni privremeni folderi:
             * zadržavanje 7 dana.
             */
            $folders7Days = [
                storage_path('app/temp'),
                storage_path('app/private/temp'),
                storage_path('app/public/temp'),
            ];

            /*
             * OCR privremeni folderi:
             * zadržavanje samo 24 sata.
             */
            $ocrFolders24Hours = [
                storage_path('app/tmp/machine-ocr'),
                storage_path('app/tmp/machine-ocr-pages'),
            ];

            $deleted = 0;
            $deletedStandard = 0;
            $deletedOcr = 0;
            $checkedFolders = 0;

            /*
             * 1) Standardni temp folderi - 7 dana
             */
            foreach ($folders7Days as $folder) {
                if (! File::exists($folder)) {
                    continue;
                }

                $checkedFolders++;

                foreach (File::allFiles($folder) as $file) {
                    if ($file->getMTime() < now()->subDays(7)->timestamp) {
                        if (File::delete($file->getPathname())) {
                            $deleted++;
                            $deletedStandard++;
                        }
                    }
                }

                $this->removeEmptySubdirectories($folder);
            }

            /*
             * 2) OCR temp folderi - 24 sata
             */
            foreach ($ocrFolders24Hours as $folder) {
                if (! File::exists($folder)) {
                    continue;
                }

                $checkedFolders++;

                foreach (File::allFiles($folder) as $file) {
                    if ($file->getMTime() < now()->subHours(24)->timestamp) {
                        if (File::delete($file->getPathname())) {
                            $deleted++;
                            $deletedOcr++;
                        }
                    }
                }

                $this->removeEmptySubdirectories($folder);
            }

            $message = "Obrisano privremenih datoteka: {$deleted} "
                . "(standardne: {$deletedStandard}, OCR: {$deletedOcr}).";

            $monitor->success(
                taskKey: $taskKey,
                taskName: $taskName,
                message: $message,
                processedCount: $deleted,
                metadata: [
                    'standard_retention_days' => 7,
                    'ocr_retention_hours' => 24,
                    'deleted_standard_files' => $deletedStandard,
                    'deleted_ocr_files' => $deletedOcr,
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

    /**
     * Briše prazne poddirektorije,
     * ali nikada ne briše glavni folder.
     */
    protected function removeEmptySubdirectories(string $folder): void
    {
        if (! File::exists($folder)) {
            return;
        }

        /*
         * allDirectories() uzima i dublje poddirektorije.
         * Sortiramo od najdubljih prema višima kako bi se
         * prazna struktura mogla pravilno očistiti.
         */
        $directories = collect(File::allDirectories($folder))
            ->sortByDesc(
                fn (string $directory) => substr_count(
                    $directory,
                    DIRECTORY_SEPARATOR
                )
            );

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
}