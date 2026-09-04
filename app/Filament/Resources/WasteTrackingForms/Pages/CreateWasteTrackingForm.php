<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use App\Services\FormVersionService;
use Filament\Resources\Pages\CreateRecord;

class CreateWasteTrackingForm extends CreateRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        WasteTrackingFormResource::class;

    public function mount(): void
    {
        if (
            $this->redirectIfMissingModulePermission(
                'create'
            )
        ) {
            return;
        }

        parent::mount();
    }

    protected function beforeCreate(): void
    {
        $this->haltIfMissingModulePermission(
            'create'
        );
    }

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $data =
            WasteTrackingFormResource::fillOwnershipData(
                $data
            );

        /*
         * Poštujemo ručni odabir korisnika.
         *
         * Samo ako verzija iz nekog razloga
         * nije odabrana koristimo preporučenu
         * verziju prema datumu.
         */
        if (blank($data['form_version'] ?? null)) {
            $data['form_version'] =
                FormVersionService::ploForDate(
                    $data['handover_date'] ?? null
                );
        }

        /*
         * Novi obrazac nema polje
         * "Izvješće o obradi otpada".
         */
        if (
            FormVersionService::isCurrentPlo(
                $data['form_version']
            )
        ) {
            $data['report_choice'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this
            ->getResource()::getUrl(
                'index'
            );
    }
}
