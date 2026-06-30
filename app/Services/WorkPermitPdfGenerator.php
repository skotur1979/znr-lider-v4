<?php

namespace App\Services;

use App\Models\WorkPermit;
use App\Services\FormVersionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

class WorkPermitPdfGenerator
{
    public static function generate(WorkPermit $permit): string
    {
        $formVersion = $permit->form_version ?: FormVersionService::currentWorkPermit();

        $templatePath = FormVersionService::templatePath('work-permits', $formVersion);

        if (! file_exists($templatePath)) {
            throw new \RuntimeException('Nedostaje PDF predložak: ' . $templatePath);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 9,
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
            if ($text === null || trim((string) $text) === '') {
                return;
            }

            $html = '<div style="white-space: normal; word-wrap: break-word; line-height: 1.10; text-align: left;">'
                . htmlspecialchars((string) $text) .
                '</div>';

            $mpdf->WriteFixedPosHTML($html, $x, $y, $w, $h);
        };

        $writeOneLineNoWrap = function (float $x, float $y, ?string $text, float $w) use ($mpdf, $fitToWidth) {
            if ($text === null || trim((string) $text) === '') {
                return;
            }

            $t = preg_replace('/\s+/u', ' ', trim((string) $text));
            [$line, ] = $fitToWidth($t, $w);

            $html = '<div style="white-space: nowrap; overflow: hidden; text-overflow: clip; text-align: left;">'
                . htmlspecialchars($line) .
                '</div>';

            $mpdf->WriteFixedPosHTML($html, $x, $y, $w, 5);
        };

        $writeOneLineClamp = function (
            float $x,
            float $y,
            ?string $text,
            float $maxX,
            int|float $maxPt = 10,
            int|float $minPt = 8,
            ?int $maxChars = null
        ) use ($mpdf) {
            if ($text === null || trim((string) $text) === '') {
                return;
            }

            $t = preg_replace('/\s+/u', ' ', trim((string) $text));

            if ($maxChars !== null) {
                $t = mb_substr($t, 0, $maxChars);
            }

            $w = max(0, $maxX - $x);

            $prevPt = $mpdf->FontSizePt ?: $maxPt;
            $family = 'dejavusans';

            $chosen = $maxPt;
            $fits = false;

            for ($fs = $maxPt; $fs >= $minPt; $fs -= 0.5) {
                $mpdf->SetFont($family, '', $fs);

                if ($mpdf->GetStringWidth($t) <= $w) {
                    $chosen = $fs;
                    $fits = true;
                    break;
                }
            }

            $fontSize = $fits ? $chosen : $minPt;

            $mpdf->SetFont($family, '', $fontSize);

            $html = '<div style="white-space: nowrap; overflow: hidden; text-overflow: clip; text-align: left; font-size:'
                . $fontSize . 'pt;">'
                . htmlspecialchars($t) .
                '</div>';

            $mpdf->WriteFixedPosHTML($html, $x, $y, $w, 5);
            $mpdf->SetFont($family, '', $prevPt);
        };

        $writeBlock = function (float $x, float $y, ?string $text, float $w, float $h, int|float $fontSize = 10) use ($mpdf) {
            if ($text === null || trim((string) $text) === '') {
                return;
            }

            $html = '<div style="
                white-space: normal;
                word-wrap: break-word;
                overflow-wrap: break-word;
                line-height: 1.08;
                text-align: left;
                font-size:' . $fontSize . 'pt;
            ">' . nl2br(htmlspecialchars((string) $text)) . '</div>';

            $mpdf->WriteFixedPosHTML($html, $x, $y, $w, $h);
        };

        $writeTwoLineClampWidths = function (
            float $x1,
            float $y1,
            float $w1,
            float $x2,
            float $y2,
            float $w2,
            ?string $text,
            int|float $maxPt = 9,
            int|float $minPt = 7.5,
            int $maxChars = 150
        ) use ($mpdf, $fitToWidth) {
            if (empty($text)) {
                return;
            }

            $t = preg_replace('/\s+/u', ' ', trim((string) $text));
            $t = mb_substr($t, 0, $maxChars);

            $family = 'dejavusans';
            $prevPt = $mpdf->FontSizePt ?: $maxPt;

            for ($fs = $maxPt; $fs >= $minPt; $fs -= 0.5) {
                $mpdf->SetFont($family, '', $fs);

                [$line1, $rest1] = $fitToWidth($t, $w1);

                if ($rest1 === '') {
                    $line2 = '';
                    $fits = true;
                } else {
                    [$line2, $rest2] = $fitToWidth($rest1, $w2);
                    $fits = ($rest2 === '');
                }

                if ($fits) {
                    $html1 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($line1) . '</div>';
                    $mpdf->WriteFixedPosHTML($html1, $x1, $y1, $w1, 5);

                    if ($line2 !== '') {
                        $html2 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($line2) . '</div>';
                        $mpdf->WriteFixedPosHTML($html2, $x2, $y2, $w2, 5);
                    }

                    $mpdf->SetFont($family, '', $prevPt);
                    return;
                }
            }

            $mpdf->SetFont($family, '', $minPt);

            [$line1, $rest1] = $fitToWidth($t, $w1);
            $html1 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($line1) . '</div>';
            $mpdf->WriteFixedPosHTML($html1, $x1, $y1, $w1, 5);

            if ($rest1 !== '') {
                [$line2, ] = $fitToWidth($rest1, $w2);
                $html2 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($line2) . '</div>';
                $mpdf->WriteFixedPosHTML($html2, $x2, $y2, $w2, 5);
            }

            $mpdf->SetFont($family, '', $prevPt);
        };

        $writeThreeLineClampWidths = function (
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
            int|float $maxPt = 9,
            int|float $minPt = 7.5,
            int $maxChars = 100
        ) use ($mpdf, $fitToWidth) {
            if (empty($text)) {
                return;
            }

            $t = preg_replace('/\s+/u', ' ', trim((string) $text));
            $t = mb_substr($t, 0, $maxChars);

            $family = 'dejavusans';
            $prevPt = $mpdf->FontSizePt ?: $maxPt;

            for ($fs = $maxPt; $fs >= $minPt; $fs -= 0.5) {
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
                    $html1 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($line1) . '</div>';
                    $mpdf->WriteFixedPosHTML($html1, $x1, $y1, $w1, 5);

                    if ($line2 !== '') {
                        $html2 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($line2) . '</div>';
                        $mpdf->WriteFixedPosHTML($html2, $x2, $y2, $w2, 5);
                    }

                    if ($line3 !== '') {
                        $html3 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $fs . 'pt;">' . htmlspecialchars($line3) . '</div>';
                        $mpdf->WriteFixedPosHTML($html3, $x3, $y3, $w3, 5);
                    }

                    $mpdf->SetFont($family, '', $prevPt);
                    return;
                }
            }

            $mpdf->SetFont($family, '', $minPt);

            [$line1, $rest1] = $fitToWidth($t, $w1);
            $html1 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($line1) . '</div>';
            $mpdf->WriteFixedPosHTML($html1, $x1, $y1, $w1, 5);

            if ($rest1 !== '') {
                [$line2, $rest2] = $fitToWidth($rest1, $w2);
                $html2 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($line2) . '</div>';
                $mpdf->WriteFixedPosHTML($html2, $x2, $y2, $w2, 5);

                if ($rest2 !== '') {
                    [$line3, ] = $fitToWidth($rest2, $w3);
                    $html3 = '<div style="white-space:nowrap; overflow:hidden; font-size:' . $minPt . 'pt;">' . htmlspecialchars($line3) . '</div>';
                    $mpdf->WriteFixedPosHTML($html3, $x3, $y3, $w3, 5);
                }
            }

            $mpdf->SetFont($family, '', $prevPt);
        };

        $box = function (float $x, float $y, bool $checked, float $w = 5, float $h = 5, int|float $fontSize = 9) use ($mpdf) {
            if (! $checked) {
                return;
            }

            $html = '<div style="
                font-size:' . $fontSize . 'pt;
                font-weight:bold;
                text-align:center;
                line-height:' . $h . 'mm;
            ">X</div>';

            $mpdf->WriteFixedPosHTML($html, $x, $y, $w, $h);
        };

        $fmtDate = fn ($value) => $value ? Carbon::parse($value)->format('d.m.Y.') : '';
        $fmtDateTimeCompact = fn ($value) => $value ? Carbon::parse($value)->format('d.m.Y.H.i') : '';
        $fmtTime = fn ($value) => $value ? Carbon::parse($value)->format('H:i') : '';
        $has = fn ($arr, $key) => in_array($key, is_array($arr) ? $arr : [], true);

        Storage::makeDirectory('temp');

        $dateForFile = $permit->issue_date
            ? Carbon::parse($permit->issue_date)->format('Y-m-d')
            : now()->format('Y-m-d');

        $baseFileName = self::sanitizeFileName(
            'Dozvola za rad ' . ($permit->permit_number ?: '-') . ' - ' . $dateForFile
        ) . '.pdf';

        $outputPath = storage_path('app/temp/' . $baseFileName);

        $mpdf->SetTitle('Dozvola za rad ' . ($permit->permit_number ?: '-'));

        /*
        |--------------------------------------------------------------------------
        | GORNJI RED
        |--------------------------------------------------------------------------
        */
        $writeOneLineClamp(36.5, 23, $permit->permit_number, 53, 10, 8);
        $writeOneLineClamp(73, 23.5, $fmtDate($permit->issue_date), 91, 10, 8);
        $writeOneLineClamp(111.5, 23, $fmtDateTimeCompact($permit->valid_from), 145, 10, 7);
        $writeOneLineClamp(165, 23, $fmtDateTimeCompact($permit->valid_until), 202, 10, 7);

        /*
        |--------------------------------------------------------------------------
        | ZA POSLOVE
        |--------------------------------------------------------------------------
        */
        $box(36.3, 27.5, $has($permit->work_types, 'hot_work'));
        $box(95.8, 27.5, $has($permit->work_types, 'work_at_height'));
        $box(149.7, 27.5, $has($permit->work_types, 'electrical_work'));

        $box(36.3, 31.4, $has($permit->work_types, 'hazardous_chemicals'));
        $box(95.8, 31.4, $has($permit->work_types, 'other'));
        $writeOneLineClamp(115.5, 32, $permit->other_work_type, 198, 10, 7, 50);

        /*
        |--------------------------------------------------------------------------
        | ZAHTJEV / PROPIS
        |--------------------------------------------------------------------------
        */
        $writeTwoLineClampWidths(
        35.5, 35.5, 167.0,
        35.5, 38, 167.0,
        $permit->request_or_regulation,
        8,
        7,
        150
        );

        /*
        |--------------------------------------------------------------------------
        | RADOVE IZVODE
        |--------------------------------------------------------------------------
        */
        $box(36.3, 41.0, $has($permit->executor_types, 'company_employees'));
        $box(73.0, 41.0, $has($permit->executor_types, 'external_contractors'));

        /*
        |--------------------------------------------------------------------------
        | POPIS RADNIKA
        |--------------------------------------------------------------------------
        */
        $writeOneLineClamp(40, 45.5, $permit->worker_1, 84, 9, 7);
        $writeOneLineClamp(96, 45.5, $permit->worker_2, 142, 9, 7);
        $writeOneLineClamp(151, 45.5, $permit->worker_3, 201, 9, 7);

        $writeOneLineClamp(40, 49.5, $permit->worker_4, 84, 9, 7);
        $writeOneLineClamp(96, 49.5, $permit->worker_5, 142, 9, 7);
        $writeOneLineClamp(151, 49.5, $permit->worker_6, 201, 9, 7);

        $writeOneLineClamp(40, 53.7, $permit->worker_7, 84, 9, 7);
        $writeOneLineClamp(96, 53.7, $permit->worker_8, 142, 9, 7);
        $writeOneLineClamp(151, 53.7, $permit->worker_9, 201, 9, 7);

        /*
        |--------------------------------------------------------------------------
        | OPIS POSLOVA - 3 RETKA / MAX 150
        |--------------------------------------------------------------------------
        */
        $writeThreeLineClampWidths(
            36.0, 59.0, 162.0,
            36.0, 63.0, 162.0,
            36.0, 67.0, 162.0,
            $permit->work_description,
            9,
            7.5,
            300
        );

        /*
        |--------------------------------------------------------------------------
        | KONTAKT OSOBA - 1 RED / MAX 35
        |--------------------------------------------------------------------------
        */
        $writeOneLineClamp(36, 72.2, $permit->contact_person, 106, 10, 8, 50);
        $writeOneLineClamp(145, 72, $permit->phone, 202, 10, 8);

        /*
        |--------------------------------------------------------------------------
        | MJERE LIJEVA KOLONA
        |--------------------------------------------------------------------------
        */
        $box(9.5, 82.2, $has($permit->required_measures, 'remove_flammable_material'));
        $box(9.5, 86.1, $has($permit->required_measures, 'place_fire_extinguishers'));
        $box(9.5, 89.9, $has($permit->required_measures, 'check_welding_bottles'));
        $box(9.5, 93.8, $has($permit->required_measures, 'check_welding_hoses'));
        $box(9.5, 97.6, $has($permit->required_measures, 'cover_openings'));
        $box(9.5, 101.5, $has($permit->required_measures, 'check_grounding_cable'));
        $box(9.5, 105.3, $has($permit->required_measures, 'fire_blankets'));
        $box(9.5, 109.2, $has($permit->required_measures, 'additional_lighting'));

        /*
        |--------------------------------------------------------------------------
        | MJERE DESNA KOLONA
        |--------------------------------------------------------------------------
        */
        $box(109.9, 82.2, $has($permit->required_measures, 'check_dangerous_gases'));
        $box(109.9, 86.1, $has($permit->required_measures, 'mark_work_area'));
        $box(109.9, 89.9, $has($permit->required_measures, 'lototo'));
        $box(109.9, 93.8, $has($permit->required_measures, 'additional_risk_assessment'));
        $box(109.9, 97.6, $has($permit->required_measures, 'mandatory_scaffold'));
        $box(109.9, 101.5, $has($permit->required_measures, 'safety_rope'));
        $box(109.9, 105.3, $has($permit->required_measures, 'additional_access_exit'));
        $box(109.9, 109.2, $has($permit->required_measures, 'five_rules_electrical'));

        /*
        |--------------------------------------------------------------------------
        | DODATNE MJERE - 2 RETKA / MAX 150
        |--------------------------------------------------------------------------
        */
        $writeTwoLineClampWidths(
            41.0, 114.4, 162.0,
            41.0, 118.2, 162.0,
            $permit->additional_measures,
            9,
            7.5,
            200
        );

        /*
        |--------------------------------------------------------------------------
        | POTREBNA OPREMA
        |--------------------------------------------------------------------------
        */
        $writeBlock(11, 127, $permit->required_equipment, 199, 5, 9);

        /*
        |--------------------------------------------------------------------------
        | OPASNOSTI RADA - LIJEVA
        |--------------------------------------------------------------------------
        */
        $box(9.5, 137.3, $has($permit->work_hazards, 'fall_from_height'));
        $box(9.5, 141.2, $has($permit->work_hazards, 'sharp_objects'));
        $box(9.5, 145.1, $has($permit->work_hazards, 'mechanical_lifting'));
        $box(9.5, 148.9, $has($permit->work_hazards, 'stored_energy'));
        $box(9.5, 152.8, $has($permit->work_hazards, 'hot_cold_surfaces'));
        $box(9.5, 156.7, $has($permit->work_hazards, 'acids_alkalis'));
        $box(9.5, 160.5, $has($permit->work_hazards, 'heavy_loads'));
        $box(9.5, 164.4, $has($permit->work_hazards, 'electrical_hazard'));

        /*
        |--------------------------------------------------------------------------
        | OPASNOSTI RADA - SREDINA
        |--------------------------------------------------------------------------
        */
        $box(76.9, 137.3, $has($permit->work_hazards, 'lack_of_oxygen'));
        $box(76.9, 141.2, $has($permit->work_hazards, 'crushing'));
        $box(76.9, 145.1, $has($permit->work_hazards, 'vehicle_impact'));
        $box(76.9, 148.9, $has($permit->work_hazards, 'bad_weather'));
        $box(76.9, 152.8, $has($permit->work_hazards, 'noise'));
        $box(76.9, 156.7, $has($permit->work_hazards, 'explosive_flammable'));
        $box(76.9, 160.5, $has($permit->work_hazards, 'outdoor_work'));
        $box(76.9, 164.4, $has($permit->work_hazards, 'poor_lighting'));

        /*
        |--------------------------------------------------------------------------
        | OPASNOSTI RADA - DESNA
        |--------------------------------------------------------------------------
        */
        $box(138, 137.3, $has($permit->work_hazards, 'confined_space'));
        $box(138, 141.2, $has($permit->work_hazards, 'repetitive_movements'));
        $box(138, 145.1, $has($permit->work_hazards, 'walking_surfaces'));
        $box(138, 148.9, $has($permit->work_hazards, 'high_pressure'));
        $box(138, 152.8, $has($permit->work_hazards, 'prolonged_work'));
        $box(138, 156.7, $has($permit->work_hazards, 'eye_strain'));
        $box(138, 160.5, $has($permit->work_hazards, 'dangerous_vapors_gases'));
        $box(138, 164.4, $has($permit->work_hazards, 'other'));
        $writeOneLineClamp(157, 165.1, $permit->other_hazard, 201, 8, 6, 30);

        /*
        |--------------------------------------------------------------------------
        | OZO
        |--------------------------------------------------------------------------
        */
        $box(9.5, 174.5, $has($permit->required_ppe, 'safety_shoes'));
        $box(63.5, 174.5, $has($permit->required_ppe, 'safety_glasses'));
        $box(106.9, 174.5, $has($permit->required_ppe, 'hearing_protection'));
        $box(157.4, 174.5, $has($permit->required_ppe, 'helmet'));

        $box(9.5, 180, $has($permit->required_ppe, 'work_clothes'));
        $box(63.5, 180, $has($permit->required_ppe, 'protective_mask'));
        $box(106.9, 180, $has($permit->required_ppe, 'respirator'));
        $box(157.4, 180, $has($permit->required_ppe, 'welding_mask'));

        $box(9.5, 185.3, $has($permit->required_ppe, 'rubber_boots'));
        $box(63.5, 185.3, $has($permit->required_ppe, 'half_mask'));
        $box(106.9, 185.3, $has($permit->required_ppe, 'reflective_vest'));
        $box(157.4, 185.3, $has($permit->required_ppe, 'cap_with_protection'));

        $box(9.5, 190.6, $has($permit->required_ppe, 'protective_gloves'));
        $box(63.5, 190.6, $has($permit->required_ppe, 'face_shield'));
        $box(106.9, 190.6, $has($permit->required_ppe, 'fall_protection_belt'));

        /*
        |--------------------------------------------------------------------------
        | ODOBRENJE
        |--------------------------------------------------------------------------
        */
        $writeOneLineClamp(74, 209, $permit->requester_name, 139, 10, 8);
        $writeOneLineClamp(140.0, 209, $permit->requester_signature, 199, 10, 8);

        $writeOneLineClamp(74, 217.5, $permit->approver_name, 139, 10, 8);
        $writeOneLineClamp(140, 217.5, $permit->approver_signature, 199, 10, 8);

        /*
        |--------------------------------------------------------------------------
        | PRODUŽENJE
        |--------------------------------------------------------------------------
        */
        $writeOneLineClamp(35.0, 230, $fmtDateTimeCompact($permit->extension_valid_from), 94, 10, 7);
        $writeOneLineClamp(140.0, 230, $fmtDateTimeCompact($permit->extension_valid_until), 180, 10, 7);

        $writeOneLineClamp(76, 240.3, $permit->extension_approver_name, 139, 10, 8);
        $writeOneLineClamp(140.0, 240.3, $permit->extension_approver_signature, 199, 10, 8);

        /*
        |--------------------------------------------------------------------------
        | PROVJERA IZVRŠENIH RADOVA
        |--------------------------------------------------------------------------
        */
        $box(64.6, 251.1, $permit->works_finished === true);
        $box(82, 251.1, $permit->works_finished === false);

        $box(64.6, 256.5, $permit->checked_after === '1h');
        $box(81.7, 256.5, $permit->checked_after === '3h');

        $writeThreeLineClampWidths(
            138.0, 251.0, 64.0,
            138.0, 254.4, 64.0,
            138.0, 257.8, 64.0,
            $permit->unfinished_reason,
            8.5,
            7,
            150
        );

        $writeOneLineClamp(14.0, 271.0, $permit->verification_name, 59, 10, 8);
        $writeOneLineClamp(67.0, 271.0, $permit->verification_signature, 98, 10, 8);
        $writeOneLineClamp(108.0, 271.0, $fmtDate($permit->verification_date), 138, 10, 8);
        $writeOneLineClamp(160.0, 271.0, $fmtTime($permit->verification_time), 185, 10, 8);

        $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);

        return $outputPath;
    }

    private static function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[\/\\\\\:\*\?"<>\|]+/u', ' ', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return $name !== '' ? $name : 'Dozvola-za-rad';
    }

    public static function buildFileName(WorkPermit $permit, string $dateFormat = 'Y-m-d'): string
    {
        $date = $permit->issue_date
            ? Carbon::parse($permit->issue_date)->format($dateFormat)
            : now()->format($dateFormat);

        return self::sanitizeFileName(
            'Dozvola za rad ' . ($permit->permit_number ?: '-') . ' - ' . $date
        ) . '.pdf';
    }
}