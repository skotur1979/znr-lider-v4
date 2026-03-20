<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

class Nr1PdfGenerator
{
    public static function generate($referral): string
    {
        $templatePath = resource_path('templates/NR-1 Uputnica.pdf');

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 11,
            'default_font' => 'dejavusans',
            'tempDir' => storage_path('app/temp'),
        ]);

        $pagecount = $mpdf->SetSourceFile($templatePath);
        $tplId = $mpdf->ImportPage(1);
        $mpdf->AddPage();
        $mpdf->UseTemplate($tplId);

        $fitToWidth = function (?string $text, float $w) use ($mpdf): array {
            $t = trim((string) $text);
            if ($t === '') {
                return ['', ''];
            }

            $lo = 0;
            $hi = mb_strlen($t);

            while ($lo < $hi) {
                $mid = intdiv($lo + $hi + 1, 2);
                $piece = mb_substr($t, 0, $mid);

                if ($mpdf->GetStringWidth($piece) <= $w) {
                    $lo = $mid;
                } else {
                    $hi = $mid - 1;
                }
            }

            $first = mb_substr($t, 0, $lo);
            $rest  = ltrim(mb_substr($t, $lo));

            return [$first, $rest];
        };

        $write = function (float $x, float $y, ?string $text, float $w = 70, float $h = 5) use ($mpdf) {
            if (empty($text)) {
                return;
            }

            $html = '<div style="white-space: normal; word-wrap: break-word; line-height: 1.15; text-align: left;">'
                . htmlspecialchars((string) $text) . '</div>';

            $mpdf->WriteFixedPosHTML($html, $x, $y, $w, $h);
        };

        $writeOneLineNoWrap = function (float $x, float $y, ?string $text, float $w) use ($mpdf, $fitToWidth) {
            if ($text === null || $text === '') {
                return;
            }

            $t = preg_replace('/\s+/u', ' ', trim((string) $text));
            [$line, ] = $fitToWidth($t, $w);

            $html = '<div style="white-space: nowrap; overflow: hidden; text-overflow: clip; text-align: left;">'
                . htmlspecialchars($line) . '</div>';

            $mpdf->WriteFixedPosHTML($html, $x, $y, $w, 5);
        };

        $writeOneLineClamp = function (
            float $x,
            float $y,
            ?string $text,
            float $maxX,
            int $maxPt = 11,
            int $minPt = 8
        ) use ($mpdf) {
            if ($text === null || $text === '') {
                return;
            }

            $t = preg_replace('/\s+/u', ' ', trim((string) $text));
            $w = max(0, $maxX - $x);

            $prevPt = $mpdf->FontSizePt ?: 11;
            $family = 'dejavusans';

            $chosen = $maxPt;
            $fits = false;

            for ($fs = $maxPt; $fs >= $minPt; $fs--) {
                $mpdf->SetFont($family, '', $fs);

                if ($mpdf->GetStringWidth($t) <= $w) {
                    $chosen = $fs;
                    $fits = true;
                    break;
                }
            }

            $mpdf->SetFont($family, '', $fits ? $chosen : $minPt);

            $html = '<div style="white-space: nowrap; overflow: hidden; text-overflow: clip; text-align: left; font-size:' . ($fits ? $chosen : $minPt) . 'pt;">'
                . htmlspecialchars($t) . '</div>';

            $mpdf->WriteFixedPosHTML($html, $x, $y, $w, 5);
            $mpdf->SetFont($family, '', $prevPt);
        };

        $writeTwoLineAutoFitWidths = function (
            float $x1,
            float $y1,
            float $w1,
            float $x2,
            float $y2,
            float $w2,
            ?string $text,
            int $maxPt = 11,
            int $minPt = 8,
            int $maxChars = 180
        ) use ($mpdf, $fitToWidth) {
            if (empty($text)) {
                return;
            }

            $t = preg_replace('/\s+/u', ' ', trim((string) $text));
            $t = mb_substr($t, 0, $maxChars);

            $family = 'dejavusans';
            $prevPt = $mpdf->FontSizePt ?: $maxPt;

            for ($fs = $maxPt; $fs >= $minPt; $fs--) {
                $mpdf->SetFont($family, '', $fs);

                [$line1, $rest] = $fitToWidth($t, $w1);

                if ($rest === '') {
                    $line2 = '';
                    $fits = true;
                } else {
                    [$line2, $rest2] = $fitToWidth($rest, $w2);
                    $fits = ($rest2 === '');
                }

                if ($fits) {
                    $html1 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">'
                        . htmlspecialchars($line1) . '</div>';
                    $mpdf->WriteFixedPosHTML($html1, $x1, $y1, $w1, 5);

                    if ($line2 !== '') {
                        $html2 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">'
                            . htmlspecialchars($line2) . '</div>';
                        $mpdf->WriteFixedPosHTML($html2, $x2, $y2, $w2, 5);
                    }

                    $mpdf->SetFont($family, '', $prevPt);
                    return;
                }
            }

            $mpdf->SetFont($family, '', $minPt);

            [$line1, $rest] = $fitToWidth($t, $w1);

            $html1 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">'
                . htmlspecialchars($line1) . '</div>';
            $mpdf->WriteFixedPosHTML($html1, $x1, $y1, $w1, 5);

            if ($rest !== '') {
                [$line2, ] = $fitToWidth($rest, $w2);
                $html2 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">'
                    . htmlspecialchars($line2) . '</div>';
                $mpdf->WriteFixedPosHTML($html2, $x2, $y2, $w2, 5);
            }

            $mpdf->SetFont($family, '', $prevPt);
        };

        $box = function (float $x, float $y, bool $checked) use ($mpdf) {
            if ($checked) {
                $mpdf->WriteFixedPosHTML('X', $x, $y, 5, 5);
            }
        };

        $oibSplit = function (float $x, float $y, ?string $oib) use ($mpdf) {
            if (! $oib || strlen((string) $oib) !== 11) {
                return;
            }

            $spacing = 6;

            foreach (str_split((string) $oib) as $i => $char) {
                $mpdf->WriteFixedPosHTML($char, $x + ($i * $spacing), $y, 5, 5);
            }
        };

        $emp       = $referral->employee;
        $name      = $emp?->name ?: (string) $referral->full_name;
        $oibEmp    = $emp?->OIB ?: (string) $referral->oib;
        $education = (string) ($referral->education ?: ($emp?->education ?? ''));
        $jobTitle  = (string) ($referral->job_title ?: ($emp?->job_title ?? ''));

        $dateForFile = $referral->referral_date
            ? Carbon::parse($referral->referral_date)->format('Y-m-d')
            : now()->format('Y-m-d');

        $baseFileName = self::sanitizeFileName(
            ($name ?: 'Bez imena') . ' - NR-1 ' . ($referral->referral_number ?: '-') . ' - ' . $dateForFile
        ) . '.pdf';

        Storage::makeDirectory('temp');

        $outputPath = storage_path('app/temp/' . $baseFileName);

        $mpdf->SetTitle(($name ?: 'NR-1') . ' - NR-1 ' . ($referral->referral_number ?: '-') . ' - ' . $dateForFile);

        // Poslodavac gore lijevo
        $write(16, 17.5, $referral->employer_name, 58, 5);
        $write(16, 23.2, $referral->employer_address, 58, 5);
        $oibSplit(127.5, 29.1, $referral->employer_oib);

        // Broj / datum gore desno
        $write(154, 13.5, $referral->referral_number, 45, 5);
        $write(
            154,
            19.8,
            $referral->referral_date
                ? Carbon::parse($referral->referral_date)->format('d.m.Y.')
                : '',
            45,
            5
        );

        // Osnovni podaci
        $write(41, 54.8, $name, 160, 5);
        $writeOneLineClamp(46, 62.2, $referral->place_of_birth, 131, 10, 7);
        $write(131, 62.2, $education, 68, 5);
        $write(28, 68.9, $oibEmp, 60, 5);

        // Noćni rad za koje se utvrđuje radna sposobnost
        $writeOneLineClamp(84, 75.3, $jobTitle, 199, 10, 8);

        // Pregled
        foreach ($referral->exam_type ?? [] as $item) {
            match ($item) {
                'prethodni' => $box(51.7, 84.1, true),
                'kontrolni' => $box(81.5, 84.1, true),
                default => null,
            };
        }

        $write(73, 91.4, $referral->last_exam_date ? Carbon::parse($referral->last_exam_date)->format('d.m.Y.') : '', 35, 5);
        $write(74, 98.1, $referral->last_exam_reference3, 125, 5);

        // Opis noćnog rada
        $writeTwoLineAutoFitWidths(
            94, 107.3, 104,
            8, 113.1, 190,
            $referral->short_description,
            10, 8, 190
        );

        // Strojevi i predmet rada
        $writeOneLineClamp(45, 126.8, $referral->tools, 198, 10, 8);
        $writeOneLineClamp(41, 137.2, $referral->job_tasks, 198, 10, 8);

        // Mjesto rada
        foreach ($referral->workplace_location ?? [] as $item) {
            match ($item) {
                'zatvorenom' => $box(33.2, 149.5, true),
                'otvorenom' => $box(64.0, 149.5, true),
                'na_visini' => $box(94.3, 149.5, true),
                'u_dubini' => $box(128.8, 149.5, true),
                'u_vodi' => $box(154.2, 149.5, true),
                'mokrim_uvjetima' => $box(176.8, 149.5, true),
                default => null,
            };
        }

        // Organizacija rada
        foreach ($referral->organization ?? [] as $item) {
            match ($item) {
                'smjena' => $box(33.2, 156.3, true),
                'terenski' => $box(64.0, 156.3, true),
                'samostalni' => $box(94.3, 156.3, true),
                'rad_s_grupom' => $box(128.8, 156.3, true),
                'rad_sa_strankama' => $box(160.4, 156.3, true),
                'rad_na_traci' => $box(33.2, 162.2, true),
                'brzi_tempo' => $box(63.8, 162.2, true),
                'ritam_određen' => $box(95.8, 162.2, true),
                'monotonija' => $box(144.0, 162.2, true),
                default => null,
            };
        }

        // Položaj tijela i aktivnosti
        foreach ($referral->body_position ?? [] as $item) {
            match ($item) {
                'stojeći' => $box(33.2, 169.6, true),
                'sagibanje' => $box(89.8, 169.6, true),
                'podvlačenje' => $box(143.7, 169.6, true),

                'sjedeći' => $box(33.2, 175.6, true),
                'zakretanje' => $box(89.8, 175.6, true),
                'balansiranje' => $box(143.7, 175.6, true),

                'u_pokretu' => $box(33.2, 181.6, true),
                'klečanje' => $box(89.8, 181.6, true),
                'uspinjanje' => $box(143.7, 181.6, true),

                'kombinirano' => $box(33.2, 187.7, true),
                'čučanje' => $box(89.8, 187.7, true),
                'uspinjanje_stepenicama' => $box(143.7, 187.7, true),
                default => null,
            };
        }

        if ($referral->lifting_enabled) {
            $box(33.2, 194.0, true);
            $write(62, 194.6, ($referral->lifting_weight ?: '') . ' kg', 20, 5);
        }

        if ($referral->carrying_enabled) {
            $box(89.8, 194.0, true);
            $write(121, 194.6, ($referral->carrying_weight ?: '') . ' kg', 20, 5);
        }

        if ($referral->pushing_enabled) {
            $box(143.7, 194.0, true);
            $write(177, 194.6, ($referral->pushing_weight ?: '') . ' kg', 20, 5);
        }

        // Pri radu je važan
        foreach ($referral->job_characteristics ?? [] as $item) {
            match ($item) {
                'vid_na_daljinu' => $box(33.2, 202.0, true),
                'vid_na_blizinu' => $box(67.8, 202.0, true),
                'raspoznavanje' => $box(99.8, 202.0, true),
                'sluh' => $box(145.0, 202.0, true),
                'govor' => $box(172.2, 202.0, true),
                default => null,
            };
        }

        // Uvjeti rada
        foreach ($referral->hazards ?? [] as $item) {
            match ($item) {
                'toplina' => $box(33.2, 211.1, true),
                'vlažnost' => $box(89.8, 211.1, true),
                'hladnoća' => $box(143.7, 211.1, true),

                'buka' => $box(33.2, 217.0, true),
                'vibracije' => $box(89.8, 217.0, true),
                'ozljede' => $box(143.7, 217.0, true),

                'tlak' => $box(33.2, 223.0, true),
                'prašina' => $box(89.8, 223.0, true),
                'zračenja' => $box(143.7, 223.0, true),

                'zračenja1' => $box(33.2, 229.0, true),
                default => null,
            };
        }

        // Kemijske / biološke
        $writeOneLineClamp(40, 238.5, $referral->chemcial_substances, 198, 10, 8);
        $writeOneLineClamp(40, 244.8, $referral->biological_hazards, 198, 10, 8);

        $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);

        return $outputPath;
    }

    private static function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[\/\\\\\:\*\?"<>\|]+/u', ' ', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return $name !== '' ? $name : 'NR-1';
    }

    public static function buildFileName($referral, string $dateFormat = 'Y-m-d'): string
    {
        $emp  = $referral->employee;
        $name = $emp?->name ?: (string) $referral->full_name;

        $date = $referral->referral_date
            ? Carbon::parse($referral->referral_date)->format($dateFormat)
            : now()->format($dateFormat);

        return self::sanitizeFileName(
            ($name ?: 'Bez imena') . ' - NR-1 ' . ($referral->referral_number ?: '-') . ' - ' . $date
        ) . '.pdf';
    }
}