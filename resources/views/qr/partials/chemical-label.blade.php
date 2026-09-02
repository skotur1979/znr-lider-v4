@php

    $labelName =
        trim(
            (string)
            $chemical->product_name
        );

    $nameLength =
        mb_strlen(
            $labelName
        );

    $nameClass =
        match (true) {

            $nameLength > 75 =>
                'chemical-qr-label-name very-long',

            $nameLength > 48 =>
                'chemical-qr-label-name long',

            default =>
                'chemical-qr-label-name',
        };

@endphp


<div class="chemical-qr-label">

    <div class="chemical-qr-label-type">
        ZNR LIDER · KEMIKALIJE
    </div>

    <div class="{{ $nameClass }}">
        {{ $labelName }}
    </div>

    <div class="chemical-qr-label-subtitle">

        @if(
            filled(
                $chemical->cas_number
            )
        )
            CAS:
            {{ $chemical->cas_number }}
        @else
            SIGURNOSNI PODACI
        @endif

    </div>

    <div class="chemical-qr-label-code">
        {!! $qrImageHtml !!}
    </div>

    <div class="chemical-qr-label-instruction">
        Skenirajte QR kod za pregled podataka, oznaka opasnosti i sigurnosnih dokumenata.
    </div>

</div>