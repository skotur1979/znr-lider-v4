<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Observations\ObservationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditObservation extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        ObservationResource::class;

    public ?string $pregled = null;

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
         * Spremamo kontekst dashboard pregleda
         * prije parent::mount().
         */
        $pregled = request()->query('pregled');

        if (
            in_array(
                $pregled,
                [
                    'isteklo',
                    'uskoro',
                ],
                true
            )
        ) {
            $this->pregled = $pregled;
        }

        /*
         * Filament zatim učitava stvarni
         * Observation model.
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
        | Stara fotografija
        |--------------------------------------------------------------------------
        |
        | Pamtimo put postojeće fotografije kako
        | bismo je nakon spremanja mogli obrisati
        | ako ju je korisnik zamijenio novom.
        |
        */

        $this->oldPicturePath =
            filled(
                $this->record->picture_path
            )
                ? (string)
                    $this->record->picture_path
                : null;

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
        |
        | Adrese se samo spremaju uz zapažanje.
        |
        | Uređivanje ili zatvaranje zapažanja više
        | ne šalje automatsku e-mail obavijest.
        |
        | E-mail se šalje isključivo ručno kroz
        | akciju "Pošalji zapažanje / podsjetnik".
        |
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
         * Namjerno nema automatskog slanja e-maila.
         *
         * E-mail obavijest šalje se samo ručno
         * kroz akciju "Pošalji zapažanje / podsjetnik"
         * u ObservationResource.
         */
    }

    protected function getRedirectUrl(): string
    {
        /*
         * Ako smo došli iz dashboard pregleda
         * Isteklo ili Uskoro, nakon spremanja
         * vraćamo se na isti filtrirani popis.
         */
        if (
            in_array(
                $this->pregled,
                [
                    'isteklo',
                    'uskoro',
                ],
                true
            )
        ) {
            return static::getResource()::getUrl(
                'index',
                [
                    'pregled' =>
                        $this->pregled,
                ]
            );
        }

        /*
         * Kod normalnog ulaska u modul
         * zadržavamo postojeće ponašanje.
         */
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}