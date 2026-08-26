<?php

namespace App\Services;

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
    public function getOrCreateForMachine(
        Machine $machine,
        ?User $actor = null
    ): QrCode {
        $actor ??= Auth::user();

        /*
         * Machine user_id kod ZNR LIDER-a predstavlja
         * vlasnika organizacije.
         */
        $ownerId = $machine->user_id;

        return QrCode::query()
            ->firstOrCreate(
                [
                    'type' => 'machine',
                    'qrable_type' => Machine::class,
                    'qrable_id' => $machine->getKey(),
                ],
                [
                    'owner_id' => $ownerId,
                    'created_by' => $actor?->id,
                    'name' => $this->machineQrName($machine),
                    'is_active' => true,
                ]
            );
    }

    public function publicMachineUrl(
        QrCode $qrCode
    ): string {
        return route(
            'public.machine.show',
            [
                'token' => $qrCode->token,
            ]
        );
    }

    public function machineSvg(
        QrCode $qrCode,
        int $size = 500
    ): string {
        $renderer = new ImageRenderer(
            new RendererStyle(
                $size,
                4
            ),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString(
            $this->publicMachineUrl($qrCode)
        );
    }

    public function machineQrName(
        Machine $machine
    ): string {
        $identifier =
            filled($machine->inventory_number)
                ? $machine->inventory_number
                : $machine->factory_number;

        if (filled($identifier)) {
            return $machine->name
                . ' - '
                . $identifier;
        }

        return $machine->name;
    }

    public function regenerate(
        QrCode $qrCode
    ): QrCode {
        $qrCode->forceFill([
            'token' => QrCode::generateUniqueToken(),
            'is_active' => true,
            'scan_count' => 0,
            'last_scanned_at' => null,
        ])->save();

        return $qrCode->refresh();
    }

    public function deactivate(
        QrCode $qrCode
    ): void {
        $qrCode->update([
            'is_active' => false,
        ]);
    }

    public function activate(
        QrCode $qrCode
    ): void {
        $qrCode->update([
            'is_active' => true,
        ]);
    }
}