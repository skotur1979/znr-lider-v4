<?php

namespace App\Services;

use App\Models\OntoEntry;
use App\Models\OntoRecord;
use App\Models\WasteTrackingForm;
use Illuminate\Support\Facades\Auth;
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
        if ($quantityKg <= 0) {
            throw new RuntimeException('Količina za ulaz mora biti veća od 0.');
        }

        return DB::transaction(function () use (
            $ontoRecord,
            $date,
            $quantityKg,
            $method,
            $note
        ) {
            $ontoRecord = $this->lockOntoRecord($ontoRecord);

            $this->authorizeOntoRecord($ontoRecord);

            $nextNo = ((int) $ontoRecord->entries()->max('entry_no')) + 1;

            $currentBalance = (float) $ontoRecord->current_balance_kg;

            $newBalance = $currentBalance + $quantityKg;

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
        if ($quantityKg <= 0) {
            throw new RuntimeException('Količina za izlaz mora biti veća od 0.');
        }

        return DB::transaction(function () use (
            $ontoRecord,
            $date,
            $quantityKg,
            $method,
            $note,
            $trackingFormId
        ) {
            $ontoRecord = $this->lockOntoRecord($ontoRecord);

            $this->authorizeOntoRecord($ontoRecord);

            $currentBalance = (float) $ontoRecord->current_balance_kg;

            if ($quantityKg > $currentBalance) {
                throw new RuntimeException(
                    'Nema dovoljno otpada na stanju za izlaz.'
                );
            }

            if ($trackingFormId !== null) {
                $trackingForm = WasteTrackingForm::query()
                    ->lockForUpdate()
                    ->find($trackingFormId);

                if (! $trackingForm) {
                    throw new RuntimeException(
                        'Povezani prateći list nije pronađen.'
                    );
                }

                $this->authorizeTrackingForm(
                    $trackingForm,
                    $ontoRecord
                );

                $existingEntry = OntoEntry::query()
                    ->where(
                        'waste_tracking_form_id',
                        $trackingFormId
                    )
                    ->where(
                        'entry_type',
                        'output'
                    )
                    ->exists();

                if ($existingEntry) {
                    throw new RuntimeException(
                        'Za ovaj prateći list već postoji izlaz u ONTO evidenciji.'
                    );
                }
            }

            $nextNo = ((int) $ontoRecord->entries()->max('entry_no')) + 1;

            $newBalance = $currentBalance - $quantityKg;

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

    public function lockTrackingForm(
        WasteTrackingForm $trackingForm
    ): void {
        DB::transaction(function () use ($trackingForm) {
            $trackingForm = WasteTrackingForm::query()
                ->with('ontoRecord')
                ->lockForUpdate()
                ->find($trackingForm->getKey());

            if (! $trackingForm) {
                throw new RuntimeException(
                    'Prateći list nije pronađen.'
                );
            }

            if ($trackingForm->isLocked()) {
                throw new RuntimeException(
                    'Prateći list je već zaključen.'
                );
            }

            $ontoRecord = $trackingForm->ontoRecord;

            if (! $ontoRecord) {
                throw new RuntimeException(
                    'Prateći list nema povezan ONTO zapis.'
                );
            }

            $ontoRecord = $this->lockOntoRecord(
                $ontoRecord
            );

            $this->authorizeTrackingForm(
                $trackingForm,
                $ontoRecord
            );

            $this->authorizeOntoRecord(
                $ontoRecord
            );

            $quantityKg = (float) $trackingForm->quantity_kg;

            if ($quantityKg <= 0) {
                throw new RuntimeException(
                    'Količina na pratećem listu mora biti veća od 0.'
                );
            }

            if (
                $quantityKg >
                (float) $ontoRecord->current_balance_kg
            ) {
                throw new RuntimeException(
                    'Količina na pratećem listu je veća od količine na stanju.'
                );
            }

            $existingEntry = OntoEntry::query()
                ->where(
                    'waste_tracking_form_id',
                    $trackingForm->id
                )
                ->where(
                    'entry_type',
                    'output'
                )
                ->exists();

            if ($existingEntry) {
                throw new RuntimeException(
                    'Za ovaj prateći list već postoji izlaz u ONTO evidenciji.'
                );
            }

            $method = filled(
                $trackingForm->document_number
            )
                ? 'IP-' . $trackingForm->document_number
                : 'IP';

            /*
             * addOutput() koristi vlastitu DB transakciju.
             *
             * Laravel podržava ugniježđene transakcije,
             * a zaključani ONTO zapis ostaje zaštićen
             * do završetka vanjske transakcije.
             */
            $this->addOutput(
                $ontoRecord,
                $trackingForm->handover_date?->format('Y-m-d')
                    ?? now()->format('Y-m-d'),
                $quantityKg,
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

    protected function lockOntoRecord(
        OntoRecord $ontoRecord
    ): OntoRecord {
        $lockedRecord = OntoRecord::query()
            ->lockForUpdate()
            ->find($ontoRecord->getKey());

        if (! $lockedRecord) {
            throw new RuntimeException(
                'ONTO zapis nije pronađen.'
            );
        }

        return $lockedRecord;
    }

    protected function authorizeOntoRecord(
        OntoRecord $ontoRecord
    ): void {
        $user = Auth::user();

        if (! $user) {
            throw new RuntimeException(
                'Korisnik nije prijavljen.'
            );
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            throw new RuntimeException(
                'Organizacija korisnika nije pronađena.'
            );
        }

        if (
            (int) $ontoRecord->user_id !==
            (int) $ownerId
        ) {
            abort(403);
        }
    }

    protected function authorizeTrackingForm(
        WasteTrackingForm $trackingForm,
        OntoRecord $ontoRecord
    ): void {
        $user = Auth::user();

        if (! $user) {
            throw new RuntimeException(
                'Korisnik nije prijavljen.'
            );
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            throw new RuntimeException(
                'Organizacija korisnika nije pronađena.'
            );
        }

        if (
            (int) $trackingForm->user_id !==
            (int) $ownerId
        ) {
            abort(403);
        }

        if (
            (int) $ontoRecord->user_id !==
            (int) $ownerId
        ) {
            abort(403);
        }

        if (
            (int) $trackingForm->onto_record_id !==
            (int) $ontoRecord->id
        ) {
            throw new RuntimeException(
                'Prateći list nije povezan s odabranim ONTO zapisom.'
            );
        }
    }
}