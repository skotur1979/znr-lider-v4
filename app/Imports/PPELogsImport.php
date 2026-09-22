<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\PPEEquipment;
use App\Models\PPEItem;
use App\Models\PPELog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

    public int $notInRegistry = 0;

    public function __construct(
        protected int $ownerId
    ) {
        if ($this->ownerId <= 0) {
            throw new RuntimeException(
                'Organizacija nije određena.'
            );
        }
    }

    public function collection(
        Collection $rows
    ): void {
        [
            $headerRowIndex,
            $headers,
        ] = $this->findHeaderRow(
            $rows
        );

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
                    'hrn en norma',
                    'hrn en',
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
                    'rok uporabe mjeseci',
                    'rok upotrebe mjeseci',
                    'rok mjeseci',
                    'rok uporabe mj',
                    'trajanje mjeseci',
                ],
                required: false
            );

        /*
         * Datum izdavanja više nije
         * obavezan za SVAKI red.
         *
         * Ako zapis već postoji i datum
         * nije naveden, postojeći datum
         * jednostavno ostaje.
         *
         * Za NOVI zapis ipak je potreban.
         */
        $issueDateIndex =
            $this->headerIndex(
                $headers,
                [
                    'datum izdavanja',
                    'izdano',
                    'datum izdano',
                ],
                required: false
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

        foreach (
            $rows->slice(
                $headerRowIndex + 1
            )
            as $row
        ) {
            $values =
                collect(
                    $row
                )
                    ->values();

            $oib =
                $this->normalizeOib(
                    $values->get(
                        $oibIndex,
                        ''
                    )
                );

            $equipmentName =
                trim(
                    (string)
                    $values->get(
                        $equipmentIndex,
                        ''
                    )
                );

            /*
             * Potpuno prazan red.
             */
            if (
                $oib === ''
                && $equipmentName === ''
            ) {
                continue;
            }

            /*
             * OIB + Naziv OZO su jedina
             * obavezna identifikacijska polja.
             */
            if (
                $oib === ''
                || $equipmentName === ''
            ) {
                $this->skipped++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | ZAPOSLENIK
            |--------------------------------------------------------------------------
            */

            $employee =
                $this->findEmployee(
                    $oib
                );

            if (! $employee) {
                $this->missingEmployees++;
                $this->skipped++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | OPCIONALNA POLJA
            |--------------------------------------------------------------------------
            |
            | Razlikujemo:
            |
            | - prazno polje
            | - stvarno unesenu vrijednost
            |
            | Prazna ćelija kod postojećeg
            | zapisa NE briše postojeći podatak.
            |
            */

            $rawStandard =
                $standardIndex !== null
                    ? $values->get(
                        $standardIndex
                    )
                    : null;

            $standardProvided =
                $this->hasValue(
                    $rawStandard
                );

            $standard =
                $standardProvided
                    ? trim(
                        (string)
                        $rawStandard
                    )
                    : null;

            $rawSize =
                $sizeIndex !== null
                    ? $values->get(
                        $sizeIndex
                    )
                    : null;

            $sizeProvided =
                $this->hasValue(
                    $rawSize
                );

            $size =
                $sizeProvided
                    ? trim(
                        (string)
                        $rawSize
                    )
                    : null;

            $rawDuration =
                $durationIndex !== null
                    ? $values->get(
                        $durationIndex
                    )
                    : null;

            $durationProvided =
                $this->hasValue(
                    $rawDuration
                );

            $durationMonths =
                $durationProvided
                    ? max(
                        0,
                        (int)
                        $rawDuration
                    )
                    : null;

            /*
            |--------------------------------------------------------------------------
            | DATUM IZDAVANJA
            |--------------------------------------------------------------------------
            */

            $rawIssueDate =
                $issueDateIndex !== null
                    ? $values->get(
                        $issueDateIndex
                    )
                    : null;

            $issueDateProvided =
                $this->hasValue(
                    $rawIssueDate
                );

            $issueDate =
                $issueDateProvided
                    ? $this->parseDate(
                        $rawIssueDate
                    )
                    : null;

            /*
             * Ako je korisnik nešto upisao
             * kao datum, ali datum nije valjan,
             * cijeli red preskačemo.
             */
            if (
                $issueDateProvided
                && ! $issueDate
            ) {
                $this->skipped++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | DATUM VRAĆANJA
            |--------------------------------------------------------------------------
            */

            $rawReturnDate =
                $returnDateIndex !== null
                    ? $values->get(
                        $returnDateIndex
                    )
                    : null;

            $returnDateProvided =
                $this->hasValue(
                    $rawReturnDate
                );

            $returnDate =
                $returnDateProvided
                    ? $this->parseDate(
                        $rawReturnDate
                    )
                    : null;

            if (
                $returnDateProvided
                && ! $returnDate
            ) {
                $this->skipped++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | REGISTAR OZO
            |--------------------------------------------------------------------------
            |
            | Registar je samo predložak.
            |
            | Ako ga pronađemo možemo uzeti
            | normu i rok za NOVO zaduženje.
            |
            | Ako ga nema, red se svejedno
            | može uvesti iz Excela.
            |
            */

            $equipment =
                $this->findEquipment(
                    $equipmentName,
                    $standard ?? ''
                );

            if (! $equipment) {
                $this->notInRegistry++;
            }

            /*
            |--------------------------------------------------------------------------
            | UPISNIK ZAPOSLENIKA
            |--------------------------------------------------------------------------
            */

            $employeeOib =
                $this->employeeOib(
                    $employee
                );

            $ppeLog =
                PPELog::withTrashed()
                    ->where(
                        'user_id',
                        $this->ownerId
                    )
                    ->where(
                        'user_oib',
                        $employeeOib
                    )
                    ->first();

            /*
             * Zaposlenik još nema
             * Upisnik OZO.
             */
            if (! $ppeLog) {
                $ppeLog =
                    PPELog::create([
                        'user_id' =>
                            $this->ownerId,

                        'user_last_name' =>
                            $employee->name,

                        'user_oib' =>
                            $employeeOib,

                        'workplace' =>
                            $employee->workplace,

                        'organization_unit' =>
                            $employee
                                ->organization_unit,
                    ]);
            } else {
                /*
                 * Ako je Upisnik bio
                 * deaktiviran, vraćamo ga.
                 */
                if (
                    $ppeLog->trashed()
                ) {
                    $ppeLog->restore();
                }

                /*
                 * Osvježavamo osnovne podatke
                 * zaposlenika.
                 */
                $ppeLog->update([
                    'user_last_name' =>
                        $employee->name,

                    'workplace' =>
                        $employee->workplace,

                    'organization_unit' =>
                        $employee
                            ->organization_unit,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | TRAŽENJE POSTOJEĆEG AKTIVNOG OZO-a
            |--------------------------------------------------------------------------
            |
            | Datum izdavanja NIJE ključ.
            |
            | Primjer:
            |
            | postojeće:
            | Radne cipele | 11.06.2026.
            |
            | Excel:
            | Radne cipele | 20.09.2026.
            |
            | rezultat:
            | datum postojećeg zapisa postaje
            | 20.09.2026.
            |
            | Ne nastaje novi duplikat.
            |
            | Vraćena OZO se ne dira jer mora
            | ostati povijesna evidencija.
            |
            */

            $existingQuery =
                PPEItem::query()
                    ->where(
                        'personal_protective_equipment_log_id',
                        $ppeLog->id
                    )
                    ->where(
                        'equipment_name',
                        $equipmentName
                    )
                    ->whereNull(
                        'return_date'
                    );

            $item = null;

            /*
             * Ako je veličina unesena,
             * prvo pokušavamo pronaći
             * isti OZO i istu veličinu.
             */
            if ($sizeProvided) {
                $item =
                    (clone $existingQuery)
                        ->where(
                            'size',
                            $size
                        )
                        ->orderByDesc(
                            'issue_date'
                        )
                        ->orderByDesc(
                            'id'
                        )
                        ->first();
            }

            /*
             * Ako nema točnog podudaranja
             * po veličini, uzimamo zadnji
             * aktivni isti OZO.
             */
            $item ??=
                (clone $existingQuery)
                    ->orderByDesc(
                        'issue_date'
                    )
                    ->orderByDesc(
                        'id'
                    )
                    ->first();

            /*
            |--------------------------------------------------------------------------
            | NOVO ZADUŽENJE
            |--------------------------------------------------------------------------
            */

            if (! $item) {
                /*
                 * Novi zapis bez datuma
                 * izdavanja nema smisla.
                 */
                if (! $issueDate) {
                    $this->skipped++;

                    continue;
                }

                /*
                 * Ako Norma nije unesena,
                 * pokušamo je uzeti iz Registra.
                 */
                $newStandard =
                    $standardProvided
                        ? $standard
                        : trim(
                            (string) (
                                $equipment
                                    ?->standard
                                ?? ''
                            )
                        );

                /*
                 * Ako Rok nije unesen,
                 * pokušamo ga uzeti iz Registra.
                 */
                $newDuration =
                    $durationProvided
                        ? $durationMonths
                        : (
                            $equipment
                                ? (int) (
                                    $equipment
                                        ->duration_months
                                    ?? 0
                                )
                                : null
                        );

                $newEndDate =
                    null;

                /*
                 * Ako je odmah upisan
                 * Datum vraćanja, nema isteka.
                 */
                if (
                    ! $returnDate
                    && $newDuration !== null
                    && $newDuration > 0
                ) {
                    $newEndDate =
                        $issueDate
                            ->copy()
                            ->addMonths(
                                $newDuration
                            )
                            ->toDateString();
                }

                PPEItem::create([
                    'personal_protective_equipment_log_id' =>
                        $ppeLog->id,

                    'equipment_name' =>
                        $equipmentName,

                    'standard' =>
                        $newStandard !== ''
                            ? $newStandard
                            : null,

                    'size' =>
                        $sizeProvided
                            ? $size
                            : null,

                    'duration_months' =>
                        $newDuration,

                    'issue_date' =>
                        $issueDate
                            ->toDateString(),

                    'end_date' =>
                        $newEndDate,

                    'return_date' =>
                        $returnDate
                            ?->toDateString(),
                ]);

                $this->created++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | AŽURIRANJE POSTOJEĆEG OZO-a
            |--------------------------------------------------------------------------
            |
            | Najvažnije pravilo:
            |
            | PRAZNO U EXCELU
            | =
            | NE DIRAJ POSTOJEĆU VRIJEDNOST.
            |
            */

            $changes = [];

            if ($standardProvided) {
                $changes[
                    'standard'
                ] =
                    $standard;
            }

            if ($sizeProvided) {
                $changes[
                    'size'
                ] =
                    $size;
            }

            if ($durationProvided) {
                $changes[
                    'duration_months'
                ] =
                    $durationMonths;
            }

            /*
             * Datum izdavanja se mijenja
             * ako je u Excelu popunjen
             * i drugačiji od postojećeg.
             */
            if ($issueDateProvided) {
                $changes[
                    'issue_date'
                ] =
                    $issueDate
                        ->toDateString();
            }

            /*
             * Prazan Datum vraćanja
             * ne briše postojeći podatak.
             */
            if ($returnDateProvided) {
                $changes[
                    'return_date'
                ] =
                    $returnDate
                        ->toDateString();
            }

            /*
            |--------------------------------------------------------------------------
            | NOVI DATUM ISTEKA
            |--------------------------------------------------------------------------
            */

            $effectiveIssueDate =
                $issueDateProvided
                    ? $issueDate
                    : (
                        $item->issue_date
                            ? Carbon::parse(
                                $item
                                    ->issue_date
                            )
                            : null
                    );

            $effectiveDuration =
                $durationProvided
                    ? $durationMonths
                    : (
                        $item
                            ->duration_months
                        !== null
                            ? (int)
                                $item
                                    ->duration_months
                            : null
                    );

            $effectiveReturnDate =
                $returnDateProvided
                    ? $returnDate
                    : (
                        $item->return_date
                            ? Carbon::parse(
                                $item
                                    ->return_date
                            )
                            : null
                    );

            /*
             * Rok ponovno računamo samo ako
             * je korisnik promijenio:
             *
             * - Datum izdavanja
             * - Rok mjeseci
             * - Datum vraćanja
             */
            if (
                $issueDateProvided
                || $durationProvided
                || $returnDateProvided
            ) {
                /*
                 * Vraćeni OZO nema
                 * aktivni datum isteka.
                 */
                if ($effectiveReturnDate) {
                    $changes[
                        'end_date'
                    ] =
                        null;
                } elseif (
                    $effectiveIssueDate
                    && $effectiveDuration !== null
                    && $effectiveDuration > 0
                ) {
                    $changes[
                        'end_date'
                    ] =
                        $effectiveIssueDate
                            ->copy()
                            ->addMonths(
                                $effectiveDuration
                            )
                            ->toDateString();
                } elseif (
                    $durationProvided
                    && $effectiveDuration === 0
                ) {
                    /*
                     * Rok 0 mjeseci znači
                     * bez izračunatog isteka.
                     */
                    $changes[
                        'end_date'
                    ] =
                        null;
                }
            }

            /*
             * Ako u Excelu nije bilo
             * ničega za promjenu.
             */
            if ($changes === []) {
                $this->unchanged++;

                continue;
            }

            $item->fill(
                $changes
            );

            /*
             * Vrijednosti su možda popunjene,
             * ali identične postojećima.
             */
            if (! $item->isDirty()) {
                $this->unchanged++;

                continue;
            }

            $item->save();

            $this->updated++;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OIB
    |--------------------------------------------------------------------------
    */

    protected function normalizeOib(
        mixed $value
    ): string {
        if (
            $value === null
            || $value === ''
        ) {
            return '';
        }

        /*
         * Excel ponekad OIB pretvori u broj.
         */
        if (is_numeric($value)) {
            $value =
                number_format(
                    (float) $value,
                    0,
                    '',
                    ''
                );
        }

        $value =
            preg_replace(
                '/\D+/',
                '',
                (string) $value
            );

        if ($value === '') {
            return '';
        }

        /*
         * Vraćanje početne nule
         * ako ju je Excel uklonio.
         */
        return strlen($value) < 11
            ? str_pad(
                $value,
                11,
                '0',
                STR_PAD_LEFT
            )
            : $value;
    }

    /*
    |--------------------------------------------------------------------------
    | HEADER
    |--------------------------------------------------------------------------
    */

    protected function findHeaderRow(
        Collection $rows
    ): array {
        foreach (
            $rows->take(10)
            as $index => $row
        ) {
            $headers =
                collect(
                    $row
                )
                    ->map(
                        fn ($value): string =>
                            $this
                                ->normalizeHeader(
                                    $value
                                )
                    )
                    ->values()
                    ->all();

            if (
                in_array(
                    'oib',
                    $headers,
                    true
                )
                && in_array(
                    'naziv ozo',
                    $headers,
                    true
                )
            ) {
                return [
                    (int) $index,
                    $headers,
                ];
            }
        }

        throw new RuntimeException(
            'Nije pronađen red zaglavlja. '
            . 'Excel mora sadržavati stupce '
            . 'OIB i Naziv OZO.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ZAPOSLENIK
    |--------------------------------------------------------------------------
    */

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

        if (
            ! $hasOIBUpper
            && ! $hasOIBLower
        ) {
            return null;
        }

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
        return trim(
            (string) (
                $employee->OIB
                ?? $employee->oib
                ?? ''
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTAR OZO
    |--------------------------------------------------------------------------
    */

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
                    'name',
                    $name
                );

        /*
         * Ako je Norma navedena,
         * prvo pokušavamo točan pogodak.
         */
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

        /*
         * Ako postoji više zapisa
         * istog naziva, aktivni ima prednost.
         */
        return $query
            ->orderByDesc(
                'is_active'
            )
            ->orderBy(
                'id'
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | HEADER POMOĆNE FUNKCIJE
    |--------------------------------------------------------------------------
    */

    protected function headerIndex(
        array $headers,
        array $aliases,
        bool $required = true
    ): ?int {
        foreach (
            $aliases
            as $alias
        ) {
            $index =
                array_search(
                    $this->normalizeHeader(
                        $alias
                    ),
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
        $value =
            Str::ascii(
                Str::lower(
                    (string) $value
                )
            );

        $value =
            preg_replace(
                '/[^a-z0-9]+/',
                ' ',
                $value
            );

        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                (string) $value
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRAZNA / POPUNJENA ĆELIJA
    |--------------------------------------------------------------------------
    */

    protected function hasValue(
        mixed $value
    ): bool {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim(
                $value
            ) !== '';
        }

        /*
         * Broj 0 je stvarna vrijednost,
         * nije prazna ćelija.
         */
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | DATUM
    |--------------------------------------------------------------------------
    */

    protected function parseDate(
        mixed $value
    ): ?Carbon {
        if (
            ! $this->hasValue(
                $value
            )
        ) {
            return null;
        }

        /*
         * Pravi Excel datum.
         */
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
                /*
                 * Pokušaj idući format.
                 */
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