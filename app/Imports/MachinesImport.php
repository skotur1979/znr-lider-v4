<?php

namespace App\Imports;

use App\Models\Machine;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MachinesImport implements ToCollection
{
    public int $created = 0;

    public int $updated = 0;

    public int $unchanged = 0;

    public int $skipped = 0;

    protected int $ownerId;

    public function __construct()
    {
        $user = Auth::user();

        if (
            ! $user
            || $user->isSuperAdmin()
            || ! $user->hasModulePermission(
                'machines',
                'create'
            )
        ) {
            abort(403);
        }

        $this->ownerId =
            (int) $user->ownerId();

        if ($this->ownerId <= 0) {
            abort(403);
        }
    }

    public function collection(
        Collection $rows
    ): void {
        /*
         * Excel predložak:
         *
         * red 4 = zaglavlje
         * red 5 = prvi podatak
         *
         * Collection koristi zero-based index.
         */
        $headerRowIndex = 3;
        $dataStartIndex = 4;

        $headers = $rows->get(
            $headerRowIndex
        );

        if (! $headers) {
            $this->skipped++;

            $this->logImport();

            return;
        }

        /*
         * Mapiranje naziva stupaca.
         */
        $map = [];

        foreach (
            $headers as $index => $header
        ) {
            $key = $this->normalizeKey(
                $header
            );

            if ($key) {
                $map[$key] = $index;
            }
        }

        /*
         * Obrada redova.
         */
        for (
            $i = $dataStartIndex;
            $i < $rows->count();
            $i++
        ) {
            $row = $rows->get($i);

            if (
                ! $row
                || $this->isEmptyRow($row)
            ) {
                continue;
            }

            /*
             * Osnovni podaci.
             */
            $name = $this->clean(
                $this->value(
                    $row,
                    $map,
                    'naziv'
                )
            );

            if (! $name) {
                $this->skipped++;

                continue;
            }

            $manufacturer = $this->clean(
                $this->value(
                    $row,
                    $map,
                    'proizvodac'
                )
            );

            $factoryNumber = $this->clean(
                $this->value(
                    $row,
                    $map,
                    'tvornicki_broj'
                )
            );

            $inventoryNumber = $this->clean(
                $this->value(
                    $row,
                    $map,
                    'inventarni_broj'
                )
            );

            $validFrom = $this->parseDate(
                $this->value(
                    $row,
                    $map,
                    'vrijedi_od'
                )
            );

            $validUntil = $this->parseDate(
                $this->value(
                    $row,
                    $map,
                    'vrijedi_do'
                )
            );

            $examinedBy = $this->clean(
                $this->value(
                    $row,
                    $map,
                    'ispitao'
                )
            );

            $reportNo = $this->clean(
                $this->value(
                    $row,
                    $map,
                    'broj_izvjestaja'
                )
            );

            $location = $this->clean(
                $this->value(
                    $row,
                    $map,
                    'lokacija'
                )
            );

            $remark = $this->clean(
                $this->value(
                    $row,
                    $map,
                    'napomena'
                )
            );

            /*
             * Obavezna polja.
             */
            if (
                ! $validFrom
                || ! $validUntil
                || ! $location
            ) {
                $this->skipped++;

                continue;
            }

            /*
             * Datum isteka ne smije biti prije
             * datuma ispitivanja.
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

            /*
             * Zaštita prema stvarnim duljinama
             * stupaca u bazi.
             *
             * Vrijednosti ne režemo automatski jer
             * tvornički broj, inventarni broj i broj
             * izvještaja mogu biti važni identifikatori.
             */
            if (
                mb_strlen($name) > 255
                || (
                    $manufacturer !== null
                    && mb_strlen(
                        $manufacturer
                    ) > 255
                )
                || (
                    $factoryNumber !== null
                    && mb_strlen(
                        $factoryNumber
                    ) > 125
                )
                || (
                    $inventoryNumber !== null
                    && mb_strlen(
                        $inventoryNumber
                    ) > 125
                )
                || (
                    $examinedBy !== null
                    && mb_strlen(
                        $examinedBy
                    ) > 191
                )
                || (
                    $reportNo !== null
                    && mb_strlen(
                        $reportNo
                    ) > 191
                )
                || mb_strlen(
                    $location
                ) > 255
                || (
                    $remark !== null
                    && mb_strlen(
                        $remark
                    ) > 1024
                )
            ) {
                $this->skipped++;

                continue;
            }

            $userId =
                $this->ownerId;

            /*
             * Postojeću radnu opremu tražimo
             * unutar iste organizacije prema:
             *
             * - nazivu
             * - tvorničkom broju
             *
             * Ako tvornički broj nije zadan,
             * traži se zapis s NULL vrijednošću.
             */
            $machine = Machine::query()
                ->where(
                    'user_id',
                    $userId
                )
                ->where(
                    'name',
                    $name
                )
                ->where(
                    function (
                        $query
                    ) use (
                        $factoryNumber
                    ): void {
                        if ($factoryNumber) {
                            $query->where(
                                'factory_number',
                                $factoryNumber
                            );
                        } else {
                            $query->whereNull(
                                'factory_number'
                            );
                        }
                    }
                )
                ->first();

            /*
             * Broj izvještaja NAMJERNO nije
             * jedinstven.
             *
             * Jedan zapisnik / izvještaj može
             * obuhvatiti više komada radne opreme.
             */
            $data = [
                'user_id' =>
                    $userId,

                'name' =>
                    $name,

                'manufacturer' =>
                    $manufacturer,

                'factory_number' =>
                    $factoryNumber,

                'inventory_number' =>
                    $inventoryNumber,

                'examination_valid_from' =>
                    $validFrom,

                'examination_valid_until' =>
                    $validUntil,

                'examined_by' =>
                    $examinedBy,

                'report_number' =>
                    $reportNo,

                'location' =>
                    $location,

                'remark' =>
                    $remark,
            ];

            /*
             * Novi zapis.
             */
            if (! $machine) {
                Machine::create($data);

                $this->created++;

                continue;
            }

            /*
             * Postojeći zapis.
             *
             * Ownership i osnovni identitet zapisa
             * ne mijenjamo tijekom importa.
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
                            'factory_number',
                        ],
                        true
                    )
                ) {
                    continue;
                }

                /*
                 * Prazna Excel vrijednost ne briše
                 * postojeći podatak iz baze.
                 */
                if ($value === null) {
                    continue;
                }

                $current =
                    $machine->{$field};

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

            $machine->update(
                $changed
            );

            $this->updated++;
        }

        $this->logImport();
    }

    private function logImport(): void
    {
        ActivityLogger::import(
            module: 'Radna oprema',
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

        $value = trim(
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

        /*
         * Već parsirani datum.
         */
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

        /*
         * Excel serijski datum.
         */
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

        $value = rtrim(
            trim(
                (string) $value
            ),
            '.'
        );

        /*
         * Najčešći formati datuma
         * u Excel dokumentima.
         */
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

                if (
                    $date !== false
                ) {
                    return $date->format(
                        'Y-m-d'
                    );
                }
            } catch (\Throwable) {
                //
            }
        }

        /*
         * Zadnji fallback.
         */
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

        $key = Str::of(
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

            'proizvodac'
                => 'proizvodac',

            'tvornicki_broj',
            'tvorn_broj',
            'tvornicki_br',
            'tvor_broj'
                => 'tvornicki_broj',

            'inventarni_broj',
            'inventarni_br'
                => 'inventarni_broj',

            'vrijedi_od'
                => 'vrijedi_od',

            'vrijedi_do'
                => 'vrijedi_do',

            'ispitao'
                => 'ispitao',

            'broj_izvjestaja',
            'broj_izvestaja',
            'izvjestaj_broj'
                => 'broj_izvjestaja',

            'lokacija'
                => 'lokacija',

            'napomena'
                => 'napomena',

            default =>
                $key,
        };
    }
}