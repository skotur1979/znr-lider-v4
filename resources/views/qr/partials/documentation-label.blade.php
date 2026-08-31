<div class="documentation-qr-label">

    <div class="documentation-qr-label-type">
        ZNR LIDER · DOKUMENTACIJA
    </div>

    <div class="documentation-qr-label-name">
        {{ $documentationItem->naziv }}
    </div>

    <div class="documentation-qr-label-company">
        @if(
            filled($documentationItem->tvrtka)
            && trim($documentationItem->tvrtka) !== '-'
        )
            {{ $documentationItem->tvrtka }}
        @endif
    </div>

    <div class="documentation-qr-label-code">
        {!! $qrImageHtml !!}
    </div>

    @if($documentationItem->datum_izrade)

        <div class="documentation-qr-label-date">
            Datum:
            {{ $documentationItem->datum_izrade->format('d.m.Y.') }}
        </div>

    @endif

    <div class="documentation-qr-label-instruction">
        Skenirajte QR kod za pregled dokumentacije i priloga.
    </div>

</div>