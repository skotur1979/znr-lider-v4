<style>
    .znr-problem-card {
        margin-bottom: 16px;
        padding: 20px;
        background: #fff7ed;
        border: 1px solid #fdba74;
        border-radius: 16px;
    }

    .znr-problem-card h2 {
        margin: 0 0 8px;
        color: #9a3412;
        font-size: 18px;
    }

    .znr-problem-card p {
        margin: 0 0 14px;
        color: #4b5563;
        font-size: 14px;
        line-height: 1.5;
    }

    .znr-problem-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 13px 16px;
        border-radius: 10px;
        background: #f59e0b;
        color: #111827;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
    }

    .znr-problem-button:hover {
        background: #d97706;
    }

    .znr-problem-note {
        margin-top: 10px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.4;
    }

    .znr-qr-error {
        margin-bottom: 16px;
        padding: 12px 14px;
        border: 1px solid #fecaca;
        border-radius: 10px;
        background: #fef2f2;
        color: #991b1b;
        font-size: 13px;
        line-height: 1.5;
    }
</style>

@if(session('qr_error'))

    <div class="znr-qr-error">
        {{ session('qr_error') }}
    </div>

@endif

<div class="znr-problem-card">

    <h2>
        Uočili ste problem?
    </h2>

    <p>
        Ako ste ovlašteni korisnik ZNR LIDER-a,
        možete prijaviti problem i otvoriti novo
        zapažanje povezano s {{ $contextLabel }}.
    </p>

    <a
        href="{{ route(
            'qr.problem-report',
            [
                'token' => $qrCode->token,
            ]
        ) }}"
        class="znr-problem-button"
    >
        ⚠ Prijavi problem
    </a>

    <div class="znr-problem-note">

        @auth
            Prijava će se otvoriti u modulu Zapažanja.
        @else
            Za prijavu problema potrebna je prijava u ZNR LIDER.
        @endauth

    </div>

</div>