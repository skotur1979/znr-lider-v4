<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Filament\Resources\PPELogs\PPELogResource;
use Filament\Resources\Pages\EditRecord;

class EditPPELog extends EditRecord
{
    protected static string $resource =
        PPELogResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo dashboard kontekst.
         */
        $pregled =
            request()->query(
                'pregled'
            );

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
            $this->pregled =
                $pregled;
        }

        parent::mount(
            $record
        );
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership Upisnika OZO ne može
         * se promijeniti uređivanjem.
         */
        $data['user_id'] =
            $this->record->user_id;

        return $data;
    }

    /*
     * PDF i Excel su namjerno uklonjeni
     * s Edit stranice.
     *
     * Nalaze se na Pregled OZO.
     */

    protected function getRedirectUrl(): string
    {
        /*
         * Povratak na dashboard pregled.
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

        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}