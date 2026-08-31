<div class="misc-qr-label">

    <div class="misc-qr-label-type">
        ZNR LIDER · OSTALA ISPITIVANJA
    </div>

    <div class="misc-qr-label-name">
        {{ $miscellaneous->name }}
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