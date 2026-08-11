<?php

namespace App\Filament\Resources\MedicalReferrals\Pages;

use App\Filament\Resources\MedicalReferrals\MedicalReferralResource;
use App\Models\Employee;
use App\Services\FormVersionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMedicalReferral extends CreateRecord
{
    protected static string $resource = MedicalReferralResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        /*
         * RA-1 je poslovni zapis organizacije.
         * Superadmin ga ne kreira.
         */
        if ($user->isSuperAdmin()) {
            abort(403);
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            abort(403);
        }

        /*
         * Ownership uvijek pripada glavnom korisniku
         * organizacije.
         */
        $data['user_id'] = $ownerId;

        /*
         * Verzija RA-1 obrasca.
         *
         * Ako nije već definirana u formi,
         * koristi se trenutno važeća verzija.
         */
        $data['form_version'] =
            $data['form_version']
            ?? FormVersionService::currentRa1();

        /*
         * Kod ručnog unosa zaposlenik nije povezan
         * s Employee zapisom.
         *
         * Time sprječavamo da u formi slučajno ostane
         * prethodno odabrani employee_id.
         */
        if (! empty($data['manual_entry'])) {
            $data['employee_id'] = null;
        }

        /*
         * Ako je odabran zaposlenik, mora pripadati
         * istoj organizaciji.
         */
        if (! empty($data['employee_id'])) {
            $employeeExists = Employee::query()
                ->whereKey($data['employee_id'])
                ->where('user_id', $ownerId)
                ->exists();

            abort_unless($employeeExists, 403);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
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