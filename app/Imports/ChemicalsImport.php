<?php

namespace App\Imports;

use App\Enums\HazardStatement;
use App\Enums\PrecautionaryStatement;
use App\Filament\Resources\Chemicals\ChemicalResource;
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

    /*
     * Stvarni DB stupci su varchar(191).
     */
    private const STRING_LIMITS = [
        'product_name' => 191,
        'cas_number' => 191,
        'ufi_number' => 191,
        'usage_location' => 191,
        'annual_quantity' => 191,
        'gvi_kgvi' => 191,
        'voc' => 191,
    ];

    public function __construct()
    {
        $user = Auth::user();

        /*
         * Import je poslovna radnja organizacije.
         *
         * Superadmin ne importira podatke
         * u ime organizacija.
         */
        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            abort(403);
        }

        /*
         * Import može:
         * - kreirati novi zapis
         * - ažurirati postojeći zapis
         *
         * Zato korisnik mora imati obje dozvole.
         */
        if (
            ! ChemicalResource::allowsModulePermission(
                'create'
            )
            || ! ChemicalResource::allowsModulePermission(
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
    }

    public function collection(
        Collection $rows
    ): void {
        /*
         * Postojeći Excel predložak:
         *
         * index 3 = red zaglavlja
         * index 4 = prvi podatkovni red
         */
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

            /*
             * Ime proizvoda je obvezno.
             */
            if (
                ! $productName
                || mb_strlen(
                    $productName
                ) > 191
            ) {
                $this->skipped++;

                continue;
            }

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
             * Prvo tražimo AKTIVNI zapis.
             *
             * Ako postoji aktivni zapis,
             * ažurirat ćemo njega čak i ako postoji
             * stariji deaktivirani duplikat.
             */
            $chemical =
                $this->findMatchingChemical(
                    userId: $userId,
                    productName: $productName,
                    cas: $cas,
                    trashedOnly: false
                );

            /*
             * Ako aktivni zapis ne postoji,
             * provjeravamo postoji li isti
             * deaktivirani zapis.
             *
             * Import ga NE vraća automatski
             * jer bi to zaobišlo restore/delete
             * dozvolu.
             */
            if (! $chemical) {
                $trashedChemical =
                    $this->findMatchingChemical(
                        userId: $userId,
                        productName: $productName,
                        cas: $cas,
                        trashedOnly: true
                    );

                if ($trashedChemical) {
                    $this->skipped++;

                    continue;
                }
            }

            $ufiRaw =
                trim(
                    (string) (
                        $this->value(
                            $row,
                            $map,
                            'ufi_broj'
                        )
                        ?? ''
                    )
                );

            $ufi =
                in_array(
                    $ufiRaw,
                    [
                        '',
                        '/',
                        '-',
                    ],
                    true
                )
                    ? null
                    : $this->clean(
                        $ufiRaw
                    );

            /*
             * Array polja.
             *
             * Prazna Excel ćelija vraća null.
             *
             * Kod UPDATE-a null znači:
             * "ne mijenjaj postojeću vrijednost".
             */
            $hazardPictograms =
                $this->parsePictograms(
                    $this->value(
                        $row,
                        $map,
                        'piktogrami'
                    )
                );

            $hStatements =
                $this->parseStatementList(
                    $this->value(
                        $row,
                        $map,
                        'h_oznake'
                    ),
                    'H'
                );

            $pStatements =
                $this->parseStatementList(
                    $this->value(
                        $row,
                        $map,
                        'p_oznake'
                    ),
                    'P'
                );

            $usageLocation =
                $this->clean(
                    $this->value(
                        $row,
                        $map,
                        'mjesto_upotrebe'
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
                    $ufi,

                'hazard_pictograms' =>
                    $hazardPictograms,

                'h_statements' =>
                    $hStatements,

                'p_statements' =>
                    $pStatements,

                'usage_location' =>
                    $usageLocation,

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
             * Zaštita stvarnih DB duljina.
             *
             * Ne režemo podatke tiho.
             * Ako je vrijednost preduga,
             * red se preskače.
             */
            if (
                ! $this->fitsDatabaseLimits(
                    $data
                )
            ) {
                $this->skipped++;

                continue;
            }

            /*
             * NOVI ZAPIS.
             */
            if (! $chemical) {
                /*
                 * Mjesto upotrebe je obvezno
                 * za novi Chemical, jednako kao
                 * u ručnoj formi.
                 */
                if (blank($usageLocation)) {
                    $this->skipped++;

                    continue;
                }

                $createData =
                    $data;

                $createData[
                    'hazard_pictograms'
                ] =
                    $hazardPictograms
                    ?? [];

                $createData[
                    'h_statements'
                ] =
                    $hStatements
                    ?? [];

                $createData[
                    'p_statements'
                ] =
                    $pStatements
                    ?? [];

                Chemical::create(
                    $createData
                );

                $this->created++;

                continue;
            }

            /*
             * POSTOJEĆI ZAPIS.
             *
             * Ownership i identifikacijska
             * polja ne mijenjamo.
             *
             * Prazne Excel ćelije ne brišu
             * postojeće podatke.
             */
            $changed = [];

            foreach (
                $data as
                $field => $value
            ) {
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
                    $changed[$field] =
                        $value;
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

    /**
     * Traži isti proizvod isključivo
     * unutar iste organizacije.
     *
     * CAS normaliziramo samo za usporedbu:
     * uklanjamo whitespace koji često nastaje
     * prijelomom ćelija u Excelu.
     */
    private function findMatchingChemical(
        int $userId,
        string $productName,
        ?string $cas,
        bool $trashedOnly
    ): ?Chemical {
        $query =
            Chemical::query()
                ->where(
                    'user_id',
                    $userId
                )
                ->whereRaw(
                    'LOWER(TRIM(product_name)) = LOWER(?)',
                    [
                        trim($productName),
                    ]
                );

        if ($trashedOnly) {
            $query->onlyTrashed();
        }

        $normalizedCas =
            $this->normalizeCas(
                $cas
            );

        return $query
            ->get()
            ->first(
                fn (
                    Chemical $chemical
                ): bool =>
                    $this->normalizeCas(
                        $chemical->cas_number
                    )
                    ===
                    $normalizedCas
            );
    }

    private function normalizeCas(
        ?string $value
    ): string {
        if ($value === null) {
            return '';
        }

        $value =
            preg_replace(
                '/\s+/u',
                '',
                trim($value)
            ) ?? '';

        return mb_strtolower(
            $value
        );
    }

    private function fitsDatabaseLimits(
        array $data
    ): bool {
        foreach (
            self::STRING_LIMITS as
            $field => $maxLength
        ) {
            $value =
                $data[$field]
                ?? null;

            if ($value === null) {
                continue;
            }

            if (
                mb_strlen(
                    (string) $value
                ) > $maxLength
            ) {
                return false;
            }
        }

        return true;
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
        foreach (
            $row as $value
        ) {
            if (
                $value
                instanceof
                \DateTimeInterface
            ) {
                return false;
            }

            if (
                $this->clean(
                    $value
                ) !== null
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

        if (
            $value
            instanceof
            \DateTimeInterface
        ) {
            return $value->format(
                'Y-m-d'
            );
        }

        if (
            ! is_scalar($value)
            && ! $value
                instanceof
                \Stringable
        ) {
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

    /**
     * Prazna ćelija vraća null.
     *
     * Neispravni/poznato nepodržani kodovi
     * također ne prepisuju postojeći UPDATE.
     *
     * @return array<int, string>|null
     */
    private function parsePictograms(
        $value
    ): ?array {
        $value =
            $this->clean(
                $value
            );

        if (! $value) {
            return null;
        }

        $result =
            collect(
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
                    ): ?string {
                        $item =
                            strtoupper(
                                trim(
                                    (string) $item
                                )
                            );

                        /*
                         * Podržava npr.
                         * GHS01.png
                         */
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

                        if (
                            preg_match(
                                '/^GHS0?([1-9])$/',
                                $item,
                                $matches
                            )
                        ) {
                            return
                                'GHS0'
                                . $matches[1];
                        }

                        /*
                         * Sve ostalo odbacujemo.
                         */
                        return null;
                    }
                )
                ->filter()
                ->unique()
                ->values()
                ->all();

        /*
         * Ako ćelija nije prazna, ali niti jedna
         * oznaka nije valjana, UPDATE neće obrisati
         * postojeće podatke.
         */
        return empty($result)
            ? null
            : $result;
    }

    /**
     * @return array<int, string>|null
     */
    private function parseStatementList(
        $value,
        string $type
    ): ?array {
        $value =
            $this->clean(
                $value
            );

        if (! $value) {
            return null;
        }

        $type =
            strtoupper(
                $type
            );

        $available =
            $type === 'H'
                ? HazardStatement::list()
                : PrecautionaryStatement::list();

        /*
         * Case-insensitive mapa.
         *
         * Primjeri:
         * H361D -> stvarni H361d
         * P280E -> možemo posebno mapirati.
         */
        $availableMap = [];

        foreach (
            array_keys(
                $available
            ) as $code
        ) {
            $availableMap[
                strtoupper(
                    $code
                )
            ] = $code;
        }

        $result =
            collect(
                preg_split(
                    '/[,\n\r;:]+/',
                    $value
                ) ?: []
            )
                ->map(
                    fn ($item): string =>
                        trim(
                            (string) $item
                        )
                )
                ->filter()
                ->flatMap(
                    function (
                        string $item
                    ) use (
                        $type,
                        $availableMap
                    ): array {
                        return $this
                            ->normalizeImportedStatement(
                                $item,
                                $type,
                                $availableMap
                            );
                    }
                )
                ->filter()
                ->unique()
                ->values()
                ->all();

        return empty($result)
            ? null
            : $result;
    }

    /**
     * @param array<string, string> $availableMap
     * @return array<int, string>
     */
    private function normalizeImportedStatement(
        string $item,
        string $type,
        array $availableMap
    ): array {
        $item =
            trim(
                $item
            );

        if ($item === '') {
            return [];
        }

        /*
         * Normalizacija whitespacea.
         */
        $item =
            str_replace(
                [
                    ' ',
                    '_',
                ],
                '',
                $item
            );

        /*
         * Unicode crtice -> obična crtica.
         */
        $item =
            str_replace(
                [
                    '–',
                    '—',
                ],
                '-',
                $item
            );

        $upper =
            strtoupper(
                $item
            );

        /*
        |--------------------------------------------------------------------------
        | H / EUH OZNAKE
        |--------------------------------------------------------------------------
        */
        if ($type === 'H') {
            /*
             * Neki izvori koriste npr.
             * H222-H229 za dvije zasebne oznake.
             */
            if (
                preg_match(
                    '/^(H\d{3}[A-Z]?)-H?(\d{3}[A-Z]?)$/',
                    $upper,
                    $matches
                )
            ) {
                $first =
                    $matches[1];

                $second =
                    'H'
                    . $matches[2];

                $result = [];

                if (
                    isset(
                        $availableMap[
                            $first
                        ]
                    )
                ) {
                    $result[] =
                        $availableMap[
                            $first
                        ];
                }

                if (
                    isset(
                        $availableMap[
                            $second
                        ]
                    )
                ) {
                    $result[] =
                        $availableMap[
                            $second
                        ];
                }

                return $result;
            }

            /*
             * Za ostale statemente crtica
             * nije separator.
             */
            $upper =
                str_replace(
                    '-',
                    '',
                    $upper
                );

            $aliases = [
                'H300+H310+H330' =>
                    'H300',

                'H301+H311+H331' =>
                    'H301',

                'H302+H312+H332' =>
                    'H302',

                'H310+H330' =>
                    'H310',

                'H311+H331' =>
                    'H311',

                'H312+H332' =>
                    'H312',
            ];

            /*
             * H300+310+330
             * ->
             * H300+H310+H330
             */
            if (
                preg_match(
                    '/^(H)(\d{3})(\+\d{3})+$/',
                    $upper
                )
            ) {
                $upper =
                    preg_replace(
                        '/\+(\d{3})/',
                        '+H$1',
                        $upper
                    )
                    ?? $upper;
            }

            if (
                isset(
                    $aliases[
                        $upper
                    ]
                )
            ) {
                return [
                    $aliases[
                        $upper
                    ],
                ];
            }

            if (
                isset(
                    $availableMap[
                        $upper
                    ]
                )
            ) {
                return [
                    $availableMap[
                        $upper
                    ],
                ];
            }

            /*
             * Nepoznata H / EUH oznaka
             * ne sprema se.
             */
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | P OZNAKE
        |--------------------------------------------------------------------------
        */

        /*
         * U starim podacima postoji P280e / P280E.
         *
         * Enum koristi P280.
         */
        $pAliases = [
            'P280E' =>
                'P280',
        ];

        $upper =
            str_replace(
                '-',
                '',
                $upper
            );

        if (
            isset(
                $pAliases[
                    $upper
                ]
            )
        ) {
            return [
                $pAliases[
                    $upper
                ],
            ];
        }

        /*
         * P305+351+338
         * ->
         * P305+P351+P338
         */
        if (
            preg_match(
                '/^P\d{3}(?:\+\d{3})+$/',
                $upper
            )
        ) {
            $upper =
                preg_replace(
                    '/\+(\d{3})/',
                    '+P$1',
                    $upper
                )
                ?? $upper;
        }

        /*
         * Ako cijela kombinacija postoji
         * u enumu, koristimo nju.
         */
        if (
            isset(
                $availableMap[
                    $upper
                ]
            )
        ) {
            return [
                $availableMap[
                    $upper
                ],
            ];
        }

        /*
         * Jedna nepoznata oznaka se odbacuje.
         */
        if (
            ! str_contains(
                $upper,
                '+'
            )
        ) {
            return [];
        }

        /*
         * Razbijamo kombinaciju i tražimo
         * najduže valjane kombinacije.
         *
         * Primjer:
         *
         * P305+P351+P338+P310
         *
         * ->
         *
         * P305+P351+P338
         * P310
         */
        $parts =
            array_values(
                array_filter(
                    explode(
                        '+',
                        $upper
                    )
                )
            );

        if (empty($parts)) {
            return [];
        }

        $result = [];

        $count =
            count(
                $parts
            );

        $index = 0;

        while (
            $index < $count
        ) {
            $matched = false;

            for (
                $end = $count;
                $end > $index;
                $end--
            ) {
                $candidate =
                    implode(
                        '+',
                        array_slice(
                            $parts,
                            $index,
                            $end - $index
                        )
                    );

                if (
                    isset(
                        $availableMap[
                            $candidate
                        ]
                    )
                ) {
                    $result[] =
                        $availableMap[
                            $candidate
                        ];

                    $index =
                        $end;

                    $matched =
                        true;

                    break;
                }
            }

            if ($matched) {
                continue;
            }

            /*
             * Nepoznata komponenta se preskače.
             */
            $index++;
        }

        return $result;
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
                    return $date
                        ->format(
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

            default =>
                $key,
        };
    }
}