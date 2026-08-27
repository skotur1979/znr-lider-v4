<div class="machine-qr-label">

    <div class="machine-qr-label-type">
        ZNR LIDER · RADNA OPREMA
    </div>

    <div class="machine-qr-label-name">
        {{ $machine->name }}
    </div>

    <div class="machine-qr-label-location">

        @if(
            filled($machine->location)
            && trim((string) $machine->location) !== '-'
        )
            {{ $machine->location }}
        @endif

    </div>

    <div class="machine-qr-label-code">
        {!! $qrImageHtml !!}
    </div>

    <div class="machine-qr-label-identifiers">

        @if(
            filled($machine->inventory_number)
            && trim((string) $machine->inventory_number) !== '-'
        )

            <div class="machine-qr-label-identifier">
                Inventarni broj:
                {{ $machine->inventory_number }}
            </div>

        @endif

        @if(
            filled($machine->factory_number)
            && trim((string) $machine->factory_number) !== '-'
        )

            <div class="machine-qr-label-identifier">
                Tvornički broj:
                {{ $machine->factory_number }}
            </div>

        @endif

    </div>

    <div class="machine-qr-label-instruction">
        Skenirajte QR kod za pregled podataka
        i dokumentacije radne opreme.
    </div>

</div>