<?php

namespace App\Imports;

use App\Models\Chemical;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ChemicalsImport implements ToCollection
{
    public int $created = 0;
    public int $updated = 0;
    public int $unchanged = 0;
    public int $skipped = 0;

    protected int $ownerId;

    public function __construct()
    {
        $user = Auth::user();

        /*
         * Import je poslovna radnja organizacije.
         * Superadmin ne importira podatke u ime organizacija.
         */
        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            abort(403);
        }

        /*
         * Glavni korisnik i podkorisnici uvijek
         * importiraju u vlastitu organizaciju.
         */
        $this->ownerId =
            (int) $user->ownerId();

        if ($this->ownerId <= 0) {
            abort(403);
        }
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
            $headers as
            $index => $header
        ) {
            $key =
                $this->normalizeKey(
                    $header
                );

            if ($key) {
                $map[$key] = $index;
            }
        }

        for (
            $i = $dataStartIndex;
            $i < $rows->count();
            $i++
        ) {
            $row = $rows->get($i);

            if (
                ! $row
                || $this->isEmptyRow(
                    $row
                )
            ) {
                continue;
            }

            $productName =
                $this->clean(
                    $this->value(
                        $row,
                        $map,
                        'ime_proizvoda'
                    )
                );

            if (! $productName) {
                $this->skipped++;

                continue;
            }

            /*
             * Ownership je određen jednom
             * u konstruktoru importa.
             */
            $userId =
                $this->ownerId;

            $cas =
                $this->clean(
                    $this->value(
                        $row,
                        $map,
                        'cas_broj'
                    )
                );

            /*
             * Postojeći zapis tražimo
             * ISKLJUČIVO unutar iste organizacije.
             */
            $chemical =
                Chemical::query()
                    ->where(
                        'user_id',
                        $userId
                    )
                    ->where(
                        'product_name',
                        $productName
                    )
                    ->where(
                        function (
                            $query
                        ) use (
                            $cas
                        ): void {
                            if ($cas) {
                                $query->where(
                                    'cas_number',
                                    $cas
                                );
                            } else {
                                $query->whereNull(
                                    'cas_number'
                                );
                            }
                        }
                    )
                    ->first();

            $ufiRaw =
                trim(
                    (string) $this->value(
                        $row,
                        $map,
                        'ufi_broj'
                    )
                );

            /*
             * Array polja.
             *
             * Prazna Excel ćelija vraća null.
             * Time kod UPDATE-a ne brišemo
             * postojeće podatke.
             */
            $hazardPictograms =
                $this->parseList(
                    $this->value(
                        $row,
                        $map,
                        'piktogrami'
                    )
                );

            $hStatements =
                $this->parseList(
                    $this->value(
                        $row,
                        $map,
                        'h_oznake'
                    )
                );

            $pStatements =
                $this->parseList(
                    $this->value(
                        $row,
                        $map,
                        'p_oznake'
                    )
                );

            $data = [
                'user_id' =>
                    $userId,

                'product_name' =>
                    $productName,

                'cas_number' =>
                    $cas,

                'ufi_number' =>
                    in_array(
                        $ufiRaw,
                        [
                            '/',
                            '-',
                            '',
                        ],
                        true
                    )
                        ? null
                        : $this->clean(
                            $this->value(
                                $row,
                                $map,
                                'ufi_broj'
                            )
                        ),

                'hazard_pictograms' =>
                    $hazardPictograms,

                'h_statements' =>
                    $hStatements,

                'p_statements' =>
                    $pStatements,

                'usage_location' =>
                    $this->clean(
                        $this->value(
                            $row,
                            $map,
                            'mjesto_upotrebe'
                        )
                    ),

                'annual_quantity' =>
                    $this->clean(
                        $this->value(
                            $row,
                            $map,
                            'kolicina_kg_l'
                        )
                    ),

                'gvi_kgvi' =>
                    $this->clean(
                        $this->value(
                            $row,
                            $map,
                            'gvi_kgvi'
                        )
                    ),

                'voc' =>
                    $this->clean(
                        $this->value(
                            $row,
                            $map,
                            'voc'
                        )
                    ),

                'stl_hzjz' =>
                    $this->parseDate(
                        $this->value(
                            $row,
                            $map,
                            'stl_hzjz'
                        )
                    ),
            ];

            /*
             * NOVI ZAPIS.
             *
             * Kod novog zapisa prazna array polja
             * spremamo kao prazne arraye.
             */
            if (! $chemical) {
                $createData = $data;

                $createData[
                    'hazard_pictograms'
                ] =
                    $hazardPictograms ?? [];

                $createData[
                    'h_statements'
                ] =
                    $hStatements ?? [];

                $createData[
                    'p_statements'
                ] =
                    $pStatements ?? [];

                Chemical::create(
                    $createData
                );

                $this->created++;

                continue;
            }

            /*
             * POSTOJEĆI ZAPIS.
             *
             * Mijenjamo samo podatke
             * koji su stvarno promijenjeni.
             */
            $changed = [];

            foreach (
                $data as
                $field => $value
            ) {
                /*
                 * Ownership i identifikacijska
                 * polja ne mijenjamo kod updatea.
                 */
                if (
                    in_array(
                        $field,
                        [
                            'user_id',
                            'product_name',
                            'cas_number',
                        ],
                        true
                    )
                ) {
                    continue;
                }

                /*
                 * Prazna ćelija iz Excela
                 * NE briše postojeći podatak.
                 */
                if ($value === null) {
                    continue;
                }

                $current =
                    $chemical->{$field};

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

                /*
                 * Array vrijednosti uspoređujemo
                 * kao normalizirane JSON vrijednosti.
                 */
                if (is_array($current)) {
                    $current =
                        json_encode(
                            array_values(
                                $current
                            ),
                            JSON_UNESCAPED_UNICODE
                        );
                }

                $compareValue =
                    is_array($value)
                        ? json_encode(
                            array_values(
                                $value
                            ),
                            JSON_UNESCAPED_UNICODE
                        )
                        : (string) $value;

                if (
                    (string) (
                        $current ?? ''
                    )
                    !==
                    (string) $compareValue
                ) {
                    $changed[
                        $field
                    ] = $value;
                }
            }

            if (empty($changed)) {
                $this->unchanged++;

                continue;
            }

            $chemical->update(
                $changed
            );

            $this->updated++;
        }

        $this->logImport();
    }

    private function logImport(): void
    {
        ActivityLogger::import(
            module: 'Kemikalije',
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
                ] ?? null
            )
            : null;
    }

    private function isEmptyRow(
        $row
    ): bool {
        foreach ($row as $value) {
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

    /**
     * Prazna ćelija vraća null.
     *
     * To omogućuje da prilikom UPDATE-a
     * prazna Excel ćelija ne obriše postojeće
     * GHS / H / P oznake.
     *
     * @return array<int, string>|null
     */
    private function parseList(
        $value
    ): ?array {
        $value =
            $this->clean(
                $value
            );

        if (! $value) {
            return null;
        }

        return collect(
            preg_split(
                '/[,\n\r;:]+/',
                $value
            ) ?: []
        )
            ->map(
                fn ($item) =>
                    trim(
                        (string) $item
                    )
            )
            ->filter()
            ->map(
                function (
                    $item
                ) {
                    $item =
                        strtoupper(
                            trim(
                                (string) $item
                            )
                        );

                    $item =
                        pathinfo(
                            $item,
                            PATHINFO_FILENAME
                        );

                    $item =
                        str_replace(
                            [
                                ' ',
                                '_',
                                '-',
                            ],
                            '',
                            $item
                        );

                    /*
                     * GHS1 / GHS01 -> GHS01
                     */
                    if (
                        preg_match(
                            '/GHS0?([1-9])/',
                            $item,
                            $matches
                        )
                    ) {
                        return
                            'GHS0'
                            . $matches[1];
                    }

                    return
                        $this
                            ->normalizeStatementCode(
                                $item
                            );
                }
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
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

    private function normalizeStatementCode(
        string $item
    ): string {
        $item =
            strtoupper(
                trim($item)
            );

        $item =
            str_replace(
                [
                    ' ',
                    '_',
                    '-',
                ],
                '',
                $item
            );

        /*
         * Primjer:
         *
         * H300+310+330
         *
         * postaje:
         *
         * H300+H310+H330
         */
        if (
            preg_match(
                '/^([HP])(\d{3})(\+\d{3})+$/',
                $item,
                $matches
            )
        ) {
            return preg_replace(
                '/\+(\d{3})/',
                '+'
                    . $matches[1]
                    . '$1',
                $item
            );
        }

        return $item;
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
            'ime_proizvoda',
            'product_name'
                => 'ime_proizvoda',

            'cas',
            'cas_broj',
            'cas_number'
                => 'cas_broj',

            'ufi',
            'ufi_broj',
            'ufi_number'
                => 'ufi_broj',

            'piktogrami',
            'hazard_pictograms'
                => 'piktogrami',

            'h_oznake',
            'h_oznaka',
            'h_statements'
                => 'h_oznake',

            'p_oznake',
            'p_oznaka',
            'p_statements'
                => 'p_oznake',

            'mjesto_upotrebe',
            'usage_location'
                => 'mjesto_upotrebe',

            'kolicina',
            'kolicina_kg_l',
            'annual_quantity'
                => 'kolicina_kg_l',

            'gvi_kgvi'
                => 'gvi_kgvi',

            'voc'
                => 'voc',

            'stl',
            'stl_hzjz'
                => 'stl_hzjz',

            default => $key,
        };
    }
}