<?php

namespace App\Filament\Pages;

use App\Models\SystemTaskRun;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;
use UnitEnum;

class SystemHealth extends Page
{
    protected string $view = 'filament.pages.system-health';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $navigationLabel = 'Status sustava';

    protected static ?string $title = 'Status sustava';

    protected static ?string $slug = 'status-sustava';

    protected static string|UnitEnum|null $navigationGroup = 'Administracija';

    protected static ?int $navigationSort = 98;

    public array $checks = [];

    public array $taskChecks = [];

    public array $serverChecks = [];

    public array $summary = [];

    public string $backupOutput = '';

    public function mount(): void
    {
        $this->loadChecks();
    }

    public static function canAccess(): bool
    {
        return Auth::check()
            && Auth::user()?->isSuperAdmin() === true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function loadChecks(): void
    {
        $this->loadConfigurationChecks();
        $this->loadTaskChecks();
        $this->loadServerChecks();
        $this->loadBackupOutput();
        $this->calculateSummary();
    }

    protected function loadConfigurationChecks(): void
    {
        $appUrl = (string) config('app.url');
        $isLocalUrl = str_contains($appUrl, '127.0.0.1')
            || str_contains($appUrl, 'localhost');

        $storageLinked = File::exists(public_path('storage'));

        $backupPath = storage_path('app/private/ZNR LIDER');
        $backupFolderExists = File::exists($backupPath);

        $mailDriver = (string) config('mail.default');
        $queueDriver = (string) config('queue.default');

        $this->checks = [
            [
                'label' => 'Okruženje',
                'value' => app()->environment(),
                'ok' => app()->environment('production'),
                'note' => app()->environment('production')
                    ? 'Produkcijsko okruženje'
                    : 'Lokalno ili testno okruženje.',
            ],
            [
                'label' => 'Debug mode',
                'value' => config('app.debug') ? 'UKLJUČEN' : 'ISKLJUČEN',
                'ok' => ! config('app.debug'),
                'note' => config('app.debug')
                    ? 'Prije produkcije postaviti APP_DEBUG=false.'
                    : 'Ispravno za produkciju.',
            ],
            [
                'label' => 'APP URL',
                'value' => $appUrl,
                'ok' => ! $isLocalUrl,
                'note' => $isLocalUrl
                    ? 'Prije produkcije postaviti stvarnu domenu.'
                    : 'URL izgleda produkcijski.',
            ],
            [
                'label' => 'Mail driver',
                'value' => $mailDriver,
                'ok' => $mailDriver === 'smtp',
                'note' => $mailDriver === 'smtp'
                    ? 'SMTP slanje e-mailova je uključeno.'
                    : 'Trenutni mail driver nije SMTP.',
            ],
            [
                'label' => 'Queue driver',
                'value' => $queueDriver,
                'ok' => true,
                'note' => $queueDriver === 'sync'
                    ? 'Sync je prihvatljiv dok se ne koristi queue worker.'
                    : 'Za ovaj queue driver mora biti aktivan queue worker.',
            ],
            [
                'label' => 'Storage link',
                'value' => $storageLinked ? 'LINKED' : 'NIJE LINKED',
                'ok' => $storageLinked,
                'note' => 'Provjera poveznice public/storage.',
            ],
            [
                'label' => 'Backup folder',
                'value' => $backupFolderExists ? 'Postoji' : 'Nije pronađen',
                'ok' => $backupFolderExists,
                'note' => 'Provjera mape storage/app/private/ZNR LIDER.',
            ],
            [
                'label' => 'Backup command',
                'value' => 'backup:list',
                'ok' => $this->artisanCommandExists('backup:list'),
                'note' => $this->artisanCommandExists('backup:list')
                    ? 'Backup naredba je dostupna.'
                    : 'Naredba backup:list nije pronađena.',
            ],
        ];
    }

    protected function loadTaskChecks(): void
    {
        $definitions = [
            'scheduler_heartbeat' => [
                'name' => 'Laravel scheduler',
                'warning_minutes' => 5,
                'critical_minutes' => 15,
            ],
            'database_backup' => [
                'name' => 'Dnevni backup baze',
                'warning_minutes' => 26 * 60,
                'critical_minutes' => 48 * 60,
            ],
            'full_backup' => [
                'name' => 'Tjedni kompletni backup',
                'warning_minutes' => 8 * 24 * 60,
                'critical_minutes' => 10 * 24 * 60,
            ],
            'daily_status_email' => [
                'name' => 'Dnevni status e-mail',
                'warning_minutes' => 36 * 60,
                'critical_minutes' => 48 * 60,
            ],
            'weekly_status_email' => [
                'name' => 'Tjedni status e-mail',
                'warning_minutes' => 8 * 24 * 60,
                'critical_minutes' => 10 * 24 * 60,
            ],
            'kpi_generation' => [
                'name' => 'Automatsko generiranje KPI vrijednosti',
                'warning_minutes' => 36 * 60,
                'critical_minutes' => 48 * 60,
            ],
            'activity_cleanup' => [
                'name' => 'Čišćenje aktivnosti',
                'warning_minutes' => 36 * 60,
                'critical_minutes' => 48 * 60,
            ],
            'temp_files_cleanup' => [
                'name' => 'Čišćenje privremenih datoteka',
                'warning_minutes' => 36 * 60,
                'critical_minutes' => 48 * 60,
            ],
            'backup_cleanup' => [
                'name' => 'Čišćenje starih backupa',
                'warning_minutes' => 36 * 60,
                'critical_minutes' => 48 * 60,
            ],
        ];

        if (! Schema::hasTable('system_task_runs')) {
            $this->taskChecks = collect($definitions)
                ->map(function (array $definition, string $key): array {
                    return [
                        'key' => $key,
                        'label' => $definition['name'],
                        'status' => 'never_run',
                        'status_label' => 'Tablica nije dostupna',
                        'last_run' => null,
                        'message' => 'Migracija system_task_runs još nije izvršena.',
                        'processed_count' => null,
                        'duration_ms' => null,
                    ];
                })
                ->values()
                ->all();

            return;
        }

        $tasks = SystemTaskRun::query()
            ->get()
            ->keyBy('task_key');

        $this->taskChecks = [];

        foreach ($definitions as $key => $definition) {
            /** @var SystemTaskRun|null $task */
            $task = $tasks->get($key);

            if (! $task) {
                $this->taskChecks[] = [
                    'key' => $key,
                    'label' => $definition['name'],
                    'status' => 'never_run',
                    'status_label' => 'Nije pokrenuto',
                    'last_run' => null,
                    'message' => 'Zadatak još nema evidentirano izvršenje.',
                    'processed_count' => null,
                    'duration_ms' => null,
                ];

                continue;
            }

            $referenceTime = $task->last_success_at
                ?? $task->last_finished_at
                ?? $task->last_started_at;

            if ($task->status === 'failed') {
                $status = 'critical';
                $statusLabel = 'Neuspješno';
            } elseif (! $referenceTime) {
                $status = 'never_run';
                $statusLabel = 'Nije završeno';
            } else {
                $minutesSinceRun = $referenceTime->diffInMinutes(now());

                if ($minutesSinceRun > $definition['critical_minutes']) {
                    $status = 'critical';
                    $statusLabel = 'Kritično kašnjenje';
                } elseif ($minutesSinceRun > $definition['warning_minutes']) {
                    $status = 'warning';
                    $statusLabel = 'Kasni';
                } elseif ($task->status === 'running') {
                    $status = 'warning';
                    $statusLabel = 'U tijeku';
                } else {
                    $status = 'ok';
                    $statusLabel = 'U redu';
                }
            }

            $this->taskChecks[] = [
                'key' => $key,
                'label' => $task->task_name ?: $definition['name'],
                'status' => $status,
                'status_label' => $statusLabel,
                'last_run' => $referenceTime
                    ? $referenceTime
                        ->copy()
                        ->timezone('Europe/Zagreb')
                        ->format('d.m.Y. H:i:s')
                    : null,
                'message' => $task->message ?: 'Nema dodatne poruke.',
                'processed_count' => $task->processed_count,
                'duration_ms' => $task->duration_ms,
            ];
        }
    }

    protected function loadServerChecks(): void
    {
        $diskTotal = @disk_total_space(base_path());
        $diskFree = @disk_free_space(base_path());

        $diskUsed = is_numeric($diskTotal) && is_numeric($diskFree)
            ? (int) $diskTotal - (int) $diskFree
            : null;

        $diskPercentage = is_numeric($diskTotal)
            && (int) $diskTotal > 0
            && $diskUsed !== null
                ? round(($diskUsed / (int) $diskTotal) * 100, 1)
                : null;

        $databaseOk = true;
        $databaseMessage = 'Veza s bazom podataka je dostupna.';

        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            $databaseOk = false;
            $databaseMessage = $exception->getMessage();
        }

        $failedJobs = 0;
        $pendingJobs = 0;

        try {
            if (Schema::hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }

            if (
                config('queue.default') === 'database'
                && Schema::hasTable('jobs')
            ) {
                $pendingJobs = DB::table('jobs')->count();
            }
        } catch (Throwable) {
            // Status baze već se zasebno prikazuje.
        }

        $diskStatus = match (true) {
            $diskPercentage === null => 'warning',
            $diskPercentage >= 90 => 'critical',
            $diskPercentage >= 80 => 'warning',
            default => 'ok',
        };

        $this->serverChecks = [
            [
                'label' => 'Prostor na disku',
                'value' => $diskPercentage !== null
                    ? number_format($diskPercentage, 1, ',', '.') . '% zauzeto'
                    : 'Nije dostupno',
                'status' => $diskStatus,
                'note' => is_numeric($diskFree)
                    ? 'Slobodno: ' . $this->formatBytes((int) $diskFree)
                    : 'Podatak o slobodnom prostoru nije dostupan.',
            ],
            [
                'label' => 'Baza podataka',
                'value' => $databaseOk ? 'Dostupna' : 'Nedostupna',
                'status' => $databaseOk ? 'ok' : 'critical',
                'note' => $databaseMessage,
            ],
            [
                'label' => 'Poslovi na čekanju',
                'value' => (string) $pendingJobs,
                'status' => $pendingJobs > 100 ? 'warning' : 'ok',
                'note' => config('queue.default') === 'sync'
                    ? 'Queue koristi sync način rada.'
                    : 'Broj zapisa koji čekaju izvršenje.',
            ],
            [
                'label' => 'Neuspjeli queue poslovi',
                'value' => (string) $failedJobs,
                'status' => $failedJobs > 0 ? 'critical' : 'ok',
                'note' => $failedJobs > 0
                    ? 'Postoje neuspjeli poslovi koje treba provjeriti.'
                    : 'Nema evidentiranih neuspjelih poslova.',
            ],
        ];
    }

    protected function loadBackupOutput(): void
    {
        if (! $this->artisanCommandExists('backup:list')) {
            $this->backupOutput = 'Naredba backup:list nije dostupna.';

            return;
        }

        try {
            Artisan::call('backup:list');

            $output = trim(Artisan::output());

            $this->backupOutput = $output !== ''
                ? $output
                : 'Backup naredba nije vratila nikakav rezultat.';
        } catch (Throwable $exception) {
            $this->backupOutput = 'Greška pri dohvaćanju backup statusa: '
                . $exception->getMessage();
        }
    }

    protected function calculateSummary(): void
    {
        $configurationStatuses = collect($this->checks)
            ->map(fn (array $check): string => $check['ok'] ? 'ok' : 'critical');

        $taskStatuses = collect($this->taskChecks)
            ->pluck('status');

        $serverStatuses = collect($this->serverChecks)
            ->pluck('status');

        $statuses = $configurationStatuses
            ->merge($taskStatuses)
            ->merge($serverStatuses);

        $critical = $statuses
            ->filter(fn (string $status): bool => $status === 'critical')
            ->count();

        $warning = $statuses
            ->filter(
                fn (string $status): bool =>
                    in_array($status, ['warning', 'never_run'], true)
            )
            ->count();

        $ok = $statuses
            ->filter(fn (string $status): bool => $status === 'ok')
            ->count();

        $this->summary = [
            'overall' => match (true) {
                $critical > 0 => 'critical',
                $warning > 0 => 'warning',
                default => 'ok',
            },
            'ok' => $ok,
            'warning' => $warning,
            'critical' => $critical,
            'updated_at' => now()
                ->timezone('Europe/Zagreb')
                ->format('d.m.Y. H:i:s'),
        ];
    }

    protected function artisanCommandExists(string $command): bool
    {
        try {
            return array_key_exists(
                $command,
                Artisan::all()
            );
        } catch (Throwable) {
            return false;
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_099_511_627_776) {
            return number_format(
                $bytes / 1_099_511_627_776,
                2,
                ',',
                '.'
            ) . ' TB';
        }

        if ($bytes >= 1_073_741_824) {
            return number_format(
                $bytes / 1_073_741_824,
                2,
                ',',
                '.'
            ) . ' GB';
        }

        if ($bytes >= 1_048_576) {
            return number_format(
                $bytes / 1_048_576,
                2,
                ',',
                '.'
            ) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format(
                $bytes / 1024,
                2,
                ',',
                '.'
            ) . ' KB';
        }

        return $bytes . ' B';
    }

    public function sendTestMail(): void
    {
        $user = Auth::user();

        if (! $user?->email) {
            Notification::make()
                ->title('Testni mail nije poslan.')
                ->body('Prijavljeni korisnik nema upisanu e-mail adresu.')
                ->danger()
                ->send();

            return;
        }

        try {
            Mail::raw(
                'Test mail iz ZNR LIDER aplikacije.',
                function ($message) use ($user): void {
                    $message
                        ->to($user->email)
                        ->subject('ZNR LIDER - test mail');
                }
            );

            Notification::make()
                ->title('Testni mail je poslan.')
                ->body('Poruka je poslana na ' . $user->email . '.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Testni mail nije poslan.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}