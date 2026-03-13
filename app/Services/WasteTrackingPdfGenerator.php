<?php

namespace App\Services;

use App\Models\WasteTrackingForm;
use Illuminate\Support\Carbon;
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

        $pdf->SetFont('Helvetica', '', 7);

        $text = function (
            float $x,
            float $y,
            string|int|float|null $value,
            float $w = 0,
            int $fontSize = 7,
            string $style = '',
            string $align = 'L'
        ) use ($pdf) {
            if ($value === null || $value === '') {
                return;
            }

            $pdf->SetFont('Helvetica', $style, $fontSize);
            $pdf->SetXY($x, $y);

            $txt = (string) $value;

            if ($w > 0) {
                $pdf->Cell($w, 3.5, $txt, 0, 0, $align);
            } else {
                $pdf->Write(3.5, $txt);
            }
        };

        $box = function (bool $checked, float $x, float $y, int $fontSize = 9) use ($pdf) {
            if (! $checked) {
                return;
            }

            $pdf->SetFont('Helvetica', 'B', $fontSize);
            $pdf->SetXY($x, $y);
            $pdf->Cell(3, 3, 'X', 0, 0, 'C');
        };

        $multiline = function (
            float $x,
            float $y,
            float $w,
            string|int|float|null $value,
            int $fontSize = 7,
            string $style = ''
        ) use ($pdf) {
            if ($value === null || $value === '') {
                return;
            }

            $pdf->SetFont('Helvetica', $style, $fontSize);
            $pdf->SetXY($x, $y);
            $pdf->MultiCell($w, 3.2, (string) $value, 0, 'L');
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

        $has = fn (?array $items, string $value) => in_array($value, $items ?? [], true);

        // A - POŠILJKA OTPADA
        $text(7.5, 24.5, $record->waste_code_manual, 28, 7, 'B');
        $text(84.5, 24.5, $record->document_number, 43, 7, 'B', 'C');

        $box($has($record->waste_source_types, 'komunalni'), 149.5, 24.0);
        $box($has($record->waste_source_types, 'proizvodni'), 173.7, 24.0);
        $box($record->waste_kind === 'opasni', 189.0, 24.0);
        $box($record->waste_kind === 'neopasni', 200.5, 24.0);

        $hpY = 29.2;
        $hpXs = [
            'HP1' => 31.7, 'HP2' => 42.6, 'HP3' => 53.4, 'HP4' => 64.4,
            'HP5' => 75.2, 'HP6' => 86.2, 'HP7' => 97.1, 'HP8' => 108.0,
            'HP9' => 118.8, 'HP10' => 130.0, 'HP11' => 141.6, 'HP12' => 152.7,
        ];

        foreach ($hpXs as $hp => $x) {
            $box($has($record->hazard_properties, $hp), $x, $hpY);
        }

        $box($has($record->hazard_properties, 'HP13'), 75.3, 34.4);
        $box($has($record->hazard_properties, 'HP14'), 119.2, 34.4);
        $box($has($record->hazard_properties, 'HP15'), 163.4, 34.4);

        $box($has($record->physical_properties, 'kruto'), 31.8, 39.7);
        $box($has($record->physical_properties, 'muljevito'), 75.4, 39.7);
        $box($has($record->physical_properties, 'prasina'), 119.2, 39.7);
        $box($has($record->physical_properties, 'tekucina'), 141.4, 39.7);
        $box($has($record->physical_properties, 'plinovito'), 163.5, 39.7);
        $box($has($record->physical_properties, 'ostalo'), 185.5, 39.7);

        if ($has($record->physical_properties, 'ostalo')) {
            $text(189.0, 39.5, $record->physical_properties_other, 12, 6);
        }

        $packY = 45.0;
        $packXs = [
            'rasuto' => 20.7,
            'posude' => 31.7,
            'kanta' => 42.5,
            'kutija' => 53.5,
            'kanister' => 64.5,
            'kontejner' => 75.4,
            'bacva' => 86.5,
            'vreca' => 97.4,
            'ostalo' => 108.4,
        ];

        foreach ($packXs as $pack => $x) {
            $box($has($record->packaging_types, $pack), $x, $packY);
        }

        if ($has($record->packaging_types, 'ostalo')) {
            $text(111.8, 45.0, $record->packaging_other, 10, 6);
        }

        $text(177.0, 45.0, $record->package_count, 14, 7, 'B', 'C');

        $multiline(7.5, 52.5, 194, $record->waste_description, 7);
        $multiline(7.5, 63.8, 194, $record->municipal_origin_note, 7);

        // B - POŠILJATELJ
        $text(7.5, 81.0, $record->sender_person_name, 70, 7);
        $text(7.5, 86.1, $record->sender_oib, 70, 7);
        $text(7.5, 91.3, $record->sender_nkd_code, 70, 7);
        $text(7.5, 96.5, $record->sender_contact_person, 70, 7);
        $text(7.5, 101.7, $record->sender_contact_data, 70, 7);

        // F - TOK OTPADA
        $text(112.2, 81.0, $record->waste_owner_at_handover, 83, 7);

        $box($record->report_choice === 'da', 145.1, 86.0);
        $box($record->report_choice === 'ne', 157.7, 86.0);
        $box($record->purpose_choice === 'oporaba', 177.6, 86.0);
        $box($record->purpose_choice === 'zbrinjavanje', 196.6, 86.0);

        $text(112.2, 91.3, $record->dispatch_point, 83, 7);
        $text(112.2, 96.5, $record->destination_point, 83, 7);
        $text(144.0, 101.7, $fmtNum($record->quantity_m3, 3), 16, 7);
        $text(169.8, 101.7, $fmtNum($record->quantity_kg, 2), 16, 7);

        $box($record->quantity_determination_choice === 'vaganje', 187.8, 101.4);
        $box($record->quantity_determination_choice === 'procjena', 200.3, 101.4);

        $text(112.2, 106.9, $fmtDateTime($record->handover_datetime), 40, 7);
        $text(112.2, 112.0, $record->handed_over_by, 83, 7);

        // C - PRIJEVOZNIK
        $text(7.5, 129.8, $record->carrier_name, 70, 7);
        $text(7.5, 135.0, $record->carrier_oib, 70, 7);
        $text(7.5, 140.2, $record->carrier_authorization, 70, 7);
        $text(7.5, 145.4, $record->carrier_contact_person, 70, 7);
        $text(7.5, 150.6, $record->carrier_contact_data, 70, 7);

        $box($has($record->transport_modes, 'cestovni'), 170.0, 129.3);
        $box($has($record->transport_modes, 'zeljeznicki'), 181.5, 129.3);
        $box($has($record->transport_modes, 'morski'), 193.2, 129.3);
        $box($has($record->transport_modes, 'zracni'), 170.0, 134.2);
        $box($has($record->transport_modes, 'unutarnji_plovni_put'), 193.2, 134.2);

        $text(112.2, 140.2, $record->carrier_vehicle_registration, 83, 7);
        $text(112.2, 145.4, $record->carrier_taken_over_by, 83, 7);
        $text(112.2, 150.6, $fmtDateTime($record->carrier_taken_over_at), 40, 7);
        $text(112.2, 155.8, $record->carrier_delivered_by, 83, 7);

        // D - PRIMATELJ
        $text(7.5, 173.0, $record->receiver_name, 70, 7);
        $text(7.5, 178.2, $record->receiver_oib, 70, 7);
        $text(7.5, 183.4, $record->receiver_authorization, 70, 7);
        $text(7.5, 188.6, $record->receiver_contact_person, 70, 7);
        $text(7.5, 193.8, $record->receiver_contact_data, 70, 7);

        $text(112.2, 178.2, $record->receiver_taken_over_by, 83, 7);
        $text(112.2, 188.6, $fmtDateTime($record->receiver_weighing_time), 40, 7);
        $text(145.0, 193.8, $fmtNum($record->receiver_measured_quantity_kg, 2), 20, 7);

        // E - POSREDNIK / TRGOVAC
        $text(7.5, 220.0, $record->trader_name, 70, 7);
        $text(7.5, 225.2, $record->trader_oib, 70, 7);
        $text(7.5, 230.4, $record->trader_authorization, 70, 7);
        $text(7.5, 235.6, $record->trader_contact_person, 70, 7);
        $text(7.5, 240.8, $record->trader_contact_data, 70, 7);

        // G - OBRAĐIVAČ
        $text(112.2, 220.0, $record->processor_name, 83, 7);
        $text(112.2, 225.2, $record->processor_oib, 83, 7);
        $text(112.2, 230.4, $record->processor_authorization, 83, 7);
        $text(112.2, 235.6, $fmtDate($record->processing_completed_at), 40, 7);
        $text(112.2, 240.8, $record->final_processing_method, 83, 7);
        $text(112.2, 246.0, $record->processor_confirmed_by, 83, 7);

        // H - NAPOMENE I PRILOZI
        $multiline(7.5, 256.0, 194, $record->note, 7);
        $multiline(7.5, 269.0, 194, collect($record->attachments ?? [])->map(fn ($f) => basename($f))->implode(', '), 7);

        return (string) $pdf->Output('S');
    }
}