<?php

namespace App\Filament\Resources\MedicalReferrals\Pages;

use App\Filament\Resources\MedicalReferrals\MedicalReferralResource;
use App\Models\Employee;
use App\Services\Ra1PdfGenerator;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMedicalReferral extends EditRecord
{
    protected static string $resource = MedicalReferralResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /*
         * Ownership postojeće RA-1 uputnice
         * nikada se ne mijenja.
         */
        $ownerId = (int) $this->record->user_id;

        if ($ownerId <= 0) {
            abort(403);
        }

        $data['user_id'] = $ownerId;

        /*
         * Verzija obrasca također ostaje vezana
         * uz postojeću RA-1 uputnicu.
         *
         * Stara uputnica ne smije tijekom uređivanja
         * prijeći na novu verziju obrasca.
         */
        $data['form_version'] = $this->record->form_version;

        /*
         * Kod ručnog unosa zaposlenik nije povezan
         * s Employee zapisom.
         *
         * Time uklanjamo eventualni stari employee_id.
         */
        if (! empty($data['manual_entry'])) {
            $data['employee_id'] = null;
        }

        /*
         * Ako je odabran zaposlenik, mora pripadati
         * istoj organizaciji kao RA-1 uputnica.
         */
        if (! empty($data['employee_id'])) {
            $employeeExists = Employee::query()
                ->whereKey($data['employee_id'])
                ->where('user_id', $ownerId)
                ->exists();

            abort_unless($employeeExists, 403);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Pregled'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(function () {
                    $record = $this->getRecord();

                    $path = Ra1PdfGenerator::generate(
                        $record
                    );

                    return response()
                        ->download(
                            $path,
                            Ra1PdfGenerator::buildFileName(
                                $record,
                                'd.m.Y.'
                            )
                        )
                        ->deleteFileAfterSend(true);
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    protected function getFormContentGrid(): ?array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 1,
            'lg' => 1,
            'xl' => 1,
            '2xl' => 1,
        ];
    }
}