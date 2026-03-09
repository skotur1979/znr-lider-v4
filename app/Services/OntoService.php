<?php

namespace App\Services;

use App\Models\OntoEntry;
use App\Models\OntoRecord;
use App\Models\WasteTrackingForm;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OntoService
{
    public function addInput(
        OntoRecord $ontoRecord,
        string $date,
        float $quantityKg,
        ?string $method = 'UVL',
        ?string $note = null
    ): OntoEntry {
        return DB::transaction(function () use ($ontoRecord, $date, $quantityKg, $method, $note) {
            $ontoRecord->refresh();

            $nextNo = ((int) $ontoRecord->entries()->max('entry_no')) + 1;
            $newBalance = (float) $ontoRecord->current_balance_kg + $quantityKg;

            $entry = $ontoRecord->entries()->create([
                'entry_no' => $nextNo,
                'entry_date' => $date,
                'entry_type' => 'input',
                'input_kg' => $quantityKg,
                'output_kg' => 0,
                'method' => $method,
                'balance_after_kg' => $newBalance,
                'note' => $note,
            ]);

            $ontoRecord->update([
                'current_balance_kg' => $newBalance,
            ]);

            return $entry;
        });
    }

    public function addOutput(
        OntoRecord $ontoRecord,
        string $date,
        float $quantityKg,
        ?string $method = 'IP',
        ?string $note = null,
        ?int $trackingFormId = null
    ): OntoEntry {
        return DB::transaction(function () use ($ontoRecord, $date, $quantityKg, $method, $note, $trackingFormId) {
            $ontoRecord->refresh();

            if ($quantityKg <= 0) {
                throw new RuntimeException('Količina za izlaz mora biti veća od 0.');
            }

            if ($quantityKg > (float) $ontoRecord->current_balance_kg) {
                throw new RuntimeException('Nema dovoljno otpada na stanju za izlaz.');
            }

            $nextNo = ((int) $ontoRecord->entries()->max('entry_no')) + 1;
            $newBalance = (float) $ontoRecord->current_balance_kg - $quantityKg;

            $entry = $ontoRecord->entries()->create([
                'entry_no' => $nextNo,
                'entry_date' => $date,
                'entry_type' => 'output',
                'input_kg' => 0,
                'output_kg' => $quantityKg,
                'method' => $method,
                'balance_after_kg' => $newBalance,
                'note' => $note,
                'waste_tracking_form_id' => $trackingFormId,
            ]);

            $ontoRecord->update([
                'current_balance_kg' => $newBalance,
            ]);

            return $entry;
        });
    }

    public function lockTrackingForm(WasteTrackingForm $trackingForm): void
    {
        DB::transaction(function () use ($trackingForm) {
            $trackingForm->refresh();

            if ($trackingForm->isLocked()) {
                throw new RuntimeException('Prateći list je već zaključen.');
            }

            $ontoRecord = $trackingForm->ontoRecord;

            if (! $ontoRecord) {
                throw new RuntimeException('Prateći list nema povezan ONTO zapis.');
            }

            if ((float) $trackingForm->quantity_kg <= 0) {
                throw new RuntimeException('Količina na pratećem listu mora biti veća od 0.');
            }

            if ((float) $trackingForm->quantity_kg > (float) $ontoRecord->current_balance_kg) {
                throw new RuntimeException('Količina na pratećem listu je veća od količine na stanju.');
            }

            $method = filled($trackingForm->document_number)
                ? 'IP-' . $trackingForm->document_number
                : 'IP';

            $this->addOutput(
                $ontoRecord,
                $trackingForm->handover_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                (float) $trackingForm->quantity_kg,
                $method,
                $trackingForm->note,
                $trackingForm->id
            );

            $trackingForm->update([
                'status' => 'locked',
                'locked_at' => now(),
            ]);
        });
    }
}
