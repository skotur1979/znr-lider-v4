<?php

namespace App\Imports;

use App\Filament\Resources\PPELogs\PPELogResource;
use App\Models\Employee;
use App\Models\PPEEquipment;
use App\Models\PPEItem;
use App\Models\PPELog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class PPELogsImport implements ToCollection
{
    public int $created = 0;

    public int $updated = 0;

    public int $unchanged = 0;

    public int $skipped = 0;

    public int $missingEmployees = 0;

    /*
     * OZO nije pronađen u Registru, ali ga
     * svejedno uvozimo kao ručni OZO.
     */
    public int $notInRegistry = 0;

    /*
     * Kompatibilnost sa starijim kodom.
     */
    public int $missingEquipment = 0;

    public function __construct(
        protected int $ownerId
    ) {
        $user = Auth::user();

        if (
            $this->ownerId <= 0
            || ! $user
            || $user->isSuperAdmin()
            || (int) $user->ownerId()
                !== $this->ownerId
            || ! PPELogResource::canCreate()
            || ! PPELogResource::allowsModulePermission(
                'update'
            )
        ) {
            throw new RuntimeException(
                'Nemate dopuštenje za uvoz Upisnika OZO.'
            );
        }
    }

    public function collection(
        Collection $rows
    ): void {
        $headerRowIndex = null;
        $headers = [];

        foreach (
            $rows->take(10)
            as $index => $row
        ) {
            $normalized =
                collect($row)
                    ->map(
                        fn ($value): string =>
                            $this->normalizeHeader(
                                $value
                            )
                    )
                    ->values()
                    ->all();

            if (
                in_array(
                    'oib',
                    $normalized,
                    true
                )
                && (
                    in_array(
                        'naziv ozo',
                        $normalized,
                        true
                    )
                    || in_array(
                        'naziv',
                        $normalized,
                        true
                    )
                    || in_array(
                        'ozo',
                        $normalized,
                        true
                    )
                )
                && (
                    in_array(
                        'datum izdavanja',
                        $normalized,
                        true
                    )
                    || in_array(
                        'izdano',
                        $normalized,
                        true
                    )
                    || in_array(
                        'datum izdano',
                        $normalized,
                        true
                    )
                )
            ) {
                $headerRowIndex =
                    (int) $index;

                $headers =
                    $normalized;

                break;
            }
        }

        if ($headerRowIndex === null) {
            throw new RuntimeException(
                'Nije pronađen red zaglavlja. '
                . 'Excel mora sadržavati stupce OIB, Naziv OZO i Datum izdavanja.'
            );
        }

        $oibIndex =
            $this->headerIndex(
                $headers,
                [
                    'oib',
                ]
            );

        $equipmentIndex =
            $this->headerIndex(
                $headers,
                [
                    'naziv ozo',
                    'naziv',
                    'ozo',
                ]
            );

        $standardIndex =
            $this->headerIndex(
                $headers,
                [
                    'hrn en',
                    'hrn en norma',
                    'norma',
                    'standard',
                ],
                required: false
            );

        $sizeIndex =
            $this->headerIndex(
                $headers,
                [
                    'velicina',
                ],
                required: false
            );

        $durationIndex =
            $this->headerIndex(
                $headers,
                [
                    'rok mjeseci',
                    'rok uporabe mjeseci',
                    'rok upotrebe mjeseci',
                    'rok uporabe mj',
                    'trajanje mjeseci',
                ],
                required: false
            );

        $issueDateIndex =
            $this->headerIndex(
                $headers,
                [
                    'datum izdavanja',
                    'izdano',
                    'datum izdano',
                ]
            );

        $returnDateIndex =
            $this->headerIndex(
                $headers,
                [
                    'datum vracanja',
                    'vraceno',
                ],
                required: false
            );

        $dataRows =
            $rows->slice(
                $headerRowIndex + 1
            );

        foreach ($dataRows as $row) {
            $values =
                collect($row)
                    ->values();

            $oib =
                preg_replace(
                    '/\s+/',
                    '',
                    trim(
                        (string)
                        $values->get(
                            $oibIndex,
                            ''
                        )
                    )
                );

            $equipmentName =
                preg_replace(
                    '/\s+/',
                    ' ',
                    trim(
                        (string)
                        $values->get(
                            $equipmentIndex,
                            ''
                        )
                    )
                );

            $issueDate =
                $this->parseDate(
                    $values->get(
                        $issueDateIndex
                    )
                );

            /*
             * Potpuno prazan red ignoriramo.
             */
            if (
                $oib === ''
                && $equipmentName === ''
                && $issueDate === null
            ) {
                continue;
            }

            if (
                $oib === ''
                || $equipmentName === ''
                || $issueDate === null
            ) {
                $this->skipped++;

                continue;
            }

            if (
                mb_strlen(
                    $equipmentName
                ) > 191
            ) {
                $this->skipped++;

                continue;
            }

            $standard =
                $standardIndex !== null
                    ? preg_replace(
                        '/\s+/',
                        ' ',
                        trim(
                            (string)
                            $values->get(
                                $standardIndex,
                                ''
                            )
                        )
                    )
                    : '';

            $size =
                $sizeIndex !== null
                    ? trim(
                        (string)
                        $values->get(
                            $sizeIndex,
                            ''
                        )
                    )
                    : '';

            if (
                mb_strlen($standard) > 191
                || mb_strlen($size) > 20
            ) {
                $this->skipped++;

                continue;
            }

            $rawDuration =
                $durationIndex !== null
                    ? trim(
                        (string)
                        $values->get(
                            $durationIndex,
                            ''
                        )
                    )
                    : '';

            if (
                $rawDuration !== ''
                && ! is_numeric(
                    $rawDuration
                )
            ) {
                $this->skipped++;

                continue;
            }

            $durationMonths =
                $rawDuration !== ''
                    ? (int) $rawDuration
                    : 0;

            if (
                $durationMonths < 0
                || $durationMonths > 120
            ) {
                $this->skipped++;

                continue;
            }

            $returnDate =
                $returnDateIndex !== null
                    ? $this->parseDate(
                        $values->get(
                            $returnDateIndex
                        )
                    )
                    : null;

            if (
                $returnDate
                && $returnDate->lt(
                    $issueDate
                )
            ) {
                $this->skipped++;

                continue;
            }

            $employee =
                $this->findEmployee(
                    $oib
                );

            if (! $employee) {
                $this->missingEmployees++;
                $this->skipped++;

                continue;
            }

            $equipment =
                $this->findEquipment(
                    $equipmentName,
                    $standard
                );

            if (! $equipment) {
                $this->notInRegistry++;
            }

            /*
             * Ako rok nije naveden u Excelu,
             * koristimo rok iz Registra OZO.
             */
            if (
                $durationMonths <= 0
                && $equipment
            ) {
                $durationMonths =
                    (int) (
                        $equipment
                            ->duration_months
                        ?? 0
                    );
            }

            $endDate =
                $returnDate !== null
                    ? null
                    : (
                        $durationMonths > 0
                            ? $issueDate
                                ->copy()
                                ->addMonths(
                                    $durationMonths
                                )
                                ->toDateString()
                            : null
                    );

            /*
             * Jedan Upisnik OZO po zaposleniku
             * unutar organizacije.
             */
            $ppeLog =
                PPELog::withTrashed()
                    ->where(
                        'user_id',
                        $this->ownerId
                    )
                    ->where(
                        'user_oib',
                        $this->employeeOib(
                            $employee
                        )
                    )
                    ->first();

            /*
             * Ako je Upisnik deaktiviran,
             * import ga ne vraća automatski
             * i ne izrađuje duplikat.
             */
            if (
                $ppeLog
                && $ppeLog->trashed()
            ) {
                $this->skipped++;

                continue;
            }

            if (! $ppeLog) {
                $ppeLog =
                    PPELog::create([
                        'user_id' =>
                            $this->ownerId,

                        'user_last_name' =>
                            $employee->name,

                        'user_oib' =>
                            $this->employeeOib(
                                $employee
                            ),

                        'workplace' =>
                            $employee
                                ->workplace,

                        'organization_unit' =>
                            $employee
                                ->organization_unit,
                    ]);
            } else {
                /*
                 * Osvježavamo snapshot zaposlenika.
                 */
                $ppeLog->update([
                    'user_last_name' =>
                        $employee->name,

                    'workplace' =>
                        $employee
                            ->workplace,

                    'organization_unit' =>
                        $employee
                            ->organization_unit,
                ]);
            }

            /*
             * Zaduženje identificiramo po:
             *
             * - Upisniku
             * - datumu izdavanja
             * - veličini
             * - registry FK-u ako postoji
             * - ili nazivu ako je ručni OZO.
             */
            $itemQuery =
                PPEItem::withTrashed()
                    ->where(
                        'personal_protective_equipment_log_id',
                        $ppeLog->id
                    )
                    ->whereDate(
                        'issue_date',
                        $issueDate
                            ->toDateString()
                    );

            if ($size === '') {
                $itemQuery->whereNull(
                    'size'
                );
            } else {
                $itemQuery->where(
                    'size',
                    $size
                );
            }

            if ($equipment) {
                $itemQuery->where(
                    function (
                        $query
                    ) use (
                        $equipment,
                        $equipmentName
                    ): void {
                        $query
                            ->where(
                                'ppe_equipment_id',
                                $equipment->id
                            )
                            ->orWhere(
                                function (
                                    $query
                                ) use (
                                    $equipmentName
                                ): void {
                                    $query
                                        ->whereNull(
                                            'ppe_equipment_id'
                                        )
                                        ->where(
                                            'equipment_name',
                                            $equipmentName
                                        );
                                }
                            );
                    }
                );
            } else {
                $itemQuery
                    ->whereNull(
                        'ppe_equipment_id'
                    )
                    ->where(
                        'equipment_name',
                        $equipmentName
                    );
            }

            $item =
                $itemQuery->first();

            if (
                $item
                && $item->trashed()
            ) {
                /*
                * Deaktivirani OZO ne vraćamo automatski
                * niti stvaramo novi duplikat.
                */
                $this->skipped++;

                continue;
            }

            if (! $item) {
                $item =
                    new PPEItem([
                        'personal_protective_equipment_log_id' =>
                            $ppeLog->id,

                        'issue_date' =>
                            $issueDate
                                ->toDateString(),

                        'size' =>
                            $size !== ''
                                ? $size
                                : null,
                    ]);
            }

            $item->fill([
                'ppe_equipment_id' =>
                    $equipment?->id,

                'equipment_name' =>
                    $equipment?->name
                    ?? $equipmentName,

                'standard' =>
                    $standard !== ''
                        ? $standard
                        : $equipment?->standard,

                'duration_months' =>
                    $durationMonths > 0
                        ? $durationMonths
                        : null,

                'end_date' =>
                    $endDate,

                'return_date' =>
                    $returnDate
                        ?->toDateString(),
            ]);

            if (! $item->exists) {
                $item->save();

                $this->created++;

                continue;
            }

            if (! $item->isDirty()) {
                $this->unchanged++;

                continue;
            }

            $item->save();

            $this->updated++;
        }
    }

    protected function findEmployee(
        string $oib
    ): ?Employee {
        $table =
            (new Employee())
                ->getTable();

        $hasOIBUpper =
            Schema::hasColumn(
                $table,
                'OIB'
            );

        $hasOIBLower =
            Schema::hasColumn(
                $table,
                'oib'
            );

        return Employee::query()
            ->where(
                'user_id',
                $this->ownerId
            )
            ->where(
                function (
                    $query
                ) use (
                    $oib,
                    $hasOIBUpper,
                    $hasOIBLower
                ): void {
                    if ($hasOIBUpper) {
                        $query->orWhere(
                            'OIB',
                            $oib
                        );
                    }

                    if ($hasOIBLower) {
                        $query->orWhere(
                            'oib',
                            $oib
                        );
                    }
                }
            )
            ->first();
    }

    protected function employeeOib(
        Employee $employee
    ): string {
        return preg_replace(
            '/\s+/',
            '',
            trim(
                (string) (
                    $employee->OIB
                    ?? $employee->oib
                    ?? ''
                )
            )
        );
    }

    protected function findEquipment(
        string $name,
        string $standard
    ): ?PPEEquipment {
        $query =
            PPEEquipment::query()
                ->where(
                    'user_id',
                    $this->ownerId
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'name',
                    $name
                );

        if ($standard !== '') {
            $exact =
                (clone $query)
                    ->where(
                        'standard',
                        $standard
                    )
                    ->first();

            if ($exact) {
                return $exact;
            }
        }

        $matches =
            $query
                ->limit(2)
                ->get();

        return $matches->count() === 1
            ? $matches->first()
            : null;
    }

    protected function headerIndex(
        array $headers,
        array $aliases,
        bool $required = true
    ): ?int {
        foreach ($aliases as $alias) {
            $normalizedAlias =
                $this->normalizeHeader(
                    $alias
                );

            $index =
                array_search(
                    $normalizedAlias,
                    $headers,
                    true
                );

            if ($index !== false) {
                return (int) $index;
            }
        }

        if (! $required) {
            return null;
        }

        throw new RuntimeException(
            'U Excelu nedostaje obavezni stupac: '
            . $aliases[0]
            . '.'
        );
    }

    protected function normalizeHeader(
        mixed $value
    ): string {
        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                Str::lower(
                    Str::ascii(
                        (string) $value
                    )
                )
            )
        );
    }

    protected function parseDate(
        mixed $value
    ): ?Carbon {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        if (
            is_numeric($value)
            && (float) $value > 0
        ) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        (float) $value
                    )
                )
                    ->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        $value =
            trim(
                (string) $value
            );

        foreach (
            [
                'd.m.Y.',
                'd.m.Y',
                'd/m/Y',
                'Y-m-d',
                'd-m-Y',
            ]
            as $format
        ) {
            try {
                return Carbon::createFromFormat(
                    $format,
                    $value
                )
                    ->startOfDay();
            } catch (\Throwable) {
                // pokušaj sljedeći format
            }
        }

        try {
            return Carbon::parse(
                $value
            )
                ->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}