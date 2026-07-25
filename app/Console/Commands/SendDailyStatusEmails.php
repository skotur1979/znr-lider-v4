<?php

namespace App\Console\Commands;

use App\Mail\DailyStatusMail;
use App\Models\User;
use App\Services\SystemTaskMonitor;
use App\Services\UserStatusSummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDailyStatusEmails extends Command
{
    protected $signature = 'emails:send-daily-status {--user_id=}';

    protected $description = 'Šalje dnevni status e-mail korisnicima kojima je ta opcija uključena';

    public function handle(
        UserStatusSummaryService $service,
        SystemTaskMonitor $monitor,
    ): int {
        $taskKey = 'daily_status_email';
        $taskName = 'Dnevni status e-mail';

        $monitor->start($taskKey, $taskName);

        try {
            $query = User::query()
                ->where('is_active', true)
                ->where('daily_status_email_enabled', true)
                ->whereNotNull('email');

            if ($this->option('user_id')) {
                $query->where('id', $this->option('user_id'));
            }

            $users = $query->get();

            if ($users->isEmpty()) {
                $message = 'Nema korisnika za slanje dnevnog status e-maila.';

                $monitor->success(
                    taskKey: $taskKey,
                    taskName: $taskName,
                    message: $message,
                    processedCount: 0,
                    metadata: [
                        'sent' => 0,
                        'failed' => 0,
                    ],
                );

                $this->warn($message);

                return self::SUCCESS;
            }

            $sent = 0;
            $failed = 0;
            $errors = [];

            foreach ($users as $user) {
                try {
                    $data = $service->getDailySummary($user);

                    Mail::to($user->email)
                        ->send(new DailyStatusMail($data));

                    $sent++;

                    $this->info(
                        "Dnevni status poslan korisniku: {$user->email}"
                    );
                } catch (Throwable $exception) {
                    $failed++;
                    $errors[] = "{$user->email}: {$exception->getMessage()}";

                    report($exception);

                    $this->error(
                        "Greška za {$user->email}: {$exception->getMessage()}"
                    );
                }
            }

            if ($failed > 0) {
                $monitor->failure(
                    taskKey: $taskKey,
                    taskName: $taskName,
                    error: "Poslano: {$sent}, neuspješno: {$failed}.",
                    metadata: [
                        'sent' => $sent,
                        'failed' => $failed,
                        'errors' => array_slice($errors, 0, 10),
                    ],
                );

                return self::FAILURE;
            }

            $monitor->success(
                taskKey: $taskKey,
                taskName: $taskName,
                message: "Dnevni status uspješno je poslan za {$sent} korisnika.",
                processedCount: $sent,
                metadata: [
                    'sent' => $sent,
                    'failed' => 0,
                ],
            );

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