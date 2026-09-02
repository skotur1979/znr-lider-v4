<?php

namespace App\Http\Controllers\PublicQr;

use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use App\Http\Controllers\Controller;
use App\Models\FirstAidKit;
use App\Models\QrCode;

class FirstAidKitQrController extends Controller
{
    public function show(
        string $token
    ) {
        $qrCode =
            QrCode::query()
                ->where(
                    'token',
                    $token
                )
                ->where(
                    'type',
                    'first_aid_kit'
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'qrable_type',
                    FirstAidKit::class
                )
                ->firstOrFail();

        $firstAidKit =
            FirstAidKit::query()
                ->with([
                    'items' =>
                        fn ($query) =>
                            $query
                                ->orderByRaw(
                                    'valid_until IS NULL'
                                )
                                ->orderBy(
                                    'valid_until'
                                )
                                ->orderBy(
                                    'material_type'
                                ),
                ])
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->first();

        abort_unless(
            $firstAidKit,
            404,
            'Ormarić prve pomoći nije dostupan.'
        );

        /*
         * QR mora pripadati istoj organizaciji
         * kao i sam ormarić.
         */
        abort_unless(
            (int) $firstAidKit->user_id
                ===
            (int) $qrCode->owner_id,
            404
        );

        /*
        |--------------------------------------------------------------------------
        | MOŽE LI TRENUTNI KORISNIK UREĐIVATI ORMARIĆ
        |--------------------------------------------------------------------------
        |
        | Javna QR ruta nema auth middleware,
        | ali ako je korisnik već prijavljen u aplikaciju,
        | auth()->user() će ga prepoznati kroz postojeću
        | web sesiju.
        |
        | Posjetitelj:
        | $user = null
        | => samo javni pregled.
        |
        | Organizacijski korisnik:
        | smije uređivati samo zapis svoje organizacije.
        |
        | Superadmin:
        | smije administrativno otvoriti uređivanje.
        |
        */

        $user =
            auth()->user();

        $canEdit = false;

        if ($user) {
            if ($user->isSuperAdmin()) {
                $canEdit = true;
            } else {
                $canEdit =
                    (int) $firstAidKit->user_id
                    ===
                    (int) $user->ownerId();
            }
        }

        /*
         * URL postoji samo ako trenutni
         * korisnik smije uređivati zapis.
         */
        $editUrl =
            $canEdit
                ? FirstAidKitResource::getUrl(
                    'edit',
                    [
                        'record' =>
                            $firstAidKit,
                    ]
                )
                : null;

        $qrCode->recordScan();

        return view(
            'public.qr.first-aid-kit',
            compact(
                'firstAidKit',
                'qrCode',
                'canEdit',
                'editUrl'
            )
        );
    }
}