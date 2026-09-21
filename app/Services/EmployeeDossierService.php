<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PPELog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmployeeDossierService
{
    public function build(
        Employee $employee
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Učitavanje podataka zaposlenika
        |--------------------------------------------------------------------------
        */

        $employee->load([
            'user',

            'certificates' =>
                fn ($query) =>
                    $query
                        ->orderBy('title')
                        ->orderByDesc(
                            'valid_from'
                        ),

            'alcoholTests' =>
                fn ($query) =>
                    $query
                        ->orderByDesc(
                            'test_date'
                        ),
        ]);

        /*
        |--------------------------------------------------------------------------
        | OZO
        |--------------------------------------------------------------------------
        */

        $ppeItems =
            $this->ppeItems(
                $employee
            );

        /*
        |--------------------------------------------------------------------------
        | Prilozi
        |--------------------------------------------------------------------------
        */

        $attachments =
            collect(
                is_array($employee->pdf)
                    ? $employee->pdf
                    : (
                        filled($employee->pdf)
                            ? [$employee->pdf]
                            : []
                    )
            )
                ->filter()
                ->values()
                ->map(
                    fn ($path): array => [
                        'path' =>
                            (string) $path,

                        'name' =>
                            basename(
                                (string) $path
                            ),
                    ]
                );

        return [
            'employee' =>
                $employee,

            'statusItems' =>
                $this->statusItems(
                    $employee
                ),

            'certificates' =>
                $employee
                    ->certificates
                    ->map(
                        function (
                            $certificate
                        ): array {
                            return [
                                'title' =>
                                    $certificate->title
                                    ?: '—',

                                'valid_from' =>
                                    $this->formatDate(
                                        $certificate
                                            ->valid_from
                                    ),

                                'valid_until' =>
                                    $this->formatDate(
                                        $certificate
                                            ->valid_until
                                    ),

                                'state' =>
                                    $this->deadlineState(
                                        $certificate
                                            ->valid_until
                                    ),

                                'state_label' =>
                                    $certificate
                                        ->valid_until
                                            ? $this
                                                ->deadlineLabel(
                                                    $certificate
                                                        ->valid_until
                                                )
                                            : 'BEZ ROKA',
                            ];
                        }
                    )
                    ->values(),

            'alcoholTests' =>
                $employee
                    ->alcoholTests
                    ->map(
                        function (
                            $test
                        ): array {
                            return [
                                'test_date' =>
                                    $this->formatDate(
                                        $test->test_date
                                    ),

                                'result' =>
                                    filled(
                                        $test->result
                                    )
                                        ? $test->result
                                            . ' ‰'
                                        : '—',

                                'tested_by' =>
                                    $test->tested_by
                                    ?: '—',

                                'note' =>
                                    $test->note
                                    ?: '—',

                                'state' =>
                                    $this
                                        ->alcoholState(
                                            $test
                                                ->result
                                        ),
                            ];
                        }
                    )
                    ->values(),

            'ppeItems' =>
                $ppeItems,

            'attachments' =>
                $attachments,

            'generatedAt' =>
                now(
                    'Europe/Zagreb'
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | OZO
    |--------------------------------------------------------------------------
    |
    | Upisnik OZO u postojećoj aplikaciji
    | ima:
    |
    | user_id       = organizacija
    | user_oib      = OIB zaposlenika
    | user_last_name = ime zaposlenika
    |
    | Prvo tražimo po OIB-u.
    | Ako OIB nije evidentiran, koristimo
    | točno ime unutar iste organizacije.
    |
    */

    protected function ppeItems(
        Employee $employee
    ): Collection {
        $query =
            PPELog::query()
                ->where(
                    'user_id',
                    $employee->user_id
                );

        $oib =
            trim(
                (string) (
                    $employee->OIB
                    ?? $employee->oib
                    ?? ''
                )
            );

        if ($oib !== '') {
            $query->where(
                'user_oib',
                $oib
            );
        } elseif (
            filled($employee->name)
        ) {
            $query->where(
                'user_last_name',
                $employee->name
            );
        } else {
            return collect();
        }

        $logs =
            $query
                ->with([
                    'items' =>
                        fn ($itemsQuery) =>
                            $itemsQuery
                                ->whereNull(
                                    'return_date'
                                )
                                ->orderBy(
                                    'equipment_name'
                                ),
                ])
                ->get();

        return $logs
            ->flatMap(
                fn ($log) =>
                    $log->items
            )
            ->map(
                function ($item): array {
                    return [
                        'equipment_name' =>
                            $item->equipment_name
                            ?: '—',

                        'standard' =>
                            $item->standard
                            ?: '—',

                        'size' =>
                            $item->size
                            ?: '—',

                        'issue_date' =>
                            $this->formatDate(
                                $item->issue_date
                            ),

                        'end_date' =>
                            $this->formatDate(
                                $item->end_date
                            ),

                        'state' =>
                            $this->deadlineState(
                                $item->end_date
                            ),

                        'state_label' =>
                            $item->end_date
                                ? $this
                                    ->deadlineLabel(
                                        $item
                                            ->end_date
                                    )
                                : 'BEZ ROKA',
                    ];
                }
            )
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS ZNR
    |--------------------------------------------------------------------------
    */

    protected function statusItems(
        Employee $employee
    ): array {
        $items = [];

        /*
         * Liječnički pregled
         */
        $items[] =
            $this->deadlineStatus(
                icon: '🩺',
                label:
                    'Liječnički pregled',
                date:
                    $employee
                        ->medical_examination_valid_until
            );

        /*
         * ZNR
         *
         * Kod ZNR-a trenutno nema
         * valid_until.
         *
         * Ako je datum osposobljavanja
         * evidentiran, prikazujemo ga.
         *
         * Ako nije, koristimo postojeće
         * pravilo zaposlenje + 60 dana.
         */
        if (
            filled(
                $employee
                    ->occupational_safety_valid_from
            )
        ) {
            $items[] =
                $this->recordedStatus(
                    icon: '🎓',
                    label:
                        'Zaštita na radu – ZNR',
                    date:
                        $employee
                            ->occupational_safety_valid_from
                );
        } elseif (
            $employee->znrTrainingDueDate()
        ) {
            $dueDate =
                Carbon::parse(
                    $employee
                        ->znrTrainingDueDate()
                )
                    ->startOfDay();

            $today =
                Carbon::today(
                    'Europe/Zagreb'
                );

            $soon =
                $today
                    ->copy()
                    ->addDays(30);

            if (
                $dueDate->lt($today)
            ) {
                $state =
                    'danger';

                $label =
                    'ROK ISTEKAO';
            } elseif (
                $dueDate->lte($soon)
            ) {
                $state =
                    'warning';

                $label =
                    'USKORO';
            } else {
                $state =
                    'info';

                $label =
                    'ROK';
            }

            $items[] = [
                'icon' =>
                    '🎓',

                'label' =>
                    'Zaštita na radu – ZNR',

                'value' =>
                    'Položiti do '
                    . $dueDate->format(
                        'd.m.Y.'
                    ),

                'state' =>
                    $state,

                'state_label' =>
                    $label,
            ];
        } else {
            $items[] =
                $this->missingStatus(
                    icon: '🎓',
                    label:
                        'Zaštita na radu – ZNR'
                );
        }

        /*
         * ZOP
         */
        $zopDate =
            $employee
                ->fire_protection_valid_from;

        if ($zopDate) {
            $value =
                'Evidentirano '
                . Carbon::parse(
                    $zopDate
                )->format(
                    'd.m.Y.'
                );

            if (
                filled(
                    $employee
                        ->fire_protection_statement_at
                )
            ) {
                $value .=
                    ' | Izjava '
                    . Carbon::parse(
                        $employee
                            ->fire_protection_statement_at
                    )->format(
                        'd.m.Y.'
                    );
            }

            $items[] = [
                'icon' =>
                    '🔥',

                'label' =>
                    'Zaštita od požara – ZOP',

                'value' =>
                    $value,

                'state' =>
                    'success',

                'state_label' =>
                    'EVIDENTIRANO',
            ];
        } elseif (
            filled(
                $employee
                    ->fire_protection_statement_at
            )
        ) {
            $items[] = [
                'icon' =>
                    '🔥',

                'label' =>
                    'Zaštita od požara – ZOP',

                'value' =>
                    'Izjava evidentirana '
                    . Carbon::parse(
                        $employee
                            ->fire_protection_statement_at
                    )->format(
                        'd.m.Y.'
                    ),

                'state' =>
                    'success',

                'state_label' =>
                    'EVIDENTIRANO',
            ];
        } else {
            $items[] =
                $this->missingStatus(
                    icon: '🔥',
                    label:
                        'Zaštita od požara – ZOP'
                );
        }

        /*
         * Evakuacija
         */
        $items[] =
            $this->recordedStatus(
                icon: '🚪',
                label:
                    'Voditelj evakuacije',
                date:
                    $employee
                        ->evacuation_valid_from
            );

        /*
         * Toksikologija
         */
        $items[] =
            $this->deadlineStatus(
                icon: '🧪',
                label:
                    'Toksikologija',
                date:
                    $employee
                        ->toxicology_valid_until
            );

        /*
         * Zapaljive tvari
         */
        $items[] =
            $this->deadlineStatus(
                icon: '🔥',
                label:
                    'Rukovanje zapaljivim tvarima',
                date:
                    $employee
                        ->handling_flammable_materials_valid_until
            );

        /*
         * Ovlaštenik
         */
        $items[] =
            $this->deadlineStatus(
                icon: '🛡️',
                label:
                    'Ovlaštenik poslodavca za ZNR',
                date:
                    $employee
                        ->employers_authorization_valid_until
            );

        /*
         * Prva pomoć nema rok važenja.
         */
        $items[] =
            $this->recordedStatus(
                icon: '⛑️',
                label:
                    'Prva pomoć',
                date:
                    $employee
                        ->first_aid_valid_from
            );

        return $items;
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS S ROKOM
    |--------------------------------------------------------------------------
    */

    protected function deadlineStatus(
        string $icon,
        string $label,
        $date
    ): array {
        if (! $date) {
            return $this
                ->missingStatus(
                    $icon,
                    $label
                );
        }

        $carbon =
            Carbon::parse(
                $date
            )
                ->startOfDay();

        $today =
            Carbon::today(
                'Europe/Zagreb'
            );

        $soon =
            $today
                ->copy()
                ->addDays(30);

        if (
            $carbon->lt($today)
        ) {
            return [
                'icon' =>
                    $icon,

                'label' =>
                    $label,

                'value' =>
                    'Isteklo '
                    . $carbon->format(
                        'd.m.Y.'
                    ),

                'state' =>
                    'danger',

                'state_label' =>
                    'ISTEKLO',
            ];
        }

        if (
            $carbon->lte($soon)
        ) {
            return [
                'icon' =>
                    $icon,

                'label' =>
                    $label,

                'value' =>
                    'Vrijedi do '
                    . $carbon->format(
                        'd.m.Y.'
                    ),

                'state' =>
                    'warning',

                'state_label' =>
                    'USKORO',
            ];
        }

        return [
            'icon' =>
                $icon,

            'label' =>
                $label,

            'value' =>
                'Vrijedi do '
                . $carbon->format(
                    'd.m.Y.'
                ),

            'state' =>
                'success',

            'state_label' =>
                'VAŽEĆE',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS BEZ ROKA
    |--------------------------------------------------------------------------
    */

    protected function recordedStatus(
        string $icon,
        string $label,
        $date
    ): array {
        if (! $date) {
            return $this
                ->missingStatus(
                    $icon,
                    $label
                );
        }

        return [
            'icon' =>
                $icon,

            'label' =>
                $label,

            'value' =>
                'Evidentirano '
                . Carbon::parse(
                    $date
                )->format(
                    'd.m.Y.'
                ),

            'state' =>
                'success',

            'state_label' =>
                'EVIDENTIRANO',
        ];
    }

    protected function missingStatus(
        string $icon,
        string $label
    ): array {
        return [
            'icon' =>
                $icon,

            'label' =>
                $label,

            'value' =>
                'Nije evidentirano',

            'state' =>
                'gray',

            'state_label' =>
                'NIJE EVIDENTIRANO',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | POMOĆNE FUNKCIJE
    |--------------------------------------------------------------------------
    */

    protected function deadlineState(
        $date
    ): string {
        if (! $date) {
            return 'gray';
        }

        $date =
            Carbon::parse(
                $date
            )
                ->startOfDay();

        $today =
            Carbon::today(
                'Europe/Zagreb'
            );

        if (
            $date->lt($today)
        ) {
            return 'danger';
        }

        if (
            $date->lte(
                $today
                    ->copy()
                    ->addDays(30)
            )
        ) {
            return 'warning';
        }

        return 'success';
    }

    protected function deadlineLabel(
        $date
    ): string {
        return match (
            $this->deadlineState(
                $date
            )
        ) {
            'danger' =>
                'ISTEKLO',

            'warning' =>
                'USKORO',

            'success' =>
                'VAŽEĆE',

            default =>
                'BEZ ROKA',
        };
    }

    protected function alcoholState(
        $result
    ): string {
        if (
            blank($result)
        ) {
            return 'gray';
        }

        $value =
            (float)
            str_replace(
                ',',
                '.',
                (string) $result
            );

        return $value > 0.5
            ? 'danger'
            : 'success';
    }

    protected function formatDate(
        $date
    ): string {
        return $date
            ? Carbon::parse(
                $date
            )->format(
                'd.m.Y.'
            )
            : '—';
    }
}