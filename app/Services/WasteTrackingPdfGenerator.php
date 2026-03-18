<?php

namespace App\Services;

use App\Models\WasteTrackingForm;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Fpdi;

class WasteTrackingPdfGenerator
{
    public function generate(WasteTrackingForm $record): string
    {
        $templatePath = storage_path('app/pdf/Prateci-list-PL-O.pdf');

        if (! file_exists($templatePath)) {
            throw new \RuntimeException('PL-O predložak nije pronađen: ' . $templatePath);
        }

        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $pdf->setSourceFile($templatePath);
        $templateId = $pdf->importPage(1);
        $pdf->useTemplate($templateId, 0, 0, 210);

        /*
        |--------------------------------------------------------------------------
        | FONT
        |--------------------------------------------------------------------------
        | Ako postoji DejaVuSans.php u storage/app/pdf-fonts koristi ga.
        | Inače fallback na Helvetica.
        */
        $fontDir = storage_path('app/pdf-fonts');
        $dejavuDef = $fontDir . '/DejaVuSans.php';

        if (file_exists($dejavuDef)) {
            if (! defined('FPDF_FONTPATH')) {
                define('FPDF_FONTPATH', $fontDir . DIRECTORY_SEPARATOR);
            }

            $pdf->AddFont('DejaVuSans', '', 'DejaVuSans.php');
            $defaultFont = 'DejaVuSans';
        } else {
            $defaultFont = 'Helvetica';
        }

        $pdf->SetFont($defaultFont, '', 7);
        $pdf->SetTextColor(0, 0, 0);

        /*
        |--------------------------------------------------------------------------
        | GLOBALNI OFFSET
        |--------------------------------------------------------------------------
        */
        $offsetX = 3.2;
        $offsetY = 0.0;

        /*
        |--------------------------------------------------------------------------
        | POSEBNI OFFSET ZA CHECKBOXOVE
        |--------------------------------------------------------------------------
        */
        $boxOffsetX = 0.0;
        $boxOffsetY = -0.2;

        /*
        |--------------------------------------------------------------------------
        | VELIČINA X
        |--------------------------------------------------------------------------
        */
        $boxFontSize = 6;
        $boxCellW = 2.0;
        $boxCellH = 2.0;

        $toArray = function ($value): array {
            if (is_array($value)) {
                return array_values(array_filter($value, fn ($item) => $item !== null && $item !== ''));
            }

            if ($value instanceof Collection) {
                return array_values(array_filter($value->toArray(), fn ($item) => $item !== null && $item !== ''));
            }

            if (blank($value)) {
                return [];
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return array_values(array_filter($decoded, fn ($item) => $item !== null && $item !== ''));
                }

                return [$value];
            }

            return (array) $value;
        };

        $has = function ($items, string $value) use ($toArray): bool {
            return in_array($value, $toArray($items), true);
        };

        $fmtDate = function ($value, string $format = 'd.m.Y.') {
            if (blank($value)) {
                return '';
            }

            try {
                return Carbon::parse($value)->format($format);
            } catch (\Throwable $e) {
                return (string) $value;
            }
        };

        $fmtDateTime = function ($value, string $format = 'd.m.Y. H:i') {
            if (blank($value)) {
                return '';
            }

            try {
                return Carbon::parse($value)->format($format);
            } catch (\Throwable $e) {
                return (string) $value;
            }
        };

        $fmtNum = function ($value, int $decimals = 2) {
            if ($value === null || $value === '') {
                return '';
            }

            return number_format((float) $value, $decimals, ',', '.');
        };

        $clean = function ($value) use ($defaultFont): string {
            if ($value === null) {
                return '';
            }

            if (is_bool($value)) {
                return $value ? 'Da' : 'Ne';
            }

            if (is_array($value)) {
                $value = implode(', ', array_filter($value, fn ($v) => $v !== null && $v !== ''));
            }

            $text = trim((string) $value);

            if ($text === '') {
                return '';
            }

            if ($defaultFont === 'Helvetica') {
                $converted = @iconv('UTF-8', 'windows-1250//TRANSLIT//IGNORE', $text);
                return $converted !== false ? $converted : $text;
            }

            return $text;
        };

        $writeLine = function (
            float $x,
            float $y,
            float $w,
            string|int|float|null $value,
            int $fontSize = 7,
            string $style = '',
            string $align = 'L'
        ) use ($pdf, $defaultFont, $clean, $offsetX, $offsetY) {
            $txt = $clean($value);

            if ($txt === '') {
                return;
            }

            $pdf->SetFont($defaultFont, $style, $fontSize);
            $pdf->SetXY($x + $offsetX, $y + $offsetY);
            $pdf->Cell($w, 3.5, $txt, 0, 0, $align);
        };

        $multiline = function (
            float $x,
            float $y,
            float $w,
            string|int|float|null $value,
            int $fontSize = 7,
            string $style = '',
            float $lineHeight = 3.2
        ) use ($pdf, $defaultFont, $clean, $offsetX, $offsetY) {
            $txt = $clean($value);

            if ($txt === '') {
                return;
            }

            $pdf->SetFont($defaultFont, $style, $fontSize);
            $pdf->SetXY($x + $offsetX, $y + $offsetY);
            $pdf->MultiCell($w, $lineHeight, $txt, 0, 'L');
        };

        $box = function (
            bool $checked,
            float $x,
            float $y,
            ?int $fontSize = null
        ) use (
            $pdf,
            $defaultFont,
            $offsetX,
            $offsetY,
            $boxOffsetX,
            $boxOffsetY,
            $boxFontSize,
            $boxCellW,
            $boxCellH
        ) {
            if (! $checked) {
                return;
            }

            $pdf->SetFont($defaultFont, 'B', $fontSize ?? $boxFontSize);
            $pdf->SetXY($x + $offsetX + $boxOffsetX, $y + $offsetY + $boxOffsetY);
            $pdf->Cell($boxCellW, $boxCellH, 'X', 0, 0, 'C');
        };

        $writeWrappedNoBreak = function (
            float $x,
            float $y,
            float $w,
            string|int|float|null $value,
            int $fontSize = 8,
            string $style = '',
            float $lineHeight = 3.8,
            int $maxLines = 2
        ) use ($pdf, $defaultFont, $clean, $offsetX, $offsetY) {
            $txt = $clean($value);

            if ($txt === '') {
                return;
            }

            $txt = preg_replace('/\s+/', ' ', $txt);
            $tokens = preg_split('/\s+/u', $txt, -1, PREG_SPLIT_NO_EMPTY);

            $pdf->SetFont($defaultFont, $style, $fontSize);

            $lines = [];
            $current = '';
            $allUsed = true;

            foreach ($tokens as $index => $token) {
                $candidate = $current === '' ? $token : $current . ' ' . $token;

                if ($pdf->GetStringWidth($candidate) <= $w) {
                    $current = $candidate;
                    continue;
                }

                if ($current !== '') {
                    $lines[] = $current;
                    $current = $token;
                } else {
                    $lines[] = $token;
                    $current = '';
                }

                if (count($lines) >= $maxLines) {
                    $allUsed = false;
                    break;
                }
            }

            if ($current !== '' && count($lines) < $maxLines) {
                $lines[] = $current;
            }

            if (! $allUsed && isset($lines[$maxLines - 1])) {
                while ($pdf->GetStringWidth($lines[$maxLines - 1] . '...') > $w && mb_strlen($lines[$maxLines - 1]) > 1) {
                    $lines[$maxLines - 1] = mb_substr($lines[$maxLines - 1], 0, -1);
                }

                $lines[$maxLines - 1] .= '...';
            }

            foreach ($lines as $index => $line) {
                $pdf->SetFont($defaultFont, $style, $fontSize);
                $pdf->SetXY($x + $offsetX, $y + $offsetY + ($index * $lineHeight));
                $pdf->Cell($w, $lineHeight, $line, 0, 0, 'L');
            }
        };

        /*
        |--------------------------------------------------------------------------
        | A - POŠILJKA OTPADA
        |--------------------------------------------------------------------------
        */

        // KLJUČNI BROJ po kućicama: 15 01 01 *
        $keyNumber = (string) ($record->waste_code_manual ?? '');
        $keyNumber = preg_replace('/\s+/', '', $keyNumber);
        $keyNumber = str_replace('*', '', $keyNumber);

        $pairs = str_split($keyNumber, 2);
        $normalized = implode('', array_slice($pairs, 0, 3)) . '*';
        $chars = preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        // malo više lijevo + bolji razmaci
        $keyNumberXs = [28.2, 33.5, 43, 48.6, 58.4, 63.2, 73];

        foreach ($keyNumberXs as $i => $x) {
            $char = $chars[$i] ?? '';
            if ($char !== '') {
                $writeLine($x, 35, 3.8, $char, 8, 'B', 'C');
            }
        }

        // BROJ PL-O malo veći i širi
        $writeLine(116.0, 29.6, 68, $record->document_number, 11, 'B', 'C');

        $box($has($record->waste_source_types, 'komunalni'), 114.5, 35.6);
        $box($has($record->waste_source_types, 'proizvodni'), 136.5, 35.6);
        $box($record->waste_kind === 'opasni', 165.3, 35.6);
        $box($record->waste_kind === 'neopasni', 187.5, 35.6);

        $hpY = 40.0;
        $hpXs = [
            'HP1'  => 38.3,
            'HP2'  => 48.6,
            'HP3'  => 58.8,
            'HP4'  => 69.1,
            'HP5'  => 79.4,
            'HP6'  => 89.8,
            'HP7'  => 100.0,
            'HP8'  => 110.2,
            'HP9'  => 120.6,
            'HP10' => 132.4,
            'HP11' => 144.2,
            'HP12' => 155.2,
            'HP13' => 166.2,
            'HP14' => 178.0,
            'HP15' => 189.5,
        ];

        foreach ($hpXs as $hp => $x) {
            $box($has($record->hazard_properties, $hp), $x, $hpY);
        }

        /*
        |--------------------------------------------------------------------------
        | FIZIKALNA SVOJSTVA
        |--------------------------------------------------------------------------
        */

        $box($has($record->physical_properties, 'prasina'),   41.4, 44.4);
        $box($has($record->physical_properties, 'kruto'),     54.5, 44.4);
        $box($has($record->physical_properties, 'pastozno'),  70.9, 44.4);
        $box($has($record->physical_properties, 'muljevito'), 87.7, 44.4);
        $box($has($record->physical_properties, 'tekucina'),  101.3, 44.4);
        $box($has($record->physical_properties, 'plinovito'), 117.0, 44.4);
        $box($has($record->physical_properties, 'ostalo'),    130.0, 44.4);

        if ($has($record->physical_properties, 'ostalo') && filled($record->physical_properties_other)) {
            $writeLine(145.0, 43.8, 20, $record->physical_properties_other, 7, '', 'L');
        }

        /*
        |--------------------------------------------------------------------------
        | PAKIRANJE OTPADA
        |--------------------------------------------------------------------------
        */

        $packY = 48.8;
        $packXs = [
            'rasuto'    => 42.7,
            'posude'    => 57.2,
            'kanta'     => 69.8,
            'kanister'  => 85.0,
            'kontejner' => 101.6,
            'bacva'     => 114.9,
            'kutija'    => 127.0,
            'vreca'     => 139.2,
            'ostalo'    => 152.2,
        ];

        foreach ($packXs as $pack => $x) {
            $box($has($record->packaging_types, $pack), $x, $packY);
        }

        // Ne ispisuj packaging_other da se ne pojavljuje "paket"
        $writeLine(176.8, 48.0, 14, $record->package_count, 7, 'B', 'C');

        $multiline(14.0, 52.1, 170, $record->waste_description, 10);
        $multiline(108.0, 67.0, 60, $record->municipal_origin_note, 10);

        /*
        |--------------------------------------------------------------------------
        | B - POŠILJATELJ
        |--------------------------------------------------------------------------
        */

        $writeLine(15.0, 79.8, 90, $record->sender_person_name, 9);
        $writeLine(18.0, 85.2, 90, $record->sender_oib, 9);
        $writeLine(32.0, 90.8, 90, $record->sender_nkd_code, 9);
        $writeLine(29.7, 96.2, 90, $record->sender_contact_person, 9);

        $writeWrappedNoBreak(30.0, 101.7, 74, $record->sender_contact_data, 8, '', 3.8, 2);

        /*
        |--------------------------------------------------------------------------
        | F - TOK OTPADA
        |--------------------------------------------------------------------------
        */

        $box($record->report_choice === 'da', 147.6, 81.0);
        $box($record->report_choice === 'ne', 156.0, 81.0);
        $box($record->purpose_choice === 'oporaba', 131.0, 87.4);
        $box($record->purpose_choice === 'zbrinjavanje', 154.8, 87.4);

        $writeLine(119.0, 92.5, 83, $record->dispatch_point, 9);
        $writeLine(119.0, 97.9, 83, $record->destination_point, 9);
        $writeLine(113.8, 103.8, 16, $fmtNum($record->quantity_m3, 3), 9, '', 'R');
        $writeLine(133.7, 103.8, 16, $fmtNum($record->quantity_kg, 2), 9, '', 'R');

        $box($record->quantity_determination_choice === 'vaganje', 173.7, 104.7);
        $box($record->quantity_determination_choice === 'procjena', 193.3, 104.7);

        $writeLine(126.0, 109.7, 40, $fmtDateTime($record->handover_datetime), 9);
        $writeLine(114.8, 115.5, 83, $record->handed_over_by, 9);

        /*
        |--------------------------------------------------------------------------
        | C - PRIJEVOZNIK
        |--------------------------------------------------------------------------
        */

        $writeLine(15.0, 126.5, 90, $record->carrier_name, 8);
        $writeLine(12.0, 131.9, 90, $record->carrier_oib, 8);
        $writeLine(35.0, 137.4, 90, $record->carrier_authorization, 8);
        $writeLine(30.0, 142.9, 90, $record->carrier_contact_person, 8);
        $multiline(30.0, 148.5, 90, $record->carrier_contact_data, 8);

        $box($has($record->transport_modes, 'cestovni'), 159.0, 122.4);
        $box($has($record->transport_modes, 'zeljeznicki'), 176.5, 122.4);
        $box($has($record->transport_modes, 'morski'), 189.9, 122.4);
        $box($has($record->transport_modes, 'zracni'), 158.5, 126.7);
        $box($has($record->transport_modes, 'unutarnji_plovni_put'), 193.8, 126.7);

        $writeLine(136.0, 131.8, 83, $record->carrier_vehicle_registration, 8);
        $writeLine(170.2, 137.0, 83, $record->carrier_taken_over_by, 8);
        $writeLine(127.0, 142.4, 40, $fmtDateTime($record->carrier_taken_over_at), 8);
        $writeLine(170.2, 148.0, 83, $record->carrier_delivered_by, 8);

        /*
        |--------------------------------------------------------------------------
        | D - PRIMATELJ
        |--------------------------------------------------------------------------
        */

        $writeLine(15.0, 159.0, 90, $record->receiver_name, 8);
        $writeLine(12.0, 164.0, 90, $record->receiver_oib, 8);
        $writeLine(41.0, 169.3, 90, $record->receiver_authorization, 8);
        $writeLine(30.0, 174.7, 90, $record->receiver_contact_person, 8);
        $multiline(30.0, 180.7, 90, $record->receiver_contact_data, 8);

        $writeLine(170.2, 161.0, 83, $record->receiver_taken_over_by, 8);
        $writeLine(130.0, 172.5, 40, $fmtDateTime($record->receiver_weighing_time), 8);
        $writeLine(133.0, 178.0, 20, $fmtNum($record->receiver_measured_quantity_kg, 2), 8, '', 'R');

        /*
        |--------------------------------------------------------------------------
        | E - POSREDNIK / TRGOVAC
        |--------------------------------------------------------------------------
        */

        $writeLine(15.0, 192.0, 90, $record->trader_name, 8);
        $writeLine(12.0, 197.2, 90, $record->trader_oib, 8);
        $writeLine(19.0, 202.8, 90, $record->trader_authorization, 8);
        $writeLine(30.0, 208.2, 90, $record->trader_contact_person, 8);
        $multiline(30.0, 213.8, 90, $record->trader_contact_data, 8);

        /*
        |--------------------------------------------------------------------------
        | G - OBRAĐIVAČ
        |--------------------------------------------------------------------------
        */

        $writeLine(112.5, 192.0, 83, $record->processor_name, 8);
        $writeLine(111.0, 197.2, 83, $record->processor_oib, 8);
        $writeLine(130.0, 202.8, 83, $record->processor_authorization, 8);
        $writeLine(137.0, 208.0, 40, $fmtDate($record->processing_completed_at), 8);
        $writeLine(130.0, 213.2, 83, $record->final_processing_method, 8);
        $writeLine(175.0, 219.0, 83, $record->processor_confirmed_by, 8);

        /*
        |--------------------------------------------------------------------------
        | H - NAPOMENE I PRILOZI
        |--------------------------------------------------------------------------
        */

        $multiline(7.0, 229.5, 194, $record->note, 8);

        $attachments = collect($toArray($record->attachments))
            ->map(fn ($file) => basename((string) $file))
            ->implode(', ');

        $multiline(7.8, 269.0, 194, $attachments, 7);

        $tempDir = storage_path('app/temp');

        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $filePath = $tempDir . '/prateci-list-' . $record->id . '-' . now()->timestamp . '.pdf';

        $pdf->Output($filePath, 'F');

        return $filePath;
    }
}