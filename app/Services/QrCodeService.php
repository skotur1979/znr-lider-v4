<?php

namespace App\Services;

use App\Models\Fire;
use App\Models\Machine;
use App\Models\QrCode;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;

class QrCodeService
{
    /*
    |--------------------------------------------------------------------------
    | RADNA OPREMA
    |--------------------------------------------------------------------------
    */
    public function findForMachine(
        Machine $machine
    ): ?QrCode {
        return QrCode::query()
            ->where(
                'type',
                'machine'
            )
            ->where(
                'qrable_type',
                Machine::class
            )
            ->where(
                'qrable_id',
                $machine->getKey()
            )
            ->where(
                'owner_id',
                $machine->user_id
            )
            ->first();
    }

    public function getOrCreateForMachine(
        Machine $machine,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        /*
        * Prvo provjeravamo postoji li već QR
        * za ovu radnu opremu.
        */
        $existing = QrCode::query()
            ->where(
                'type',
                'machine'
            )
            ->where(
                'qrable_type',
                Machine::class
            )
            ->where(
                'qrable_id',
                $machine->getKey()
            )
            ->first();

        if ($existing) {
            /*
            * QR vezan uz radnu opremu mora
            * pripadati istom owneru.
            *
            * Ako nije, ne stvaramo drugi QR
            * nego zaustavljamo operaciju jer
            * postoji nekonzistentnost podataka.
            */
            if (
                (int) $existing->owner_id
                !== (int) $machine->user_id
            ) {
                throw new \RuntimeException(
                    'QR kod radne opreme ima neispravnog vlasnika.'
                );
            }

            return $existing;
        }

        return QrCode::create([
            'type' =>
                'machine',

            'qrable_type' =>
                Machine::class,

            'qrable_id' =>
                $machine->getKey(),

            'owner_id' =>
                $machine->user_id,

            'created_by' =>
                $actor?->id,

            'name' =>
                $this->machineQrName(
                    $machine
                ),

            'is_active' =>
                true,
        ]);
    }

    public function publicMachineUrl(
        QrCode $qrCode
    ): string {
        return route(
            'public.machine.show',
            [
                'token' =>
                    $qrCode->token,
            ]
        );
    }

    public function machineSvg(
        QrCode $qrCode,
        int $size = 500
    ): string {
        return $this->generateSvg(
            $this->publicMachineUrl(
                $qrCode
            ),
            $size
        );
    }

    public function machineQrName(
        Machine $machine
    ): string {
        $identifier =
            filled(
                $machine->inventory_number
            )
                ? $machine->inventory_number
                : $machine->factory_number;

        if (filled($identifier)) {
            return $machine->name
                . ' - '
                . $identifier;
        }

        return $machine->name;
    }


    /*
    |--------------------------------------------------------------------------
    | VATROGASNI APARATI
    |--------------------------------------------------------------------------
    */

    public function findForFire(
        Fire $fire
    ): ?QrCode {
        return QrCode::query()
            ->where(
                'type',
                'fire'
            )
            ->where(
                'qrable_type',
                Fire::class
            )
            ->where(
                'qrable_id',
                $fire->getKey()
            )
            ->where(
                'owner_id',
                $fire->user_id
            )
            ->first();
    }

    public function getOrCreateForFire(
        Fire $fire,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        /*
        * Prvo provjeravamo postoji li već QR
        * za ovaj vatrogasni aparat.
        */
        $existing = QrCode::query()
            ->where(
                'type',
                'fire'
            )
            ->where(
                'qrable_type',
                Fire::class
            )
            ->where(
                'qrable_id',
                $fire->getKey()
            )
            ->first();

        if ($existing) {
            /*
            * QR mora pripadati istoj organizaciji
            * kao i vatrogasni aparat.
            */
            if (
                (int) $existing->owner_id
                !== (int) $fire->user_id
            ) {
                throw new \RuntimeException(
                    'QR kod vatrogasnog aparata ima neispravnog vlasnika.'
                );
            }

            return $existing;
        }

        return QrCode::create([
            'type' =>
                'fire',

            'qrable_type' =>
                Fire::class,

            'qrable_id' =>
                $fire->getKey(),

            'owner_id' =>
                $fire->user_id,

            'created_by' =>
                $actor?->id,

            'name' =>
                $this->fireQrName(
                    $fire
                ),

            'is_active' =>
                true,
        ]);
    }

    public function publicFireUrl(
        QrCode $qrCode
    ): string {
        return route(
            'public.fire.show',
            [
                'token' =>
                    $qrCode->token,
            ]
        );
    }

    public function fireSvg(
        QrCode $qrCode,
        int $size = 500
    ): string {
        return $this->generateSvg(
            $this->publicFireUrl(
                $qrCode
            ),
            $size
        );
    }

    public function fireQrName(
        Fire $fire
    ): string {
        $name =
            filled($fire->type)
                ? $fire->type
                : 'Vatrogasni aparat';

        if (
            filled(
                $fire
                    ->factory_number_year_of_production
            )
        ) {
            return $name
                . ' - '
                . $fire
                    ->factory_number_year_of_production;
        }

        return $name;
    }


    /*
    |--------------------------------------------------------------------------
    | ZAPAŽANJA - JEDAN QR PO ORGANIZACIJI
    |--------------------------------------------------------------------------
    */

    public function getOrCreateForObservationOwner(
        User $owner,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        return QrCode::query()
            ->firstOrCreate(
                [
                    'type' =>
                        'observation_report',

                    /*
                     * QR je vezan uz glavnog korisnika,
                     * odnosno organizaciju.
                     */
                    'qrable_type' =>
                        User::class,

                    'qrable_id' =>
                        $owner->getKey(),
                ],
                [
                    'owner_id' =>
                        $owner->getKey(),

                    'created_by' =>
                        $actor?->id,

                    'name' =>
                        'QR prijava zapažanja',

                    'metadata' =>
                        [
                            'purpose' =>
                                'public_observation_report',
                        ],

                    'is_active' =>
                        true,
                ]
            );
    }

    public function publicObservationUrl(
        QrCode $qrCode
    ): string {
        return route(
            'public.observation.show',
            [
                'token' =>
                    $qrCode->token,
            ]
        );
    }

    public function observationSvg(
        QrCode $qrCode,
        int $size = 900
    ): string {
        return $this->generateSvg(
            $this->publicObservationUrl(
                $qrCode
            ),
            $size
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ZAJEDNIČKI QR GENERATOR
    |--------------------------------------------------------------------------
    */

    protected function generateSvg(
        string $url,
        int $size
    ): string {
        $renderer =
            new ImageRenderer(
                new RendererStyle(
                    $size,
                    4
                ),
                new SvgImageBackEnd()
            );

        $writer =
            new Writer(
                $renderer
            );

        return $writer->writeString(
            $url
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ZAJEDNIČKE QR AKCIJE
    |--------------------------------------------------------------------------
    */

    public function regenerate(
        QrCode $qrCode
    ): QrCode {
        $qrCode->forceFill([
            'token' =>
                QrCode::generateUniqueToken(),

            'is_active' =>
                true,

            'scan_count' =>
                0,

            'last_scanned_at' =>
                null,
        ])->save();

        return $qrCode->refresh();
    }

    public function deactivate(
        QrCode $qrCode
    ): void {
        $qrCode->update([
            'is_active' =>
                false,
        ]);
    }

    public function activate(
        QrCode $qrCode
    ): void {
        $qrCode->update([
            'is_active' =>
                true,
        ]);
    }
}