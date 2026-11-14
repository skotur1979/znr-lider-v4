<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Miscellaneous;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MiscellaneousImport implements ToCollection
{
    public int $created = 0;

    public int $updated = 0;

    public int $unchanged = 0;

    public int $skipped = 0;

    protected int $ownerId;

    protected bool $canCreateCategories = false;

    public function __construct()
    {
        $user = Auth::user();

        if (
            ! $user
            || $user->isSuperAdmin()
            || ! $user->hasModulePermission(
                'miscellaneous',
                'create'
            )
            || ! $user->hasModulePermission(
                'miscellaneous',
                'update'
            )
        ) {
            abort(403);
        }

        $this->ownerId =
            (int) $user->ownerId();

        if ($this->ownerId <= 0) {
            abort(403);
        }

        /*
         * Nova kategorija kroz import smije se
         * kreirati samo ako korisnik ima CREATE
         * pravo za modul Kategorije ispitivanja.
         */
        $this->canCreateCategories =
            $user->hasModulePermission(
                'categories',
                'create'
            );
    }

    public function collection(
        Collection $rows
    ): void {
        $headerRowIndex = 3;
        $dataStartIndex = 4;

        $headers =
            $rows->get(
                $headerRowIndex
            );

        if (! $headers) {
            $this->skipped++;

            $this->logImport();

            return;
        }

        $map = [];

        foreach (
            $headers as $index => $header
        ) {
            $key =
                $this->normalizeKey(
                    $header
                );

            if ($key) {
                $map[$key] =
                    $index;
            }
        }

        for (
            $i = $dataStartIndex;
            $i < $rows->count();
            $i++
        ) {
            $row =
                $rows->get($i);

            if (
                ! $row
                || $this->isEmptyRow($row)
            ) {
                continue;
            }

            $name = $this->clean(
                $this->value(
                    $row,
                    $map,
                    'naziv'
                )
            );

            $categoryName =
                $this->clean(
                    $this->value(
                        $row,
                        $map,
                        'kategorija'
                    )
                );

            $examiner =
                $this->clean(
                    $this->value(
                        $row,
                        $map,
                        'ispitao'
                    )
                );

            $reportNo =
                $this->clean(
                    $this->value(
                        $row,
                        $map,
                        'broj_izvjestaja'
                    )
                );

            $validFrom =
                $this->parseDate(
                    $this->value(
                        $row,
                        $map,
                        'vrijedi_od'
                    )
                );

            $validUntil =
                $this->parseDate(
                    $this->value(
                        $row,
                        $map,
                        'vrijedi_do'
                    )
                );

            $remark =
                $this->clean(
                    $this->value(
                        $row,
                        $map,
                        'napomena'
                    )
                );

            /*
             * Obavezni podaci.
             */
            if (
                ! $name
                || ! $categoryName
                || ! $validFrom
                || ! $validUntil
            ) {
                $this->skipped++;

                continue;
            }

            /*
             * Duljine prema stvarnoj DB shemi.
             */
            if (
                mb_strlen($name) > 512
                || mb_strlen($categoryName) > 256
                || (
                    $examiner !== null
                    && mb_strlen($examiner) > 256
                )
                || (
                    $reportNo !== null
                    && mb_strlen($reportNo) > 191
                )
                || (
                    $remark !== null
                    && mb_strlen($remark) > 1024
                )
            ) {
                $this->skipped++;

                continue;
            }

            /*
             * Datum završetka ne može biti prije
             * početnog datuma valjanosti.
             */
            if (
                Carbon::parse($validUntil)
                    ->lt(
                        Carbon::parse(
                            $validFrom
                        )
                    )
            ) {
                $this->skipped++;

                continue;
            }

            $userId =
                $this->ownerId;

            /*
             * Kategoriju tražimo isključivo
             * unutar trenutne organizacije.
             */
            $category =
                Category::query()
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'name',
                        $categoryName
                    )
                    ->first();

            /*
             * Ako kategorija ne postoji,
             * kreiranje zahtijeva CREATE pravo
             * za categories.
             */
            if (! $category) {
                if (
                    ! $this->canCreateCategories
                ) {
                    $this->skipped++;

                    continue;
                }

                $category =
                    Category::create([
                        'user_id' =>
                            $userId,

                        'name' =>
                            $categoryName,
                    ]);
            }

            /*
             * Postojeći zapis ne smijemo
             * pronaći običnim first() po
             * name + category jer u stvarnim
             * podacima postoje duplikati.
             */
            $resolution =
                $this->resolveExistingRecord(
                    userId: $userId,
                    categoryId:
                        (int) $category->id,
                    name: $name,
                    reportNo: $reportNo,
                    validFrom: $validFrom,
                    validUntil: $validUntil,
                );

            /*
             * Ako postoji više kandidata i nije
             * moguće jednoznačno odrediti zapis,
             * sigurnije je preskočiti red nego
             * ažurirati pogrešno ispitivanje.
             */
            if (
                $resolution['ambiguous']
            ) {
                $this->skipped++;

                continue;
            }

            /** @var Miscellaneous|null $record */
            $record =
                $resolution['record'];

            $data = [
                'user_id' =>
                    $userId,

                'category_id' =>
                    $category->id,

                'name' =>
                    $name,

                'examiner' =>
                    $examiner,

                'report_number' =>
                    $reportNo,

                'examination_valid_from' =>
                    $validFrom,

                'examination_valid_until' =>
                    $validUntil,

                'remark' =>
                    $remark,
            ];

            /*
             * Novi zapis.
             */
            if (! $record) {
                Miscellaneous::create(
                    $data
                );

                $this->created++;

                continue;
            }

            /*
             * Postojeći zapis.
             *
             * Ownership, naziv i kategoriju
             * ne mijenjamo automatskim importom.
             */
            $changed = [];

            foreach (
                $data as $field => $value
            ) {
                if (
                    in_array(
                        $field,
                        [
                            'user_id',
                            'name',
                            'category_id',
                        ],
                        true
                    )
                ) {
                    continue;
                }

                /*
                 * Prazna Excel ćelija ne briše
                 * postojeću vrijednost.
                 */
                if ($value === null) {
                    continue;
                }

                $current =
                    $record->{$field};

                if (
                    $current
                    instanceof
                    \Carbon\CarbonInterface
                ) {
                    $current =
                        $current->format(
                            'Y-m-d'
                        );
                }

                if (
                    (string) (
                        $current ?? ''
                    )
                    !==
                    (string) $value
                ) {
                    $changed[$field] =
                        $value;
                }
            }

            if (empty($changed)) {
                $this->unchanged++;

                continue;
            }

            $record->update(
                $changed
            );

            $this->updated++;
        }

        $this->logImport();
    }

    /**
     * Sigurno pronalaženje postojećeg zapisa.
     *
     * name + category nije uvijek jedinstven,
     * zato u slučaju više kandidata koristimo
     * broj izvještaja i datume kao dodatne
     * diskriminatore.
     */
    private function resolveExistingRecord(
        int $userId,
        int $categoryId,
        string $name,
        ?string $reportNo,
        string $validFrom,
        string $validUntil
    ): array {
        $candidates =
            Miscellaneous::query()
                ->where(
                    'user_id',
                    $userId
                )
                ->where(
                    'category_id',
                    $categoryId
                )
                ->where(
                    'name',
                    $name
                )
                ->get();

        if ($candidates->isEmpty()) {
            return [
                'record' => null,
                'ambiguous' => false,
            ];
        }

        if ($candidates->count() === 1) {
            return [
                'record' =>
                    $candidates->first(),

                'ambiguous' =>
                    false,
            ];
        }

        /*
         * Ako postoji broj izvještaja,
         * prvo pokušavamo njime suziti izbor.
         */
        if ($reportNo !== null) {
            $byReport =
                $candidates->filter(
                    fn (
                        Miscellaneous $record
                    ): bool =>
                        trim(
                            (string)
                            $record->report_number
                        )
                        ===
                        $reportNo
                );

            if (
                $byReport->count() === 1
            ) {
                return [
                    'record' =>
                        $byReport->first(),

                    'ambiguous' =>
                        false,
                ];
            }

            if (
                $byReport->count() > 1
            ) {
                $candidates =
                    $byReport->values();
            }
        }

        /*
         * Ako još postoji više kandidata,
         * pokušavamo potpuno podudaranje
         * datuma valjanosti.
         */
        $byDates =
            $candidates->filter(
                function (
                    Miscellaneous $record
                ) use (
                    $validFrom,
                    $validUntil
                ): bool {
                    $recordFrom =
                        $record
                            ->examination_valid_from
                            ?->format('Y-m-d');

                    $recordUntil =
                        $record
                            ->examination_valid_until
                            ?->format('Y-m-d');

                    return
                        $recordFrom
                            === $validFrom
                        &&
                        $recordUntil
                            === $validUntil;
                }
            );

        if ($byDates->count() === 1) {
            return [
                'record' =>
                    $byDates->first(),

                'ambiguous' =>
                    false,
            ];
        }

        return [
            'record' => null,
            'ambiguous' => true,
        ];
    }

    private function logImport(): void
    {
        ActivityLogger::import(
            module: 'Ostala ispitivanja',
            created: $this->created,
            updated: $this->updated,
            unchanged: $this->unchanged,
            skipped: $this->skipped,
        );
    }

    private function value(
        $row,
        array $map,
        string $key
    ) {
        return array_key_exists(
            $key,
            $map
        )
            ? (
                $row[
                    $map[$key]
                ]
                ?? null
            )
            : null;
    }

    private function isEmptyRow(
        $row
    ): bool {
        foreach (
            $row as $value
        ) {
            if (
                $this->clean($value)
                !== null
            ) {
                return false;
            }
        }

        return true;
    }

    private function clean(
        $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value === ''
            ? null
            : $value;
    }

    private function parseDate(
        $value
    ): ?string {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        if (
            $value
            instanceof
            \DateTimeInterface
        ) {
            return Carbon::instance(
                $value
            )->format(
                'Y-m-d'
            );
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(
                    Date::excelToDateTimeObject(
                        (float) $value
                    )
                )->format(
                    'Y-m-d'
                );
            } catch (\Throwable) {
                return null;
            }
        }

        $value =
            rtrim(
                trim(
                    (string) $value
                ),
                '.'
            );

        foreach (
            [
                'd.m.Y',
                'd/m/Y',
                'd-m-Y',
                'Y-m-d',
                'd.m.y',
                'd/m/y',
                'd-m-y',
            ] as $format
        ) {
            try {
                $date =
                    Carbon::createFromFormat(
                        $format,
                        $value
                    );

                if ($date !== false) {
                    return $date->format(
                        'Y-m-d'
                    );
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            return Carbon::parse(
                $value
            )->format(
                'Y-m-d'
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeKey(
        $key
    ): ?string {
        if (
            $key === null
            || trim(
                (string) $key
            ) === ''
        ) {
            return null;
        }

        $key =
            Str::of(
                (string) $key
            )
                ->lower()
                ->replace(
                    [
                        'š',
                        'đ',
                        'č',
                        'ć',
                        'ž',
                    ],
                    [
                        's',
                        'd',
                        'c',
                        'c',
                        'z',
                    ]
                )
                ->replace(
                    [
                        '/',
                        '-',
                        '.',
                        '(',
                        ')',
                    ],
                    ' '
                )
                ->replace(
                    "\u{00A0}",
                    ' '
                )
                ->replaceMatches(
                    '/\s+/',
                    ' '
                )
                ->trim()
                ->replace(
                    ' ',
                    '_'
                )
                ->toString();

        return match ($key) {
            'naziv'
                => 'naziv',

            'kategorija'
                => 'kategorija',

            'ispitao'
                => 'ispitao',

            'broj_izvjestaja',
            'broj_izvestaja',
            'izvjestaj_broj'
                => 'broj_izvjestaja',

            'vrijedi_od'
                => 'vrijedi_od',

            'vrijedi_do'
                => 'vrijedi_do',

            'napomena'
                => 'napomena',

            default =>
                $key,
        };
    }
}