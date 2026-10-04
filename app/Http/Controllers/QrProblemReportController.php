<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Observations\ObservationResource;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Models\QrCode;
use Illuminate\Http\RedirectResponse;

class QrProblemReportController extends Controller
{
    public function __invoke(
        string $token
    ): RedirectResponse {
        $qrCode = QrCode::query()
            ->where('token', $token)
            ->where('is_active', true)
            ->whereIn(
                'type',
                [
                    'machine',
                    'miscellaneous',
                ]
            )
            ->firstOrFail();

        /*
         * Ako korisnik nije prijavljen,
         * šaljemo ga na ZNR LIDER login.
         *
         * redirect()->guest() sprema intended URL
         * kako bi se nakon prijave mogao vratiti
         * na ovu radnju.
         */
        if (! auth()->check()) {
            return redirect()->guest(
                route(
                    'filament.admin.auth.login'
                )
            );
        }

        $user = auth()->user();

        /*
         * Superadmin ne kreira poslovna
         * zapažanja u ime organizacije.
         */
        if ($user->isSuperAdmin()) {
            return $this->backToQr(
                $qrCode,
                'Superadministrator ne kreira zapažanja u ime organizacije.'
            );
        }

        /*
         * Tenant zaštita.
         */
        if (
            (int) $user->ownerId()
            !==
            (int) $qrCode->owner_id
        ) {
            return $this->backToQr(
                $qrCode,
                'Prijavu problema mogu napraviti samo korisnici organizacije kojoj ovaj QR kod pripada.'
            );
        }

        /*
         * Korisnik mora imati pravo CREATE
         * za modul Zapažanja.
         */
        if (
            ! ObservationResource::allowsModulePermission(
                'create'
            )
        ) {
            return $this->backToQr(
                $qrCode,
                'Nemate ovlasti za akciju, kontaktirajte administratora.'
            );
        }

        /*
         * Dodatno potvrđujemo da stvarni zapis
         * pripada QR kodu i organizaciji.
         */
        if ($qrCode->type === 'machine') {
            abort_unless(
                $qrCode->qrable_type
                    === Machine::class,
                404
            );

            $recordExists =
                Machine::query()
                    ->whereKey(
                        $qrCode->qrable_id
                    )
                    ->where(
                        'user_id',
                        $qrCode->owner_id
                    )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->exists();

            abort_unless(
                $recordExists,
                404
            );
        }

        if (
            $qrCode->type
            === 'miscellaneous'
        ) {
            abort_unless(
                $qrCode->qrable_type
                    === Miscellaneous::class,
                404
            );

            $recordExists =
                Miscellaneous::query()
                    ->whereKey(
                        $qrCode->qrable_id
                    )
                    ->where(
                        'user_id',
                        $qrCode->owner_id
                    )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->exists();

            abort_unless(
                $recordExists,
                404
            );
        }

        return redirect()->to(
            ObservationResource::getUrl(
                'create',
                [
                    'qr_problem_token' =>
                        $qrCode->token,
                ]
            )
        );
    }

    protected function backToQr(
        QrCode $qrCode,
        string $message
    ): RedirectResponse {
        $routeName =
            match ($qrCode->type) {
                'machine' =>
                    'public.machine.show',

                'miscellaneous' =>
                    'public.miscellaneous.show',

                default =>
                    abort(404),
            };

        return redirect()
            ->route(
                $routeName,
                [
                    'token' =>
                        $qrCode->token,
                ]
            )
            ->with(
                'qr_error',
                $message
            );
    }
}