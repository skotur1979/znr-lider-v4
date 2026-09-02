@php

    $labelName =
        trim(
            (string)
            $learningMaterial->title
        );

    $nameLength =
        mb_strlen(
            $labelName
        );

    $nameClass =
        match (true) {

            $nameLength > 75 =>
                'learning-qr-label-name very-long',

            $nameLength > 48 =>
                'learning-qr-label-name long',

            default =>
                'learning-qr-label-name',
        };

@endphp


<div class="learning-qr-label">

    <div class="learning-qr-label-type">
        ZNR LIDER · EDUKACIJSKI CENTAR
    </div>

    <div class="{{ $nameClass }}">
        {{ $labelName }}
    </div>

    <div class="learning-qr-label-category">

        @if(
            filled(
                $learningMaterial
                    ->category
                    ?->name
            )
        )

            {{
                $learningMaterial
                    ->category
                    ->name
            }}

        @endif

    </div>

    <div class="learning-qr-label-code">
        {!! $qrImageHtml !!}
    </div>

    <div class="learning-qr-label-scope">

        {{
            $learningMaterial->is_global
                ? 'GLOBALNI MATERIJAL'
                : 'MATERIJAL ORGANIZACIJE'
        }}

    </div>

    <div class="learning-qr-label-instruction">
        Skenirajte QR kod za pregled edukacijskog materijala, linkova i dokumenata.
    </div>

</div>