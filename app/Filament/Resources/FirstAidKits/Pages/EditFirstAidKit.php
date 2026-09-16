<?php

namespace App\Filament\Resources\FirstAidKits\Pages;

use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use Filament\Resources\Pages\EditRecord;

class EditFirstAidKit extends EditRecord
{
    protected static string $resource =
        FirstAidKitResource::class;

    public ?string $pregled = null;

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

        parent::mount($record);
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership postojećeg zapisa
         * prve pomoći nikada se ne mijenja
         * kroz edit formu.
         */
        unset($data['user_id']);

        return $data;
    }

    public function getTitle(): string
    {
        return 'Uredi Prva pomoć';
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