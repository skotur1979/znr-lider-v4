<?php

namespace App\Filament\Resources\InspectionZones\Pages;

use App\Filament\Resources\Inspections\InspectionResource;
use App\Filament\Resources\InspectionZones\InspectionZoneResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditInspectionZone extends EditRecord
{
    protected static string $resource =
        InspectionZoneResource::class;

    public function mount(
        int|string $record
    ): void {
        parent::mount($record);

        abort_unless(
            InspectionResource::canUpdateModuleRecord(),
            403,
            'Nemate ovlasti za uređivanje nadzora.'
        );
    }

    protected function beforeSave(): void
    {
        abort_unless(
            InspectionResource::canUpdateModuleRecord(),
            403,
            'Nemate ovlasti za uređivanje nadzora.'
        );
    }

    public function getTitle(): string
    {
        return 'Ocjenjivanje zone';
    }

    protected function getReturnUrl(): string
    {
        $fallback =
            InspectionResource::getUrl(
                'index'
            );

        $returnUrl =
            request()->query(
                'return_url'
            );

        if (
            blank($returnUrl)
            || ! is_string($returnUrl)
        ) {
            return $fallback;
        }

        $parts =
            parse_url($returnUrl);

        if ($parts === false) {
            return $fallback;
        }

        $host =
            $parts['host']
            ?? null;

        $path =
            $parts['path']
            ?? '';

        /*
         * Ne dopuštamo redirect na vanjsku domenu.
         */
        if (
            $host !== null
            && strcasecmp(
                $host,
                request()->getHost()
            ) !== 0
        ) {
            return $fallback;
        }

        /*
         * Povratak je dopušten samo unutar
         * modula Nadzori.
         */
        if (
            ! str_starts_with(
                $path,
                '/admin/inspections'
            )
        ) {
            return $fallback;
        }

        $query =
            isset($parts['query'])
                ? '?' . $parts['query']
                : '';

        return $host !== null
            ? url($path) . $query
            : $path . $query;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Povratak')
                ->icon(
                    'heroicon-o-arrow-left'
                )
                ->color('gray')
                ->url(
                    $this->getReturnUrl()
                )
                ->extraAttributes([
                    'type' => 'button',
                ]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getReturnUrl();
    }
}