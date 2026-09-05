<?php

namespace App\Services;

use Carbon\Carbon;
use App\Services\FormVersionService;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

class Ra1PdfGenerator
{
    public static function generate($referral): string
    {
        $formVersion = $referral->form_version ?: FormVersionService::currentRa1();

        $templatePath = FormVersionService::templatePath('ra1', $formVersion);

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

        $writeBlock = function (float $x, float $y, ?string $text, float $w, float $h) use ($mpdf) {
            if (empty($text)) return;
            $html = '<div style="white-space: normal; word-wrap: break-word; line-height: 1.15; text-align: left;">'
                . htmlspecialchars($text) . '</div>';
            $mpdf->WriteFixedPosHTML($html, $x, $y, $w, $h);
        };

        $fitToWidth = function (
        ?string $text,
        float $w
    ) use ($mpdf): array {
        $t = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $text)
        );

        if ($t === '') {
            return ['', ''];
        }

        /*
        * Prvo pokušavamo lomiti tekst
        * isključivo između cijelih riječi.
        */
        $words = preg_split(
            '/\s+/u',
            $t,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $line = '';
        $usedWords = 0;

        foreach ($words as $index => $word) {
            $candidate =
                $line === ''
                    ? $word
                    : $line . ' ' . $word;

            if (
                $mpdf->GetStringWidth(
                    $candidate
                ) <= $w
            ) {
                $line = $candidate;
                $usedWords = $index + 1;

                continue;
            }

            break;
        }

        /*
        * Ako je barem jedna cijela riječ
        * stala u red, vraćamo uredan prijelom.
        */
        if ($line !== '') {
            $rest = implode(
                ' ',
                array_slice(
                    $words,
                    $usedWords
                )
            );

            return [
                $line,
                trim($rest),
            ];
        }

        /*
        * Samo ako je jedna jedina riječ / oznaka
        * duža od cijelog raspoloživog reda
        * dopuštamo rezanje po znakovima.
        *
        * To je fallback za vrijednosti poput
        * jako dugih šifri bez razmaka.
        */
        $lo = 0;
        $hi = mb_strlen($t);

        while ($lo < $hi) {
            $mid =
                intdiv(
                    $lo + $hi + 1,
                    2
                );

            $piece =
                mb_substr(
                    $t,
                    0,
                    $mid
                );

            if (
                $mpdf->GetStringWidth(
                    $piece
                ) <= $w
            ) {
                $lo = $mid;
            } else {
                $hi = $mid - 1;
            }
        }

        $first =
            mb_substr(
                $t,
                0,
                $lo
            );

        $rest =
            ltrim(
                mb_substr(
                    $t,
                    $lo
                )
            );

        return [
            $first,
            $rest,
        ];
    };

        $writeOneLineNoWrap = function (float $x, float $y, ?string $text, float $w) use ($mpdf, $fitToWidth) {
            if ($text === null || $text === '') return;

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
            int $minPt = 9
        ) use ($mpdf) {
            if ($text === null || $text === '') return;

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

        $writeTwoLineClamp = function (
            float $x1,
            float $y1,
            float $x2,
            float $y2,
            float $maxX,
            ?string $text
        ) use ($fitToWidth, $writeOneLineNoWrap) {
            if (empty($text)) return;

            $w1 = max(0, $maxX - $x1);
            $w2 = max(0, $maxX - $x2);

            [$line1, $rest] = $fitToWidth($text, $w1);

            if ($line1 !== '') {
                $writeOneLineNoWrap($x1, $y1, $line1, $w1);
            }

            if ($rest !== '') {
                [$line2, ] = $fitToWidth($rest, $w2);

                if ($line2 !== '') {
                    $writeOneLineNoWrap($x2, $y2, $line2, $w2);
                }
            }
        };

        $writeTwoLineClampWidths = function (
            float $x1,
            float $y1,
            float $w1,
            float $x2,
            float $y2,
            float $w2,
            ?string $text
        ) use ($fitToWidth, $writeOneLineNoWrap) {
            if (empty($text)) return;

            [$line1, $rest] = $fitToWidth($text, $w1);

            if ($line1 !== '') {
                $writeOneLineNoWrap($x1, $y1, $line1, $w1);
            }

            if ($rest !== '') {
                [$line2, ] = $fitToWidth($rest, $w2);

                if ($line2 !== '') {
                    $writeOneLineNoWrap($x2, $y2, $line2, $w2);
                }
            }
        };

        $write = function (float $x, float $y, ?string $text, float $w = 70, float $h = 5) use ($mpdf) {
            if (empty($text)) return;

            $html = '<div style="white-space: normal; word-wrap: break-word; line-height: 1.15; text-align: left;">'
                . htmlspecialchars($text) . '</div>';

            $mpdf->WriteFixedPosHTML($html, $x, $y, $w, $h);
        };

        $box = function (float $x, float $y, bool $checked) use ($mpdf) {
            if ($checked) {
                $mpdf->WriteFixedPosHTML('X', $x, $y, 5, 5);
            }
        };

        $oibSplit = function (float $x, float $y, ?string $oib) use ($mpdf) {
            if (! $oib || strlen($oib) !== 11) return;

            $spacing = 6;

            foreach (str_split($oib) as $i => $char) {
                $mpdf->WriteFixedPosHTML($char, $x + ($i * $spacing), $y, 5, 5);
            }
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
            int $minPt = 9,
            int $maxChars = 180
        ) use ($mpdf, $fitToWidth) {
            if (empty($text)) return;

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

        $emp       = $referral->employee;
        $name      = $emp?->name ?: (string) $referral->full_name;
        $oibEmp    = $emp?->OIB ?: (string) $referral->oib;
        $jobTitle  = (string) ($referral->job_title ?: ($emp?->job_title ?? ''));
        $education = (string) ($referral->education ?: ($emp?->education ?? ''));

        $dateForFile = $referral->referral_date
            ? Carbon::parse($referral->referral_date)->format('Y-m-d')
            : now()->format('Y-m-d');

        $baseFileName = self::sanitizeFileName(
            ($name ?: 'Bez imena') . ' - RA-1 ' . ($referral->referral_number ?: '-') . ' - ' . $dateForFile
        ) . '.pdf';

        Storage::makeDirectory('temp');

        $outputPath = storage_path('app/temp/' . $baseFileName);

        $mpdf->SetTitle(($name ?: 'RA-1') . ' - RA-1 ' . ($referral->referral_number ?: '-') . ' - ' . $dateForFile);

        $write(36, 55,  $name);
        $writeOneLineClamp(49, 62.5, $referral->place_of_birth, 120, 10, 7);
        $write(28, 67.8, $jobTitle);
        $write(130, 67.8, $education);

        $write(10, 18,  $referral->employer_name);
        $write(10, 24,  $referral->employer_address);
        $oibSplit(128, 29, $referral->employer_oib);

        $writeOneLineClamp(95, 73, $referral->health_jobs_description, 200);
        $writeOneLineClamp(47, 172, $referral->tools, 210, 10, 8);
        $write(76, 130, $referral->last_exam_date ? Carbon::parse($referral->last_exam_date)->format('d.m.Y.') : '');
        $write(150, 14, $referral->referral_number);
        $write(
            150,
            21,
            $referral->referral_date
                ? Carbon::parse($referral->referral_date)->format('d.m.Y.')
                : ''
        );
        $write(125, 55, $referral->name_of_parents);
        $write(128, 62.5, $oibEmp);
        $write(54, 82, $referral->law_reference);

        $law = preg_replace('/\s*,\s*/u', ',', (string) $referral->law_reference1);
        $maxChars = mb_strlen($law);

        $writeTwoLineAutoFitWidths(
            76.0,
            81.3,
            31.0,

            76.0,
            84.2,
            31.0,

            $law,

            /*
            * Kratke vrijednosti prikazujemo
            * normalno velikim fontom.
            *
            * Font se smanjuje tek ako tekst
            * zaista ne stane.
            */
            10,
            7,

            $maxChars
        );

        $writeTwoLineAutoFitWidths(
            122, 88, 80,
            8, 94, 115,
            $referral->special_conditions,
            10, 8,
            110
        );

        $write(125, 130, $referral->last_exam_reference);
        $write(152, 130, $referral->last_exam_reference1);

        $writeTwoLineAutoFitWidths(
            96, 136, 104,
            8, 142, 202,
            $referral->last_exam_reference2,
            10, 8,
            170
        );

        $write(70, 153, $referral->last_exam_reference3);
        $write(40, 117, $referral->total_years);
        $write(163, 117, $referral->work_years_in_job);

        $writeTwoLineAutoFitWidths(
            45, 160, 155,
            7, 166, 210,
            $referral->short_description,
            10, 8,
            190
        );

        $writeOneLineClamp(47, 178, $referral->job_tasks, 210, 10, 8);
        $writeOneLineClamp(40, 249, $referral->chemcial_substances, 210, 10, 8);
        $writeOneLineClamp(40, 255, $referral->biological_hazards, 210, 10, 8);

        if ($referral->lifting_enabled) {
            $box(41.3, 217.5, true);
            $write(65, 219, $referral->lifting_weight . ' kg');
        }

        if ($referral->carrying_enabled) {
            $box(88.7, 217.5, true);
            $write(118, 219, $referral->carrying_weight . ' kg');
        }

        if ($referral->pushing_enabled) {
            $box(146.1, 217.5, true);
            $write(176, 219, $referral->pushing_weight . ' kg');
        }

        foreach ($referral->exam_type ?? [] as $item) {
            match ($item) {
                'prethodni' => $box(59.5, 123, true),
                'periodični' => $box(92.3, 123, true),
                'izvanredni' => $box(124.8, 123, true),
                default => null,
            };
        }

        foreach ($referral->workplace_location ?? [] as $item) {
            match ($item) {
                'otvorenom' => $box(68.3, 187.8, true),
                'zatvorenom' => $box(41.3, 187.8, true),
                'na_visini' => $box(96.5, 187.8, true),
                'u_jami' => $box(115.5, 187.8, true),
                'u_vodi' => $box(131, 187.8, true),
                'pod_vodom' => $box(146.8, 187.8, true),
                'mokrim_uvjetima' => $box(170, 187.8, true),
                default => null,
            };
        }

        foreach ($referral->organization ?? [] as $item) {
            match ($item) {
                'smjena' => $box(41.3, 193.6, true),
                'rad_na_traci' => $box(41.3, 199.6, true),
                'noćni' => $box(66.2, 193.6, true),
                'terenski' => $box(87.3, 193.6, true),
                'samostalni' => $box(112.5, 193.6, true),
                'rad_s_grupom' => $box(132.5, 193.6, true),
                'rad_sa_strankama' => $box(159.8, 193.6, true),
                'brzi_tempo' => $box(65.7, 199.6, true),
                'ritam_određen' => $box(96.2, 199.6, true),
                'monotonija' => $box(124.5, 199.6, true),
                default => null,
            };
        }

        foreach ($referral->body_position ?? [] as $item) {
            match ($item) {
                'sjedeći' => $box(119.7, 205.5, true),
                'stojeći' => $box(41.3, 205.5, true),
                'u_pokretu' => $box(41.3, 211.5, true),
                'sagibanje' => $box(63.5, 205.5, true),
                'klečanje' => $box(60.9, 211.5, true),
                'podvlačenje' => $box(95.7, 205.5, true),
                'uspinjanje' => $box(78.8, 211.5, true),
                'kombinirano' => $box(113.8, 211.5, true),
                'zakretanje' => $box(142, 205.5, true),
                'čučanje' => $box(138, 211.5, true),
                'balansiranje' => $box(170.9, 205.5, true),
                'uspinjanje_stepenicama' => $box(155, 211.5, true),
                default => null,
            };
        }

        foreach ($referral->job_characteristics ?? [] as $item) {
            match ($item) {
                'vid_na_daljinu' => $box(41.3, 223.7, true),
                'vid_na_blizinu' => $box(68.5, 223.7, true),
                'raspoznavanje' => $box(96.2, 223.7, true),
                'sluh' => $box(133, 223.7, true),
                'govor' => $box(166.2, 223.7, true),
                default => null,
            };
        }

        foreach ($referral->hazards ?? [] as $item) {
            match ($item) {
                'buka' => $box(143, 229.5, true),
                'vibracije' => $box(41.3, 235.5, true),
                'vibracije1' => $box(161.5, 229.5, true),
                'zračenja' => $box(41.3, 241.4, true),
                'zračenja1' => $box(83.2, 241.4, true),
                'toplina' => $box(41.3, 229.5, true),
                'vlažnost' => $box(78, 229.5, true),
                'hladnoća' => $box(108, 229.5, true),
                'tlak' => $box(70.5, 235.5, true),
                'ozljede' => $box(115.5, 235.5, true),
                'prašina' => $box(133, 241.4, true),
                default => null,
            };
        }

        $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);

        return $outputPath;
    }

    private static function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[\/\\\\\:\*\?"<>\|]+/u', ' ', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return $name !== '' ? $name : 'RA-1';
    }

    public static function buildFileName($referral, string $dateFormat = 'Y-m-d'): string
    {
        $emp  = $referral->employee;
        $name = $emp?->name ?: (string) $referral->full_name;

        $date = $referral->referral_date
            ? Carbon::parse($referral->referral_date)->format($dateFormat)
            : now()->format($dateFormat);

        return self::sanitizeFileName(
            ($name ?: 'Bez imena') . ' - RA-1 ' . ($referral->referral_number ?: '-') . ' - ' . $date
        ) . '.pdf';
    }
}