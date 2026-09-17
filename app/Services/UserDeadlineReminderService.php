<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Fire;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Models\Observation;
use App\Models\User;
use App\Models\WorkPermit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UserDeadlineReminderService
{
    public function getReminders(
        User $user,
        int|string $type
    ): array {
        $targetDate =
            $this->targetDate($type);

        $ownerId =
            (int) $user->ownerId();

        $items = collect();

        $items = $items->merge(
            $this->medicalExaminations(
                $ownerId,
                $targetDate
            )
        );

        $items = $items->merge(
            $this->employeeCertificates(
                $ownerId,
                $targetDate
            )
        );

        $items = $items->merge(
            $this->znrTraining(
                $ownerId,
                $targetDate
            )
        );

        $items = $items->merge(
            $this->machines(
                $ownerId,
                $targetDate
            )
        );

        $items = $items->merge(
            $this->fires(
                $ownerId,
                $targetDate
            )
        );

        $items = $items->merge(
            $this->miscellaneous(
                $ownerId,
                $targetDate
            )
        );

        $items = $items->merge(
            $this->firstAid(
                $ownerId,
                $targetDate
            )
        );

        $items = $items->merge(
            $this->workPermits(
                $ownerId,
                $targetDate
            )
        );

        $items = $items->merge(
            $this->observations(
                $ownerId,
                $targetDate
            )
        );

        return [
            'user' => $user,

            'type' => $type,

            'label' =>
                $this->label($type),

            'date' =>
                $targetDate,

            'items' =>
                $items->values(),

            'count' =>
                $items->count(),

            'generated_at' =>
                now(),
        ];
    }

    protected function targetDate(
        int|string $type
    ): Carbon {
        if ($type === 'overdue') {
            /*
             * Podsjetnik prvi dan nakon isteka.
             *
             * Ako je rok bio jučer,
             * danas šaljemo upozorenje.
             */
            return Carbon::today()
                ->subDay();
        }

        return Carbon::today()
            ->addDays((int) $type);
    }

    protected function label(
        int|string $type
    ): string {
        return match ($type) {
            30 =>
                'Istječe za 30 dana',

            14 =>
                'Istječe za 14 dana',

            7 =>
                'Istječe za 7 dana',

            'overdue' =>
                'Isteklo',

            default =>
                'Podsjetnik na rok',
        };
    }

    protected function medicalExaminations(
        int $ownerId,
        Carbon $date
    ): Collection {
        return Employee::query()
            ->where(
                'user_id',
                $ownerId
            )
            ->whereNotNull(
                'medical_examination_valid_until'
            )
            ->whereDate(
                'medical_examination_valid_until',
                $date
            )
            ->get()
            ->map(
                fn ($employee) => [
                    'module' =>
                        'Zaposlenici - Liječnički pregledi',

                    'name' =>
                        $employee->first_name
                        . ' '
                        . $employee->last_name,

                    'deadline' =>
                        $employee
                            ->medical_examination_valid_until,
                ]
            );
    }

    protected function employeeCertificates(
        int $ownerId,
        Carbon $date
    ): Collection {
        return Employee::query()
            ->where(
                'user_id',
                $ownerId
            )
            ->with([
                'certificates' =>
                    fn ($query) =>
                        $query->whereDate(
                            'valid_until',
                            $date
                        ),
            ])
            ->get()
            ->flatMap(
                function ($employee) {
                    return collect(
                        $employee->certificates
                            ?? []
                    )->map(
                        fn ($certificate) => [
                            'module' =>
                                'Zaposlenici - Edukacije',

                            'name' =>
                                trim(
                                    $employee->first_name
                                    . ' '
                                    . $employee->last_name
                                ),

                            'description' =>
                                $certificate->name
                                ?? $certificate->title
                                ?? 'Edukacija',

                            'deadline' =>
                                $certificate
                                    ->valid_until,
                        ]
                    );
                }
            );
    }

    protected function znrTraining(
        int $ownerId,
        Carbon $date
    ): Collection {
        /*
         * Kod tebe vrijedi:
         *
         * ako occupational_safety_valid_from
         * nije upisan, rok za ZNR je
         * employeed_at + 60 dana.
         */

        return Employee::query()
            ->where(
                'user_id',
                $ownerId
            )
            ->whereNull(
                'occupational_safety_valid_from'
            )
            ->whereNotNull(
                'employeed_at'
            )
            ->get()
            ->filter(
                function ($employee) use (
                    $date
                ): bool {
                    return Carbon::parse(
                        $employee->employeed_at
                    )
                        ->addDays(60)
                        ->isSameDay($date);
                }
            )
            ->map(
                fn ($employee) => [
                    'module' =>
                        'Zaposlenici - ZNR',

                    'name' =>
                        trim(
                            $employee->first_name
                            . ' '
                            . $employee->last_name
                        ),

                    'description' =>
                        'Osposobljavanje za rad na siguran način',

                    'deadline' =>
                        Carbon::parse(
                            $employee->employeed_at
                        )->addDays(60),
                ]
            );
    }

    protected function machines(
        int $ownerId,
        Carbon $date
    ): Collection {
        return Machine::query()
            ->where(
                'user_id',
                $ownerId
            )
            ->whereNotNull(
                'examination_valid_until'
            )
            ->whereDate(
                'examination_valid_until',
                $date
            )
            ->get()
            ->map(
                fn ($machine) => [
                    'module' =>
                        'Radna oprema',

                    'name' =>
                        $machine->name
                        ?? 'Radna oprema',

                    'deadline' =>
                        $machine
                            ->examination_valid_until,
                ]
            );
    }

    protected function fires(
        int $ownerId,
        Carbon $date
    ): Collection {
        return Fire::query()
            ->where(
                'user_id',
                $ownerId
            )
            ->whereNotNull(
                'examination_valid_until'
            )
            ->whereDate(
                'examination_valid_until',
                $date
            )
            ->get()
            ->map(
                fn ($fire) => [
                    'module' =>
                        'Vatrogasni aparati',

                    'name' =>
                        $fire->place
                        ?? $fire->type
                        ?? 'Vatrogasni aparat',

                    'deadline' =>
                        $fire
                            ->examination_valid_until,
                ]
            );
    }

    protected function miscellaneous(
        int $ownerId,
        Carbon $date
    ): Collection {
        return Miscellaneous::query()
            ->where(
                'user_id',
                $ownerId
            )
            ->whereNotNull(
                'examination_valid_until'
            )
            ->whereDate(
                'examination_valid_until',
                $date
            )
            ->get()
            ->map(
                fn ($item) => [
                    'module' =>
                        'Ostala ispitivanja',

                    'name' =>
                        $item->name
                        ?? $item->location
                        ?? 'Ostalo ispitivanje',

                    'deadline' =>
                        $item
                            ->examination_valid_until,
                ]
            );
    }

    protected function firstAid(
        int $ownerId,
        Carbon $date
    ): Collection {
        if (
            ! class_exists(
                \App\Models\FirstAidItem::class
            )
        ) {
            return collect();
        }

        return \App\Models\FirstAidItem::query()
            ->whereHas(
                'kit',
                function ($query) use (
                    $ownerId
                ) {
                    $query->where(
                        'user_id',
                        $ownerId
                    );
                }
            )
            ->whereNotNull(
                'valid_until'
            )
            ->whereDate(
                'valid_until',
                $date
            )
            ->with('kit')
            ->get()
            ->map(
                fn ($item) => [
                    'module' =>
                        'Prva pomoć',

                    'name' =>
                        $item->name
                        ?? 'Materijal prve pomoći',

                    'description' =>
                        $item->kit?->name
                        ?? $item->kit?->location
                        ?? null,

                    'deadline' =>
                        $item->valid_until,
                ]
            );
    }

    protected function workPermits(
        int $ownerId,
        Carbon $date
    ): Collection {
        return WorkPermit::query()
            ->where(
                'user_id',
                $ownerId
            )
            ->whereNotNull(
                'valid_until'
            )
            ->whereDate(
                'valid_until',
                $date
            )
            ->get()
            ->map(
                fn ($permit) => [
                    'module' =>
                        'Dozvole za rad',

                    'name' =>
                        $permit->name
                        ?? $permit->title
                        ?? 'Dozvola za rad',

                    'deadline' =>
                        $permit->valid_until,
                ]
            );
    }

    protected function observations(
        int $ownerId,
        Carbon $date
    ): Collection {
        return Observation::query()
            ->where(
                'user_id',
                $ownerId
            )
            ->whereIn(
                'status',
                [
                    'Not started',
                    'In progress',
                ]
            )
            ->whereNotNull(
                'target_date'
            )
            ->whereDate(
                'target_date',
                $date
            )
            ->get()
            ->map(
                fn ($observation) => [
                    'module' =>
                        'Zapažanja',

                    'name' =>
                        $observation->item
                        ?? $observation
                            ->observation_type
                        ?? 'Zapažanje',

                    'description' =>
                        $observation->responsible,

                    'deadline' =>
                        $observation->target_date,
                ]
            );
    }
}