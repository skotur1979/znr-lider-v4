<?php

namespace App\Services;

use Carbon\Carbon;
use App\Services\FormVersionService;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

class Nr1PdfGenerator
{
    public static function generate($referral): string
    {
        $formVersion = $referral->form_version ?: FormVersionService::currentNr1();

        $templatePath = FormVersionService::templatePath('nr1', $formVersion);

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
            $rest = ltrim(mb_substr($t, $lo));

            return [$first, $rest];
        };

        $write = function (float $x, float $y, ?string $text, float $w = 70, float $h = 5) use ($mpdf) {
            if (blank($text)) {
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

        $writeThreeLineAutoFitWidths = function (
            float $x1,
            float $y1,
            float $w1,
            float $x2,
            float $y2,
            float $w2,
            float $x3,
            float $y3,
            float $w3,
            ?string $text,
            int $maxPt = 11,
            int $minPt = 8,
            int $maxChars = 260
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

                [$line1, $rest1] = $fitToWidth($t, $w1);

                if ($rest1 === '') {
                    $line2 = '';
                    $line3 = '';
                    $fits = true;
                } else {
                    [$line2, $rest2] = $fitToWidth($rest1, $w2);

                    if ($rest2 === '') {
                        $line3 = '';
                        $fits = true;
                    } else {
                        [$line3, $rest3] = $fitToWidth($rest2, $w3);
                        $fits = ($rest3 === '');
                    }
                }

                if ($fits) {
                    if ($line1 !== '') {
                        $mpdf->WriteFixedPosHTML(
                            '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($line1) . '</div>',
                            $x1,
                            $y1,
                            $w1,
                            5
                        );
                    }

                    if (! empty($line2)) {
                        $mpdf->WriteFixedPosHTML(
                            '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($line2) . '</div>',
                            $x2,
                            $y2,
                            $w2,
                            5
                        );
                    }

                    if (! empty($line3)) {
                        $mpdf->WriteFixedPosHTML(
                            '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($line3) . '</div>',
                            $x3,
                            $y3,
                            $w3,
                            5
                        );
                    }

                    $mpdf->SetFont($family, '', $prevPt);
                    return;
                }
            }

            $mpdf->SetFont($family, '', $minPt);

            [$line1, $rest1] = $fitToWidth($t, $w1);
            [$line2, $rest2] = $rest1 !== '' ? $fitToWidth($rest1, $w2) : ['', ''];
            [$line3, ] = $rest2 !== '' ? $fitToWidth($rest2, $w3) : ['', ''];

            if ($line1 !== '') {
                $mpdf->WriteFixedPosHTML(
                    '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($line1) . '</div>',
                    $x1,
                    $y1,
                    $w1,
                    5
                );
            }

            if ($line2 !== '') {
                $mpdf->WriteFixedPosHTML(
                    '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($line2) . '</div>',
                    $x2,
                    $y2,
                    $w2,
                    5
                );
            }

            if ($line3 !== '') {
                $mpdf->WriteFixedPosHTML(
                    '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($line3) . '</div>',
                    $x3,
                    $y3,
                    $w3,
                    5
                );
            }

            $mpdf->SetFont($family, '', $prevPt);
        };

        $writeTwoLineLeftReset = function (
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
                    if ($line1 !== '') {
                        $mpdf->WriteFixedPosHTML(
                            '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($line1) . '</div>',
                            $x1,
                            $y1,
                            $w1,
                            5
                        );
                    }

                    if ($line2 !== '') {
                        $mpdf->WriteFixedPosHTML(
                            '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($line2) . '</div>',
                            $x2,
                            $y2,
                            $w2,
                            5
                        );
                    }

                    $mpdf->SetFont($family, '', $prevPt);
                    return;
                }
            }

            $mpdf->SetFont($family, '', $minPt);

            [$line1, $rest] = $fitToWidth($t, $w1);

            if ($line1 !== '') {
                $mpdf->WriteFixedPosHTML(
                    '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($line1) . '</div>',
                    $x1,
                    $y1,
                    $w1,
                    5
                );
            }

            if ($rest !== '') {
                [$line2, ] = $fitToWidth($rest, $w2);

                if ($line2 !== '') {
                    $mpdf->WriteFixedPosHTML(
                        '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($line2) . '</div>',
                        $x2,
                        $y2,
                        $w2,
                        5
                    );
                }
            }

            $mpdf->SetFont($family, '', $prevPt);
        };

        $writeInlinePairClamp = function (
            float $x1,
            float $y,
            float $w1,
            ?string $text1,
            float $x2,
            float $w2,
            ?string $text2,
            string $separator = ', ',
            int $maxPt = 11,
            int $minPt = 7
        ) use ($mpdf, $fitToWidth) {
            $t1 = trim((string) $text1);
            $t2 = trim((string) $text2);

            if ($t1 === '' && $t2 === '') {
                return;
            }

            $family = 'dejavusans';
            $prevPt = $mpdf->FontSizePt ?: $maxPt;

            for ($fs = $maxPt; $fs >= $minPt; $fs--) {
                $mpdf->SetFont($family, '', $fs);

                $left = $t1;
                if ($left !== '' && $t2 !== '') {
                    $left .= $separator;
                }

                [$leftLine, ] = $fitToWidth($left, $w1);
                [$rightLine, ] = $fitToWidth($t2, $w2);

                if ($leftLine === $left && $rightLine === $t2) {
                    if ($left !== '') {
                        $mpdf->WriteFixedPosHTML(
                            '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($left) . '</div>',
                            $x1,
                            $y,
                            $w1,
                            5
                        );
                    }

                    if ($t2 !== '') {
                        $mpdf->WriteFixedPosHTML(
                            '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($t2) . '</div>',
                            $x2,
                            $y,
                            $w2,
                            5
                        );
                    }

                    $mpdf->SetFont($family, '', $prevPt);
                    return;
                }
            }

            $mpdf->SetFont($family, '', $minPt);

            $left = $t1;
            if ($left !== '' && $t2 !== '') {
                $left .= $separator;
            }

            [$leftLine, ] = $fitToWidth($left, $w1);
            [$rightLine, ] = $fitToWidth($t2, $w2);

            if ($leftLine !== '') {
                $mpdf->WriteFixedPosHTML(
                    '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($leftLine) . '</div>',
                    $x1,
                    $y,
                    $w1,
                    5
                );
            }

            if ($rightLine !== '') {
                $mpdf->WriteFixedPosHTML(
                    '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($rightLine) . '</div>',
                    $x2,
                    $y,
                    $w2,
                    5
                );
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

        $emp = $referral->employee;

$name = (string) ($referral->full_name ?: ($emp?->name ?? ''));
$oibEmp = (string) ($referral->oib ?: ($emp?->OIB ?? ''));
$education = (string) ($referral->education ?: ($emp?->education ?? ''));
$jobTitle = (string) ($referral->job_title ?: ($emp?->workplace ?? ''));
$parents = (string) ($referral->name_of_parents ?: ($emp?->name_of_parents ?? ''));

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
        $write(15, 12, $referral->employer_name, 90, 5);
        $write(15, 17.7, $referral->employer_address, 90, 5);
        $write(15, 25.5, 'OIB: ' . $referral->employer_oib, 90, 5);

        // Broj / datum gore desno
        $write(174, 13.5, $referral->referral_number, 45, 5);
        $write(
            174,
            19.2,
            $referral->referral_date
                ? Carbon::parse($referral->referral_date)->format('d.m.Y.')
                : '',
            45,
            5
        );

        // Osnovni podaci
        $writeInlinePairClamp(
    65,   // ime i prezime
    51,
    85,   // VEĆI prostor → nema smanjenja fonta
    $name,
    140,  // ime oca/majke pomaknuto LIJEVO
    35,   // dovoljno mjesta da stane normalno
    $parents,
    ', ',
    10,
    10   // 🔴 zaključan font 10 (nema smanjenja)
);

        $writeOneLineClamp(53, 57.2, $referral->place_of_birth, 131, 10, 7);
        $write(168, 57.2, $education, 68, 5);
        $write(28, 64, $oibEmp, 60, 5);

        // Noćni rad za koje se utvrđuje radna sposobnost
        $writeOneLineClamp(93, 70.3, $jobTitle, 199, 10, 8);

        // Pregled
        foreach ($referral->exam_type ?? [] as $item) {
            match ($item) {
                'prethodni' => $box(57, 81, true),
                'kontrolni' => $box(87, 81, true),
                default => null,
            };
        }

        $write(80, 88, $referral->last_exam_date ? Carbon::parse($referral->last_exam_date)->format('d.m.Y.') : '', 35, 5);
        $write(76, 94.5, $referral->last_exam_reference3, 125, 5);

        // Kratak opis noćnog rada - 3 reda
        $writeThreeLineAutoFitWidths(
            102, 107.3, 102,
            10, 112.8, 194,
            10, 118.0, 194,
            $referral->short_description,
            10, 7, 260
        );

        // Strojevi, alati, uređaji - 2 reda, drugi red opet lijevo
        $writeTwoLineLeftReset(
            50, 124.0, 148,
            10, 128.8, 188,
            $referral->tools,
            10, 7, 180
        );

        // Predmet rada - 2 reda, drugi red opet lijevo
        $writeTwoLineLeftReset(
            41, 135, 157,
            10, 139.7, 188,
            $referral->job_tasks,
            10, 7, 180
        );

        // Mjesto rada
        foreach ($referral->workplace_location ?? [] as $item) {
            match ($item) {
                'zatvorenom' => $box(41, 147.5, true),
                'otvorenom' => $box(70.9, 147.5, true),
                'na_visini' => $box(100.9, 147.5, true),
                'u_dubini' => $box(135.6, 147.5, true),
                'u_vodi' => $box(160, 147.5, true),
                'mokrim_uvjetima' => $box(183.3, 147.5, true),
                default => null,
            };
        }

        // Organizacija rada
        foreach ($referral->organization ?? [] as $item) {
            match ($item) {
                'smjena' => $box(41, 155.1, true),
                'terenski' => $box(70.9, 155.1, true),
                'samostalni' => $box(100.9, 155.1, true),
                'rad_s_grupom' => $box(135.6, 155.1, true),
                'rad_sa_strankama' => $box(160, 155.1, true),
                'rad_na_traci' => $box(41, 161.6, true),
                'brzi_tempo' => $box(68.5, 161.6, true),
                'ritam_određen' => $box(100.9, 161.6, true),
                'monotonija' => $box(151.0, 161.6, true),
                default => null,
            };
        }

        // Položaj tijela i aktivnosti
        foreach ($referral->body_position ?? [] as $item) {
            match ($item) {
                'stojeći' => $box(41, 169.7, true),
                'sagibanje' => $box(97, 169.6, true),
                'podvlačenje' => $box(151.7, 169.6, true),

                'sjedeći' => $box(41, 176, true),
                'zakretanje' => $box(97, 176, true),
                'balansiranje' => $box(151.7, 176, true),

                'u_pokretu' => $box(41, 182.5, true),
                'klečanje' => $box(97, 182.5, true),
                'uspinjanje' => $box(151.7, 182.5, true),

                'kombinirano' => $box(41, 189, true),
                'čučanje' => $box(97, 189, true),
                'uspinjanje_stepenicama' => $box(151.7, 189, true),
                default => null,
            };
        }

        if ($referral->lifting_enabled) {
            $box(41, 195.5, true);
            $write(67.5, 195.5, (string) ($referral->lifting_weight ?: ''), 12, 5);
        }

        if ($referral->carrying_enabled) {
            $box(97, 195.5, true);
            $write(130.5, 195.5, (string) ($referral->carrying_weight ?: ''), 12, 5);
        }

        if ($referral->pushing_enabled) {
            $box(151.7, 195.5, true);
            $write(181.5, 195.5, (string) ($referral->pushing_weight ?: ''), 12, 5);
        }

        // Pri radu je važan
        foreach ($referral->job_characteristics ?? [] as $item) {
            match ($item) {
                'vid_na_daljinu' => $box(41, 203.4, true),
                'vid_na_blizinu' => $box(77, 203.4, true),
                'raspoznavanje' => $box(107, 203.4, true),
                'sluh' => $box(152.5, 203.4, true),
                'govor' => $box(179.3, 203.4, true),
                default => null,
            };
        }

        // Uvjeti rada
        foreach ($referral->hazards ?? [] as $item) {
            match ($item) {
                'toplina' => $box(41, 211.5, true),
                'vlažnost' => $box(97, 211.5, true),
                'hladnoća' => $box(152, 211.5, true),

                'buka' => $box(41, 217.9, true),
                'vibracije' => $box(97, 217.9, true),
                'ozljede' => $box(151.8, 217.9, true),

                'tlak' => $box(41, 224.5, true),
                'prašina' => $box(97, 224.5, true),
                'zračenja' => $box(151.8, 224.5, true),

                'zračenja1' => $box(41, 230.8, true),
                default => null,
            };
        }

        // Kemijske / biološke
        $writeOneLineClamp(40, 239.8, $referral->chemcial_substances, 198, 10, 7);
        $writeOneLineClamp(40, 245.0, $referral->biological_hazards, 198, 8, 6);

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
        $emp = $referral->employee;
        $name = $emp?->name ?: (string) $referral->full_name;

        $date = $referral->referral_date
            ? Carbon::parse($referral->referral_date)->format($dateFormat)
            : now()->format($dateFormat);

        return self::sanitizeFileName(
            ($name ?: 'Bez imena') . ' - NR-1 ' . ($referral->referral_number ?: '-') . ' - ' . $date
        ) . '.pdf';
    }
}