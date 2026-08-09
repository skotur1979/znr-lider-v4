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

        $data['user_id'] = $ownerId;

        /*
         * Odabrani zaposlenik mora pripadati
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
            Actions\ViewAction::make(),

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
        return $this->getResource()::getUrl('index');
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