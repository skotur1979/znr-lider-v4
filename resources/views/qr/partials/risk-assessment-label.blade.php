<div class="risk-qr-label">

    <div class="risk-qr-label-type">
        ZNR LIDER · PROCJENA RIZIKA
    </div>

    <div class="risk-qr-label-company">
        {{ $riskAssessment->tvrtka }}
    </div>

    <div class="risk-qr-label-number">
        Procjena:
        {{ $riskAssessment->broj_procjene }}
    </div>

    <div class="risk-qr-label-code">
        {!! $qrImageHtml !!}
    </div>

    <div class="risk-qr-label-instruction">
        Skenirajte QR kod za pregled
        procjene rizika i dokumentacije.
    </div>

</div>