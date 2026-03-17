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
        | Sve ti je bilo pomaknuto ulijevo.
        | Ovdje samo podešavaš cijeli obrazac.
        */
        $offsetX = 3.2;
        $offsetY = 0.0;

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
            int $fontSize = 9
        ) use ($pdf, $defaultFont, $offsetX, $offsetY) {
            if (! $checked) {
                return;
            }

            $pdf->SetFont($defaultFont, 'B', $fontSize);
            $pdf->SetXY($x + $offsetX, $y + $offsetY);
            $pdf->Cell(3, 3, 'X', 0, 0, 'C');
        };

        /*
        |--------------------------------------------------------------------------
        | A - POŠILJKA OTPADA
        |--------------------------------------------------------------------------
        */

        $writeLine(35, 35, 28, $record->waste_code_manual, 8, 'B', 'L');
        $writeLine(120, 30, 43, $record->document_number, 8, 'B', 'C');

        $box($has($record->waste_source_types, 'komunalni'), 114, 35);
        $box($has($record->waste_source_types, 'proizvodni'), 136, 35);
        $box($record->waste_kind === 'opasni', 155, 35);
        $box($record->waste_kind === 'neopasni', 187, 35);

        $hpY = 40.0;
        $hpXs = [
            'HP1'  => 31.7,
            'HP2'  => 42.6,
            'HP3'  => 53.5,
            'HP4'  => 64.3,
            'HP5'  => 75.2,
            'HP6'  => 86.2,
            'HP7'  => 97.1,
            'HP8'  => 108.0,
            'HP9'  => 118.9,
            'HP10' => 130.0,
            'HP11' => 141.6,
            'HP12' => 152.7,
        ];

        foreach ($hpXs as $hp => $x) {
            $box($has($record->hazard_properties, $hp), $x, $hpY);
        }

        $box($has($record->hazard_properties, 'HP13'), 75.2, 40);
        $box($has($record->hazard_properties, 'HP14'), 119.1, 40);
        $box($has($record->hazard_properties, 'HP15'), 163.3, 40);

        $box($has($record->physical_properties, 'kruto'), 45, 45);
        $box($has($record->physical_properties, 'muljevito'), 65, 45);
        $box($has($record->physical_properties, 'prasina'), 40, 45);
        $box($has($record->physical_properties, 'tekucina'), 110, 45);
        $box($has($record->physical_properties, 'plinovito'), 120, 45);
        $box($has($record->physical_properties, 'ostalo'), 140, 45);

        if ($has($record->physical_properties, 'ostalo')) {
            $writeLine(188.8, 45, 12, $record->physical_properties_other, 8, '', 'L');
        }

        $packY = 48;
        $packXs = [
            'rasuto'    => 40,
            'posude'    => 50,
            'kanta'     => 60,
            'kutija'    => 70,
            'kanister'  => 80,
            'kontejner' => 90,
            'bacva'     => 100,
            'vreca'     => 110,
            'ostalo'    => 120,
        ];

        foreach ($packXs as $pack => $x) {
            $box($has($record->packaging_types, $pack), $x, $packY);
        }

        if ($has($record->packaging_types, 'ostalo')) {
            $writeLine(111.8, 48, 18, $record->packaging_other, 6, '', 'L');
        }

        $writeLine(176.8, 48, 14, $record->package_count, 7, 'B', 'C');

        $multiline(20, 52.1, 194, $record->waste_description, 10);
        $multiline(120, 66, 194, $record->municipal_origin_note, 10);

        /*
        |--------------------------------------------------------------------------
        | B - POŠILJATELJ
        |--------------------------------------------------------------------------
        */

        $writeLine(15, 79.5, 90, $record->sender_person_name, 9);
        $writeLine(20, 85.2, 90, $record->sender_oib, 9);
        $writeLine(32, 90.5, 90, $record->sender_nkd_code, 9);
        $writeLine(30, 96, 90, $record->sender_contact_person, 9);
        $multiline(30, 101.6, 90, $record->sender_contact_data, 9);

        /*
        |--------------------------------------------------------------------------
        | F - TOK OTPADA
        |--------------------------------------------------------------------------
        */

        $writeLine(112.2, 80.8, 83, $record->waste_owner_at_handover, 9);

        $box($record->report_choice === 'da', 135.0, 80.8);
        $box($record->report_choice === 'ne', 145, 80.8);
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