<?php

namespace App\Services;

use App\Models\OntoEntry;
use App\Models\OntoRecord;
use App\Models\WasteTrackingForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
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
            throw new RuntimeException(
                'Količina za ulaz mora biti veća od 0.'
            );
        }

        return DB::transaction(function () use (
            $ontoRecord,
            $date,
            $quantityKg,
            $method,
            $note
        ) {
            $ontoRecord =
                $this->lockOntoRecord(
                    $ontoRecord
                );

            $this->authorizeOntoRecord(
                $ontoRecord
            );

            $nextNo =
                ((int) $ontoRecord
                    ->entries()
                    ->max('entry_no'))
                + 1;

            $currentBalance =
                (float)
                $ontoRecord
                    ->current_balance_kg;

            $newBalance =
                $currentBalance
                + $quantityKg;

            $entry =
                $ontoRecord
                    ->entries()
                    ->create([
                        'entry_no' =>
                            $nextNo,

                        'entry_date' =>
                            $date,

                        'entry_type' =>
                            'input',

                        'input_kg' =>
                            $quantityKg,

                        'output_kg' =>
                            0,

                        'method' =>
                            $method,

                        'balance_after_kg' =>
                            $newBalance,

                        'note' =>
                            $note,
                    ]);

            $ontoRecord->update([
                'current_balance_kg' =>
                    $newBalance,
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
            throw new RuntimeException(
                'Količina za izlaz mora biti veća od 0.'
            );
        }

        return DB::transaction(function () use (
            $ontoRecord,
            $date,
            $quantityKg,
            $method,
            $note,
            $trackingFormId
        ) {
            $ontoRecord =
                $this->lockOntoRecord(
                    $ontoRecord
                );

            $this->authorizeOntoRecord(
                $ontoRecord
            );

            $this->assertOutputDateIsValid(
                $ontoRecord,
                $date
            );

            $currentBalance =
                (float)
                $ontoRecord
                    ->current_balance_kg;

            if (
                $quantityKg
                > $currentBalance
            ) {
                throw new RuntimeException(
                    'Nema dovoljno otpada na stanju za izlaz.'
                );
            }

            if (
                $trackingFormId !== null
            ) {
                $trackingForm =
                    WasteTrackingForm::query()
                        ->lockForUpdate()
                        ->find(
                            $trackingFormId
                        );

                if (! $trackingForm) {
                    throw new RuntimeException(
                        'Povezani prateći list nije pronađen.'
                    );
                }

                $this->authorizeTrackingForm(
                    $trackingForm,
                    $ontoRecord
                );

                $existingEntry =
                    OntoEntry::query()
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

            $nextNo =
                ((int) $ontoRecord
                    ->entries()
                    ->max('entry_no'))
                + 1;

            $newBalance =
                $currentBalance
                - $quantityKg;

            $entry =
                $ontoRecord
                    ->entries()
                    ->create([
                        'entry_no' =>
                            $nextNo,

                        'entry_date' =>
                            $date,

                        'entry_type' =>
                            'output',

                        'input_kg' =>
                            0,

                        'output_kg' =>
                            $quantityKg,

                        'method' =>
                            $method,

                        'balance_after_kg' =>
                            $newBalance,

                        'note' =>
                            $note,

                        'waste_tracking_form_id' =>
                            $trackingFormId,
                    ]);

            $ontoRecord->update([
                'current_balance_kg' =>
                    $newBalance,
            ]);

            return $entry;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ZAKLJUČAVANJE PL-O
    |--------------------------------------------------------------------------
    |
    | PRVO zaključavanje:
    | - kreira novi ONTO izlaz.
    |
    | PONOVNO zaključavanje nakon otključavanja:
    | - NE kreira novi izlaz,
    | - ažurira postojeći izlaz,
    | - ponovno računa ONTO stanje.
    |
    */

    public function lockTrackingForm(
        WasteTrackingForm $trackingForm
    ): void {
        DB::transaction(
            function () use (
                $trackingForm
            ): void {
                /*
                * Učitavamo svježi PL-O zapis
                * i zaključavamo ga za vrijeme transakcije.
                */
                $trackingForm =
                    WasteTrackingForm::query()
                        ->with(
                            'ontoRecord'
                        )
                        ->lockForUpdate()
                        ->find(
                            $trackingForm
                                ->getKey()
                        );

                if (! $trackingForm) {
                    throw new RuntimeException(
                        'Prateći list nije pronađen.'
                    );
                }

                /*
                * Već zaključani PL-O
                * ne zaključavamo ponovno.
                *
                * Za ispravak se prvo mora
                * koristiti akcija Otključaj.
                */
                if (
                    $trackingForm
                        ->isLocked()
                ) {
                    throw new RuntimeException(
                        'Prateći list je već zaključen.'
                    );
                }

                /*
                * PL-O mora biti povezan
                * s ONTO obrascem.
                */
                $ontoRecord =
                    $trackingForm
                        ->ontoRecord;

                if (! $ontoRecord) {
                    throw new RuntimeException(
                        'Prateći list nema povezan ONTO zapis.'
                    );
                }

                /*
                * Zaključavamo ONTO zapis
                * kako dvije paralelne radnje
                * ne bi istodobno mijenjale stanje.
                */
                $ontoRecord =
                    $this->lockOntoRecord(
                        $ontoRecord
                    );

                /*
                * Tenant / ownership zaštita.
                */
                $this->authorizeTrackingForm(
                    $trackingForm,
                    $ontoRecord
                );

                $this->authorizeOntoRecord(
                    $ontoRecord
                );

                /*
                * =============================================================
                * PROVJERA REDOSLIJEDA DATUMA
                * =============================================================
                *
                * Provjerava:
                *
                * - predaja prijevozniku >= prvi ONTO ulaz
                * - datum predaje >= prvi ONTO ulaz
                * - datum predaje >= datum predaje prijevozniku
                * - datum vaganja >= datum predaje
                * - datum vaganja >= datum predaje prijevozniku
                * - obrada završena >= svi prethodni datumi
                */
                $this->validateTrackingFormChronology(
                    $trackingForm,
                    $ontoRecord
                );

                /*
                * =============================================================
                * KONAČNA KOLIČINA
                * =============================================================
                *
                * Ako primatelj ima upisanu
                * PREUZETU KOLIČINU,
                * ona postaje konačna količina PL-O.
                *
                * Primjer:
                *
                * početna količina = 50 kg
                * primatelj izvagao = 45 kg
                *
                * quantity_kg postaje 45 kg
                * i ONTO skida 45 kg.
                */
                $quantityKg =
                    $this->synchronizeTrackingFormQuantity(
                        $trackingForm
                    );

                if ($quantityKg <= 0) {
                    throw new RuntimeException(
                        'Količina na pratećem listu mora biti veća od 0.'
                    );
                }

                /*
                * =============================================================
                * POSTOJEĆI ONTO IZLAZ
                * =============================================================
                *
                * Ako postoji, ovaj PL-O je već ranije
                * bio zaključan pa otključan.
                *
                * Ne smijemo napraviti drugi izlaz.
                */
                $existingEntries =
                    OntoEntry::query()
                        ->where(
                            'waste_tracking_form_id',
                            $trackingForm->id
                        )
                        ->where(
                            'entry_type',
                            'output'
                        )
                        ->lockForUpdate()
                        ->get();

                if (
                    $existingEntries->count()
                    > 1
                ) {
                    throw new RuntimeException(
                        'Za ovaj prateći list pronađeno je više ONTO izlaza. Potrebna je provjera podataka.'
                    );
                }

                /** @var OntoEntry|null $existingEntry */
                $existingEntry =
                    $existingEntries
                        ->first();

                /*
                * Način izlaza.
                */
                $method =
                    filled(
                        $trackingForm
                            ->document_number
                    )
                        ? 'IP-'
                            . $trackingForm
                                ->document_number
                        : 'IP';

                /*
                * Datum izlaza u ONTO je
                * datum predaje PL-O.
                */
                $entryDate =
                    $trackingForm
                        ->handover_date
                        ?->format('Y-m-d')
                    ?? now()
                        ->format('Y-m-d');

                /*
                * Dodatna serverska zaštita:
                * izlaz nikada ne smije biti
                * prije prvog ulaza otpada.
                */
                $this->assertOutputDateIsValid(
                    $ontoRecord,
                    $entryDate
                );

                /*
                * =============================================================
                * PRVO ZAKLJUČAVANJE
                * =============================================================
                */
                if (! $existingEntry) {
                    /*
                    * Provjera trenutnog stanja.
                    */
                    if (
                        $quantityKg
                        >
                        (float)
                        $ontoRecord
                            ->current_balance_kg
                    ) {
                        throw new RuntimeException(
                            'Količina na pratećem listu je veća od količine na stanju.'
                        );
                    }

                    /*
                    * Kreira se JEDAN ONTO izlaz
                    * povezan s ovim PL-O.
                    */
                    $this->addOutput(
                        $ontoRecord,
                        $entryDate,
                        $quantityKg,
                        $method,
                        $trackingForm->note,
                        $trackingForm->id
                    );
                } else {
                    /*
                    * =========================================================
                    * PONOVNO ZAKLJUČAVANJE
                    * =========================================================
                    *
                    * PL-O je ranije:
                    *
                    * Zaključen
                    * → Otključan
                    * → Uređen
                    * → ponovno Zaključen
                    *
                    * NE stvaramo novi ONTO izlaz.
                    */

                    /*
                    * Postojeći izlaz mora pripadati
                    * istom ONTO obrascu.
                    */
                    if (
                        (int)
                        $existingEntry
                            ->onto_record_id
                        !==
                        (int)
                        $ontoRecord
                            ->id
                    ) {
                        throw new RuntimeException(
                            'ONTO obrazac ovog pratećeg lista ne može se promijeniti nakon prvog zaključavanja.'
                        );
                    }

                    /*
                    * Ažuriramo isti postojeći izlaz.
                    */
                    $existingEntry->update([
                        'entry_date' =>
                            $entryDate,

                        'input_kg' =>
                            0,

                        'output_kg' =>
                            $quantityKg,

                        'method' =>
                            $method,

                        'note' =>
                            $trackingForm
                                ->note,
                    ]);

                    /*
                    * Promjena starog izlaza može
                    * promijeniti sva kasnija stanja.
                    *
                    * Zato ponovno računamo cijeli
                    * ONTO saldo.
                    *
                    * Ako bi negdje nastalo negativno
                    * stanje, cijela transakcija se poništava.
                    */
                    $this->recalculateOntoBalances(
                        $ontoRecord
                    );
                }

                /*
                * =============================================================
                * ZAKLJUČAVANJE PL-O
                * =============================================================
                *
                * Status mijenjamo tek kada su
                * sve ONTO provjere i promjene uspjele.
                */
                $trackingForm->update([
                    'status' =>
                        'locked',

                    'locked_at' =>
                        now(),
                ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | OTKLJUČAVANJE PL-O
    |--------------------------------------------------------------------------
    |
    | Otključavanje NE briše ONTO izlaz i NE vraća količinu.
    |
    | Postojeći ONTO izlaz ostaje kao veza s ovim PL-O.
    | Kada korisnik ponovno zaključa dokument,
    | lockTrackingForm() ažurira isti izlaz.
    |
    */

    public function unlockTrackingForm(
        WasteTrackingForm $trackingForm
    ): void {
        DB::transaction(
            function () use (
                $trackingForm
            ): void {
                $trackingForm =
                    WasteTrackingForm::query()
                        ->with(
                            'ontoRecord'
                        )
                        ->lockForUpdate()
                        ->find(
                            $trackingForm
                                ->getKey()
                        );

                if (! $trackingForm) {
                    throw new RuntimeException(
                        'Prateći list nije pronađen.'
                    );
                }

                if (
                    ! $trackingForm
                        ->isLocked()
                ) {
                    throw new RuntimeException(
                        'Prateći list nije zaključan.'
                    );
                }

                $ontoRecord =
                    $trackingForm
                        ->ontoRecord;

                if (! $ontoRecord) {
                    throw new RuntimeException(
                        'Prateći list nema povezan ONTO zapis.'
                    );
                }

                $ontoRecord =
                    $this->lockOntoRecord(
                        $ontoRecord
                    );

                $this->authorizeTrackingForm(
                    $trackingForm,
                    $ontoRecord
                );

                $this->authorizeOntoRecord(
                    $ontoRecord
                );

                /*
                 * Zaključani PL-O bi morao imati
                 * povezani ONTO izlaz.
                 */
                $existingEntry =
                    OntoEntry::query()
                        ->where(
                            'waste_tracking_form_id',
                            $trackingForm->id
                        )
                        ->where(
                            'entry_type',
                            'output'
                        )
                        ->lockForUpdate()
                        ->first();

                if (! $existingEntry) {
                    throw new RuntimeException(
                        'ONTO izlaz povezan s ovim pratećim listom nije pronađen.'
                    );
                }

                /*
                 * VAŽNO:
                 *
                 * ONTO izlaz se NE briše.
                 * current_balance_kg se NE mijenja.
                 *
                 * Samo dopuštamo uređivanje PL-O.
                 */
                $trackingForm->update([
                    'status' =>
                        'draft',

                    'locked_at' =>
                        null,
                ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PONOVNI IZRAČUN ONTO STANJA
    |--------------------------------------------------------------------------
    |
    | Koristi se kada je već evidentirani PL-O
    | otključan, ispravljen i ponovno zaključan.
    |
    | Primjer:
    |
    | prije: izlaz 100 kg
    | poslije: izlaz 80 kg
    |
    | postojeća ONTO stavka postaje 80 kg,
    | a sva balance_after_kg i current_balance_kg
    | ponovno se pravilno računaju.
    |
    */

    protected function recalculateOntoBalances(
        OntoRecord $ontoRecord
    ): void {
        $entries =
            OntoEntry::query()
                ->where(
                    'onto_record_id',
                    $ontoRecord->id
                )
                ->orderBy(
                    'entry_no'
                )
                ->orderBy(
                    'id'
                )
                ->lockForUpdate()
                ->get();

        $balance = 0.0;

        foreach ($entries as $entry) {
            $inputKg =
                (float)
                ($entry->input_kg ?? 0);

            $outputKg =
                (float)
                ($entry->output_kg ?? 0);

            $balance +=
                $inputKg;

            $balance -=
                $outputKg;

            /*
             * Ne dopuštamo da ispravak
             * napravi povijesno negativno stanje.
             */
            if ($balance < -0.00001) {
                throw new RuntimeException(
                    'Ispravak nije moguć jer bi ONTO stanje u jednom trenutku postalo negativno.'
                );
            }

            /*
             * Uklanjamo sitne floating point
             * ostatke poput -0.00000001.
             */
            if (
                abs($balance)
                < 0.00001
            ) {
                $balance = 0.0;
            }

            $entry->update([
                'balance_after_kg' =>
                    $balance,
            ]);
        }

        $ontoRecord->update([
            'current_balance_kg' =>
                $balance,
        ]);
    }

    protected function assertOutputDateIsValid(
        OntoRecord $ontoRecord,
        string $outputDate
    ): void {
        $firstInputDate =
            OntoEntry::query()
                ->where(
                    'onto_record_id',
                    $ontoRecord->id
                )
                ->where(
                    'entry_type',
                    'input'
                )
                ->min('entry_date');

        if (! $firstInputDate) {
            throw new RuntimeException(
                'Izlaz nije moguć jer u ONTO obrascu još nema evidentiranog ulaza otpada.'
            );
        }

        $output =
            Carbon::parse(
                $outputDate
            )->startOfDay();

        $firstInput =
            Carbon::parse(
                $firstInputDate
            )->startOfDay();

        if (
            $output->lt(
                $firstInput
            )
        ) {
            throw new RuntimeException(
                'Datum izlaza ne može biti prije datuma nastanka odnosno prvog ulaza otpada u ONTO.'
            );
        }
    }

    protected function validateTrackingFormChronology(
        WasteTrackingForm $trackingForm,
        OntoRecord $ontoRecord
    ): void {
        $firstInputDate =
            OntoEntry::query()
                ->where(
                    'onto_record_id',
                    $ontoRecord->id
                )
                ->where(
                    'entry_type',
                    'input'
                )
                ->min('entry_date');

        if (! $firstInputDate) {
            throw new RuntimeException(
                'Prateći list nije moguće zaključiti jer ONTO nema evidentirani ulaz otpada.'
            );
        }

        $firstInput =
            Carbon::parse(
                $firstInputDate
            )->startOfDay();

        $carrierDate =
            filled(
                $trackingForm
                    ->carrier_taken_over_at
            )
                ? Carbon::parse(
                    $trackingForm
                        ->carrier_taken_over_at
                )->startOfDay()
                : null;

        $handoverDate =
            filled(
                $trackingForm
                    ->handover_date
            )
                ? Carbon::parse(
                    $trackingForm
                        ->handover_date
                )->startOfDay()
                : null;

        $weighingDate =
            filled(
                $trackingForm
                    ->receiver_weighing_time
            )
                ? Carbon::parse(
                    $trackingForm
                        ->receiver_weighing_time
                )->startOfDay()
                : null;

        $processingDate =
            filled(
                $trackingForm
                    ->processing_completed_at
            )
                ? Carbon::parse(
                    $trackingForm
                        ->processing_completed_at
                )->startOfDay()
                : null;

        /*
        * Predaja prijevozniku ne smije
        * biti prije nastanka/ulaza otpada.
        */
        if (
            $carrierDate
            && $carrierDate->lt(
                $firstInput
            )
        ) {
            throw new RuntimeException(
                'Datum predaje prijevozniku ne može biti prije datuma nastanka odnosno ulaza otpada.'
            );
        }

        /*
        * Datum predaje PL-O ne smije
        * biti prije ulaza otpada.
        */
        if (
            $handoverDate
            && $handoverDate->lt(
                $firstInput
            )
        ) {
            throw new RuntimeException(
                'Datum predaje ne može biti prije datuma nastanka odnosno ulaza otpada.'
            );
        }

        /*
        * Datum predaje ne smije biti
        * prije datuma prijevoznika.
        */
        if (
            $carrierDate
            && $handoverDate
            && $handoverDate->lt(
                $carrierDate
            )
        ) {
            throw new RuntimeException(
                'Datum predaje ne može biti prije datuma predaje prijevozniku.'
            );
        }

        /*
        * Vaganje ne smije biti prije
        * datuma prijevoznika.
        */
        if (
            $carrierDate
            && $weighingDate
            && $weighingDate->lt(
                $carrierDate
            )
        ) {
            throw new RuntimeException(
                'Datum vaganja ne može biti prije datuma predaje prijevozniku.'
            );
        }

        /*
        * Vaganje ne smije biti prije
        * konačnog datuma predaje.
        */
        if (
            $handoverDate
            && $weighingDate
            && $weighingDate->lt(
                $handoverDate
            )
        ) {
            throw new RuntimeException(
                'Datum vaganja ne može biti prije datuma predaje.'
            );
        }

        /*
        * Završetak obrade mora biti nakon
        * svih prethodnih relevantnih datuma.
        */
        foreach (
            [
                'datuma nastanka odnosno ulaza otpada' =>
                    $firstInput,

                'datuma predaje prijevozniku' =>
                    $carrierDate,

                'datuma predaje' =>
                    $handoverDate,

                'datuma vaganja' =>
                    $weighingDate,
            ]
            as $label => $previousDate
        ) {
            if (
                $processingDate
                && $previousDate
                && $processingDate->lt(
                    $previousDate
                )
            ) {
                throw new RuntimeException(
                    'Datum završetka obrade ne može biti prije '
                    . $label
                    . '.'
                );
            }
        }
    }

    protected function synchronizeTrackingFormQuantity(
        WasteTrackingForm $trackingForm
    ): float {
        $declaredQuantity =
            (float)
            $trackingForm
                ->quantity_kg;

        $measuredQuantity =
            $trackingForm
                ->receiver_measured_quantity_kg;

        /*
        * Ako primatelj nije upisao stvarno
        * izmjerenu količinu, ostaje početna
        * količina PL-O.
        */
        if (
            $measuredQuantity === null
            || $measuredQuantity === ''
        ) {
            return $declaredQuantity;
        }

        $measuredQuantity =
            (float)
            $measuredQuantity;

        if (
            $measuredQuantity <= 0
        ) {
            throw new RuntimeException(
                'Preuzeta količina kod primatelja mora biti veća od 0.'
            );
        }

        /*
        * Stvarna količina koju je primatelj
        * izvagao je konačna količina PL-O.
        */
        if (
            abs(
                $declaredQuantity
                - $measuredQuantity
            ) > 0.00001
        ) {
            $trackingForm->update([
                'quantity_kg' =>
                    $measuredQuantity,
            ]);
        }

        return $measuredQuantity;
    }

    protected function lockOntoRecord(
        OntoRecord $ontoRecord
    ): OntoRecord {
        $lockedRecord =
            OntoRecord::query()
                ->lockForUpdate()
                ->find(
                    $ontoRecord
                        ->getKey()
                );

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
        $user =
            Auth::user();

        if (! $user) {
            throw new RuntimeException(
                'Korisnik nije prijavljen.'
            );
        }

        if (
            $user->isSuperAdmin()
        ) {
            return;
        }

        $ownerId =
            $user->ownerId();

        if (! $ownerId) {
            throw new RuntimeException(
                'Organizacija korisnika nije pronađena.'
            );
        }

        if (
            (int)
            $ontoRecord
                ->user_id
            !==
            (int)
            $ownerId
        ) {
            abort(403);
        }
    }

    protected function authorizeTrackingForm(
        WasteTrackingForm $trackingForm,
        OntoRecord $ontoRecord
    ): void {
        $user =
            Auth::user();

        if (! $user) {
            throw new RuntimeException(
                'Korisnik nije prijavljen.'
            );
        }

        if (
            $user->isSuperAdmin()
        ) {
            return;
        }

        $ownerId =
            $user->ownerId();

        if (! $ownerId) {
            throw new RuntimeException(
                'Organizacija korisnika nije pronađena.'
            );
        }

        if (
            (int)
            $trackingForm
                ->user_id
            !==
            (int)
            $ownerId
        ) {
            abort(403);
        }

        if (
            (int)
            $ontoRecord
                ->user_id
            !==
            (int)
            $ownerId
        ) {
            abort(403);
        }

        if (
            (int)
            $trackingForm
                ->onto_record_id
            !==
            (int)
            $ontoRecord
                ->id
        ) {
            throw new RuntimeException(
                'Prateći list nije povezan s odabranim ONTO zapisom.'
            );
        }
    }
}