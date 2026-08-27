<div class="fire-qr-label">

    <div class="fire-qr-label-type">
        ZNR LIDER · VATROGASNI APARAT
    </div>

    <div class="fire-qr-label-name">
        {{ $fire->type ?: 'Vatrogasni aparat' }}
    </div>

    <div class="fire-qr-label-place">
        {{ $fire->place }}
    </div>

    <div class="fire-qr-label-code">
        {!! $qrImageHtml !!}
    </div>

    @if(
        filled($fire->factory_number_year_of_production)
        && trim((string) $fire->factory_number_year_of_production) !== '-'
    )

        <div class="fire-qr-label-factory">
            {{ $fire->factory_number_year_of_production }}
        </div>

        <div class="fire-qr-label-factory-caption">
            Tvornički broj / godina proizvodnje
        </div>

    @endif

    <div class="fire-qr-label-instruction">
        Skenirajte QR kod za pregled podataka vatrogasnog aparata.
    </div>

</div>