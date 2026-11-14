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

            /*
            * entry_no predstavlja redoslijed
            * evidentiranja zapisa.
            *
            * Datum može biti stariji jer je
            * retroaktivni unos dopušten.
            */
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

                        /*
                        * Privremena vrijednost.
                        * Odmah ispod ponovno
                        * računamo cijelu evidenciju.
                        */
                        'balance_after_kg' =>
                            0,

                        'note' =>
                            $note,
                    ]);

            /*
            * Retroaktivni unos može promijeniti
            * saldo svih kasnijih stavki.
            */
            $this->recalculateOntoBalances(
                $ontoRecord
            );

            return $entry->refresh();
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

            /*
            * Izlaz ne može postojati prije
            * prvog evidentiranog ulaza.
            *
            * Ako korisnik naknadno otkrije da
            * nedostaje raniji ulaz, prvo evidentira
            * taj ulaz pa zatim izlaz.
            */
            $this->assertOutputDateIsValid(
                $ontoRecord,
                $date
            );

            /*
            * Brza provjera ukupnog trenutnog stanja.
            *
            * Konačna povijesna kontrola radi se
            * nakon kreiranja kroz
            * recalculateOntoBalances().
            */
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

                        /*
                        * Preračunava se odmah
                        * nakon kreiranja.
                        */
                        'balance_after_kg' =>
                            0,

                        'note' =>
                            $note,

                        'waste_tracking_form_id' =>
                            $trackingFormId,
                    ]);

            /*
            * Ovo je ključ retroaktivnog unosa:
            *
            * sve stavke ponovno računamo po
            * stvarnom datumu događaja.
            *
            * Ako bi saldo na bilo kojem datumu
            * postao negativan, RuntimeException
            * poništava cijelu DB transakciju.
            */
            $this->recalculateOntoBalances(
                $ontoRecord
            );

            return $entry->refresh();
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
        /*
        * Zaključavamo sve aktivne stavke ONTO obrasca.
        *
        * Redoslijed obračuna određujemo nakon dohvaćanja
        * kako bi bio potpuno deterministički:
        *
        * 1. datum događaja
        * 2. ulazi prije izlaza na isti datum
        * 3. entry_no
        * 4. id
        *
        * Time su dopušteni retroaktivni unosi,
        * a entry_no ostaje audit trag redoslijeda
        * stvarnog evidentiranja u aplikaciji.
        */
        $entries =
            OntoEntry::query()
                ->where(
                    'onto_record_id',
                    $ontoRecord->id
                )
                ->lockForUpdate()
                ->get()
                ->sort(
                    function (
                        OntoEntry $a,
                        OntoEntry $b
                    ): int {
                        $dateA =
                            $a->entry_date
                                ?->format('Y-m-d')
                            ?? '';

                        $dateB =
                            $b->entry_date
                                ?->format('Y-m-d')
                            ?? '';

                        $dateCompare =
                            strcmp(
                                $dateA,
                                $dateB
                            );

                        if ($dateCompare !== 0) {
                            return $dateCompare;
                        }

                        /*
                        * Na isti datum ulaz mora biti
                        * obračunat prije izlaza.
                        */
                        $typePriorityA =
                            $a->entry_type === 'input'
                                ? 0
                                : 1;

                        $typePriorityB =
                            $b->entry_type === 'input'
                                ? 0
                                : 1;

                        $typeCompare =
                            $typePriorityA
                            <=> $typePriorityB;

                        if ($typeCompare !== 0) {
                            return $typeCompare;
                        }

                        $entryNoCompare =
                            (int) $a->entry_no
                            <=> (int) $b->entry_no;

                        if ($entryNoCompare !== 0) {
                            return $entryNoCompare;
                        }

                        return
                            (int) $a->id
                            <=> (int) $b->id;
                    }
                )
                ->values();

        $balance = 0.0;

        foreach ($entries as $entry) {
            $inputKg =
                (float) (
                    $entry->input_kg
                    ?? 0
                );

            $outputKg =
                (float) (
                    $entry->output_kg
                    ?? 0
                );

            $balance += $inputKg;
            $balance -= $outputKg;

            /*
            * Retroaktivni unos je dopušten,
            * ali stanje u bilo kojem povijesnom
            * trenutku ne smije biti negativno.
            */
            if ($balance < -0.00001) {
                throw new RuntimeException(
                    'ONTO stanje bi na datum '
                    . $entry->entry_date->format('d.m.Y.')
                    . ' postalo negativno. '
                    . 'Provjerite nedostaje li raniji ulaz otpada '
                    . 'ili je potrebno ispraviti datum odnosno količinu.'
                );
            }

            /*
            * Uklanjamo sitne floating-point ostatke.
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
        $user = Auth::user();

        if (! $user) {
            throw new RuntimeException(
                'Korisnik nije prijavljen.'
            );
        }

        /*
        * Superadmin pregledava poslovne ONTO podatke,
        * ali ne izvršava transakcije organizacije.
        */
        if ($user->isSuperAdmin()) {
            abort(403);
        }

        /*
        * Korisnik mora imati pristup ONTO modulu.
        *
        * Ako ONTO jednog dana postane granularno
        * kontrolirani modul, ova provjera će automatski
        * poštovati i update permission.
        */
        if (
            ! $user->hasModulePermission(
                'onto_records',
                'update'
            )
        ) {
            abort(403);
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            throw new RuntimeException(
                'Organizacija korisnika nije pronađena.'
            );
        }

        if (
            (int) $ontoRecord->user_id
            !== (int) $ownerId
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
            abort(403);
        }

        /*
        * Zaključavanje / otključavanje PL-O
        * mijenja i PL-O i ONTO stanje.
        *
        * Zato korisnik mora imati pravo nad oba modula.
        */
        if (
            ! $user->hasModulePermission(
                'waste_tracking_forms',
                'update'
            )
            || ! $user->hasModulePermission(
                'onto_records',
                'update'
            )
        ) {
            abort(403);
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            throw new RuntimeException(
                'Organizacija korisnika nije pronađena.'
            );
        }

        if (
            (int) $trackingForm->user_id
            !== (int) $ownerId
            || (int) $ontoRecord->user_id
            !== (int) $ownerId
        ) {
            abort(403);
        }

        if (
            (int) $trackingForm->onto_record_id
            !== (int) $ontoRecord->id
        ) {
            throw new RuntimeException(
                'Prateći list nije povezan s odabranim ONTO zapisom.'
            );
        }
    }
}