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
         * Ownership Upisnika OZO
         * se ne mijenja kroz edit.
         */
        $data['user_id'] =
            $this
                ->record
                ->user_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        /*
         * Ako smo došli iz:
         *
         * Isteklo
         * ili
         * Uskoro
         *
         * vraćamo se upravo na taj
         * filtrirani pregled.
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

                    'tableRecordsPerPage' =>
                        'all',
                ]
            );
        }

        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}