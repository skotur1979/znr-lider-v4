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
         * Filament prvo učitava stvarni NR-1 zapis
         * kroz Resource query.
         *
         * BaseResource već osigurava:
         * - superadmin vidi sve postojeće zapise
         * - organizacijski korisnik vidi samo
         *   zapise svoje organizacije.
         */
        parent::mount($record);


        /*
         * Dodatna provjera prava uređivanja.
         *
         * Superadmin smije uređivati postojeći zapis,
         * ali ne može promijeniti njegov ownership.
         */
        if (
            ! NightWorkReferralResource::canEdit(
                $this->getRecord()
            )
        ) {
            abort(403);
        }
    }


    protected function beforeSave(): void
    {
        /*
         * Ponovna serverska provjera neposredno
         * prije spremanja.
         */
        if (
            ! NightWorkReferralResource::canEdit(
                $this->getRecord()
            )
        ) {
            $this->halt();

            abort(403);
        }
    }


    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        $user = auth()->user();


        if (! $user) {
            abort(403);
        }


        /*
         * Ownership postojećeg NR-1 zapisa
         * nikada se ne mijenja.
         */
        $ownerId =
            (int) $this->record->user_id;


        if ($ownerId <= 0) {
            abort(403);
        }


        /*
         * Organizacijski korisnik mora pripadati
         * istoj organizaciji kao postojeći zapis.
         *
         * Superadmin smije administrirati postojeći
         * zapis bez promjene ownershipa.
         */
        if (
            ! $user->isSuperAdmin()
            && (int) $user->ownerId() !== $ownerId
        ) {
            abort(403);
        }


        $data['user_id'] =
            $ownerId;


        /*
         * Verzija obrasca ostaje vezana uz
         * postojeću NR-1 uputnicu.
         *
         * Uređivanje starog zapisa ne smije
         * ga automatski prebaciti na novu
         * verziju obrasca.
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
         *
         * Ovo vrijedi i kada zapis uređuje
         * superadmin.
         */
        if (! empty($data['employee_id'])) {
            $employeeExists = Employee::query()
                ->whereKey(
                    $data['employee_id']
                )
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