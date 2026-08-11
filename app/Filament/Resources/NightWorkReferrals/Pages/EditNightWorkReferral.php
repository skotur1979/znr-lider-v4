<?php

namespace App\Filament\Resources\NightWorkReferrals\Pages;

use App\Filament\Resources\NightWorkReferrals\NightWorkReferralResource;
use App\Models\Employee;
use App\Services\Nr1PdfGenerator;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNightWorkReferral extends EditRecord
{
    protected static string $resource =
        NightWorkReferralResource::class;

    public function mount(int|string $record): void
    {
        /*
         * Prvo se mora učitati stvarni NR-1 zapis.
         */
        parent::mount($record);

        /*
         * Superadmin smije pregledavati NR-1 zapise,
         * ali ne smije uređivati poslovne zapise
         * organizacija.
         */
        if (auth()->user()?->isSuperAdmin()) {
            $this->redirect(
                NightWorkReferralResource::getUrl(
                    'view',
                    [
                        'record' =>
                            $this->getRecord(),
                    ]
                ),
                navigate: true
            );

            return;
        }
    }

    protected function beforeSave(): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            abort(403);
        }
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        $user = auth()->user();

        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            abort(403);
        }

        /*
         * Ownership postojećeg NR-1 zapisa
         * nikada se ne mijenja.
         */
        $ownerId =
            (int) $this->record->user_id;

        if (
            $ownerId <= 0
            || (int) $user->ownerId()
                !== $ownerId
        ) {
            abort(403);
        }

        $data['user_id'] =
            $ownerId;

        /*
         * Verzija obrasca također ostaje
         * vezana uz postojeću NR-1 uputnicu.
         *
         * Uređivanje starog zapisa ne smije
         * ga prebaciti na novu verziju obrasca.
         */
        $data['form_version'] =
            $this->record->form_version;

        /*
         * Kod ručnog unosa zaposlenik nije
         * povezan s Employee zapisom.
         */
        if (! empty($data['manual_entry'])) {
            $data['employee_id'] = null;
        }

        /*
         * Ako je zaposlenik povezan,
         * mora pripadati istoj organizaciji
         * kao NR-1 zapis.
         */
        if (! empty($data['employee_id'])) {
            $employeeExists = Employee::query()
                ->whereKey($data['employee_id'])
                ->where(
                    'user_id',
                    $ownerId
                )
                ->exists();

            abort_unless(
                $employeeExists,
                403
            );
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
                ->icon(
                    'heroicon-o-arrow-down-tray'
                )
                ->color('danger')
                ->action(function () {
                    $record =
                        $this->getRecord();

                    $path =
                        Nr1PdfGenerator::generate(
                            $record
                        );

                    return response()
                        ->download(
                            $path,
                            Nr1PdfGenerator::
                                buildFileName(
                                    $record,
                                    'd.m.Y.'
                                )
                        )
                        ->deleteFileAfterSend(
                            true
                        );
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
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