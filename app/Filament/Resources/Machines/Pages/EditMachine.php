<?php

namespace App\Filament\Resources\Machines\Pages;

use App\Filament\Resources\Machines\MachineResource;
use App\Services\MachineReportOcrService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Http\UploadedFile;
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
                ->url(static::getResource()::getUrl('index')),

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
            $oldValue = data_get($this->data, $field);
            $newValue = $ocrData[$field] ?? null;

            $oldString = $this->stringifyValue($oldValue);
            $newString = $this->stringifyValue($newValue);

            $isSame = $this->valuesAreEqual(
                $oldValue,
                $newValue
            );

            $hasNew = filled($newString);
            $hasOld = filled($oldString);

            if (! $hasNew || $isSame) {
                continue;
            }

            $this->ocrDiffs[$field] = [
                'label' => $label,
                'old' => $oldString,
                'new' => $newString,
                'replace' => true,
                'same' => false,
                'type' => $hasOld ? 'changed' : 'new',
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
                'Prikazana su samo polja koja su nova, različita ili neprepoznata.'
            )
            ->success()
            ->send();
    }

    public function applyOcrDiffs(): void
    {
        if (! MachineResource::ensureModulePermission('update')) {
            return;
        }

        if (! $this->showOcrDiffs || empty($this->ocrDiffs)) {
            Notification::make()
                ->title('Nema OCR razlika')
                ->body('Prvo pokreni OCR analizu.')
                ->warning()
                ->send();

            return;
        }

        $replaced = 0;
        $skipped = 0;

        foreach ($this->ocrDiffs as $field => $diff) {
            $newValue = $diff['new'] ?? null;
            $replace = (bool) ($diff['replace'] ?? false);

            if (blank($newValue) || ! $replace) {
                $skipped++;

                continue;
            }

            data_set(
                $this->data,
                $field,
                $newValue
            );

            $replaced++;
        }

        $this->form->fill($this->data);

        $data = $this->form->getState();
        $data = $this->mutateFormDataBeforeSave($data);

        $this->record = $this->handleRecordUpdate(
            $this->getRecord(),
            $data
        );

        data_set(
            $this->data,
            'ocr_source',
            null
        );

        $this->form->fill($this->data);

        $this->ocrDiffs = [];
        $this->showOcrDiffs = false;

        Notification::make()
            ->title('OCR razlike primijenjene i spremljene')
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

    protected function runOcrAndGetData(): array
    {
        $state = method_exists($this->form, 'getRawState')
            ? $this->form->getRawState()
            : $this->form->getState();

        $file = data_get($state, 'ocr_source');

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

        /** @var MachineReportOcrService $service */
        $service = app(MachineReportOcrService::class);

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

        if (blank($ocrData)) {
            Notification::make()
                ->title('OCR nije pronašao podatke')
                ->body(
                    'Dokument je učitan, ali nisu pronađena prepoznatljiva polja.'
                )
                ->warning()
                ->send();

            return [];
        }

        return $ocrData;
    }

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

    protected function valuesAreEqual(
        mixed $oldValue,
        mixed $newValue
    ): bool {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if ($oldValue === null || $newValue === null) {
            return false;
        }

        $old = trim((string) $oldValue);
        $new = trim((string) $newValue);

        return $old === $new;
    }

    protected function stringifyValue(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('d.m.Y.');
        }

        return trim((string) $value);
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        unset($data['ocr_source']);
        unset($data['ocr_original_name']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}