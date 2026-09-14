<?php

namespace App\Filament\Resources\Machines\Pages;

use App\Filament\Resources\Machines\MachineResource;
use App\Services\MachineReportOcrService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditMachine extends EditRecord
{
    protected static string $resource = MachineResource::class;

    protected string $view = 'filament.resources.machines.pages.edit-machine';

    public array $ocrDiffs = [];

    public bool $showOcrDiffs = false;

    public function mount(int|string $record): void
    {
        /*
         * Filament prvo mora pronaći zapis i pretvoriti
         * vrijednost iz URL-a u Machine model.
         */
        parent::mount($record);

        /*
         * Tek nakon toga provjeravamo dozvolu.
         */
        if (! MachineResource::ensureModulePermission('update')) {
            $this->redirect(
                MachineResource::getUrl('index'),
                navigate: true
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Natrag')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(
                    static::getResource()::getUrl('index')
                ),

            Action::make('ocr_preview')
                ->label('OCR analiza')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('warning')
                ->extraAttributes([
                    'type' => 'button',
                ])
                ->action('runOcrPreview'),

            Action::make('apply_ocr_diffs')
                ->label('Primijeni OCR razlike')
                ->icon('heroicon-o-check')
                ->color('success')
                ->extraAttributes([
                    'type' => 'button',
                ])
                ->visible(
                    fn (): bool =>
                        $this->showOcrDiffs
                        && count($this->ocrDiffs) > 0
                )
                ->action('applyOcrDiffs'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Spremi promjene'),

            $this->getCancelFormAction()
                ->label('Odustani'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | OCR PREVIEW
    |--------------------------------------------------------------------------
    */

    public function runOcrPreview(): void
    {
        if (! MachineResource::ensureModulePermission('update')) {
            return;
        }

        $ocrData = $this->runOcrAndGetData();

        if (blank($ocrData)) {
            return;
        }

        $this->ocrDiffs = [];

        foreach ($this->getComparableFields() as $field => $label) {
            $oldValue = data_get(
                $this->data,
                $field
            );

            $newValue = $ocrData[$field] ?? null;

            /*
             * Datume normaliziramo prije usporedbe.
             *
             * Primjer:
             *
             * 2029-08-24 00:00:00
             * i
             * 2029-08-24
             *
             * predstavljaju isti datum.
             */
            if ($this->isDateField($field)) {
                $oldValue = $this->normalizeDateValue(
                    $oldValue
                );

                $newValue = $this->normalizeDateValue(
                    $newValue
                );
            }

            $oldString = $this->stringifyValue(
                $oldValue,
                $field
            );

            $newString = $this->stringifyValue(
                $newValue,
                $field
            );

            $isSame = $this->valuesAreEqual(
                $oldValue,
                $newValue,
                $field
            );

            $hasNew = filled($newString);
            $hasOld = filled($oldString);

            if (! $hasNew || $isSame) {
                continue;
            }

            $this->ocrDiffs[$field] = [
                'label' => $label,

                /*
                 * Vrijednosti samo za prikaz korisniku.
                 */
                'old' => $oldString,
                'new' => $newString,

                /*
                 * Stvarna vrijednost koja se sprema.
                 *
                 * Ovo je posebno važno kod datuma jer ne želimo
                 * spremati "24.08.2029." nego "2029-08-24".
                 */
                'new_raw' => $newValue,

                'replace' => true,
                'same' => false,
                'type' => $hasOld
                    ? 'changed'
                    : 'new',
            ];
        }

        $this->showOcrDiffs = true;

        if (count($this->ocrDiffs) === 0) {
            Notification::make()
                ->title('OCR analiza završena')
                ->body(
                    'Nema razlika — sva prepoznata polja već su ista kao postojeća.'
                )
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('OCR analiza završena')
            ->body(
                'Prikazana su samo polja koja su nova ili različita od postojećih.'
            )
            ->success()
            ->send();
    }

    /*
    |--------------------------------------------------------------------------
    | PRIMJENA OCR RAZLIKA
    |--------------------------------------------------------------------------
    */

    public function applyOcrDiffs(): void
    {
        if (! MachineResource::ensureModulePermission('update')) {
            return;
        }

        if (
            ! $this->showOcrDiffs
            || empty($this->ocrDiffs)
        ) {
            Notification::make()
                ->title('Nema OCR razlika')
                ->body(
                    'Prvo pokreni OCR analizu.'
                )
                ->warning()
                ->send();

            return;
        }

        $replaced = 0;
        $skipped = 0;

        foreach ($this->ocrDiffs as $field => $diff) {
            /*
             * Za spremanje koristimo originalnu vrijednost,
             * a ne formatirani prikaz.
             */
            $newValue = array_key_exists(
                'new_raw',
                $diff
            )
                ? $diff['new_raw']
                : ($diff['new'] ?? null);

            $replace = (bool) (
                $diff['replace']
                ?? false
            );

            if (
                blank($newValue)
                || ! $replace
            ) {
                $skipped++;

                continue;
            }

            if ($this->isDateField($field)) {
                $newValue = $this->normalizeDateValue(
                    $newValue
                );
            }

            data_set(
                $this->data,
                $field,
                $newValue
            );

            $replaced++;
        }

        /*
         * OCR upload više nije potreban.
         */
        data_set(
            $this->data,
            'ocr_source',
            null
        );

        $this->form->fill(
            $this->data
        );

        $data = $this->form->getState();

        $data = $this->mutateFormDataBeforeSave(
            $data
        );

        $this->record = $this->handleRecordUpdate(
            $this->getRecord(),
            $data
        );

        /*
         * Ponovno napuni formu vrijednostima iz baze.
         */
        $this->data = $this->record
            ->refresh()
            ->attributesToArray();

        $this->form->fill(
            $this->data
        );

        $this->ocrDiffs = [];
        $this->showOcrDiffs = false;

        Notification::make()
            ->title(
                'OCR razlike primijenjene i spremljene'
            )
            ->body(
                "Spremljeno zamjena: {$replaced}, preskočeno: {$skipped}."
            )
            ->success()
            ->send();
    }

    protected function beforeSave(): void
    {
        if (! MachineResource::ensureModulePermission('update')) {
            $this->halt();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OCR ANALIZA
    |--------------------------------------------------------------------------
    */

    protected function runOcrAndGetData(): array
    {
        $state = method_exists(
            $this->form,
            'getRawState'
        )
            ? $this->form->getRawState()
            : $this->form->getState();

        $file = data_get(
            $state,
            'ocr_source'
        );

        if (is_array($file)) {
            $file = reset($file);
        }

        $storedPath = null;

        if (
            $file instanceof TemporaryUploadedFile
            || $file instanceof UploadedFile
        ) {
            $storedPath = $file->store(
                'tmp/machine-ocr',
                'local'
            );
        } elseif (is_string($file)) {
            $storedPath = $file;
        }

        if (blank($storedPath)) {
            Notification::make()
                ->title('OCR greška')
                ->body(
                    'Dokument nije moguće spremiti za OCR.'
                )
                ->danger()
                ->send();

            return [];
        }

        try {
            /** @var MachineReportOcrService $service */
            $service = app(
                MachineReportOcrService::class
            );

            $result = $service->extractFromStoredFile(
                $storedPath,
                'local'
            );

            if (! ($result['success'] ?? false)) {
                Notification::make()
                    ->title('OCR greška')
                    ->body(
                        $result['message']
                            ?? 'Provjeri dokument ili OCR instalaciju.'
                    )
                    ->danger()
                    ->send();

                return [];
            }

            $ocrData = $result['data'] ?? [];

            /*
             * Sirovi OCR tekst nije polje modela i ne treba
             * sudjelovati u daljnjem radu forme.
             */
            unset(
                $ocrData['ocr_raw_text']
            );

            if (blank($ocrData)) {
                Notification::make()
                    ->title(
                        'OCR nije pronašao podatke'
                    )
                    ->body(
                        'Dokument je učitan, ali nisu pronađena prepoznatljiva polja.'
                    )
                    ->warning()
                    ->send();

                return [];
            }

            /*
             * Datumi iz OCR-a uvijek se vraćaju
             * u obliku Y-m-d.
             */
            foreach (
                [
                    'examination_valid_from',
                    'examination_valid_until',
                ] as $dateField
            ) {
                if (
                    filled(
                        $ocrData[$dateField]
                            ?? null
                    )
                ) {
                    $ocrData[$dateField] =
                        $this->normalizeDateValue(
                            $ocrData[$dateField]
                        );
                }
            }

            return $ocrData;
        } finally {
            /*
             * OCR dokument služi isključivo za analizu.
             *
             * Nakon OCR-a ga odmah brišemo iz:
             *
             * storage/app/private/tmp/machine-ocr
             *
             * Ne brišu se trajni prilozi radne opreme.
             */
            $this->deleteTemporaryOcrFile(
                $storedPath
            );

            /*
             * Makni upload i iz Livewire forme.
             */
            data_set(
                $this->data,
                'ocr_source',
                null
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVREMENI OCR UPLOAD
    |--------------------------------------------------------------------------
    */

    protected function deleteTemporaryOcrFile(
        ?string $storedPath
    ): void {
        if (blank($storedPath)) {
            return;
        }

        $normalizedPath = str_replace(
            '\\',
            '/',
            ltrim(
                $storedPath,
                '/\\'
            )
        );

        /*
         * Sigurnosna zaštita:
         *
         * brišemo samo datoteke iz OCR temp direktorija.
         */
        if (
            ! str_starts_with(
                $normalizedPath,
                'tmp/machine-ocr/'
            )
        ) {
            return;
        }

        try {
            if (
                Storage::disk('local')
                    ->exists($normalizedPath)
            ) {
                Storage::disk('local')
                    ->delete($normalizedPath);
            }
        } catch (\Throwable $e) {
            /*
             * Ne prekidamo OCR samo zato što eventualno
             * nije uspjelo čišćenje privremene datoteke.
             */
            report($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POLJA ZA USPOREDBU
    |--------------------------------------------------------------------------
    */

    protected function getComparableFields(): array
    {
        return [
            'name' => 'Naziv stroja',
            'manufacturer' => 'Proizvođač',
            'factory_number' => 'Tvornički broj',
            'inventory_number' => 'Inventarni broj',
            'report_number' => 'Broj izvještaja',
            'location' => 'Lokacija',
            'examination_valid_from' => 'Vrijedi od',
            'examination_valid_until' => 'Vrijedi do',
            'examined_by' => 'Ovlaštena tvrtka',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DATUMSKA POLJA
    |--------------------------------------------------------------------------
    */

    protected function isDateField(
        string $field
    ): bool {
        return in_array(
            $field,
            [
                'examination_valid_from',
                'examination_valid_until',
            ],
            true
        );
    }

    /*
     * Sve varijante:
     *
     * 2029-08-24
     * 2029-08-24 00:00:00
     * 24.08.2029.
     * Carbon objekt
     *
     * pretvaramo u:
     *
     * 2029-08-24
     */
    protected function normalizeDateValue(
        mixed $value
    ): ?string {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return null;
        }

        /*
         * MySQL date/datetime.
         */
        if (
            preg_match(
                '/^(\d{4}-\d{2}-\d{2})(?:\s+\d{2}:\d{2}:\d{2})?$/',
                $value,
                $matches
            )
        ) {
            return $matches[1];
        }

        /*
         * Hrvatski format:
         *
         * 24.08.2029.
         */
        if (
            preg_match(
                '/^(\d{1,2})\.(\d{1,2})\.(\d{4})\.?$/',
                $value,
                $matches
            )
        ) {
            try {
                return Carbon::create(
                    (int) $matches[3],
                    (int) $matches[2],
                    (int) $matches[1]
                )->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        /*
         * Zadnji fallback.
         */
        try {
            return Carbon::parse(
                $value
            )->format('Y-m-d');
        } catch (\Throwable) {
            return $value;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | USPOREDBA
    |--------------------------------------------------------------------------
    */

    protected function valuesAreEqual(
        mixed $oldValue,
        mixed $newValue,
        ?string $field = null
    ): bool {
        if (
            $oldValue === null
            && $newValue === null
        ) {
            return true;
        }

        if (
            $oldValue === null
            || $newValue === null
        ) {
            return false;
        }

        /*
         * Datume uspoređujemo samo po Y-m-d.
         *
         * Zato:
         *
         * 2026-08-24 00:00:00
         *
         * i
         *
         * 2026-08-24
         *
         * više nisu različiti.
         */
        if (
            $field !== null
            && $this->isDateField($field)
        ) {
            return $this->normalizeDateValue(
                $oldValue
            ) === $this->normalizeDateValue(
                $newValue
            );
        }

        $old = $this->normalizeComparableText(
            $oldValue
        );

        $new = $this->normalizeComparableText(
            $newValue
        );

        return $old === $new;
    }

    protected function normalizeComparableText(
        mixed $value
    ): string {
        $value = trim(
            (string) $value
        );

        /*
         * Višestruke razmake pretvaramo u jedan.
         */
        $value = preg_replace(
            '/\s+/u',
            ' ',
            $value
        );

        return mb_strtolower(
            trim($value)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRIKAZ VRIJEDNOSTI U OCR DIFF-U
    |--------------------------------------------------------------------------
    */

    protected function stringifyValue(
        mixed $value,
        ?string $field = null
    ): string {
        if (blank($value)) {
            return '';
        }

        /*
         * Datum u korisničkom sučelju uvijek:
         *
         * 24.08.2029.
         */
        if (
            (
                $field !== null
                && $this->isDateField($field)
            )
            || $value instanceof CarbonInterface
        ) {
            $normalized =
                $this->normalizeDateValue(
                    $value
                );

            if (blank($normalized)) {
                return '';
            }

            try {
                return Carbon::createFromFormat(
                    'Y-m-d',
                    $normalized
                )->format('d.m.Y.');
            } catch (\Throwable) {
                return $normalized;
            }
        }

        return trim(
            (string) $value
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SPREMANJE
    |--------------------------------------------------------------------------
    */

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        unset(
            $data['ocr_source'],
            $data['ocr_original_name'],
            $data['ocr_raw_text']
        );

        /*
         * Datume u model spremamo kao čisti datum Y-m-d.
         */
        foreach (
            [
                'examination_valid_from',
                'examination_valid_until',
            ] as $field
        ) {
            if (
                array_key_exists(
                    $field,
                    $data
                )
            ) {
                $data[$field] =
                    $this->normalizeDateValue(
                        $data[$field]
                    );
            }
        }

        /*
         * Ownership postojećeg zapisa nikada se
         * ne mijenja uređivanjem.
         *
         * Ovo vrijedi i kada zapis uređuje superadmin.
         */
        $data['user_id'] =
            $this->record->user_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }
}