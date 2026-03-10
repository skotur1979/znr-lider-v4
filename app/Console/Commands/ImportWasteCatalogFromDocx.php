<?php

namespace App\Console\Commands;

use App\Models\WasteCatalogItem;
use Illuminate\Console\Command;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ImportWasteCatalogFromDocx extends Command
{
    protected $signature = 'waste:import-catalog {path : Putanja do DOCX datoteke}';
    protected $description = 'Uvezi katalog ključnih brojeva otpada iz DOCX datoteke.';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("Datoteka ne postoji: {$path}");
            return self::FAILURE;
        }

        try {
            $paragraphs = $this->extractParagraphsFromDocx($path);
        } catch (\Throwable $e) {
            $this->error('Greška pri čitanju DOCX datoteke: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (empty($paragraphs)) {
            $this->warn('Nije pronađen sadržaj za uvoz.');
            return self::SUCCESS;
        }

        $rows = $this->parseCatalogRows($paragraphs);

        if (empty($rows)) {
            $this->warn('Nije pronađen nijedan važeći ključni broj otpada za uvoz.');
            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $existing = WasteCatalogItem::query()
                ->where('waste_code', $row['waste_code'])
                ->first();

            if ($existing) {
                $existing->update($row);
                $updated++;
            } else {
                WasteCatalogItem::create($row);
                $created++;
            }
        }

        $this->info("Uvoz završen. Kreirano: {$created}, ažurirano: {$updated}.");
        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function extractParagraphsFromDocx(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('DOCX nije moguće otvoriti.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('word/document.xml nije pronađen u DOCX datoteci.');
        }

        $document = new SimpleXMLElement($xml);
        $document->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $paragraphs = [];

        foreach ($document->xpath('//w:p') as $paragraph) {
            $texts = $paragraph->xpath('.//w:t');
            $line = '';

            foreach ($texts as $textNode) {
                $line .= (string) $textNode;
            }

            $line = $this->normalizeWhitespace($line);

            if ($line !== '') {
                $paragraphs[] = $line;
            }
        }

        return $paragraphs;
    }

    /**
     * @param  array<int, string>  $paragraphs
     * @return array<int, array<string, mixed>>
     */
    protected function parseCatalogRows(array $paragraphs): array
    {
        $rows = [];

        $count = count($paragraphs);

        for ($i = 0; $i < $count; $i++) {
            $line = $this->normalizeWhitespace($paragraphs[$i]);

            // Uzimamo samo "leaf" kodove tipa 01 03 04* ili 15 01 10*
            if (! preg_match('/^\d{2}\s\d{2}\s\d{2}\*?$/u', $line)) {
                continue;
            }

            $wasteCode = str_replace(' ', '', $line);

            $name = null;
            $recordMark = null;

            // Iduća smislenija linija nakon šifre obično je naziv
            for ($j = $i + 1; $j < min($i + 6, $count); $j++) {
                $candidate = $this->normalizeWhitespace($paragraphs[$j]);

                if ($candidate === '') {
                    continue;
                }

                // preskoči druge šifre
                if (preg_match('/^\d{2}(\s\d{2}){0,2}\*?$/u', $candidate)) {
                    break;
                }

                // preskoči zaglavlja
                if (in_array(mb_strtoupper($candidate), [
                    'POPIS OTPADA',
                    'KLJUČNI BROJ',
                    'NAZIV OTPADA',
                    'OZNAKA ZAPISA',
                ], true)) {
                    continue;
                }

                // Oznaka tipa N, V1, V2...
                if (preg_match('/^(N|V\d+|V)$/u', $candidate)) {
                    if ($recordMark === null) {
                        $recordMark = $candidate;
                    }
                    continue;
                }

                if ($name === null) {
                    $name = $candidate;
                    continue;
                }

                if ($recordMark === null && preg_match('/^(N|V\d+|V)$/u', $candidate)) {
                    $recordMark = $candidate;
                }
            }

            if (! $name) {
                continue;
            }

            $isHazardous = str_contains($wasteCode, '*')
                || ($recordMark !== null && str_starts_with($recordMark, 'V'));

            $rows[$wasteCode] = [
                'waste_code' => $wasteCode,
                'name' => $name,
                'is_hazardous' => $isHazardous,
                'record_mark' => $recordMark,
            ];
        }

        return array_values($rows);
    }

    protected function normalizeWhitespace(string $value): string
    {
        $value = str_replace("\xc2\xa0", ' ', $value); // nbsp
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }
}