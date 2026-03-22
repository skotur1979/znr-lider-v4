<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Spatie\PdfToImage\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;
use ZipArchive;

class MachineReportOcrService
{
    public function extractFromStoredFile(string|array|null $storedPath, string $disk = 'local'): array
    {
        $storedPath = $this->normalizeStoredPath($storedPath);

        if (blank($storedPath)) {
            return [
                'success' => false,
                'data' => [],
                'text_excerpt' => '',
                'message' => 'Nije pronađena putanja učitanog dokumenta.',
            ];
        }

        $absolutePath = Storage::disk($disk)->path($storedPath);

        if (! file_exists($absolutePath)) {
            $fallbackPath = storage_path('app/livewire-tmp/' . basename($storedPath));

            if (file_exists($fallbackPath)) {
                $absolutePath = $fallbackPath;
            } else {
                return [
                    'success' => false,
                    'data' => [],
                    'text_excerpt' => '',
                    'message' => 'Datoteka ne postoji na disku: ' . $storedPath,
                ];
            }
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        $rawText = match ($extension) {
            'pdf' => $this->extractTextFromPdf($absolutePath),
            'jpg', 'jpeg', 'png', 'webp' => $this->extractTextFromImage($absolutePath),
            'docx' => $this->extractTextFromDocx($absolutePath),
            'txt' => $this->extractTextFromTxt($absolutePath),
            'rtf' => $this->extractTextFromRtf($absolutePath),
            'odt' => $this->extractTextFromOdt($absolutePath),
            'doc' => $this->extractTextFromDoc($absolutePath),
            default => '',
        };

        $normalized = $this->normalizeText($rawText);

        $data = [
            'name' => $this->extractMachineName($normalized),

            'manufacturer' => $this->extractField($normalized, [
                '/Ispitivana radna oprema.*?Proizvođač\s+([^\n\r]+)/isu',
                '/Podaci kojima se pobliže određuje radna oprema.*?Proizvođač\s+([^\n\r]+)/isu',
                '/Proizvođač\s+([^\n\r]+)/iu',
            ]),

            'factory_number' => $this->extractField($normalized, [
                '/Ispitivana radna oprema.*?Tvornički broj\s+([^\n\r]+)/isu',
                '/Podaci kojima se pobliže određuje radna oprema.*?Tvornički broj\s+([^\n\r]+)/isu',
                '/Tvornički broj\s+([^\n\r]+)/iu',
            ]),

            'inventory_number' => $this->extractField($normalized, [
                '/Ispitivana radna oprema.*?Inventarni broj\s+([^\n\r]+)/isu',
                '/Podaci kojima se pobliže određuje radna oprema.*?Inventarni broj\s+([^\n\r]+)/isu',
                '/Inventarni broj\s+([^\n\r]+)/iu',
            ]),

            'report_number' => $this->extractField($normalized, [
                '/Broj zapisnika\s+([^\n\r]+)/iu',
                '/Zapisnik br\.\s*([^\n\r]+)/iu',
                '/Broj izvještaja\s+([^\n\r]+)/iu',
            ]),

            'location' => $this->extractLocation($normalized),

            'examination_valid_from' => $this->extractDateField($normalized, [
                '/Datum sastav\.\s*zapisnika\s+([0-9]{2}\.[0-9]{2}\.[0-9]{4}\.?)/iu',
                '/Datum sastavljanja zapisnika\s+([0-9]{2}\.[0-9]{2}\.[0-9]{4}\.?)/iu',
                '/Datum početka pregleda i ispitivanja\s+([0-9]{2}\.[0-9]{2}\.[0-9]{4}\.?)/iu',
            ]),

            'examination_valid_until' => $this->extractDateField($normalized, [
                '/-prije:\s*([0-9]{2}\.[0-9]{2}\.[0-9]{4}\.?)/iu',
                '/Slijedeći pregled treba obaviti\s*-?\s*prije:\s*([0-9]{2}\.[0-9]{2}\.[0-9]{4}\.?)/iu',
                '/vrijedi do\s+([0-9]{2}\.[0-9]{2}\.[0-9]{4}\.?)/iu',
            ]),

            'examined_by' => $this->extractAuthorizedCompany($normalized),

            'ocr_raw_text' => $normalized,
        ];

        $data = $this->cleanupData($data);

        return [
            'success' => true,
            'data' => $data,
            'text_excerpt' => mb_substr($normalized, 0, 3000),
            'message' => null,
        ];
    }

    protected function normalizeStoredPath(string|array|null $storedPath): ?string
    {
        if (is_string($storedPath)) {
            return trim($storedPath) !== '' ? $storedPath : null;
        }

        if (is_array($storedPath)) {
            $first = reset($storedPath);

            if (is_string($first)) {
                return trim($first) !== '' ? $first : null;
            }

            if (is_array($first)) {
                foreach (['path', 'file', 'filepath', 'name'] as $key) {
                    if (! empty($first[$key]) && is_string($first[$key])) {
                        return $first[$key];
                    }
                }
            }
        }

        return null;
    }

    protected function extractTextFromPdf(string $absolutePath): string
    {
        $text = '';

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($absolutePath);
            $text = $pdf->getText() ?: '';
        } catch (\Throwable $e) {
            Log::warning('PDF parser failed', [
                'file' => $absolutePath,
                'message' => $e->getMessage(),
            ]);
        }

        if (mb_strlen(trim($text)) > 300) {
            return $text;
        }

        $ocrText = '';

        try {
            $pdf = new Pdf($absolutePath);
            $pageCount = method_exists($pdf, 'pageCount') ? $pdf->pageCount() : 3;
            $maxPages = min($pageCount, 3);

            for ($page = 1; $page <= $maxPages; $page++) {
                $tempImage = storage_path('app/tmp/machine-ocr/page-' . uniqid() . '-' . $page . '.jpg');

                if (! is_dir(dirname($tempImage))) {
                    mkdir(dirname($tempImage), 0775, true);
                }

                $pdf->selectPage($page)->save($tempImage);

                $ocrText .= "\n" . $this->extractTextFromImage($tempImage);

                if (file_exists($tempImage)) {
                    @unlink($tempImage);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('PDF OCR fallback failed', [
                'file' => $absolutePath,
                'message' => $e->getMessage(),
            ]);
        }

        return $ocrText;
    }

    protected function extractTextFromImage(string $absolutePath): string
    {
        try {
            return (new TesseractOCR($absolutePath))
                ->lang('hrv', 'eng')
                ->run();
        } catch (\Throwable $e) {
            Log::warning('Image OCR failed', [
                'file' => $absolutePath,
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }

    protected function extractTextFromDocx(string $absolutePath): string
    {
        try {
            $zip = new ZipArchive();

            if ($zip->open($absolutePath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();

                if ($xml !== false) {
                    $xml = str_replace(['</w:p>', '</w:tr>', '</w:tc>'], ["\n", "\n", ' '], $xml);
                    $text = strip_tags($xml);

                    return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('DOCX parse failed', [
                'file' => $absolutePath,
                'message' => $e->getMessage(),
            ]);
        }

        return '';
    }

    protected function extractTextFromTxt(string $absolutePath): string
    {
        try {
            return file_get_contents($absolutePath) ?: '';
        } catch (\Throwable $e) {
            Log::warning('TXT parse failed', [
                'file' => $absolutePath,
                'message' => $e->getMessage(),
            ]);
        }

        return '';
    }

    protected function extractTextFromRtf(string $absolutePath): string
    {
        try {
            $content = file_get_contents($absolutePath) ?: '';
            $content = preg_replace('/\\\\par[d]?/', "\n", $content);
            $content = preg_replace('/\\\\[a-z]+\d* ?/i', '', $content);
            $content = preg_replace('/[{}]/', '', $content);

            return $content ?: '';
        } catch (\Throwable $e) {
            Log::warning('RTF parse failed', [
                'file' => $absolutePath,
                'message' => $e->getMessage(),
            ]);
        }

        return '';
    }

    protected function extractTextFromOdt(string $absolutePath): string
    {
        try {
            $zip = new ZipArchive();

            if ($zip->open($absolutePath) === true) {
                $xml = $zip->getFromName('content.xml');
                $zip->close();

                if ($xml !== false) {
                    $xml = str_replace(['</text:p>', '</table:table-row>', '</table:table-cell>'], ["\n", "\n", ' '], $xml);
                    $text = strip_tags($xml);

                    return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ODT parse failed', [
                'file' => $absolutePath,
                'message' => $e->getMessage(),
            ]);
        }

        return '';
    }

    protected function extractTextFromDoc(string $absolutePath): string
    {
        // Stari .doc nije pouzdan bez dodatnih alata (npr. antiword / libreoffice).
        // Dopuštamo upload, ali parsiranje može biti ograničeno.
        try {
            $content = @file_get_contents($absolutePath);

            if ($content === false) {
                return '';
            }

            $text = preg_replace("/[^(\x20-\x7F)\x0A\x0D]/", ' ', $content);
            $text = preg_replace('/\s+/', ' ', $text);

            return trim($text);
        } catch (\Throwable $e) {
            Log::warning('DOC parse failed', [
                'file' => $absolutePath,
                'message' => $e->getMessage(),
            ]);
        }

        return '';
    }

    protected function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n{2,}/u', "\n", $text);

        return trim($text);
    }

    protected function extractField(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1] ?? '');

                if ($this->isDashLike($value)) {
                    return null;
                }

                return $value;
            }
        }

        return null;
    }

    protected function extractDateField(string $text, array $patterns): ?string
    {
        $value = $this->extractField($text, $patterns);

        if (! $value) {
            return null;
        }

        $value = trim($value);
        $value = rtrim($value, '.');

        try {
            return Carbon::createFromFormat('d.m.Y', $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function extractLocation(string $text): ?string
    {
        $raw = $this->extractField($text, [
            '/Ispitivana radna oprema.*?Položaj u radnom prostoru\s+([^\n\r]+)/isu',
            '/Podaci kojima se pobliže određuje radna oprema.*?Položaj u radnom prostoru\s+([^\n\r]+)/isu',
            '/Položaj u radnom prostoru\s+([^\n\r]+)/iu',
            '/Lokacija\s+([^\n\r]+)/iu',
        ]);

        if (! $raw) {
            return null;
        }

        if (preg_match('/-\s*([A-ZČĆŽŠĐ0-9 ]+)$/u', $raw, $m)) {
            return trim($m[1]);
        }

        return trim($raw);
    }

    protected function extractAuthorizedCompany(string $text): ?string
    {
        $patterns = [
            '/2\.\s*Ovlaštena osoba koja obavlja pregled i ispitivanje radne opreme\s+naziv\s+([^\n\r]+)/iu',
            '/Ovlaštena osoba koja obavlja pregled i ispitivanje radne opreme\s+naziv\s+([^\n\r]+)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1] ?? '');

                if ($this->isDashLike($value)) {
                    return null;
                }

                return $value;
            }
        }

        return null;
    }

    protected function extractMachineName(string $text): ?string
    {
        $patterns = [
            '/Ispitivana radna oprema\s+Naziv\s+([^\n\r]+)/iu',
            '/Ispitivana radna oprema.*?Naziv\s+([^\n\r]+)/isu',
            '/Podaci kojima se pobliže određuje radna oprema\s+Naziv\s+([^\n\r]+)/iu',
            '/Podaci kojima se pobliže određuje radna oprema.*?Naziv\s+([^\n\r]+)/isu',
            '/^Naziv\s+([^\n\r]+)/imu',
            '/Naziv radne opreme\s+([^\n\r]+)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $candidate = trim($matches[1] ?? '');

                if (
                    ! $this->isDashLike($candidate) &&
                    mb_strlen($candidate) > 2 &&
                    ! preg_match('/^DW REUSABLES d\.o\.o\.$/iu', $candidate)
                ) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    protected function cleanupData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);
            $value = preg_replace('/\s{2,}/u', ' ', $value);

            if ($this->isDashLike($value)) {
                $value = null;
            }

            $data[$key] = $value;
        }

        return $data;
    }

    protected function isDashLike(?string $value): bool
    {
        if ($value === null) {
            return true;
        }

        $normalized = trim($value);

        return in_array($normalized, ['-', '--', '/', '//', '—'], true);
    }

    public function compare(array $current, array $extracted): array
    {
        $fields = [
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

        $result = [];

        foreach ($fields as $field) {
            $old = Arr::get($current, $field);
            $new = Arr::get($extracted, $field);

            $old = is_string($old) ? trim($old) : $old;
            $new = is_string($new) ? trim($new) : $new;

            $status = 'missing';

            if (filled($new) && blank($old)) {
                $status = 'new';
            } elseif (filled($new) && filled($old) && $old === $new) {
                $status = 'same';
            } elseif (filled($new) && filled($old) && $old !== $new) {
                $status = 'conflict';
            }

            $result[$field] = [
                'old' => $old,
                'new' => $new,
                'status' => $status,
            ];
        }

        return $result;
    }
}