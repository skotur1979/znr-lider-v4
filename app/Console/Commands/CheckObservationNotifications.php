<?php

namespace App\Console\Commands;

use App\Models\Observation;
use App\Services\AppNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckObservationNotifications extends Command
{
    protected $signature =
        'notifications:observations';

    protected $description =
        'Šalje zvonce za rokove dodijeljenih radnji iz zapažanja.';

    public function handle(
        AppNotificationService $notifications
    ): int {
        $today = Carbon::today(
            'Europe/Zagreb'
        );

        $dueSoonDate =
            $today->copy()->addDays(7);

        $dueSoonSent = 0;
        $overdueSent = 0;

        Observation::query()
            ->with([
                'user',
                'responsibleUser',
            ])
            ->whereNotNull(
                'responsible_user_id'
            )
            ->whereNotNull(
                'target_date'
            )
            ->where(
                'status',
                '<>',
                'Complete'
            )
            ->where(
                function ($query) use (
                    $today,
                    $dueSoonDate
                ): void {
                    $query
                        ->whereDate(
                            'target_date',
                            $dueSoonDate
                        )
                        ->orWhereDate(
                            'target_date',
                            '<',
                            $today
                        );
                }
            )
            ->orderBy('id')
            ->chunkById(
                200,
                function ($observations) use (
                    $notifications,
                    $today,
                    $dueSoonDate,
                    &$dueSoonSent,
                    &$overdueSent
                ): void {

                    foreach (
                        $observations
                        as $observation
                    ) {
                        $targetDate =
                            $observation
                                ->target_date
                                ?->copy()
                                ->startOfDay();

                        if (! $targetDate) {
                            continue;
                        }

                        /*
                         * Točno 7 dana prije roka.
                         */
                        if (
                            $targetDate->isSameDay(
                                $dueSoonDate
                            )
                        ) {
                            if (
                                $observation
                                    ->due_soon_notified_for
                                    ?->isSameDay(
                                        $targetDate
                                    )
                            ) {
                                continue;
                            }

                            if (
                                $notifications
                                    ->sendObservationDueSoon(
                                        $observation
                                    )
                            ) {
                                $observation
                                    ->updateQuietly([
                                        'due_soon_notified_for'
                                            =>
                                            $targetDate
                                                ->toDateString(),
                                    ]);

                                $dueSoonSent++;
                            }

                            continue;
                        }

                        /*
                         * Rok istekao.
                         */
                        if (
                            $targetDate->lt(
                                $today
                            )
                        ) {
                            if (
                                $observation
                                    ->overdue_notified_for
                                    ?->isSameDay(
                                        $targetDate
                                    )
                            ) {
                                continue;
                            }

                            if (
                                $notifications
                                    ->sendObservationOverdue(
                                        $observation
                                    )
                            ) {
                                $observation
                                    ->updateQuietly([
                                        'overdue_notified_for'
                                            =>
                                            $targetDate
                                                ->toDateString(),
                                    ]);

                                $overdueSent++;
                            }
                        }
                    }
                }
            );

        $this->info(
            'Završeno. Rok uskoro: '
            . $dueSoonSent
            . ', isteklo: '
            . $overdueSent
            . '.'
        );

        return self::SUCCESS;
    }
}