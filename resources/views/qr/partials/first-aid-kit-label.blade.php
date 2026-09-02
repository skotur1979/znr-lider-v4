@php

    $location =
        trim(
            (string)
            $firstAidKit->location
        );

    $locationLength =
        mb_strlen(
            $location
        );

    $locationClass =
        match (true) {

            $locationLength > 65 =>
                'first-aid-qr-label-location very-long',

            $locationLength > 38 =>
                'first-aid-qr-label-location long',

            default =>
                'first-aid-qr-label-location',
        };

@endphp


<div class="first-aid-qr-label">

    <div class="first-aid-qr-label-type">
        ZNR LIDER · PRVA POMOĆ
    </div>

    <div class="{{ $locationClass }}">
        {{ $location }}
    </div>

    <div class="first-aid-qr-label-subtitle">
        ORMARIĆ PRVE POMOĆI
    </div>

    <div class="first-aid-qr-label-code">
        {!! $qrImageHtml !!}
    </div>

    <div class="first-aid-qr-label-instruction">
        Skenirajte QR kod za pregled sadržaja ormarića i rokova sanitetskog materijala.
    </div>

</div>