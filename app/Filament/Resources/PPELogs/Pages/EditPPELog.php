<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Filament\Resources\PPELogs\PPELogResource;
use App\Models\PPELog;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

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
        if (
            ! PPELogResource::canEdit(
                $this->record
            )
        ) {
            abort(403);
        }

        /*
         * Ownership Upisnika OZO
         * se ne mijenja kroz edit.
         */
        $data['user_id'] =
            $this->record->user_id;

        $oib = preg_replace(
            '/\s+/',
            '',
            trim(
                (string) (
                    $data['user_oib']
                    ?? ''
                )
            )
        );

        if ($oib === '') {
            throw ValidationException::withMessages([
                'user_oib' =>
                    'OIB je obavezan.',
            ]);
        }

        $duplicate =
            PPELog::withTrashed()
                ->where(
                    'user_id',
                    $this->record->user_id
                )
                ->where(
                    'user_oib',
                    $oib
                )
                ->where(
                    'id',
                    '<>',
                    $this->record->getKey()
                )
                ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'user_oib' =>
                    'Drugi Upisnik OZO za ovaj OIB već postoji.',
            ]);
        }

        $data['user_oib'] =
            $oib;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
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
