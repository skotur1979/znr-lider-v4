<?php

namespace App\Filament\Resources\OntoRecords\Pages;

use App\Filament\Resources\OntoRecords\OntoRecordResource;
use App\Models\WasteOrganization;
use App\Models\WasteOrganizationLocation;
use App\Models\WasteType;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOntoRecord extends CreateRecord
{
    protected static string $resource = OntoRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if (! $user || $user->isSuperAdmin()) {
            abort(403);
        }

        $ownerId = (int) $user->ownerId();

        if ($ownerId <= 0) {
            abort(403);
        }

        /*
         * Ownership ONTO obrasca uvijek pripada
         * glavnom korisniku organizacije.
         */
        $data['user_id'] = $ownerId;

        /*
         * Odabrana organizacija mora pripadati
         * trenutno prijavljenoj organizaciji.
         */
        $organizationId =
            (int) ($data['waste_organization_id'] ?? 0);

        $organizationExists = WasteOrganization::query()
            ->whereKey($organizationId)
            ->where('user_id', $ownerId)
            ->exists();

        abort_unless(
            $organizationExists,
            403,
            'Odabrana organizacija nije dopuštena.'
        );

        /*
         * Lokacija mora:
         * - pripadati odabranoj organizaciji
         * - biti aktivna.
         */
        $locationId =
            (int) ($data['waste_organization_location_id'] ?? 0);

        $locationExists = WasteOrganizationLocation::query()
            ->whereKey($locationId)
            ->where(
                'waste_organization_id',
                $organizationId
            )
            ->where('is_active', true)
            ->exists();

        abort_unless(
            $locationExists,
            403,
            'Odabrana lokacija nije dopuštena.'
        );

        /*
         * Vrsta otpada mora pripadati
         * istoj organizaciji.
         */
        $wasteTypeId =
            (int) ($data['waste_type_id'] ?? 0);

        $wasteTypeExists = WasteType::query()
            ->whereKey($wasteTypeId)
            ->where('user_id', $ownerId)
            ->exists();

        abort_unless(
            $wasteTypeExists,
            403,
            'Odabrana vrsta otpada nije dopuštena.'
        );

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}