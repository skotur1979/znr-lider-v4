<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\Process;
use thiagoalessio\TesseractOCR\TesseractOCR;

class MachineReportOcrService
{
    protected const OCR_FIELDS = [
        'name',
        'manufacturer',
        'factory_number',
        'inventory_number',
        'report_number',
        'location',
        'examination_valid_from',
        'examination_valid_until',
        'examined_by',
    ];

    /**
     * Poznati nazivi ovlaštenih kuća služe ISKLJUČIVO za normalizaciju polja
     * "Ispitao". Ostatak OCR parsera i dalje ostaje potpuno generički.
     *
     * Kad se pojavi nova ovlaštena kuća, dovoljno je dodati samo jedan redak
     * u ovu listu – ne radi se novi parser obrasca.
     */
    protected const INSPECTION_COMPANIES = [
        '/\bKONTROL\s+BIRO\b/iu' => 'KONTROL BIRO d.o.o.',
        '/ME[ĐD]IMURJE\s+ZAING/iu' => 'MEĐIMURJE ZAING d.o.o.',
        '/\bE\.?\s*S\.?\s*K\.?\b|ESK[-\s]*CROATIA/iu' => 'E.S.K. d.o.o.',
        '/\bZAGREBINSPEKT\b|LAGREBINSPEKT/iu' => 'ZAGREBINSPEKT d.o.o.',
        '/\bALFA\s+ATEST\b/iu' => 'ALFA ATEST d.o.o.',
        '/ENERGOZAVOD[-\s]*ZA[ŠS]TITA/iu' => 'ENERGOZAVOD-ZAŠTITA d.o.o.',
        '/\bMETRO[-\s]*ING\b/iu' => 'METRO-ING d.o.o.',
    ];

    /**
     * Središnja lista mogućih naziva polja.
     * Kod novih obrazaca u pravilu je dovoljno dodati novu varijantu ovdje.
     */
    protected const FIELD_ALIASES = [
        'name' => [
            'naziv radne opreme',
            'naziv stroja i uređaja',
            'naziv stroja',
            'naziv opreme',
            'naziv',
        ],
        'manufacturer' => [
            'naziv proizvođača',
            'proizvođač',
            'proizvodac',
            'proizvodač',
            'proizvodat',
        ],
        'factory_number' => [
            'tvornički/serijski broj',
            'tvornicki/serijski broj',
            'tvornički broj',
            'tvornicki broj',
            'serijski broj',
            'tv. broj',
        ],
        'inventory_number' => [
            'inventarski broj',
            'inventarni broj',
            'inventarni / interni broj',
            'inventarni/interni broj',
            'interni broj',
        ],
        'report_number' => [
            'broj zapisnika',
            'zapisnik br.',
            'zapisnik br',
            'broj izvještaja',
            'broj izvjestaja',
        ],
        'location' => [
            'lokacija ispitivanja',
            'mjesto ispitivanja',
            'položaj u radnom prostoru',
            'polozaj u radnom prostoru',
            'položaj u prostoru',
            'polozaj u prostoru',
        ],
        'examination_valid_from' => [
            'datum početka ispitivanja',
            'datum pocetka ispitivanja',
            'datum početka pregleda i ispitivanja',
            'datum pocetka pregleda i ispitivanja',
            'datum završetka ispitivanja',
            'datum zavrsetka ispitivanja',
            'datum završetka pregleda i ispitivanja',
            'datum zavrsetka pregleda i ispitivanja',
            'datum obavljenog ispitivanja',
            'ispitivanje započeto/završeno',
            'ispitivanje zapoceto/zavrseno',
        ],
        'examination_valid_until' => [
            'rok za sljedeći pregled i ispitivanje',
            'rok za sljedeci pregled i ispitivanje',
            'rok za slijedeći pregled i ispitivanje',
            'rok za slijedeci pregled i ispitivanje',
            'rok za sljedeći pregled',
            'rok za sljedeci pregled',
            'ponovno ispitivanje provesti',
            'datum ponovnog ispitivanja',
            'sljedeći pregled',
            'sljedeci pregled',
            'slijedeći pregled',
            'slijedeci pregled',
        ],
        'examined_by' => [
            'ovlaštena osoba',
            'ovlastena osoba',
            'ovlaštena tvrtka',
            'ovlastena tvrtka',
        ],
    ];

    public function extractFromStoredFile(
        string|array|null $storedPath,
        string $disk = 'local'
    ): array {
        $storedPath = $this->normalizeStoredPath($storedPath);

        if (blank($storedPath)) {
            return $this->errorResult('Nije pronađena putanja učitanog dokumenta.');
        }

        $absolutePath = Storage::disk($disk)->path($storedPath);

        if (! file_exists($absolutePath)) {
            $fallbackPath = storage_path('app/livewire-tmp/' . basename($storedPath));

            if (file_exists($fallbackPath)) {
                $absolutePath = $fallbackPath;
            } else {
                return $this->errorResult('Datoteka ne postoji na disku: ' . $storedPath);
            }
        }

        try {
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                [$text, $data, $meta] = $this->extractPdfData($absolutePath);
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $text = $this->normalizeText($this->ocrImageMultiPass($absolutePath));
                $data = $this->extractFieldsFromText($text);
                $meta = ['source' => 'image'];
            } else {
                return $this->errorResult('OCR analiza trenutno podržava PDF, JPG, JPEG, PNG i WEBP dokumente.');
            }

            $data = $this->cleanupData($data);
            $data['ocr_raw_text'] = $text;

            $recognized = collect($data)
                ->only(self::OCR_FIELDS)
                ->filter(fn ($v) => filled($v))
                ->count();

            if ($recognized === 0) {
                return [
                    'success' => false,
                    'data' => [],
                    'recognized_fields' => 0,
                    'meta' => $meta,
                    'text_excerpt' => mb_substr($text, 0, 4000),
                    'message' => 'Dokument je pročitan, ali nije pronađeno nijedno od traženih polja.',
                ];
            }

            return [
                'success' => true,
                'data' => $data,
                'recognized_fields' => $recognized,
                'meta' => $meta,
                'text_excerpt' => mb_substr($text, 0, 4000),
                'message' => null,
            ];
        } finally {
            $this->deleteTemporaryOcrUpload($storedPath, $disk);
        }
    }

    protected function extractPdfData(string $absolutePath): array
    {
        /*
         * 1) Prvo pokušavamo pravi PDF tekst.
         * To je praktički besplatno i odlično radi npr. na QF-27 Zagrebinspekt obrascima.
         */
        $nativeText = $this->normalizeText($this->extractNativePdfText($absolutePath));
        $nativeData = $this->extractFieldsFromText($nativeText);
        $nativeScore = $this->countRecognized($nativeData);

        if (mb_strlen($nativeText) > 300 && $nativeScore >= 7) {
            return [
                $nativeText,
                $nativeData,
                [
                    'source' => 'native_pdf_text',
                    'pages_ocr' => [],
                ],
            ];
        }

        /*
         * 2) Skenirani zapisnici: pregledamo prve 3 fizičke stranice.
         * Svaku stranicu parsiramo zasebno. Tako se kod PDF-a koji sadrži više
         * različitih zapisnika ne pomiješaju podaci različitih strojeva.
         */
        $pages = [];
        $pageData = [];

        for ($page = 1; $page <= 3; $page++) {
            $text = $this->ocrPdfPage($absolutePath, $page);

            if (blank($text)) {
                continue;
            }

            $pages[$page] = $text;
            $pageData[$page] = $this->extractFieldsFromText($text);
        }

        if (empty($pages)) {
            return ['', [], ['source' => 'ocr', 'pages_ocr' => []]];
        }

        /*
         * Biramo stranicu koja najviše izgleda kao glavna tablica zapisnika.
         * E.S.K. tako prirodno bira stranicu 2, dok standardni IS ZNR obrasci
         * uglavnom biraju stranicu 1. Kod jednakog rezultata pobjeđuje ranija stranica.
         */
        $primaryPage = $this->selectPrimaryPage($pageData);
        $data = $pageData[$primaryPage] ?? [];
        $primaryReport = $data['report_number'] ?? null;

        /*
         * Dopunjavamo samo podatke koji pripadaju ISTOM zapisniku.
         * Ako druga stranica ima drugi broj zapisnika, ne smije popuniti identitet stroja.
         */
        foreach ($pageData as $page => $candidate) {
            if ($page === $primaryPage) {
                continue;
            }

            $candidateReport = $candidate['report_number'] ?? null;
            $sameReport = blank($primaryReport)
                || blank($candidateReport)
                || $this->sameComparable($primaryReport, $candidateReport);

            foreach (self::OCR_FIELDS as $field) {
                if (filled($data[$field] ?? null)) {
                    continue;
                }

                if (! filled($candidate[$field] ?? null)) {
                    continue;
                }

                if (! $sameReport && $this->isIdentityField($field)) {
                    continue;
                }

                $data[$field] = $candidate[$field];
            }
        }

        /*
         * E.S.K. tip evidencijskog kartona često ima samo tablicu rokova.
         * Ako eksplicitna labela roka nije pronađena, iz takve tablice uzimamo
         * najnoviji datum ponovnog pregleda.
         */
        if (blank($data['examination_valid_until'] ?? null)) {
            foreach ($pages as $text) {
                $fallback = $this->extractExpiryFromHistoricalTable($text);

                if (filled($fallback)) {
                    $data['examination_valid_until'] = $fallback;
                    break;
                }
            }
        }

        /*
         * 3) Samo ako rok još nije pronađen, OCR-a se stranica 4.
         * To pokriva KONTROL BIRO / ZAING varijante gdje je rok na stranici 4,
         * bez nepotrebnog čitanja ostatka zapisnika.
         */
        if (blank($data['examination_valid_until'] ?? null)) {
            $page4 = $this->ocrPdfPage($absolutePath, 4);

            if (filled($page4)) {
                $pages[4] = $page4;
                $page4Data = $this->extractFieldsFromText($page4);

                if (filled($page4Data['examination_valid_until'] ?? null)) {
                    $data['examination_valid_until'] = $page4Data['examination_valid_until'];
                } else {
                    $fallback = $this->extractExpiryFromHistoricalTable($page4)
                        ?: $this->extractAnyExplicitExpiry($page4);

                    if (filled($fallback)) {
                        $data['examination_valid_until'] = $fallback;
                    }
                }
            }
        }

        /*
         * Ako native tekst postoji, može popuniti samo ono što OCR nije pronašao.
         * Nikada ne pregazi OCR vrijednost glavne stranice.
         */
        if (mb_strlen($nativeText) > 100) {
            foreach (self::OCR_FIELDS as $field) {
                if (blank($data[$field] ?? null) && filled($nativeData[$field] ?? null)) {
                    $data[$field] = $nativeData[$field];
                }
            }
        }

        return [
            $this->joinPages($pages),
            $data,
            [
                'source' => 'ocr',
                'primary_page' => $primaryPage,
                'pages_ocr' => array_keys($pages),
            ],
        ];
    }

    protected function selectPrimaryPage(array $pageData): int
    {
        $bestPage = (int) array_key_first($pageData);
        $bestScore = -1;

        foreach ($pageData as $page => $data) {
            $score = 0;

            foreach ([
                'name' => 4,
                'manufacturer' => 3,
                'factory_number' => 3,
                'inventory_number' => 1,
                'report_number' => 3,
                'location' => 2,
                'examination_valid_from' => 2,
                'examined_by' => 2,
            ] as $field => $weight) {
                if (filled($data[$field] ?? null)) {
                    $score += $weight;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPage = (int) $page;
            }
        }

        return $bestPage;
    }

    protected function isIdentityField(string $field): bool
    {
        return in_array($field, [
            'name',
            'manufacturer',
            'factory_number',
            'inventory_number',
            'report_number',
            'location',
            'examined_by',
        ], true);
    }

    protected function extractFieldsFromText(string $text): array
    {
        $text = $this->normalizeText($text);

        /*
         * Za identitet stroja prvo izdvajamo dio dokumenta koji stvarno opisuje
         * radnu opremu. To sprječava da generička labela "Naziv" pokupi naziv
         * korisnika/poslodavca umjesto naziva stroja.
         */
        $equipmentText = $this->extractEquipmentSection($text);

        $data = [
            'name' => $this->extractEquipmentName($equipmentText),
            'manufacturer' => $this->extractManufacturer($equipmentText),
            'factory_number' => $this->extractFactoryNumber($equipmentText),
            'inventory_number' => $this->extractInventoryNumber($equipmentText),
            'report_number' => $this->extractReportNumber($text),
            'location' => $this->extractLocation($text, $equipmentText),
            'examination_valid_from' => $this->extractStartDate($text),
            'examination_valid_until' => $this->extractAnyExplicitExpiry($text),
            'examined_by' => $this->extractAuthorizedCompany($text),
        ];


        /*
         * OCR prazne inventarne ćelije ponekad pokupi broj podtočke iz
         * sljedećeg retka, npr. "26-2 Lokacija ispitivanja ...". Takav broj
         * nije inventarski broj nego oznaka retka/sekcije, pa ga pretvaramo u
         * praznu vrijednost; završni cleanup će tada zapisati "-".
         */
        if (
            filled($data['inventory_number'] ?? null)
            && $this->isLikelySectionMarkerInventory(
                $text,
                (string) $data['inventory_number']
            )
        ) {
            $data['inventory_number'] = null;
        }

        /*
         * Ako obrazac ima polje Tvornički/serijski broj ili Inventarski broj,
         * ali je ćelija prazna ili označena crticom, u aplikaciji spremamo "-".
         * Time razlikujemo "nema dodijeljenog broja" od "OCR nije našao polje".
         */
        if (blank($data['factory_number']) && $this->hasAnyLabel($equipmentText, self::FIELD_ALIASES['factory_number'])) {
            $data['factory_number'] = '-';
        }

        if (blank($data['inventory_number']) && $this->hasAnyLabel($equipmentText, self::FIELD_ALIASES['inventory_number'])) {
            $data['inventory_number'] = '-';
        }

        return $data;
    }

    /**
     * Izdvaja dio teksta u kojem se opisuju podaci o samoj radnoj opremi.
     * Radi s novim IS ZNR zapisnicima i sa starijim Zagrebinspekt obrascima.
     */
    protected function extractEquipmentSection(string $text): string
    {
        $markers = [
            'ispitivana radna oprema',
            'podaci o radnoj opremi',
            'podaci kojima se pobliže određuje radna oprema',
            'podaci kojima se poblize odreduje radna oprema',
            'naziv stroja i uređaja',
            'naziv stroja i uredaja',
        ];

        $lines = $this->lines($text);
        $start = null;

        foreach ($lines as $i => $line) {
            $norm = $this->normalizeComparableText($line);

            foreach ($markers as $marker) {
                if (str_contains($norm, $this->normalizeComparableText($marker))) {
                    $start = $i;
                    break 2;
                }
            }
        }

        if ($start === null) {
            return $text;
        }

        $selected = [];

        for ($i = $start; $i < count($lines); $i++) {
            $line = $lines[$i];
            $norm = $this->normalizeComparableText($line);

            if ($i > $start + 2 && preg_match(
                '/^(?:1\.2|2\.|3\.|4\.|5\.|6\.|7\.|8\.|9\.|10\.|11\.|12\.|13\.|14\.|15\.|16\.|17\.|18\.)\b/u',
                $norm
            )) {
                break;
            }

            if ($i > $start + 2 && preg_match(
                '/^(?:naziv i sjediste|naziv i sjedište|ovlastena osoba koja obavlja|ovlaštena osoba koja obavlja|propisi kojima|nazivi propisa)/iu',
                $line
            )) {
                break;
            }

            $selected[] = $line;

            if (count($selected) >= 35) {
                break;
            }
        }

        return trim(implode("\n", $selected)) ?: $text;
    }

    protected function extractEquipmentName(string $text): ?string
    {
        $specific = [
            'naziv radne opreme',
            'naziv stroja i uređaja',
            'naziv stroja i uredaja',
            'naziv stroja',
            'naziv opreme',
        ];

        $value = $this->extractLabeledValue($text, $specific, 'name');

        if (filled($value)) {
            return $value;
        }

        return $this->extractLabeledValue($text, ['naziv'], 'name');
    }

    protected function extractManufacturer(string $text): ?string
    {
        $value = $this->extractLabeledValue($text, self::FIELD_ALIASES['manufacturer'], 'manufacturer');

        if (filled($value)) {
            return $value;
        }

        return $this->extractValueBetweenLabels(
            $text,
            self::FIELD_ALIASES['name'],
            self::FIELD_ALIASES['factory_number'],
            'manufacturer'
        );
    }

    protected function extractFactoryNumber(string $text): ?string
    {
        $patterns = [
            '/(?:tvorni[čc]ki\s*\/\s*serijski|tvorni[čc]ki|serijski)\s+broj\s*[:|]?\s*([^\r\n]*)/iu',
            '/(?:tv\.?\s*broj)\s*[:|]?\s*([^\r\n]*)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $candidate = $this->sanitizeFieldValue('factory_number', $m[1] ?? '');

                if ($candidate === '-') {
                    return '-';
                }

                if ($this->isAcceptableValue('factory_number', $candidate)) {
                    return $candidate;
                }
            }
        }

        $value = $this->extractLabeledValue($text, self::FIELD_ALIASES['factory_number'], 'factory_number');

        if (filled($value)) {
            return $value;
        }

        return null;
    }

    protected function extractInventoryNumber(string $text): ?string
    {
        $patterns = [
            '/(?:inventarski|inventarni)(?:\s*\/\s*interni)?\s+broj\s*[:|]?\s*([^\r\n]*)/iu',
            '/interni\s+broj\s*[:|]?\s*([^\r\n]*)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $candidate = $this->sanitizeFieldValue('inventory_number', $m[1] ?? '');

                if ($candidate === '-') {
                    return '-';
                }

                if ($this->isAcceptableValue('inventory_number', $candidate)) {
                    return $candidate;
                }
            }
        }

        $value = $this->extractLabeledValue($text, self::FIELD_ALIASES['inventory_number'], 'inventory_number');

        if (filled($value)) {
            return $value;
        }

        return null;
    }

    protected function extractLocation(string $text, string $equipmentText): ?string
    {
        foreach ([
            'lokacija ispitivanja',
            'mjesto ispitivanja',
        ] as $alias) {
            $value = $this->extractLabeledValue($text, [$alias], 'location');

            if (filled($value)) {
                return $value;
            }
        }

        foreach ([
            'položaj u radnom prostoru',
            'polozaj u radnom prostoru',
            'položaj u prostoru',
            'polozaj u prostoru',
        ] as $alias) {
            $value = $this->extractLabeledValue($equipmentText, [$alias], 'location');

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    protected function hasAnyLabel(string $text, array $aliases): bool
    {
        foreach ($this->lines($text) as $line) {
            foreach ($aliases as $alias) {
                if ($this->lineContainsLabel($line, $alias)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function extractLabeledValue(string $text, array $aliases, string $field): ?string
    {
        $lines = $this->lines($text);

        foreach ($lines as $index => $line) {
            $lineNorm = $this->normalizeComparableText($line);

            foreach ($aliases as $alias) {
                $aliasNorm = $this->normalizeComparableText($alias);

                if ($aliasNorm === '') {
                    continue;
                }

                /*
                 * 1) Labela i vrijednost u istom retku.
                 */
                if (str_starts_with($lineNorm, $aliasNorm)) {
                    $value = $this->removeLabelFromLine($line, $alias);
                    $value = $this->sanitizeFieldValue($field, $value);

                    if ($this->isAcceptableValue($field, $value)) {
                        return $value;
                    }
                }

                /*
                 * 2) OCR je labelu stavio samu u red, a vrijednost ispod nje (PSM 11).
                 */
                if ($this->fuzzyLabelMatch($line, $alias)) {
                    for ($i = $index + 1; $i <= min($index + 3, count($lines) - 1); $i++) {
                        $candidate = $this->sanitizeFieldValue($field, $lines[$i]);

                        if ($candidate === null) {
                            continue;
                        }

                        if ($this->looksLikeAnyLabel($candidate)) {
                            break;
                        }

                        if ($this->isAcceptableValue($field, $candidate)) {
                            return $candidate;
                        }
                    }
                }
            }
        }

        return null;
    }

    protected function extractValueBetweenLabels(
        string $text,
        array $startAliases,
        array $endAliases,
        string $field
    ): ?string {
        $lines = $this->lines($text);

        foreach ($lines as $i => $line) {
            $matchesStart = false;

            foreach ($startAliases as $alias) {
                if ($this->lineContainsLabel($line, $alias)) {
                    $matchesStart = true;
                    break;
                }
            }

            if (! $matchesStart) {
                continue;
            }

            for ($j = $i + 1; $j <= min($i + 4, count($lines) - 1); $j++) {
                $candidateLine = $lines[$j];

                foreach ($endAliases as $endAlias) {
                    if ($this->lineContainsLabel($candidateLine, $endAlias)) {
                        return null;
                    }
                }

                if ($this->looksLikeAnyLabel($candidateLine)) {
                    continue;
                }

                $candidate = $this->sanitizeFieldValue($field, $candidateLine);

                if ($this->isAcceptableValue($field, $candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    protected function extractReportNumber(string $text): ?string
    {
        $candidates = [];

        /*
        * 1) Najpouzdaniji kandidat:
        * vrijednost neposredno uz poznatu labelu broja zapisnika.
        */
        foreach (self::FIELD_ALIASES['report_number'] as $alias) {
            $value = $this->extractLabeledValue($text, [$alias], 'report_number');

            if (filled($value)) {
                $value = $this->cleanReportNumber($value);

                if (filled($value)) {
                    $candidates[] = [
                        'value' => $value,
                        'score' => $this->scoreReportNumberCandidate($value, true),
                    ];
                }
            }
        }

        /*
        * 2) Standardni formati koji se mogu pronaći i bez dobro očitane labele.
        */
        $patterns = [
            // npr. RO-12/2026/1234 ili RO 12/2026/1234
            '/\bRO[-\s]*\d{1,3}\/\d{4}\/\d{3,}(?:\/[A-Za-z0-9-]+)?\b/u',

            // npr. 30/01-1/46-23
            '/\b\d{1,3}\/\d{2,4}(?:-\d+)?\/\d{1,3}-\d{2,4}\b/u',

            // npr. 30/01-1/46-23 – uža starija varijanta
            '/\b\d{1,3}\/\d{2}-\d\/\d{1,3}-\d{2}\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[0] ?? [] as $match) {
                    $value = $this->cleanReportNumber($match);

                    if (filled($value)) {
                        $candidates[] = [
                            'value' => $value,
                            'score' => $this->scoreReportNumberCandidate($value, false) + 40,
                        ];
                    }
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        /*
        * Isti kandidat može biti pronađen više puta.
        * Zadržavamo njegov najbolji rezultat.
        */
        $unique = [];

        foreach ($candidates as $candidate) {
            $key = $this->normalizeReportNumberForComparison($candidate['value']);

            if ($key === '') {
                continue;
            }

            if (
                ! isset($unique[$key])
                || $candidate['score'] > $unique[$key]['score']
            ) {
                $unique[$key] = $candidate;
            }
        }

        if ($unique === []) {
            return null;
        }

        usort($unique, function (array $a, array $b): int {
            return $b['score'] <=> $a['score'];
        });

        $best = $unique[0] ?? null;

        /*
        * Ako je kandidat očito preslab, radije ne popunjavamo broj zapisnika
        * nego da u bazu ode OCR smeće poput "e0", "60", "Broj" i sl.
        */
        if (! $best || ($best['score'] ?? 0) < 25) {
            return null;
        }

        return $best['value'];
    }
    protected function scoreReportNumberCandidate(
        string $value,
        bool $foundNearLabel = false
    ): int {
        $value = $this->cleanReportNumber($value);

        if (blank($value)) {
            return -100;
        }

        $score = 0;

        if ($foundNearLabel) {
            $score += 25;
        }

        $length = mb_strlen($value);

        if ($length >= 6) {
            $score += 10;
        } elseif ($length <= 3) {
            $score -= 40;
        }

        /*
        * Brojevi zapisnika u pravilu sadrže brojke.
        */
        if (preg_match('/\d/u', $value)) {
            $score += 10;
        } else {
            $score -= 30;
        }

        /*
        * Vrlo jak signal: separator /.
        * Većina formata koje sada imamo koristi ga.
        */
        if (str_contains($value, '/')) {
            $score += 25;
        }

        /*
        * Dodatni signal za složene oznake.
        */
        if (str_contains($value, '-')) {
            $score += 8;
        }

        /*
        * RO format.
        */
        if (preg_match('/^RO[-\s]*\d/iu', $value)) {
            $score += 30;
        }

        /*
        * Jaki poznati formati.
        */
        if (preg_match(
            '/^RO[-\s]*\d{1,3}\/\d{4}\/\d{3,}(?:\/[A-Za-z0-9-]+)?$/iu',
            $value
        )) {
            $score += 50;
        }

        if (preg_match(
            '/^\d{1,3}\/\d{2,4}(?:-\d+)?\/\d{1,3}-\d{2,4}$/u',
            $value
        )) {
            $score += 50;
        }

        /*
        * OCR smeće / očito nevjerojatni kandidati.
        */
        if (preg_match('/^[A-Za-z]{1,3}\d?$/u', $value)) {
            $score -= 50;
        }

        if (preg_match('/^\d{1,3}$/u', $value)) {
            $score -= 35;
        }

        if (preg_match(
            '/^(?:broj|zapisnik|datum|stranica|page|br)$/iu',
            $value
        )) {
            $score -= 100;
        }

        return $score;
    }

    protected function normalizeReportNumberForComparison(string $value): string
    {
        $value = mb_strtoupper($value, 'UTF-8');

        /*
        * Za usporedbu ignoriramo razmake, ali ne diramo / i -
        * jer su oni važan dio broja zapisnika.
        */
        $value = preg_replace('/\s+/u', '', $value);

        return trim((string) $value);
    }

    protected function extractStartDate(string $text): ?string
    {
        foreach (self::FIELD_ALIASES['examination_valid_from'] as $alias) {
            $value = $this->extractDateNearLabel($text, $alias);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    protected function extractAnyExplicitExpiry(string $text): ?string
    {
        foreach (self::FIELD_ALIASES['examination_valid_until'] as $alias) {
            $value = $this->extractDateNearLabel($text, $alias);

            if (filled($value)) {
                return $value;
            }
        }

        if (preg_match('/-\s*prije\s*:\s*(\d{1,2}\.\d{1,2}\.\d{4}\.?)/iu', $text, $m)) {
            return $this->normalizeDate($m[1]);
        }

        return null;
    }

    protected function extractDateNearLabel(string $text, string $alias): ?string
    {
        $lines = $this->lines($text);

        foreach ($lines as $i => $line) {
            if (! $this->lineContainsLabel($line, $alias)) {
                continue;
            }

            if (preg_match('/(\d{1,2}\.\d{1,2}\.\d{4}\.?)/u', $line, $m)) {
                return $this->normalizeDate($m[1]);
            }

            if (preg_match('/(\d{4}-\d{2}-\d{2})/u', $line, $m)) {
                return $this->normalizeDate($m[1]);
            }

            for ($j = $i + 1; $j <= min($i + 2, count($lines) - 1); $j++) {
                if ($this->looksLikeAnyLabel($lines[$j])) {
                    break;
                }

                if (preg_match('/(\d{1,2}\.\d{1,2}\.\d{4}\.?)/u', $lines[$j], $m)) {
                    return $this->normalizeDate($m[1]);
                }
            }
        }

        return null;
    }

    protected function extractExpiryFromHistoricalTable(string $text): ?string
    {
        if (! preg_match('/rokovi?\s+za\s+ponovni\s+pregled|datum\s+ponovnog\s+ispitivanja/iu', $text)) {
            return null;
        }

        $dates = $this->allDates($text);

        if (count($dates) === 0) {
            return null;
        }

        usort($dates, fn (string $a, string $b) => strcmp($a, $b));

        return end($dates) ?: null;
    }

    protected function extractAuthorizedCompany(string $text): ?string
    {
        /*
         * Ako se u dokumentu jasno pojavljuje naziv poznate ovlaštene kuće,
         * koristimo kanonski naziv. Time se ne može dogoditi da polje
         * "Ispitao" pokupi korisnika/naručitelja (npr. DW Reusables).
         * Ovo NIJE parser po kućama – služi samo normalizaciji naziva tvrtke.
         */
        foreach (self::INSPECTION_COMPANIES as $pattern => $canonicalName) {
            if (preg_match($pattern, $text)) {
                return $canonicalName;
            }
        }

        $lines = $this->lines($text);
        $excluded = $this->extractExcludedCompanies($lines);
        $candidates = [];

        /*
         * Tvrtku koja je obavila ispitivanje biramo po KONTEKSTU, a ne po tome
         * koja se tvrtka prva pojavljuje u dokumentu. To je ključno jer se na
         * zapisniku prije ovlaštene kuće često pojavljuje korisnik/naručitelj.
         */
        foreach ($lines as $i => $line) {
            $lineNorm = $this->normalizeComparableText($line);

            $contextStart = max(0, $i - 4);
            $contextEnd = min(count($lines) - 1, $i + 4);
            $context = $this->normalizeComparableText(
                implode(' ', array_slice($lines, $contextStart, $contextEnd - $contextStart + 1))
            );

            $variants = [$line];
            if (isset($lines[$i + 1])) {
                $variants[] = $line . ' ' . $lines[$i + 1];
            }

            foreach ($variants as $variant) {
                $company = $this->extractCompanyName($variant);
                if (blank($company)) {
                    continue;
                }

                $company = $this->normalizeCompanyLegalForm($company);
                $normCompany = $this->normalizeComparableText($company);

                if ($this->companyIsExcluded($normCompany, $excluded)) {
                    continue;
                }

                $score = 0;

                // Najjači pozitivni kontekst: ovlaštena osoba / ovlaštena tvrtka.
                if (preg_match('/ovla[šs]ten(?:a|e|oj|u)?\s+(?:osoba|tvrtka|ku[cć]a)/iu', $context)) {
                    $score += 120;
                }

                // Odjeljak o ovlaštenoj osobi, rješenju i ovlaštenju.
                if (preg_match('/ovla[šs]ten|rje[šs]enje\s+o\s+ovla[šs]tenju|broj\s+rje[šs]enja/iu', $context)) {
                    $score += 70;
                }

                // Ako je tvrtka u istoj liniji s labelom, to je vrlo pouzdano.
                if (preg_match('/ovla[šs]ten(?:a|e)?\s+(?:osoba|tvrtka)/iu', $lineNorm)) {
                    $score += 80;
                }

                // Zaglavlje ovlaštene kuće je čest format starijih zapisnika.
                if ($i <= 20) {
                    $score += 12;
                }

                // Negativni kontekst: korisnik, naručitelj, poslodavac, vlasnik.
                if (preg_match('/naru[čc]itelj|korisnik|poslodavac|vlasnik|sjedi[šs]te\s+korisnika/iu', $context)) {
                    $score -= 140;
                }

                // Sama riječ "DW REUSABLES" i sličan korisnik ne smije pobijediti
                // ako je dokument u dijelu korisnika/naručitelja.
                if ($this->looksLikeUserCompanyContext($context)) {
                    $score -= 100;
                }

                $candidates[] = [
                    'company' => $company,
                    'score' => $score,
                    'index' => $i,
                ];
            }
        }

        if ($candidates !== []) {
            usort($candidates, function (array $a, array $b): int {
                if ($a['score'] === $b['score']) {
                    return $a['index'] <=> $b['index'];
                }

                return $b['score'] <=> $a['score'];
            });

            // Vraćamo samo smislen kandidat. Ako su svi kandidati negativni,
            // radije ostavimo prazno nego da upišemo korisnika opreme.
            if (($candidates[0]['score'] ?? -999) >= 0) {
                return $this->normalizeCompanyLegalForm($candidates[0]['company']);
            }
        }

        return null;
    }

    protected function looksLikeUserCompanyContext(string $context): bool
    {
        return (bool) preg_match(
            '/(?:naru[čc]itelj\s+ispitivanja|korisnik\s+radne\s+opreme|poslodavac\s*[–-]?\s*korisnik|naziv\s+i\s+sjedi[šs]te\s+(?:i\s+oib\s+)?korisnika)/iu',
            $context
        );
    }

    protected function extractExcludedCompanies(array $lines): array
    {
        $excluded = [];

        foreach ($lines as $i => $line) {
            $norm = $this->normalizeComparableText($line);

            $looksLikeUserSection = preg_match(
                '/(?:naru[čc]itelj\s+ispitivanja|korisnik\s+radne\s+opreme|poslodavac\s*[–-]?\s*korisnik|naziv\s+i\s+sjedi[šs]te\s+korisnika)/iu',
                $line
            );

            if (! $looksLikeUserSection && ! str_contains($norm, 'naziv i sjediste i oib korisnika')) {
                continue;
            }

            for ($j = $i; $j <= min($i + 5, count($lines) - 1); $j++) {
                foreach ([$lines[$j], $lines[$j] . ' ' . ($lines[$j + 1] ?? '')] as $candidate) {
                    $company = $this->extractCompanyName($candidate);

                    if (filled($company)) {
                        $excluded[] = $this->normalizeComparableText($company);
                    }
                }
            }
        }

        return array_values(array_unique($excluded));
    }

    protected function companyIsExcluded(string $company, array $excluded): bool
    {
        foreach ($excluded as $blocked) {
            if ($company === $blocked || str_contains($company, $blocked) || str_contains($blocked, $company)) {
                return true;
            }
        }

        return false;
    }

    protected function extractCompanyName(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = $this->cleanValue($value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = preg_replace("/[„“”«»\"']+/u", '', (string) $value);
        $value = trim((string) $value, " \t\n\r\0\x0B:;|,-");

        if ($value === '') {
            return null;
        }

        /*
         * OCR ponekad ostavi smeće prije naziva (npr. "me ... MEĐIMURJE ZAING d.o.o.").
         * Ako postoji jasno prepoznatljiv naziv velikim slovima + pravni oblik,
         * uzimamo samo taj dio.
         */
        if (preg_match(
            '/([A-ZČĆŽŠĐ][A-ZČĆŽŠĐ0-9 .&\-]{2,80}?\s+(?:d\.?\s*o\.?\s*o\.?|d\.?\s*d\.?|j\.?\s*d\.?\s*o\.?\s*o\.?))/u',
            $value,
            $m
        )) {
            return $this->normalizeCompanyLegalForm(trim($m[1]));
        }

        /*
         * Standardni oblik, tolerantan na mješavinu velikih/malih slova.
         * Krećemo od zadnjeg smislenog segmenta prije pravnog oblika kako OCR prefiks
         * ne bi završio u polju "Ispitao".
         */
        if (preg_match_all(
            '/([\p{L}0-9][\p{L}0-9 .,&()\-]{1,100}?\s+(?:d\.?\s*o\.?\s*o\.?|d\.?\s*d\.?|j\.?\s*d\.?\s*o\.?\s*o\.?))/iu',
            $value,
            $matches
        )) {
            $candidate = end($matches[1]);

            if (filled($candidate)) {
                return $this->normalizeCompanyLegalForm(trim((string) $candidate));
            }
        }

        /*
         * Logo/headline varijanta: naziv u jednom retku, a "d.o.o. za ..." u sljedećem.
         * Primjerice "ZAGREBINSPEKT d.o.o. za kontrolu i inženjering".
         */
        if (preg_match('/^(.{2,80}?)\s+(d\.?\s*o\.?\s*o\.?|d\.?\s*d\.?|j\.?\s*d\.?\s*o\.?\s*o\.?)\b/iu', $value, $m)) {
            $name = trim($m[1] . ' ' . $m[2]);

            if (! preg_match('/^(?:naziv|sjediste|sjedište|oib)\b/iu', $name)) {
                return $this->normalizeCompanyLegalForm($name);
            }
        }

        return null;
    }

    protected function normalizeCompanyLegalForm(string $value): string
    {
        $value = preg_replace('/\bd\.?\s*o\.?\s*o\.?/iu', 'd.o.o.', $value);
        $value = preg_replace('/\bj\.?\s*d\.?\s*o\.?\s*o\.?/iu', 'j.d.o.o.', (string) $value);
        $value = preg_replace('/\bd\.?\s*d\.?/iu', 'd.d.', (string) $value);
        $value = preg_replace('/\s+/u', ' ', (string) $value);

        // Ukloni navodnike i sav višak interpunkcije nakon pravnog oblika.
        $value = preg_replace('/[„“”«»"\']+/u', '', (string) $value);
        $value = preg_replace('/(d\.o\.o\.|j\.d\.o\.o\.|d\.d\.)[\s.,;:]+$/iu', '$1', (string) $value);
        $value = preg_replace('/d\.o\.o\.{2,}$/iu', 'd.o.o.', (string) $value);
        $value = preg_replace('/j\.d\.o\.o\.{2,}$/iu', 'j.d.o.o.', (string) $value);

        $value = trim((string) $value, " \t\n\r\0\x0B:;|,-");

        // Kanonski zapis poznatih ovlaštenih kuća – bez duplih točaka/OCR smeća.
        foreach (self::INSPECTION_COMPANIES as $pattern => $canonicalName) {
            if (preg_match($pattern, $value)) {
                return $canonicalName;
            }
        }

        return $value;
    }

    protected function sanitizeFieldValue(string $field, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = $this->cleanValue($value);

        if ($value === '') {
            return null;
        }

        return match ($field) {
            'name' => $this->cleanName($value),
            'manufacturer' => $this->cleanManufacturer($value),
            'factory_number' => $this->cleanFactoryNumber($value),
            'inventory_number' => $this->cleanInventory($value),
            'report_number' => $this->cleanReportNumber($value),
            'location' => $this->cleanLocation($value),
            'examined_by' => $this->extractCompanyName($value) ?: $value,
            default => $value,
        };
    }

    protected function isAcceptableValue(string $field, ?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        if ($this->looksLikeAnyLabel($value)) {
            return false;
        }

        return match ($field) {
            'name' => mb_strlen($value) >= 3
                && ! preg_match('/sjedi[šs]te|korisnika\s+radne|ovla[šs]ten|datum|broj\s+zapisnika/iu', $value),
            'manufacturer' => mb_strlen($value) >= 2
                && ! preg_match('/datum|lokacija|ovla[šs]ten|broj\s+zapisnika/iu', $value),
            'factory_number' => mb_strlen($value) >= 2
                && mb_strlen($value) <= 80
                && ! preg_match('/datum|lokacija|ovla[šs]ten/iu', $value),
            'inventory_number' => mb_strlen($value) <= 80
                && ! preg_match('/^[\p{L}]$/u', $value)
                && ! preg_match('/\b(?:d\.?\s*o\.?\s*o\.?|j\.?\s*d\.?\s*o\.?\s*o\.?|d\.?\s*d\.?)\b/iu', $value)
                && ! preg_match('/\bOIB\b/iu', $value),
            'report_number' => mb_strlen($value) >= 4
                && mb_strlen($value) <= 100
                && preg_match('/\d/u', $value)
                && ! preg_match('/^(?:broj|zapisnik|datum|stranica|page|br)$/iu', $value),
            'location' => mb_strlen($value) >= 3
                && ! preg_match('/datum|ovla[šs]tena\s+osoba|broj\s+zapisnika/iu', $value),
            default => true,
        };
    }

    protected function cleanName(string $value): ?string
    {
        $value = preg_replace('/^\s*(?:naziv|naive|nalive|naiv)\s*[:|.\-]?\s*/iu', '', $value);
        $value = trim((string) $value, " \t\n\r\0\x0B:;|,-");

        return $value !== '' ? $value : null;
    }

    protected function cleanManufacturer(string $value): ?string
    {
        $value = preg_replace('/^\s*proizvo\S*\s*[:|.\-]?\s*/iu', '', $value);
        $value = trim((string) $value, " \t\n\r\0\x0B:;|,-");

        return $value !== '' ? $value : null;
    }

    protected function cleanFactoryNumber(string $value): ?string
    {
        $value = preg_replace(
            '/^\s*(?:tvorni[čc]ki(?:\s*\/\s*serijski)?|serijski|tv\.?)\s+broj\s*[:|.\-]?\s*/iu',
            '',
            $value
        );

        return $this->cleanIdentifier((string) $value);
    }

    protected function cleanIdentifier(string $value): ?string
    {
        $value = preg_replace('/^[\\|Il1]+\s*(?=\d)/u', '', $value);
        $value = trim((string) $value, " \t\n\r\0\x0B:;|");

        /*
         * OCR često vrati "- 11887" kada je crta tablice zalijepljena uz broj.
         * Ako iza crtice stvarno postoji identifikator, crtu uklanjamo.
         */
        $value = preg_replace('/^[\-–—_]\s*(?=[A-Z0-9])/iu', '', (string) $value);
        $value = trim((string) $value);

        if ($this->isDashLike($value)) {
            return '-';
        }

        return $value !== '' ? $value : null;
    }

    protected function cleanInventory(string $value): ?string
    {
        $value = preg_replace(
            '/^\s*(?:inventarski|inventarni)(?:\s*\/\s*interni)?\s+broj\s*[:|.\-]?\s*/iu',
            '',
            $value
        );
        $value = trim((string) $value, " \t\n\r\0\x0B:;|");

        // OCR prazne ćelije često pokupi sadržaj sljedećeg retka. Takve
        // administrativne/tvrtkine vrijednosti nisu inventarski broj.
        if ($this->looksLikeAdministrativeOrCompanyValue($value)) {
            return null;
        }

        if ($this->isDashLike($value)) {
            return '-';
        }

        /*
         * Prazna ćelija se pri OCR-u zna pretvoriti u jedan znak (npr. "r", "a", "I").
         * Takav rezultat nije valjani inventarski broj i kasnije će biti pretvoren u "-".
         */
        if (preg_match('/^[\p{L}]$/u', $value)) {
            return null;
        }

        /*
         * Ne prihvaćamo tvrtku, OIB niti sljedeću labelu kao inventarski broj.
         */
        if (preg_match('/\b(?:d\.?\s*o\.?\s*o\.?|j\.?\s*d\.?\s*o\.?\s*o\.?|d\.?\s*d\.?)\b/iu', $value)) {
            return null;
        }

        if (preg_match('/\bOIB\b/iu', $value)) {
            return null;
        }

        if ($value !== '' && $this->looksLikeAnyLabel($value)) {
            return null;
        }

        if (preg_match('/^(?:ovla[šs]tena\s+osoba|lokacija\s+ispitivanja|namjena|godina\s+proizvodnje|naziv\s*,?\s*oib)$/iu', $value)) {
            return null;
        }

        /*
         * Ako je prazna ćelija, OCR ponekad zalijepi sadržaj sljedećeg retka
         * (npr. naziv ovlaštene kuće, rješenje o ovlaštenju ili OIB).
         * To nikad nije inventarski broj.
         */
        if (preg_match('/(?:rje[šs]enje\s+o\s+ovla[šs]tenju|klasa\s*:|urbroj\s*:|ovla[šs]ten|međimurje|medimurje|zagrebinspekt|kontrol\s*biro)/iu', $value)) {
            return null;
        }

        if (preg_match('/\(\s*\d{6,}\s*\)/u', $value)) {
            return null;
        }

        return $value !== '' ? $value : null;
    }

    protected function isLikelySectionMarkerInventory(string $text, string $value): bool
    {
        $value = trim($value);

        // Najčešći OCR oblik broja podtočke: 26-2, 2-6, 3-1 i sl.
        if (! preg_match('/^\d{1,2}\s*[-–—]\s*\d{1,2}$/u', $value)) {
            return false;
        }

        $escaped = preg_quote($value, '/');
        $escaped = str_replace('\-', '[-–—]', $escaped);

        // Ako isti broj stoji neposredno uz neku od sljedećih labela,
        // sigurno je oznaka retka/sekcije, a ne inventarski broj.
        return (bool) preg_match(
            '/(?:^|\R|\s)' . $escaped
            . '\s*(?:lokacija\s+ispitivanja|ovla[šs]tena\s+osoba|'
            . 'datum\s+(?:po[čc]etka|zavr[šs]etka)|broj\s+zapisnika|'
            . 'proizvo[a-zčćžšđ]*|tvorni[čc]ki|inventar)/iu',
            $text
        );
    }

    protected function looksLikeAdministrativeOrCompanyValue(string $value): bool
    {
        $norm = $this->normalizeComparableText($value);

        if ($norm === '') {
            return false;
        }

        if (preg_match('/(?:d\.?\s*o\.?\s*o\.?|j\.?\s*d\.?\s*o\.?\s*o\.?|d\.?\s*d\.?)/iu', $value)) {
            return true;
        }

        if (preg_match('/(?:oib|ovla[šs]ten|rje[šs]enje|klasa|urbroj|lokacija\s+ispitivanja|datum\s+(?:po[čc]etka|zavr[šs]etka)|broj\s+zapisnika)/iu', $value)) {
            return true;
        }

        // Tvrtka + dugačak OIB u zagradi ili samostalno nije inventarski broj.
        if (preg_match('/\(?\d{8,11}\)?/u', $value) && preg_match('/[\p{L}]{3,}/u', $value)) {
            return true;
        }

        return false;
    }

    protected function cleanLocation(string $value): ?string
    {
        $value = preg_replace('/^\s*(?:lokacija\s+ispitivanja|mjesto\s+ispitivanja|polo[žz]aj\s+u\s+(?:radnom\s+)?prostoru)\s*[:|.\-]?\s*/iu', '', $value);
        $value = preg_replace('/\s+/u', ' ', (string) $value);
        $value = trim((string) $value, " \t\n\r\0\x0B:;|");

        if ($value === '') {
            return null;
        }

        /*
         * Za pogone spremamo kratku operativnu lokaciju, bez naziva/adrese
         * korisnika. Primjeri:
         *   "DW REUSABLES d.o.o., Kupljensko 75b, Vojnić - POGON 3" -> "POGON 3"
         *   "DW REUSABLES ... POGON KUPLJENSKO - POGON B" -> "POGON KUPLJENSKO - POGON B"
         */
        if (preg_match('/\b(POGON(?:\s+[A-ZČĆŽŠĐ0-9]+)*(?:\s*[-–—]\s*POGON\s+[A-ZČĆŽŠĐ0-9]+)?)\b/iu', $value, $m)) {
            return trim($m[1]);
        }

        // Ako je u tekstu riječ "POGONI" uz adresu, ne vraćamo naziv tvrtke.
        if (preg_match('/\bPOGONI\b/iu', $value)) {
            $value = preg_replace('/^.*?\bPOGONI\b\s*[,;:\-]?\s*/iu', '', $value);
            $value = trim((string) $value);
        }

        // Ako je vrijednost samo puna adresa DW REUSABLES bez oznake pogona,
        // ona nije korisna kao lokacija stroja; radije ostavljamo original samo
        // kod drugih korisnika/objekata.
        if (preg_match('/^DW\s+REUSABLES\b/iu', $value)) {
            return null;
        }

        return $value;
    }

    protected function cleanReportNumber(string $value): ?string
    {
        $value = preg_replace('/^\s*(?:broj\s+zapisnika|zapisnik\s+br\.?|broj\s+izvje[šs]taja)\s*[:|.\-]?\s*/iu', '', $value);
        $value = preg_replace('/\s+/', '', (string) $value);
        $value = trim((string) $value, " \t\n\r\0\x0B:;|");

        return $value !== '' ? $value : null;
    }

    protected function removeLabelFromLine(string $line, string $alias): string
    {
        $lineNorm = $this->normalizeComparableText($line);
        $aliasNorm = $this->normalizeComparableText($alias);

        if (! str_starts_with($lineNorm, $aliasNorm)) {
            return '';
        }

        /*
         * Regex s tolerantnim razmacima i OCR dijakriticima.
         */
        $tokens = preg_split('/\s+/u', trim($alias)) ?: [];
        $parts = [];

        foreach ($tokens as $token) {
            $token = preg_quote($token, '/');
            $token = str_replace([
                'č', 'ć', 'š', 'ž', 'đ',
                'Č', 'Ć', 'Š', 'Ž', 'Đ',
            ], [
                '[čćcC]', '[čćcC]', '[šsS]', '[žzZ]', '[đdD]',
                '[čćcC]', '[čćcC]', '[šsS]', '[žzZ]', '[đdD]',
            ], $token);
            $parts[] = $token;
        }

        $pattern = '/^\s*' . implode('\s+', $parts) . '\s*[:|=\-]?\s*/iu';
        $value = preg_replace($pattern, '', $line, 1);

        return trim((string) $value);
    }

    protected function fuzzyLabelMatch(string $line, string $alias): bool
    {
        $a = $this->normalizeComparableText($line);
        $b = $this->normalizeComparableText($alias);

        if ($a === $b) {
            return true;
        }

        if (str_starts_with($a, $b)) {
            return true;
        }

        if (mb_strlen($a) > mb_strlen($b) + 8) {
            return false;
        }

        $distance = levenshtein($a, $b);
        $max = max(strlen($a), strlen($b), 1);

        return ($distance / $max) <= 0.28;
    }

    protected function lineContainsLabel(string $line, string $alias): bool
    {
        $lineNorm = $this->normalizeComparableText($line);
        $aliasNorm = $this->normalizeComparableText($alias);

        return str_starts_with($lineNorm, $aliasNorm)
            || $this->fuzzyLabelMatch($line, $alias);
    }

    protected function looksLikeAnyLabel(string $value): bool
    {
        $valueNorm = $this->normalizeComparableText($value);

        foreach (self::FIELD_ALIASES as $aliases) {
            foreach ($aliases as $alias) {
                $aliasNorm = $this->normalizeComparableText($alias);

                if ($valueNorm === $aliasNorm || str_starts_with($valueNorm, $aliasNorm . ' ')) {
                    return true;
                }
            }
        }

        return (bool) preg_match('/^(datum\s+sastavljanja|tip\/model|tip|model|tehni[čc]ki\s+podaci|opis\s+namjene|dodatni\s+podaci)\b/iu', $value);
    }

    protected function lines(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];

        return array_values(array_filter(array_map(
            fn ($line) => trim(preg_replace('/[ \t]+/u', ' ', (string) $line) ?? ''),
            $lines
        ), fn ($line) => $line !== ''));
    }

    protected function sameComparable(string $a, string $b): bool
    {
        return $this->normalizeComparableText($a) === $this->normalizeComparableText($b);
    }

    protected function normalizeComparableText(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        $replace = [
            'č' => 'c', 'ć' => 'c', 'š' => 's', 'ž' => 'z', 'đ' => 'd',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'á' => 'a', 'à' => 'a',
        ];

        $value = strtr($value, $replace);
        $value = preg_replace('/[^a-z0-9\/\.\- ]+/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', (string) $value);

        return trim((string) $value);
    }

    protected function allDates(string $text): array
    {
        preg_match_all('/\b(\d{1,2}\.\d{1,2}\.\d{4})\.?\b/u', $text, $matches);

        $dates = [];

        foreach ($matches[1] ?? [] as $date) {
            $normalized = $this->normalizeDate($date);

            if ($normalized !== null) {
                $dates[$normalized] = $normalized;
            }
        }

        return array_values($dates);
    }

    protected function normalizeDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = trim((string) $value);

        foreach (['d.m.Y.', 'd.m.Y', 'Y-m-d', 'Y-m-d H:i:s'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    protected function cleanupData(array $data): array
    {
        foreach (self::OCR_FIELDS as $field) {
            $value = $data[$field] ?? null;

            if (! is_string($value)) {
                continue;
            }

            $value = trim(preg_replace('/[ \t]{2,}/u', ' ', $value) ?? $value);

            if (in_array($field, ['examination_valid_from', 'examination_valid_until'], true)) {
                $value = $this->normalizeDate($value) ?: $value;
            }

            if ($field === 'inventory_number') {
                $value = $this->cleanInventory($value);

                if ($value === null || $this->looksLikeAdministrativeOrCompanyValue((string) $value)) {
                    $value = '-';
                }
            }

            if ($field === 'examined_by') {
                $value = $this->normalizeCompanyLegalForm($value);
            }

            if ($field === 'location') {
                $value = $this->cleanLocation($value);
            }

            $data[$field] = $value === '' ? null : $value;
        }

        return $data;
    }

    protected function countRecognized(array $data): int
    {
        $count = 0;

        foreach (self::OCR_FIELDS as $field) {
            if (filled($data[$field] ?? null)) {
                $count++;
            }
        }

        return $count;
    }

    protected function extractNativePdfText(string $absolutePath): string
    {
        try {
            $parser = new Parser();
            return $parser->parseFile($absolutePath)->getText() ?: '';
        } catch (\Throwable $e) {
            Log::warning('PDF native text parser failed', [
                'file' => $absolutePath,
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Render PDF stranice preko Ghostscripta umjesto preko Imagicka.
     * Na skeniranim tablicama daje stabilniju sliku za Tesseract.
     */
    protected function ocrPdfPage(string $pdfPath, int $page): string
    {
        if ($page < 1) {
            return '';
        }

        $dir = storage_path('app/tmp/machine-ocr-pages');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $png = $dir . DIRECTORY_SEPARATOR . 'page-' . uniqid('', true) . '.png';

        try {
            if (! $this->renderPdfPageWithGhostscript($pdfPath, $page, $png)) {
                return '';
            }

            $text = $this->ocrImageMultiPass($png);

            return $this->normalizeText($text);
        } catch (\Throwable $e) {
            Log::warning('PDF page OCR failed', [
                'file' => $pdfPath,
                'page' => $page,
                'message' => $e->getMessage(),
            ]);

            return '';
        } finally {
            if (file_exists($png)) {
                @unlink($png);
            }
        }
    }

    protected function renderPdfPageWithGhostscript(string $pdfPath, int $page, string $outputPng): bool
    {
        $gs = $this->resolveGhostscriptExecutable();

        if ($gs === null) {
            Log::warning('Ghostscript executable not found.');
            return false;
        }

        $process = new Process([
            $gs,
            '-dSAFER',
            '-dBATCH',
            '-dNOPAUSE',
            '-dQUIET',
            '-sDEVICE=pnggray',
            '-r300',
            '-dTextAlphaBits=4',
            '-dGraphicsAlphaBits=4',
            '-dFirstPage=' . $page,
            '-dLastPage=' . $page,
            '-sOutputFile=' . $outputPng,
            $pdfPath,
        ]);

        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('Ghostscript page render failed', [
                'page' => $page,
                'error' => $process->getErrorOutput(),
            ]);

            return false;
        }

        return file_exists($outputPng) && filesize($outputPng) > 0;
    }

    protected function resolveGhostscriptExecutable(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $known = [
                'C:\\Program Files\\gs\\gs10.08.0\\bin\\gswin64c.exe',
                'C:\\Program Files\\gs\\gs10.07.0\\bin\\gswin64c.exe',
                'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe',
                'C:\\Program Files\\gs\\gs10.05.1\\bin\\gswin64c.exe',
            ];

            foreach ($known as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            $output = @shell_exec('where gswin64c 2>NUL');

            if (is_string($output) && trim($output) !== '') {
                return trim(strtok($output, "\r\n"));
            }

            return null;
        }

        foreach (['gs', 'gswin64c'] as $binary) {
            $output = @shell_exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null');

            if (is_string($output) && trim($output) !== '') {
                return trim($output);
            }
        }

        return null;
    }

    /**
     * PSM 3 je glavni prolaz jer najbolje čita cijelu standardnu tablicu.
     * PSM 11 dodajemo samo ako nedostaje dovoljno standardnih labela.
     */
    protected function ocrImageMultiPass(string $imagePath): string
    {
        $psm3 = $this->extractTextFromImage($imagePath, 3);
        $score3 = $this->labelCoverageScore($psm3);

        if ($score3 >= 5) {
            return $psm3;
        }

        $psm11 = $this->extractTextFromImage($imagePath, 11);

        if (blank($psm11)) {
            return $psm3;
        }

        return trim($psm3 . "\n" . $psm11);
    }

    protected function labelCoverageScore(string $text): int
    {
        $score = 0;

        foreach ([
            'naziv',
            'proizvo',
            'tvorni',
            'inventar',
            'lokacija',
            'datum',
            'broj zapisnika',
            'ovla',
        ] as $needle) {
            if (str_contains($this->normalizeComparableText($text), $this->normalizeComparableText($needle))) {
                $score++;
            }
        }

        return $score;
    }

    protected function extractTextFromImage(string $absolutePath, int $psm): string
    {
        try {
            $ocr = new TesseractOCR($absolutePath);

            if (PHP_OS_FAMILY === 'Windows') {
                $windowsExe = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';

                if (file_exists($windowsExe)) {
                    $ocr->executable($windowsExe);
                }
            }

            return trim((string) $ocr
                ->lang('hrv', 'eng')
                ->psm($psm)
                ->run());
        } catch (\Throwable $e) {
            Log::warning('Tesseract OCR failed', [
                'file' => $absolutePath,
                'psm' => $psm,
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }

    protected function joinPages(array $pages): string
    {
        ksort($pages);
        $parts = [];

        foreach ($pages as $page => $text) {
            if (filled($text)) {
                $parts[] = "--- OCR PAGE {$page} ---\n" . trim($text);
            }
        }

        return implode("\n\n", $parts);
    }

    protected function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    protected function cleanValue(string $value): string
    {
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;
        $value = trim($value);
        $value = ltrim($value, ':;|= ');

        return trim($value);
    }

    protected function isDashLike(string $value): bool
    {
        $value = trim($value);

        return $value === '' || (bool) preg_match('/^[\-–—_\.\/\\|]+$/u', $value);
    }

    protected function normalizeStoredPath(string|array|null $storedPath): ?string
    {
        if (is_string($storedPath)) {
            return trim($storedPath) !== '' ? trim($storedPath) : null;
        }

        if (is_array($storedPath)) {
            $first = reset($storedPath);

            if (is_string($first)) {
                return trim($first) !== '' ? trim($first) : null;
            }

            if (is_array($first)) {
                foreach (['path', 'file', 'filepath', 'name'] as $key) {
                    if (! empty($first[$key]) && is_string($first[$key])) {
                        return trim($first[$key]);
                    }
                }
            }
        }

        return null;
    }

    protected function deleteTemporaryOcrUpload(?string $storedPath, string $disk): void
    {
        if (blank($storedPath)) {
            return;
        }

        $normalized = str_replace('\\', '/', ltrim((string) $storedPath, '/\\'));

        if (! str_starts_with($normalized, 'tmp/machine-ocr/')) {
            return;
        }

        try {
            if (Storage::disk($disk)->exists($normalized)) {
                Storage::disk($disk)->delete($normalized);
            }
        } catch (\Throwable $e) {
            Log::warning('Temporary OCR upload cleanup failed', [
                'path' => $normalized,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function errorResult(string $message): array
    {
        return [
            'success' => false,
            'data' => [],
            'recognized_fields' => 0,
            'text_excerpt' => '',
            'message' => $message,
        ];
    }
}
