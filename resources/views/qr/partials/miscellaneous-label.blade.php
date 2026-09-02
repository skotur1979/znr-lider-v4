@php

    $labelName =
        trim(
            (string) $miscellaneous->name
        );

    $nameLength =
        mb_strlen(
            $labelName
        );

    /*
     * Duži nazivi dobivaju manji font.
     *
     * Tekst ostaje maksimalno u dva reda.
     */
    $nameClass =
        match (true) {

            $nameLength > 75 =>
                'misc-qr-label-name very-long',

            $nameLength > 48 =>
                'misc-qr-label-name long',

            default =>
                'misc-qr-label-name',
        };

@endphp


<div class="misc-qr-label">

    <div class="misc-qr-label-type">
        ZNR LIDER · OSTALA ISPITIVANJA
    </div>

    <div class="{{ $nameClass }}">
        {{ $labelName }}
    </div>

    <div class="misc-qr-label-category">

        @if(
            filled(
                $miscellaneous
                    ->category
                    ?->name
            )
        )
            {{
                $miscellaneous
                    ->category
                    ->name
            }}
        @endif

    </div>

    <div class="misc-qr-label-code">
        {!! $qrImageHtml !!}
    </div>

    @if(
        filled(
            $miscellaneous->report_number
        )
    )

        <div class="misc-qr-label-identifier">
            Broj izvještaja:
            {{ $miscellaneous->report_number }}
        </div>

    @endif

    <div class="misc-qr-label-instruction">
        Skenirajte QR kod za pregled podataka i dokumentacije ispitivanja.
    </div>

</div>