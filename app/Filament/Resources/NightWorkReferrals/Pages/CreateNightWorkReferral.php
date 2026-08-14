<?php

namespace App\Filament\Resources\NightWorkReferrals\Pages;

use App\Filament\Resources\NightWorkReferrals\NightWorkReferralResource;
use App\Models\Employee;
use App\Services\FormVersionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNightWorkReferral extends CreateRecord
{
    protected static string $resource =
        NightWorkReferralResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        /*
         * NR-1 je poslovni zapis organizacije.
         * Superadmin ga ne kreira.
         */
        if ($user->isSuperAdmin()) {
            abort(403);
        }

        $ownerId = (int) $user->ownerId();

        if ($ownerId <= 0) {
            abort(403);
        }

        /*
         * Ownership uvijek pripada glavnom
         * korisniku organizacije.
         */
        $data['user_id'] = $ownerId;

        /*
         * Ako verzija NR-1 obrasca nije već
         * određena kroz formu, koristi se
         * trenutno važeća verzija.
         */
        $data['form_version'] =
            $data['form_version']
            ?? FormVersionService::currentNr1();

        /*
         * Kod ručnog unosa zaposlenik nije
         * povezan s Employee zapisom.
         *
         * Time sprječavamo da slučajno ostane
         * employee_id iz prethodnog odabira.
         */
        if (! empty($data['manual_entry'])) {
            $data['employee_id'] = null;
        }

        /*
         * Ako je povezan zaposlenik,
         * mora pripadati istoj organizaciji.
         */
        if (! empty($data['employee_id'])) {
            $employeeExists = Employee::query()
                ->whereKey($data['employee_id'])
                ->where('user_id', $ownerId)
                ->exists();

            abort_unless(
                $employeeExists,
                403
            );
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
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
