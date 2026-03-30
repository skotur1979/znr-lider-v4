<?php

namespace App\Services;

use App\Models\WorkPermit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

class WorkPermitPdfGenerator
{
    public static function generate(WorkPermit $permit): string
    {
        $templatePath = resource_path('templates/DOZVOLA-ZA-RAD.pdf');

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
            int|float $minPt = 8
        ) use ($mpdf) {
            if ($text === null || trim((string) $text) === '') {
                return;
            }

            $t = preg_replace('/\s+/u', ' ', trim((string) $text));
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
        $box(36.3, 27.6, $has($permit->work_types, 'hot_work'));
        $box(95, 28.5, $has($permit->work_types, 'work_at_height'));
        $box(150, 28.5, $has($permit->work_types, 'electrical_work'));

        $box(35, 32, $has($permit->work_types, 'hazardous_chemicals'));
        $box(95, 36, $has($permit->work_types, 'other'));
        $writeOneLineClamp(111, 36, $permit->other_work_type, 198, 10, 7);

        /*
        |--------------------------------------------------------------------------
        | ZAHTJEV / PROPIS
        |--------------------------------------------------------------------------
        */
        $writeBlock(31, 43.0, $permit->request_or_regulation, 175, 5, 9);

        /*
        |--------------------------------------------------------------------------
        | RADOVE IZVODE
        |--------------------------------------------------------------------------
        */
        $box(33.8, 49.0, $has($permit->executor_types, 'company_employees'));
        $box(73.0, 49.0, $has($permit->executor_types, 'external_contractors'));

        /*
        |--------------------------------------------------------------------------
        | POPIS RADNIKA
        |--------------------------------------------------------------------------
        */
        $writeOneLineClamp(31.5, 53.7, $permit->worker_1, 84, 9, 7);
        $writeOneLineClamp(89.3, 53.7, $permit->worker_2, 142, 9, 7);
        $writeOneLineClamp(147.5, 53.7, $permit->worker_3, 201, 9, 7);

        $writeOneLineClamp(31.5, 58.3, $permit->worker_4, 84, 9, 7);
        $writeOneLineClamp(89.3, 58.3, $permit->worker_5, 142, 9, 7);
        $writeOneLineClamp(147.5, 58.3, $permit->worker_6, 201, 9, 7);

        $writeOneLineClamp(31.5, 62.9, $permit->worker_7, 84, 9, 7);
        $writeOneLineClamp(89.3, 62.9, $permit->worker_8, 142, 9, 7);
        $writeOneLineClamp(147.5, 62.9, $permit->worker_9, 201, 9, 7);

        /*
        |--------------------------------------------------------------------------
        | OPIS POSLOVA
        |--------------------------------------------------------------------------
        */
        $writeBlock(31, 69.0, $permit->work_description, 175, 16, 9);

        /*
        |--------------------------------------------------------------------------
        | KONTAKT
        |--------------------------------------------------------------------------
        */
        $writeOneLineClamp(31.5, 88.0, $permit->contact_person, 106, 10, 8);
        $writeOneLineClamp(109.5, 88.0, $permit->phone, 202, 10, 8);

        /*
        |--------------------------------------------------------------------------
        | MJERE LIJEVA KOLONA
        |--------------------------------------------------------------------------
        */
        $box(6.2, 99.0, $has($permit->required_measures, 'remove_flammable_material'));
        $box(6.2, 103.5, $has($permit->required_measures, 'place_fire_extinguishers'));
        $box(6.2, 108.0, $has($permit->required_measures, 'check_welding_bottles'));
        $box(6.2, 112.5, $has($permit->required_measures, 'check_welding_hoses'));
        $box(6.2, 117.0, $has($permit->required_measures, 'cover_openings'));
        $box(6.2, 121.5, $has($permit->required_measures, 'check_grounding_cable'));
        $box(6.2, 126.0, $has($permit->required_measures, 'fire_blankets'));
        $box(6.2, 130.5, $has($permit->required_measures, 'additional_lighting'));

        /*
        |--------------------------------------------------------------------------
        | MJERE DESNA KOLONA
        |--------------------------------------------------------------------------
        */
        $box(111.8, 99.0, $has($permit->required_measures, 'check_dangerous_gases'));
        $box(111.8, 103.5, $has($permit->required_measures, 'mark_work_area'));
        $box(111.8, 108.0, $has($permit->required_measures, 'lototo'));
        $box(111.8, 112.5, $has($permit->required_measures, 'additional_risk_assessment'));
        $box(111.8, 117.0, $has($permit->required_measures, 'mandatory_scaffold'));
        $box(111.8, 121.5, $has($permit->required_measures, 'safety_rope'));
        $box(111.8, 126.0, $has($permit->required_measures, 'additional_access_exit'));
        $box(111.8, 130.5, $has($permit->required_measures, 'five_rules_electrical'));

        /*
        |--------------------------------------------------------------------------
        | DODATNE MJERE
        |--------------------------------------------------------------------------
        */
        $writeBlock(31, 136.7, $permit->additional_measures, 175, 5, 9);

        /*
        |--------------------------------------------------------------------------
        | POTREBNA OPREMA
        |--------------------------------------------------------------------------
        */
        $writeBlock(6, 147.2, $permit->required_equipment, 199, 5, 9);

        /*
        |--------------------------------------------------------------------------
        | OPASNOSTI RADA - LIJEVA
        |--------------------------------------------------------------------------
        */
        $box(6.2, 158.8, $has($permit->work_hazards, 'fall_from_height'));
        $box(6.2, 163.2, $has($permit->work_hazards, 'sharp_objects'));
        $box(6.2, 167.7, $has($permit->work_hazards, 'mechanical_lifting'));
        $box(6.2, 172.2, $has($permit->work_hazards, 'stored_energy'));
        $box(6.2, 176.7, $has($permit->work_hazards, 'hot_cold_surfaces'));
        $box(6.2, 181.2, $has($permit->work_hazards, 'acids_alkalis'));
        $box(6.2, 185.7, $has($permit->work_hazards, 'heavy_loads'));
        $box(6.2, 190.2, $has($permit->work_hazards, 'electrical_hazard'));

        /*
        |--------------------------------------------------------------------------
        | OPASNOSTI RADA - SREDINA
        |--------------------------------------------------------------------------
        */
        $box(76.0, 158.8, $has($permit->work_hazards, 'lack_of_oxygen'));
        $box(76.0, 163.2, $has($permit->work_hazards, 'crushing'));
        $box(76.0, 167.7, $has($permit->work_hazards, 'vehicle_impact'));
        $box(76.0, 172.2, $has($permit->work_hazards, 'bad_weather'));
        $box(76.0, 176.7, $has($permit->work_hazards, 'noise'));
        $box(76.0, 181.2, $has($permit->work_hazards, 'explosive_flammable'));
        $box(76.0, 185.7, $has($permit->work_hazards, 'outdoor_work'));
        $box(76.0, 190.2, $has($permit->work_hazards, 'poor_lighting'));

        /*
        |--------------------------------------------------------------------------
        | OPASNOSTI RADA - DESNA
        |--------------------------------------------------------------------------
        */
        $box(140.6, 158.8, $has($permit->work_hazards, 'confined_space'));
        $box(140.6, 163.2, $has($permit->work_hazards, 'repetitive_movements'));
        $box(140.6, 167.7, $has($permit->work_hazards, 'walking_surfaces'));
        $box(140.6, 172.2, $has($permit->work_hazards, 'high_pressure'));
        $box(140.6, 176.7, $has($permit->work_hazards, 'prolonged_work'));
        $box(140.6, 181.2, $has($permit->work_hazards, 'eye_strain'));
        $box(140.6, 185.7, $has($permit->work_hazards, 'dangerous_vapors_gases'));
        $box(140.6, 190.2, $has($permit->work_hazards, 'other'));
        $writeOneLineClamp(154.2, 189.8, $permit->other_hazard, 201, 8, 6);

        /*
        |--------------------------------------------------------------------------
        | OZO
        |--------------------------------------------------------------------------
        */
        $box(6.2, 202.4, $has($permit->required_ppe, 'safety_shoes'));
        $box(63.0, 202.4, $has($permit->required_ppe, 'safety_glasses'));
        $box(107.4, 202.4, $has($permit->required_ppe, 'hearing_protection'));
        $box(161.7, 202.4, $has($permit->required_ppe, 'helmet'));

        $box(6.2, 207.1, $has($permit->required_ppe, 'work_clothes'));
        $box(63.0, 207.1, $has($permit->required_ppe, 'protective_mask'));
        $box(107.4, 207.1, $has($permit->required_ppe, 'respirator'));
        $box(161.7, 207.1, $has($permit->required_ppe, 'welding_mask'));

        $box(6.2, 211.9, $has($permit->required_ppe, 'rubber_boots'));
        $box(63.0, 211.9, $has($permit->required_ppe, 'half_mask'));
        $box(107.4, 211.9, $has($permit->required_ppe, 'reflective_vest'));
        $box(161.7, 211.9, $has($permit->required_ppe, 'cap_with_protection'));

        $box(6.2, 216.7, $has($permit->required_ppe, 'protective_gloves'));
        $box(63.0, 216.7, $has($permit->required_ppe, 'face_shield'));
        $box(107.4, 216.7, $has($permit->required_ppe, 'fall_protection_belt'));

        /*
        |--------------------------------------------------------------------------
        | ODOBRENJE
        |--------------------------------------------------------------------------
        */
        $writeOneLineClamp(87.5, 229.0, $permit->requester_name, 139, 10, 8);
        $writeOneLineClamp(145.0, 229.0, $permit->requester_signature, 199, 10, 8);

        $writeOneLineClamp(87.5, 233.9, $permit->approver_name, 139, 10, 8);
        $writeOneLineClamp(145.0, 233.9, $permit->approver_signature, 199, 10, 8);

        /*
        |--------------------------------------------------------------------------
        | PRODUŽENJE
        |--------------------------------------------------------------------------
        */
        $writeOneLineClamp(16.0, 244.1, $fmtDateTimeCompact($permit->extension_valid_from), 94, 10, 7);
        $writeOneLineClamp(102.0, 244.1, $fmtDateTimeCompact($permit->extension_valid_until), 180, 10, 7);

        $writeOneLineClamp(87.5, 254.4, $permit->extension_approver_name, 139, 10, 8);
        $writeOneLineClamp(145.0, 254.4, $permit->extension_approver_signature, 199, 10, 8);

        /*
        |--------------------------------------------------------------------------
        | PROVJERA IZVRŠENIH RADOVA
        |--------------------------------------------------------------------------
        */
        $box(63.0, 265.8, $permit->works_finished === true);
        $box(81.4, 265.8, $permit->works_finished === false);

        $box(63.0, 270.5, $permit->checked_after === '1h');
        $box(81.4, 270.5, $permit->checked_after === '3h');

        $writeBlock(101.0, 264.9, $permit->unfinished_reason, 99, 8, 9);

        $writeOneLineClamp(17.0, 279.0, $permit->verification_name, 59, 10, 8);
        $writeOneLineClamp(67.0, 279.0, $permit->verification_signature, 98, 10, 8);
        $writeOneLineClamp(108.0, 279.0, $fmtDate($permit->verification_date), 138, 10, 8);
        $writeOneLineClamp(160.0, 279.0, $fmtTime($permit->verification_time), 185, 10, 8);

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