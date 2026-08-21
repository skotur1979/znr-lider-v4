<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Observations\ObservationResource;
use App\Mail\ObservationNotificationMail;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EditObservation extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        ObservationResource::class;

    protected array $oldData = [];

    protected array $oldNotificationEmails = [];

    /*
     * Put stare slike prije spremanja.
     *
     * Koristimo ga nakon uspješnog savea kako
     * bismo uklonili staru fizičku datoteku
     * ako ju je zamijenila nova fotografija.
     */
    protected ?string $oldPicturePath = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Kod EditRecord stranice parent::mount()
         * mora biti prvi kako bi Filament ID iz URL-a
         * pretvorio u Observation model.
         */
        parent::mount($record);

        $this->redirectIfMissingModulePermission(
            'update'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Prikaži'),

            DeleteAction::make()
                ->label('Deaktiviraj')
                ->requiresConfirmation()
                ->before(
                    ObservationResource
                        ::beforeModulePermission(
                            'delete'
                        )
                ),
        ];
    }

    protected function getFormContentGrid(): ?array
    {
        return [
            'default' => 1,
            'lg' => 1,
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    protected function beforeSave(): void
    {
        $this->haltIfMissingModulePermission(
            'update'
        );
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Stari podaci
        |--------------------------------------------------------------------------
        |
        | Koriste se za e-mail obavijest o izmjenama.
        |
        */

        $this->oldData =
            $this->record->only([
                'incident_date',
                'observation_type',
                'priority',
                'location',
                'item',
                'potential_incident_type',
                'picture_path',
                'action',
                'responsible',
                'notification_emails',
                'target_date',
                'status',
                'comments',
            ]);

        /*
         * Posebno pamtimo put stare slike.
         */
        $this->oldPicturePath =
            filled(
                $this->record->picture_path
            )
                ? (string)
                    $this->record->picture_path
                : null;

        $this->oldNotificationEmails =
            collect(
                $this->record
                    ->notification_emails
                    ?? []
            )
                ->map(
                    fn ($email): string =>
                        trim(
                            (string) $email
                        )
                )
                ->filter()
                ->unique()
                ->values()
                ->all();

        /*
        |--------------------------------------------------------------------------
        | Nova fotografija snimljena kamerom
        |--------------------------------------------------------------------------
        |
        | Ako je korisnik fotografirao novu sliku,
        | ona postaje nova picture_path vrijednost.
        |
        | Postojeća slika u bazi time se zamjenjuje.
        |
        */

        if (
            ! empty(
                $data['camera_picture']
                ?? null
            )
        ) {
            $data['picture_path'] =
                $data['camera_picture'];
        }

        /*
         * Pomoćno polje ne postoji
         * u observations tablici.
         */
        unset(
            $data['camera_picture']
        );

        /*
        |--------------------------------------------------------------------------
        | Zadane vrijednosti
        |--------------------------------------------------------------------------
        */

        if (
            blank(
                $data['priority']
                ?? null
            )
        ) {
            $data['priority'] =
                'medium';
        }

        if (
            blank(
                $data['status']
                ?? null
            )
        ) {
            $data['status'] =
                'Not started';
        }

        /*
        |--------------------------------------------------------------------------
        | E-mail adrese
        |--------------------------------------------------------------------------
        */

        $data['notification_emails'] =
            collect(
                $data[
                    'notification_emails'
                ] ?? []
            )
                ->map(
                    fn ($email): string =>
                        trim(
                            (string) $email
                        )
                )
                ->filter()
                ->unique()
                ->values()
                ->all();

        return $data;
    }

    protected function afterSave(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Brisanje stare slike
        |--------------------------------------------------------------------------
        |
        | Stara fizička datoteka briše se samo:
        |
        | 1. ako je prije postojala
        | 2. ako se picture_path promijenio
        |
        | Tako ne ostavljamo nepotrebne stare slike
        | u storage/app/public/observations.
        |
        */

        $newPicturePath =
            filled(
                $this->record->picture_path
            )
                ? (string)
                    $this->record->picture_path
                : null;

        if (
            filled(
                $this->oldPicturePath
            )
            && $this->oldPicturePath
                !== $newPicturePath
        ) {
            $disk =
                Storage::disk(
                    'public'
                );

            if (
                $disk->exists(
                    $this->oldPicturePath
                )
            ) {
                $disk->delete(
                    $this->oldPicturePath
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | E-mail obavijest
        |--------------------------------------------------------------------------
        */

        $emails =
            collect(
                $this->record
                    ->notification_emails
                    ?? []
            )
                ->map(
                    fn ($email): string =>
                        trim(
                            (string) $email
                        )
                )
                ->filter()
                ->unique()
                ->values()
                ->all();

        /*
         * Ako nema primatelja, mail se ne šalje.
         *
         * Brisanje stare slike iznad se ipak već
         * izvršilo, što je važno.
         */
        if (empty($emails)) {
            return;
        }

        foreach (
            $emails
            as $email
        ) {
            Mail::to($email)->send(
                new ObservationNotificationMail(
                    observation:
                        $this->record,

                    mode:
                        'updated',

                    oldData:
                        $this->oldData,
                )
            );
        }

        $this->record->updateQuietly([
            'notification_emails' =>
                $emails,

            'sent_at' =>
                now(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}