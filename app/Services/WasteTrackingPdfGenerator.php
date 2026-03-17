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
            'HP1'  => 38,
            'HP2'  => 47.8,
            'HP3'  => 58.5,
            'HP4'  => 68.8,
            'HP5'  => 79.3,
            'HP6'  => 86.2,
            'HP7'  => 100.0,
            'HP8'  => 108.0,
            'HP9'  => 115.9,
            'HP10' => 130.0,
            'HP11' => 142.6,
            'HP12' => 152.7,
            'HP13' => 160.8,
            'HP14' => 180.0,
            'HP15' => 189.2,
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
            'rasuto'    => 42.0,
            'posude'    => 56.5,
            'kanta'     => 69.8,
            'kutija'    => 144.0,
            'kanister'  => 85.0,
            'kontejner' => 113.0,
            'bacva'     => 132.0,
            'vreca'     => 140.0,
            'ostalo'    => 153.0,
        ];

        foreach ($packXs as $pack => $x) {
            $box($has($record->packaging_types, $pack), $x, $packY);
        }

        if ($has($record->packaging_types, 'ostalo')) {
            $writeLine(126.0, 48.0, 20, $record->packaging_other, 6, '', 'L');
        }

        $writeLine(176.8, 48.0, 14, $record->package_count, 7, 'B', 'C');

        $multiline(20.0, 52.1, 170, $record->waste_description, 10);
        $multiline(120.0, 66.0, 60, $record->municipal_origin_note, 10);

        /*
        |--------------------------------------------------------------------------
        | B - POŠILJATELJ
        |--------------------------------------------------------------------------
        */

        $writeLine(15.0, 79.5, 90, $record->sender_person_name, 9);
        $writeLine(20.0, 85.2, 90, $record->sender_oib, 9);
        $writeLine(32.0, 90.5, 90, $record->sender_nkd_code, 9);
        $writeLine(30.0, 96.0, 90, $record->sender_contact_person, 9);
        $multiline(30.0, 101.6, 90, $record->sender_contact_data, 9);

        /*
        |--------------------------------------------------------------------------
        | F - TOK OTPADA
        |--------------------------------------------------------------------------
        */

        $writeLine(112.2, 80.8, 83, $record->waste_owner_at_handover, 9);

        $box($record->report_choice === 'da', 135.0, 80.8);
        $box($record->report_choice === 'ne', 145.0, 80.8);
        $box($record->purpose_choice === 'oporaba', 177.5, 85.8);
        $box($record->purpose_choice === 'zbrinjavanje', 196.4, 85.8);

        $writeLine(112.2, 91.2, 83, $record->dispatch_point, 7);
        $writeLine(112.2, 96.4, 83, $record->destination_point, 7);
        $writeLine(144.0, 101.5, 16, $fmtNum($record->quantity_m3, 3), 7, '', 'R');
        $writeLine(169.8, 101.5, 16, $fmtNum($record->quantity_kg, 2), 7, '', 'R');

        $box($record->quantity_determination_choice === 'vaganje', 187.8, 101.2);
        $box($record->quantity_determination_choice === 'procjena', 200.2, 101.2);

        $writeLine(112.2, 106.7, 40, $fmtDateTime($record->handover_datetime), 7);
        $writeLine(112.2, 111.9, 83, $record->handed_over_by, 7);

        /*
        |--------------------------------------------------------------------------
        | C - PRIJEVOZNIK
        |--------------------------------------------------------------------------
        */

        $writeLine(7.8, 129.6, 90, $record->carrier_name, 7);
        $writeLine(7.8, 134.8, 90, $record->carrier_oib, 7);
        $writeLine(7.8, 140.0, 90, $record->carrier_authorization, 7);
        $writeLine(7.8, 145.2, 90, $record->carrier_contact_person, 7);
        $multiline(7.8, 150.2, 90, $record->carrier_contact_data, 7);

        $box($has($record->transport_modes, 'cestovni'), 170.0, 129.1);
        $box($has($record->transport_modes, 'zeljeznicki'), 181.5, 129.1);
        $box($has($record->transport_modes, 'morski'), 193.1, 129.1);
        $box($has($record->transport_modes, 'zracni'), 170.0, 134.0);
        $box($has($record->transport_modes, 'unutarnji_plovni_put'), 193.1, 134.0);

        $writeLine(112.2, 140.0, 83, $record->carrier_vehicle_registration, 7);
        $writeLine(112.2, 145.2, 83, $record->carrier_taken_over_by, 7);
        $writeLine(112.2, 150.4, 40, $fmtDateTime($record->carrier_taken_over_at), 7);
        $writeLine(112.2, 155.6, 83, $record->carrier_delivered_by, 7);

        /*
        |--------------------------------------------------------------------------
        | D - PRIMATELJ
        |--------------------------------------------------------------------------
        */

        $writeLine(7.8, 172.8, 90, $record->receiver_name, 7);
        $writeLine(7.8, 178.0, 90, $record->receiver_oib, 7);
        $writeLine(7.8, 183.2, 90, $record->receiver_authorization, 7);
        $writeLine(7.8, 188.4, 90, $record->receiver_contact_person, 7);
        $multiline(7.8, 193.4, 90, $record->receiver_contact_data, 7);

        $writeLine(112.2, 178.0, 83, $record->receiver_taken_over_by, 7);
        $writeLine(112.2, 188.4, 40, $fmtDateTime($record->receiver_weighing_time), 7);
        $writeLine(145.0, 193.6, 20, $fmtNum($record->receiver_measured_quantity_kg, 2), 7, '', 'R');

        /*
        |--------------------------------------------------------------------------
        | E - POSREDNIK / TRGOVAC
        |--------------------------------------------------------------------------
        */

        $writeLine(7.8, 219.8, 90, $record->trader_name, 7);
        $writeLine(7.8, 225.0, 90, $record->trader_oib, 7);
        $writeLine(7.8, 230.2, 90, $record->trader_authorization, 7);
        $writeLine(7.8, 235.4, 90, $record->trader_contact_person, 7);
        $multiline(7.8, 240.4, 90, $record->trader_contact_data, 7);

        /*
        |--------------------------------------------------------------------------
        | G - OBRAĐIVAČ
        |--------------------------------------------------------------------------
        */

        $writeLine(112.2, 219.8, 83, $record->processor_name, 7);
        $writeLine(112.2, 225.0, 83, $record->processor_oib, 7);
        $writeLine(112.2, 230.2, 83, $record->processor_authorization, 7);
        $writeLine(112.2, 235.4, 40, $fmtDate($record->processing_completed_at), 7);
        $writeLine(112.2, 240.6, 83, $record->final_processing_method, 7);
        $writeLine(112.2, 245.8, 83, $record->processor_confirmed_by, 7);

        /*
        |--------------------------------------------------------------------------
        | H - NAPOMENE I PRILOZI
        |--------------------------------------------------------------------------
        */

        $multiline(7.8, 255.8, 194, $record->note, 7);

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