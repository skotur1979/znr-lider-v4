<?php

namespace App\Console\Commands;

use App\Mail\DeadlineReminderMail;
use App\Models\User;
use App\Services\SystemTaskMonitor;
use App\Services\UserDeadlineReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDeadlineReminderEmails extends Command
{
    protected $signature =
        'emails:send-deadline-reminders {--user_id=}';

    protected $description =
        'Šalje individualne podsjetnike za rokove korisnicima';

    public function handle(
        UserDeadlineReminderService $service,
        SystemTaskMonitor $monitor,
    ): int {
        $taskKey =
            'deadline_reminder_email';

        $taskName =
            'Podsjetnici na rokove';

        $monitor->start(
            $taskKey,
            $taskName
        );

        try {
            $query =
                User::query()
                    ->whereIn(
                        'role',
                        [
                            'org_admin',
                            'org_user',
                        ]
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->whereNotNull(
                        'email'
                    )
                    ->withoutTrashed();

            if (
                $this->option(
                    'user_id'
                )
            ) {
                $query->where(
                    'id',
                    $this->option(
                        'user_id'
                    )
                );
            }

            $users =
                $query->get();

            $sent = 0;
            $failed = 0;
            $errors = [];

            foreach ($users as $user) {
                $reminderTypes = [];

                if (
                    $user
                        ->reminder_30_days_enabled
                ) {
                    $reminderTypes[] = 30;
                }

                if (
                    $user
                        ->reminder_14_days_enabled
                ) {
                    $reminderTypes[] = 14;
                }

                if (
                    $user
                        ->reminder_7_days_enabled
                ) {
                    $reminderTypes[] = 7;
                }

                if (
                    $user
                        ->reminder_overdue_enabled
                ) {
                    $reminderTypes[] =
                        'overdue';
                }

                foreach (
                    $reminderTypes
                    as $type
                ) {
                    try {
                        $data =
                            $service
                                ->getReminders(
                                    $user,
                                    $type
                                );

                        /*
                         * Ne šalji prazan mail.
                         */
                        if (
                            $data['count']
                            === 0
                        ) {
                            continue;
                        }

                        Mail::to(
                            $user->email
                        )->send(
                            new DeadlineReminderMail(
                                $data
                            )
                        );

                        $sent++;

                        $this->info(
                            "Podsjetnik {$data['label']} poslan: {$user->email}"
                        );
                    } catch (
                        Throwable $exception
                    ) {
                        $failed++;

                        $errors[] =
                            "{$user->email}: "
                            . $exception
                                ->getMessage();

                        report(
                            $exception
                        );

                        $this->error(
                            "Greška za {$user->email}: "
                            . $exception
                                ->getMessage()
                        );
                    }
                }
            }

            if ($failed > 0) {
                $monitor->failure(
                    taskKey:
                        $taskKey,

                    taskName:
                        $taskName,

                    error:
                        "Poslano: {$sent}, neuspješno: {$failed}.",

                    metadata: [
                        'sent' =>
                            $sent,

                        'failed' =>
                            $failed,

                        'errors' =>
                            array_slice(
                                $errors,
                                0,
                                10
                            ),
                    ],
                );

                return self::FAILURE;
            }

            $monitor->success(
                taskKey:
                    $taskKey,

                taskName:
                    $taskName,

                message:
                    "Podsjetnici uspješno poslani. Ukupno mailova: {$sent}.",

                processedCount:
                    $sent,

                metadata: [
                    'sent' =>
                        $sent,

                    'failed' =>
                        0,
                ],
            );

            return self::SUCCESS;
        } catch (
            Throwable $exception
        ) {
            $monitor->failure(
                taskKey:
                    $taskKey,

                taskName:
                    $taskName,

                error:
                    $exception,
            );

            report(
                $exception
            );

            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}