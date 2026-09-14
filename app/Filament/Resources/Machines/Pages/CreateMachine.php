<?php

namespace App\Filament\Resources\Machines\Pages;

use App\Filament\Resources\Machines\MachineResource;
use App\Services\MachineReportOcrService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateMachine extends CreateRecord
{
    protected static string $resource = MachineResource::class;

    public function mount(): void
    {
        if (! MachineResource::ensureModulePermission('create')) {
            $this->redirect(
                MachineResource::getUrl('index')
            );

            return;
        }

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ocr_autofill')
                ->label('OCR analiza')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('warning')
                ->extraAttributes([
                    'type' => 'button',
                ])
                ->action('runOcr'),
        ];
    }

    public function runOcr(): void
    {
        if (! MachineResource::ensureModulePermission('create')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Trenutno stanje forme
        |--------------------------------------------------------------------------
        */

        $state = method_exists($this->form, 'getRawState')
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

        /*
        |--------------------------------------------------------------------------
        | Spremanje privremene OCR datoteke
        |--------------------------------------------------------------------------
        */

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

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | OCR analiza
        |--------------------------------------------------------------------------
        */

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

            return;
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

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Polja koja OCR smije mijenjati
        |--------------------------------------------------------------------------
        */

        $fields = [
            'name',
            'manufacturer',
            'factory_number',
            'inventory_number',
            'report_number',
            'location',
            'examination_valid_from',
            'examination_valid_until',
            'examined_by',
        ];

        $filled = 0;

        /*
        |--------------------------------------------------------------------------
        | VAŽNO
        |--------------------------------------------------------------------------
        |
        | Na CREATE stranici OCR rezultat predstavlja novi dokument.
        |
        | Zato:
        |
        | - prepoznata vrijednost zamjenjuje postojeću
        | - neprepoznata vrijednost čisti eventualnu staru OCR vrijednost
        |
        | Time se sprječava miješanje podataka iz više PDF-ova tijekom
        | testiranja ili promjene dokumenta prije spremanja zapisa.
        |
        */

        foreach ($fields as $field) {
            $newValue = $ocrData[$field] ?? null;

            if (filled($newValue)) {
                data_set(
                    $state,
                    $field,
                    $newValue
                );

                $filled++;
            } else {
                data_set(
                    $state,
                    $field,
                    null
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Vrati OCR source u stanje forme
        |--------------------------------------------------------------------------
        |
        | Kod ponovnog fill() želimo zadržati učitani dokument.
        |
        */

        if (array_key_exists('ocr_source', $state)) {
            data_set(
                $state,
                'ocr_source',
                $file
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Popunjavanje forme
        |--------------------------------------------------------------------------
        */

        $this->data = $state;

        $this->form->fill(
            $this->data
        );

        /*
        |--------------------------------------------------------------------------
        | Obavijest
        |--------------------------------------------------------------------------
        */

        Notification::make()
            ->title('OCR analiza završena')
            ->body(
                "Automatski je popunjeno {$filled} polja."
            )
            ->success()
            ->send();
    }

    protected function beforeCreate(): void
    {
        if (! MachineResource::ensureModulePermission('create')) {
            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        unset(
            $data['ocr_source'],
            $data['ocr_original_name'],
            $data['ocr_raw_text']
        );

        return MachineResource::fillOwnershipData(
            $data
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }
}