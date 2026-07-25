<?php

namespace App\Filament\Pages;

use App\Models\SystemTaskRun;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Process;
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

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedHeart;

    protected static ?string $navigationLabel = 'Status sustava';

    protected static ?string $title = 'Status sustava';

    protected static ?string $slug = 'status-sustava';

    protected static string|UnitEnum|null $navigationGroup =
        'Administracija';

    protected static ?int $navigationSort = 98;

    public array $checks = [];

    public array $taskChecks = [];

    public array $serverChecks = [];

    public array $summary = [];

    public array $hostingChecks = [];

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
        $this->loadHostingChecks();
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

        $isProduction = app()->environment('production');

        $debugEnabled = (bool) config('app.debug');

        $backupCommandAvailable =
            $this->artisanCommandExists('backup:list');

        $this->checks = [
            [
                'label' => 'Okruženje',
                'value' => app()->environment(),
                'status' => $isProduction ? 'ok' : 'warning',
                'note' => $isProduction
                    ? 'Aplikacija radi u produkcijskom okruženju.'
                    : 'Aplikacija radi u lokalnom ili testnom okruženju.',
            ],
            [
                'label' => 'Debug mode',
                'value' => $debugEnabled ? 'UKLJUČEN' : 'ISKLJUČEN',
                'status' => $debugEnabled ? 'warning' : 'ok',
                'note' => $debugEnabled
                    ? 'Za produkciju preporučuje se APP_DEBUG=false.'
                    : 'Debug način rada ispravno je isključen.',
            ],
            [
                'label' => 'APP URL',
                'value' => $appUrl,
                'status' => $isLocalUrl ? 'warning' : 'ok',
                'note' => $isLocalUrl
                    ? 'Postavljena je lokalna adresa aplikacije.'
                    : 'Postavljena je produkcijska adresa aplikacije.',
            ],
            [
                'label' => 'Mail driver',
                'value' => $mailDriver,
                'status' => $mailDriver === 'smtp'
                    ? 'ok'
                    : 'warning',
                'note' => $mailDriver === 'smtp'
                    ? 'SMTP slanje e-mailova je uključeno.'
                    : 'Trenutni mail driver nije SMTP.',
            ],
            [
                'label' => 'Queue driver',
                'value' => $queueDriver,
                'status' => 'ok',
                'note' => $queueDriver === 'sync'
                    ? 'Sync je prihvatljiv dok aplikacija ne koristi queue worker.'
                    : 'Za ovaj queue driver mora biti aktivan queue worker.',
            ],
            [
                'label' => 'Storage link',
                'value' => $storageLinked
                    ? 'LINKED'
                    : 'NIJE LINKED',
                'status' => $storageLinked
                    ? 'ok'
                    : 'critical',
                'note' => $storageLinked
                    ? 'Poveznica public/storage postoji.'
                    : 'Nedostaje poveznica public/storage.',
            ],
            [
                'label' => 'Backup folder',
                'value' => $backupFolderExists
                    ? 'Postoji'
                    : 'Nije pronađen',
                'status' => $backupFolderExists
                    ? 'ok'
                    : 'critical',
                'note' => $backupFolderExists
                    ? 'Mapa za spremanje backupa je dostupna.'
                    : 'Mapa storage/app/private/ZNR LIDER nije pronađena.',
            ],
            [
                'label' => 'Backup command',
                'value' => 'backup:list',
                'status' => $backupCommandAvailable
                    ? 'ok'
                    : 'critical',
                'note' => $backupCommandAvailable
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
                'schedule' => 'Svake minute',
                'next_run' => $this->nextMinute(),
                'warning_minutes' => 5,
                'critical_minutes' => 15,
            ],
            'database_backup' => [
                'name' => 'Dnevni backup baze',
                'schedule' => 'Svaki dan u 02:30',
                'next_run' => $this->nextDailyAt('02:30'),
                'warning_minutes' => 26 * 60,
                'critical_minutes' => 48 * 60,
            ],
            'full_backup' => [
                'name' => 'Tjedni kompletni backup',
                'schedule' => 'Nedjeljom u 03:00',
                'next_run' => $this->nextWeeklyAt(
                    Carbon::SUNDAY,
                    '03:00'
                ),
                'warning_minutes' => 8 * 24 * 60,
                'critical_minutes' => 10 * 24 * 60,
            ],
            'daily_status_email' => [
                'name' => 'Dnevni status e-mail',
                'schedule' => 'Radnim danom u 08:30',
                'next_run' => $this->nextWeekdayAt('08:30'),
                'warning_minutes' => 36 * 60,
                'critical_minutes' => 48 * 60,
            ],
            'weekly_status_email' => [
                'name' => 'Tjedni status e-mail',
                'schedule' => 'Ponedjeljkom u 08:00',
                'next_run' => $this->nextWeeklyAt(
                    Carbon::MONDAY,
                    '08:00'
                ),
                'warning_minutes' => 8 * 24 * 60,
                'critical_minutes' => 10 * 24 * 60,
            ],
            'kpi_generation' => [
                'name' => 'Automatsko generiranje KPI vrijednosti',
                'schedule' => 'Svaki dan u 01:10',
                'next_run' => $this->nextDailyAt('01:10'),
                'warning_minutes' => 36 * 60,
                'critical_minutes' => 48 * 60,
            ],
            'activity_cleanup' => [
                'name' => 'Čišćenje aktivnosti',
                'schedule' => 'Svaki dan u 02:00',
                'next_run' => $this->nextDailyAt('02:00'),
                'warning_minutes' => 36 * 60,
                'critical_minutes' => 48 * 60,
            ],
            'temp_files_cleanup' => [
                'name' => 'Čišćenje privremenih datoteka',
                'schedule' => 'Svaki dan u 02:15',
                'next_run' => $this->nextDailyAt('02:15'),
                'warning_minutes' => 36 * 60,
                'critical_minutes' => 48 * 60,
            ],
            'backup_cleanup' => [
                'name' => 'Čišćenje starih backupa',
                'schedule' => 'Svaki dan u 04:00',
                'next_run' => $this->nextDailyAt('04:00'),
                'warning_minutes' => 36 * 60,
                'critical_minutes' => 48 * 60,
            ],
        ];

        if (! Schema::hasTable('system_task_runs')) {
            $this->taskChecks = collect($definitions)
                ->map(
                    function (
                        array $definition,
                        string $key
                    ): array {
                        return [
                            'key' => $key,
                            'label' => $definition['name'],
                            'status' => 'critical',
                            'status_label' => 'Tablica nije dostupna',
                            'last_run' => null,
                            'schedule' => $definition['schedule'],
                            'next_run' => $definition['next_run'],
                            'message' =>
                                'Migracija system_task_runs nije izvršena.',
                            'processed_count' => null,
                            'duration_ms' => null,
                        ];
                    }
                )
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
                    'status' => 'info',
                    'status_label' => 'Još nije izvršeno',
                    'last_run' => null,
                    'schedule' => $definition['schedule'],
                    'next_run' => $definition['next_run'],
                    'message' =>
                        'Zadatak još nema evidentirano izvršenje.',
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
            } elseif ($task->status === 'running') {
                $status = 'warning';
                $statusLabel = 'U tijeku';
            } elseif (! $referenceTime) {
                $status = 'info';
                $statusLabel = 'Još nije završeno';
            } else {
                $minutesSinceRun = $referenceTime
                    ->diffInMinutes(now());

                if (
                    $minutesSinceRun >
                    $definition['critical_minutes']
                ) {
                    $status = 'critical';
                    $statusLabel = 'Kritično kašnjenje';
                } elseif (
                    $minutesSinceRun >
                    $definition['warning_minutes']
                ) {
                    $status = 'warning';
                    $statusLabel = 'Kasni';
                } else {
                    $status = 'ok';
                    $statusLabel = 'U redu';
                }
            }

            $this->taskChecks[] = [
                'key' => $key,
                'label' => $task->task_name
                    ?: $definition['name'],
                'status' => $status,
                'status_label' => $statusLabel,
                'last_run' => $referenceTime
                    ? $referenceTime
                        ->copy()
                        ->timezone('Europe/Zagreb')
                        ->format('d.m.Y. H:i:s')
                    : null,
                'schedule' => $definition['schedule'],
                'next_run' => $definition['next_run'],
                'message' => $task->message
                    ?: 'Nema dodatne poruke.',
                'processed_count' => $task->processed_count,
                'duration_ms' => $task->duration_ms,
            ];
        }
    }

    protected function loadHostingChecks(): void
{
    if (PHP_OS_FAMILY === 'Windows') {
        $this->hostingChecks = [
            [
                'label' => 'cPanel prostor računa',
                'value' => 'Dostupno samo na serveru',
                'status' => 'info',
                'note' => 'cPanel UAPI nije dostupan u lokalnom Windows okruženju.',
            ],
            [
                'label' => 'cPanel broj datoteka',
                'value' => 'Dostupno samo na serveru',
                'status' => 'info',
                'note' => 'Podatak o inode potrošnji učitava se na produkcijskom serveru.',
            ],
        ];

        return;
    }

    try {
        $result = Process::timeout(15)->run([
            'uapi',
            '--output=json',
            'Quota',
            'get_quota_info',
        ]);

        if (! $result->successful()) {
            $this->hostingChecks = [
                [
                    'label' => 'cPanel kvota',
                    'value' => 'Nije dostupna',
                    'status' => 'warning',
                    'note' => trim($result->errorOutput())
                        ?: 'cPanel UAPI naredba nije uspješno izvršena.',
                ],
            ];

            return;
        }

        $response = json_decode($result->output(), true);

        $data = $response['result']['data'] ?? null;

        if (! is_array($data)) {
            $this->hostingChecks = [
                [
                    'label' => 'cPanel kvota',
                    'value' => 'Neispravan odgovor',
                    'status' => 'warning',
                    'note' => 'cPanel UAPI nije vratio očekivane podatke.',
                ],
            ];

            return;
        }

        $megabytesUsed = (float) ($data['megabytes_used'] ?? 0);
        $megabyteLimit = (float) ($data['megabyte_limit'] ?? 0);
        $megabytesRemain = (float) ($data['megabytes_remain'] ?? 0);

        $inodeUsed = (int) ($data['inodes_used'] ?? 0);
        $inodeLimit = (int) ($data['inode_limit'] ?? 0);
        $inodeRemain = (int) ($data['inodes_remain'] ?? 0);

        $storageUsedText = $this->formatBytes(
            (int) round($megabytesUsed * 1024 * 1024)
        );

        if ($megabyteLimit > 0) {
            $storagePercentage = round(
                ($megabytesUsed / $megabyteLimit) * 100,
                1
            );

            $storageStatus = match (true) {
                $storagePercentage >= 95 => 'critical',
                $storagePercentage >= 85 => 'warning',
                default => 'ok',
            };

            $storageValue = number_format(
                $storagePercentage,
                1,
                ',',
                '.'
            ) . '% zauzeto';

            $storageNote =
                'Iskorišteno: '
                . $storageUsedText
                . ' od '
                . $this->formatBytes(
                    (int) round($megabyteLimit * 1024 * 1024)
                )
                . '. Slobodno: '
                . $this->formatBytes(
                    (int) round($megabytesRemain * 1024 * 1024)
                )
                . '.';
        } else {
            $storageStatus = 'ok';
            $storageValue = $storageUsedText . ' iskorišteno';
            $storageNote =
                'cPanel račun nema postavljeno ograničenje prostora u MB.';
        }

        $inodePercentage = $inodeLimit > 0
            ? round(($inodeUsed / $inodeLimit) * 100, 1)
            : null;

        $inodeStatus = match (true) {
            $inodePercentage === null => 'info',
            $inodePercentage >= 95 => 'critical',
            $inodePercentage >= 80 => 'warning',
            default => 'ok',
        };

        $inodeValue = $inodeLimit > 0
            ? number_format($inodePercentage, 1, ',', '.')
                . '% iskorišteno'
            : number_format($inodeUsed, 0, ',', '.');

        $inodeNote = $inodeLimit > 0
            ? 'Iskorišteno: '
                . number_format($inodeUsed, 0, ',', '.')
                . ' od '
                . number_format($inodeLimit, 0, ',', '.')
                . '. Preostalo: '
                . number_format($inodeRemain, 0, ',', '.')
                . ' datoteka i direktorija.'
            : 'cPanel nije vratio ograničenje broja datoteka.';

        $this->hostingChecks = [
            [
                'label' => 'cPanel prostor računa',
                'value' => $storageValue,
                'status' => $storageStatus,
                'note' => $storageNote,
            ],
            [
                'label' => 'cPanel broj datoteka',
                'value' => $inodeValue,
                'status' => $inodeStatus,
                'note' => $inodeNote,
            ],
        ];
    } catch (Throwable $exception) {
        report($exception);

        $this->hostingChecks = [
            [
                'label' => 'cPanel kvota',
                'value' => 'Provjera nije uspjela',
                'status' => 'warning',
                'note' => $exception->getMessage(),
            ],
        ];
    }
}
    protected function loadServerChecks(): void
    {
        $diskTotal = @disk_total_space(base_path());

        $diskFree = @disk_free_space(base_path());

        $diskUsed = is_numeric($diskTotal)
            && is_numeric($diskFree)
                ? (int) $diskTotal - (int) $diskFree
                : null;

        $diskPercentage = is_numeric($diskTotal)
            && (int) $diskTotal > 0
            && $diskUsed !== null
                ? round(
                    ($diskUsed / (int) $diskTotal) * 100,
                    1
                )
                : null;

        $databaseOk = true;

        $databaseMessage =
            'Veza s bazom podataka je dostupna.';

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
            // Status veze s bazom prikazuje se zasebno.
        }

        $diskStatus = match (true) {
            $diskPercentage === null => 'warning',
            $diskPercentage >= 95 => 'critical',
            $diskPercentage >= 85 => 'warning',
            default => 'ok',
        };

        $diskNote = match (true) {
            $diskPercentage === null =>
                'Podatak o prostoru nije dostupan.',

            $diskPercentage >= 95 =>
                'Kritično malo slobodnog prostora. Potrebno je odmah osloboditi prostor.',

            $diskPercentage >= 85 =>
                'Slobodnog prostora je sve manje. Preporučuje se čišćenje ili povećanje prostora.',

            default =>
                'Na disku ima dovoljno slobodnog prostora.',
        };

        if (is_numeric($diskFree)) {
            $diskNote .= ' Slobodno: '
                . $this->formatBytes((int) $diskFree)
                . '.';
        }

        $this->serverChecks = [
            [
                'label' => 'Prostor na disku',
                'value' => $diskPercentage !== null
                    ? number_format(
                        $diskPercentage,
                        1,
                        ',',
                        '.'
                    ) . '% zauzeto'
                    : 'Nije dostupno',
                'status' => $diskStatus,
                'note' => $diskNote,
            ],
            [
                'label' => 'Baza podataka',
                'value' => $databaseOk
                    ? 'Dostupna'
                    : 'Nedostupna',
                'status' => $databaseOk
                    ? 'ok'
                    : 'critical',
                'note' => $databaseMessage,
            ],
            [
                'label' => 'Poslovi na čekanju',
                'value' => (string) $pendingJobs,
                'status' => $pendingJobs > 100
                    ? 'warning'
                    : 'ok',
                'note' => config('queue.default') === 'sync'
                    ? 'Queue koristi sync način rada.'
                    : 'Broj poslova koji čekaju izvršenje.',
            ],
            [
                'label' => 'Neuspjeli queue poslovi',
                'value' => (string) $failedJobs,
                'status' => $failedJobs > 0
                    ? 'critical'
                    : 'ok',
                'note' => $failedJobs > 0
                    ? 'Postoje neuspjeli poslovi koje treba provjeriti.'
                    : 'Nema evidentiranih neuspjelih poslova.',
            ],
        ];
    }

    protected function loadBackupOutput(): void
    {
        if (! $this->artisanCommandExists('backup:list')) {
            $this->backupOutput =
                'Naredba backup:list nije dostupna.';

            return;
        }

        try {
            Artisan::call('backup:list');

            $output = trim(Artisan::output());

            $this->backupOutput = $output !== ''
                ? $output
                : 'Backup naredba nije vratila rezultat.';
        } catch (Throwable $exception) {
            $this->backupOutput =
                'Greška pri dohvaćanju backup statusa: '
                . $exception->getMessage();
        }
    }

    protected function calculateSummary(): void
    {
        $configurationStatuses = collect($this->checks)
            ->pluck('status');

        $taskStatuses = collect($this->taskChecks)
            ->pluck('status');

        $serverStatuses = collect($this->serverChecks)
            ->pluck('status');
        
            $hostingStatuses = collect($this->hostingChecks)
            ->pluck('status');

        $statuses = $configurationStatuses
            ->merge($taskStatuses)
            ->merge($serverStatuses)
            ->merge($hostingStatuses);

        $critical = $statuses
            ->filter(
                fn (string $status): bool =>
                    $status === 'critical'
            )
            ->count();

        $warning = $statuses
            ->filter(
                fn (string $status): bool =>
                    $status === 'warning'
            )
            ->count();

        $info = $statuses
            ->filter(
                fn (string $status): bool =>
                    $status === 'info'
            )
            ->count();

        $ok = $statuses
            ->filter(
                fn (string $status): bool =>
                    $status === 'ok'
            )
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
            'info' => $info,
            'updated_at' => now()
                ->timezone('Europe/Zagreb')
                ->format('d.m.Y. H:i:s'),
        ];
    }

    protected function nextMinute(): string
    {
        return now('Europe/Zagreb')
            ->addMinute()
            ->startOfMinute()
            ->format('d.m.Y. H:i');
    }

    protected function nextDailyAt(string $time): string
    {
        [$hour, $minute] = array_map(
            'intval',
            explode(':', $time)
        );

        $now = now('Europe/Zagreb');

        $next = $now->copy()
            ->setTime($hour, $minute, 0);

        if ($next->lessThanOrEqualTo($now)) {
            $next->addDay();
        }

        return $next->format('d.m.Y. H:i');
    }

    protected function nextWeekdayAt(string $time): string
    {
        [$hour, $minute] = array_map(
            'intval',
            explode(':', $time)
        );

        $now = now('Europe/Zagreb');

        $next = $now->copy()
            ->setTime($hour, $minute, 0);

        if ($next->lessThanOrEqualTo($now)) {
            $next->addDay();
        }

        while ($next->isWeekend()) {
            $next->addDay();
        }

        return $next->format('d.m.Y. H:i');
    }

    protected function nextWeeklyAt(
        int $dayOfWeek,
        string $time
    ): string {
        [$hour, $minute] = array_map(
            'intval',
            explode(':', $time)
        );

        $now = now('Europe/Zagreb');

        $daysUntil = (
            $dayOfWeek - $now->dayOfWeek + 7
        ) % 7;

        $next = $now->copy()
            ->startOfDay()
            ->addDays($daysUntil)
            ->setTime($hour, $minute, 0);

        if ($next->lessThanOrEqualTo($now)) {
            $next->addWeek();
        }

        return $next->format('d.m.Y. H:i');
    }

    protected function artisanCommandExists(
        string $command
    ): bool {
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
                ->body(
                    'Prijavljeni korisnik nema e-mail adresu.'
                )
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
                ->body(
                    'Poruka je poslana na '
                    . $user->email
                    . '.'
                )
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