<?php

namespace App\Filament\Resources\Machines\Pages;

use App\Filament\Resources\Machines\MachineResource;
use App\Services\MachineReportOcrService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateMachine extends CreateRecord
{
    protected static string $resource = MachineResource::class;

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
        $state = method_exists($this->form, 'getRawState')
            ? $this->form->getRawState()
            : $this->form->getState();

        $file = data_get($state, 'ocr_source');

        if (is_array($file)) {
            $file = reset($file);
        }

        $storedPath = null;

        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            $storedPath = $file->store('tmp/machine-ocr', 'local');
        } elseif (is_string($file)) {
            $storedPath = $file;
        }

        if (blank($storedPath)) {
            Notification::make()
                ->title('OCR greška')
                ->body('Dokument nije moguće spremiti za OCR.')
                ->danger()
                ->send();

            return;
        }

        /** @var MachineReportOcrService $service */
        $service = app(MachineReportOcrService::class);
        $result = $service->extractFromStoredFile($storedPath, 'local');

        if (! ($result['success'] ?? false)) {
            Notification::make()
                ->title('OCR greška')
                ->body($result['message'] ?? 'Provjeri dokument ili OCR instalaciju.')
                ->danger()
                ->send();

            return;
        }

        $ocrData = $result['data'] ?? [];

        if (blank($ocrData)) {
            Notification::make()
                ->title('OCR nije pronašao podatke')
                ->body('Dokument je učitan, ali nisu pronađena prepoznatljiva polja.')
                ->warning()
                ->send();

            return;
        }

        $filled = 0;

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

        foreach ($fields as $field) {
            $newValue = $ocrData[$field] ?? null;
            $oldValue = data_get($this->data, $field);

            if (filled($newValue) && blank($oldValue)) {
                data_set($this->data, $field, $newValue);
                $filled++;
            }
        }

        $this->form->fill($this->data);

        Notification::make()
            ->title('OCR analiza završena')
            ->body("Automatski je popunjeno {$filled} polja.")
            ->success()
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['ocr_source']);
        unset($data['ocr_original_name']);

        if (! Auth::user()?->isAdmin()) {
            $data['user_id'] = Auth::id();
        } else {
            $data['user_id'] = $data['user_id'] ?? Auth::id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}