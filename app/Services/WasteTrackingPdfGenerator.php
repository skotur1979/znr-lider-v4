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

        $defaultFont = 'Helvetica';
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
        | Njega diraj ako treba samo X malo lijevo/desno/gore/dolje.
        */
        $boxOffsetX = 0.0;
        $boxOffsetY = -0.2;

        /*
        |--------------------------------------------------------------------------
        | VELIČINA X
        |--------------------------------------------------------------------------
        */
        $boxFontSize = 6;     // prije 9
        $boxCellW = 2.2;      // prije 3
        $boxCellH = 2.2;      // prije 3

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

        $clean = function ($value): string {
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

            $converted = @iconv('UTF-8', 'windows-1250//TRANSLIT//IGNORE', $text);

            return $converted !== false ? $converted : $text;
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

        /*
        |--------------------------------------------------------------------------
        | A - POŠILJKA OTPADA
        |--------------------------------------------------------------------------
        */

        $writeLine(35.0, 35.0, 28, $record->waste_code_manual, 8, 'B', 'L');
        $writeLine(120.0, 30.0, 43, $record->document_number, 8, 'B', 'C');

        $box($has($record->waste_source_types, 'komunalni'), 114.5, 35.6);
        $box($has($record->waste_source_types, 'proizvodni'), 136.5, 35.6);
        $box($record->waste_kind === 'opasni', 155.0, 35.6);
        $box($record->waste_kind === 'neopasni', 187.5, 35.6);

        $hpY = 40.0;
        $hpXs = [
            'HP1'  => 38.1,
            'HP2'  => 48.5,
            'HP3'  => 58.5,
            'HP4'  => 68.8,
            'HP5'  => 79.3,
            'HP6'  => 90.0,
            'HP7'  => 100.0,
            'HP8'  => 110.0,
            'HP9'  => 120.9,
            'HP10' => 132.0,
            'HP11' => 144.0,
            'HP12' => 155.0,
            'HP13' => 166.0,
            'HP14' => 178.0,
            'HP15' => 189.3,
        ];

        foreach ($hpXs as $hp => $x) {
            $box($has($record->hazard_properties, $hp), $x, $hpY);
        }

        /*
        |--------------------------------------------------------------------------
        | FIZIKALNA SVOJSTVA
        |--------------------------------------------------------------------------
        */

        $box($has($record->physical_properties, 'prasina'),    41.0, 44.6);
        $box($has($record->physical_properties, 'kruto'),      54.5, 44.6);
        $box($has($record->physical_properties, 'pastozno'),   70.0, 44.6);
        $box($has($record->physical_properties, 'muljevito'),  88.0, 44.6);
        $box($has($record->physical_properties, 'tekucina'),   101.0, 44.6);
        $box($has($record->physical_properties, 'plinovito'),  117.0, 44.6);
        $box($has($record->physical_properties, 'ostalo'),     130.0, 44.6);

        if ($has($record->physical_properties, 'ostalo')) {
            $writeLine(145, 44.0, 12, $record->physical_properties_other, 7, '', 'L');
        }

        /*
        |--------------------------------------------------------------------------
        | PAKIRANJE OTPADA
        |--------------------------------------------------------------------------
        */

        $packY = 48.8;
        $packXs = [
            'rasuto'    => 43.0,
            'posude'    => 57.0,
            'kanta'     => 69.8,
            'kutija'    => 128.0,
            'kanister'  => 85.0,
            'kontejner' => 100.0,
            'bacva'     => 115.0,
            'vreca'     => 135.0,
            'ostalo'    => 151.0,
        ];

        foreach ($packXs as $pack => $x) {
            $box($has($record->packaging_types, $pack), $x, $packY);
        }

        if ($has($record->packaging_types, 'ostalo')) {
            $writeLine(126.0, 48.0, 20, $record->packaging_other, 6, '', 'L');
        }

        $writeLine(176.8, 48.0, 14, $record->package_count, 7, 'B', 'C');

        $multiline(20.0, 52.1, 170, $record->waste_description, 10);
        $multiline(110.0, 67.0, 60, $record->municipal_origin_note, 10);

        /*
        |--------------------------------------------------------------------------
        | B - POŠILJATELJ
        |--------------------------------------------------------------------------
        */

        $writeLine(15.0,79.8, 90, $record->sender_person_name, 9);
        $writeLine(18.0, 85.2, 90, $record->sender_oib, 9);
        $writeLine(32.0, 90.8, 90, $record->sender_nkd_code, 9);
        $writeLine(29.7, 96.2, 90, $record->sender_contact_person, 9);
        $multiline(30.0, 101.7, 90, $record->sender_contact_data, 9);

        /*
        |--------------------------------------------------------------------------
        | F - TOK OTPADA
        |--------------------------------------------------------------------------
        */

        //$writeLine(112.2, 80.8, 83, $record->waste_owner_at_handover, 9);

        $box($record->report_choice === 'da', 147.0, 81);
        $box($record->report_choice === 'ne', 154.0, 81);
        $box($record->purpose_choice === 'oporaba', 139, 87);
        $box($record->purpose_choice === 'zbrinjavanje', 153, 87);

        $writeLine(119, 92.5, 83, $record->dispatch_point, 9);
        $writeLine(119, 97.9, 83, $record->destination_point, 9);
        $writeLine(113.8, 103.8, 16, $fmtNum($record->quantity_m3, 3), 9, '', 'R');
        $writeLine(133.7, 103.8, 16, $fmtNum($record->quantity_kg, 2), 9, '', 'R');

        $box($record->quantity_determination_choice === 'vaganje', 174, 104.5);
        $box($record->quantity_determination_choice === 'procjena', 185, 104.5);

        $writeLine(126, 109.5, 40, $fmtDateTime($record->handover_datetime), 9);
        $writeLine(114.8, 115, 83, $record->handed_over_by, 9);

        /*
        |--------------------------------------------------------------------------
        | C - PRIJEVOZNIK
        |--------------------------------------------------------------------------
        */

        $writeLine(15, 126.3, 90, $record->carrier_name, 8);
        $writeLine(12, 131.9, 90, $record->carrier_oib, 8);
        $writeLine(35, 137.4, 90, $record->carrier_authorization, 8);
        $writeLine(30, 142.9, 90, $record->carrier_contact_person, 8);
        $multiline(30, 148.5, 90, $record->carrier_contact_data, 8);

        $box($has($record->transport_modes, 'cestovni'), 159.0, 122.1);
        $box($has($record->transport_modes, 'zeljeznicki'), 176.0, 122.1);
        $box($has($record->transport_modes, 'morski'), 190, 122.1);
        $box($has($record->transport_modes, 'zracni'), 158.5, 126.8);
        $box($has($record->transport_modes, 'unutarnji_plovni_put'), 193.8, 126.8);

        $writeLine(136,132.0, 83, $record->carrier_vehicle_registration, 8);
        $writeLine(120.2, 137, 83, $record->carrier_taken_over_by, 8);
        $writeLine(127, 142.2, 40, $fmtDateTime($record->carrier_taken_over_at), 8);
        $writeLine(118.2,148, 83, $record->carrier_delivered_by, 8);

        /*
        |--------------------------------------------------------------------------
        | D - PRIMATELJ
        |--------------------------------------------------------------------------
        */

        $writeLine(15, 159.5, 90, $record->receiver_name, 8);
        $writeLine(12, 164, 90, $record->receiver_oib, 8);
        $writeLine(41, 169, 90, $record->receiver_authorization, 8);
        $writeLine(30, 174, 90, $record->receiver_contact_person, 8);
        $multiline(30, 180, 90, $record->receiver_contact_data, 8);

        $writeLine(170.2, 161.0, 83, $record->receiver_taken_over_by, 8);
        $writeLine(130, 172.5, 40, $fmtDateTime($record->receiver_weighing_time), 8);
        $writeLine(133.0, 178, 20, $fmtNum($record->receiver_measured_quantity_kg, 2), 8, '', 'R');

        /*
        |--------------------------------------------------------------------------
        | E - POSREDNIK / TRGOVAC
        |--------------------------------------------------------------------------
        */

        $writeLine(15, 192, 90, $record->trader_name, 8);
        $writeLine(12, 197.2, 90, $record->trader_oib, 8);
        $writeLine(20, 202.5, 90, $record->trader_authorization, 8);
        $writeLine(30, 208, 90, $record->trader_contact_person, 8);
        $multiline(30, 213, 90, $record->trader_contact_data, 8);

        /*
        |--------------------------------------------------------------------------
        | G - OBRAĐIVAČ
        |--------------------------------------------------------------------------
        */

        $writeLine(113, 192, 83, $record->processor_name, 8);
        $writeLine(111.5, 198, 83, $record->processor_oib, 8);
        $writeLine(130, 203, 83, $record->processor_authorization, 8);
        $writeLine(136, 209, 40, $fmtDate($record->processing_completed_at), 8);
        $writeLine(130, 214, 83, $record->final_processing_method, 8);
        $writeLine(175, 219, 83, $record->processor_confirmed_by, 8);

        /*
        |--------------------------------------------------------------------------
        | H - NAPOMENE I PRILOZI
        |--------------------------------------------------------------------------
        */

        $multiline(7.8, 229, 194, $record->note, 8);

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