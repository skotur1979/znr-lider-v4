@php

    $labelName =
        trim(
            (string)
            $ppeEquipment->name
        );

    $nameLength =
        mb_strlen(
            $labelName
        );

    $nameClass =
        match (true) {

            $nameLength > 75 =>
                'ppe-qr-label-name very-long',

            $nameLength > 48 =>
                'ppe-qr-label-name long',

            default =>
                'ppe-qr-label-name',
        };

@endphp


<div class="ppe-qr-label">

    <div class="ppe-qr-label-type">
        ZNR LIDER · OSOBNA ZAŠTITNA OPREMA
    </div>

    <div class="{{ $nameClass }}">
        {{ $labelName }}
    </div>

    <div class="ppe-qr-label-standard">

        @if(
            filled(
                $ppeEquipment->standard
            )
        )
            {{
                $ppeEquipment->standard
            }}
        @endif

    </div>

    <div class="ppe-qr-label-code">
        {!! $qrImageHtml !!}
    </div>

    @if(
        filled(
            $ppeEquipment->duration_months
        )
    )

        <div class="ppe-qr-label-duration">
            Rok uporabe:
            {{ $ppeEquipment->duration_months }}
            mj.
        </div>

    @endif

    <div class="ppe-qr-label-instruction">
        Skenirajte QR kod za pregled podataka, certifikata i uputa za korištenje.
    </div>

</div>