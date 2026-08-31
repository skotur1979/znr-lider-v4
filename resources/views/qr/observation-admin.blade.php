<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        QR - Prijava zapažanja
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;

            background: #f3f4f6;
            color: #111827;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .page {
            width: 100%;
            max-width: 1100px;

            margin: 0 auto;
        }

        h1 {
            margin: 0;

            font-size: 25px;
        }

        .subtitle {
            margin:
                6px
                0
                20px;

            color: #6b7280;

            font-size: 14px;
        }

        .success {
            margin-bottom: 20px;

            padding:
                13px
                16px;

            border:
                1px
                solid
                #86efac;

            border-radius: 10px;

            background: #dcfce7;
            color: #166534;

            font-weight: 700;
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;

            gap: 10px;

            margin-bottom: 22px;
        }

        .button {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-height: 40px;

            padding:
                10px
                16px;

            border: 0;
            border-radius: 8px;

            font-size: 14px;
            font-weight: 700;

            text-decoration: none;

            cursor: pointer;
        }

        .dark {
            background: #111827;
            color: #ffffff;
        }

        .gray {
            background: #4b5563;
            color: #ffffff;
        }

        .warning {
            background: #f59e0b;
            color: #111827;
        }

        .danger {
            background: #dc2626;
            color: #ffffff;
        }

        .green {
            background: #16a34a;
            color: #ffffff;
        }

        .layout {
            display: grid;

            grid-template-columns:
                minmax(0, 620px)
                minmax(280px, 1fr);

            gap: 22px;

            align-items: start;
        }

        .poster-preview {
            overflow: hidden;

            border:
                1px
                solid
                #d1d5db;

            border-radius: 12px;

            background: #ffffff;
        }

        .observation-poster {
            position: relative;

            min-height: 780px;

            padding:
                36px
                40px;

            text-align: center;
        }

        .poster-brand {
            font-size: 24px;
            font-weight: 800;

            letter-spacing: 2px;
        }

        .poster-title {
            margin-top: 28px;

            font-size: 38px;
            font-weight: 900;

            line-height: 1.05;
        }

        .poster-subtitle {
            max-width: 470px;

            margin:
                22px
                auto
                0;

            color: #4b5563;

            font-size: 19px;

            line-height: 1.4;
        }

        .poster-qr {
            width: 90mm;
            height: 90mm;

            margin:
                25px
                auto
                0;
        }

        .poster-qr svg,
        .poster-qr img {
            display: block;

            width: 90mm !important;
            height: 90mm !important;

            margin: 0;
            padding: 0;
        }

        .poster-scan {
            margin-top: 20px;

            font-size: 26px;
            font-weight: 900;
        }

        .poster-items {
            width: 270px;

            margin:
                18px
                auto
                0;

            font-size: 18px;

            line-height: 1.7;

            text-align: left;
        }

        .poster-login {
            margin-top: 22px;

            font-size: 16px;
            font-weight: 700;
        }

        .poster-thanks {
            margin-top: 22px;

            color: #6b7280;

            font-size: 15px;
        }

        .card {
            margin-bottom: 18px;

            padding: 20px;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 12px;

            background: #ffffff;

            box-shadow:
                0
                1px
                3px
                rgba(0, 0, 0, .05);
        }

        .card h2 {
            margin:
                0
                0
                14px;

            font-size: 17px;
        }

        .row {
            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 12px;

            padding:
                10px
                0;

            border-bottom:
                1px
                solid
                #f3f4f6;

            font-size: 13px;
        }

        .row:last-child {
            border-bottom: 0;
        }

        .label {
            color: #6b7280;
        }

        .value {
            text-align: right;

            font-weight: 700;
        }

        .badge {
            display: inline-block;

            padding:
                4px
                8px;

            border-radius: 999px;

            font-size: 12px;
        }

        .active {
            background: #dcfce7;
            color: #166534;
        }

        .inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .url {
            padding: 10px;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 8px;

            background: #f9fafb;
            color: #6b7280;

            font-family: monospace;
            font-size: 10px;

            word-break: break-all;
        }

        @media (
            max-width: 900px
        ) {

            body {
                padding: 15px;
            }

            .layout {
                grid-template-columns: 1fr;
            }
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {

            html,
            body {
                width: 210mm !important;
                height: 297mm !important;

                margin: 0 !important;
                padding: 0 !important;

                background: #ffffff !important;

                overflow: hidden !important;
            }

            h1,
            .subtitle,
            .success,
            .toolbar,
            .details {
                display: none !important;
            }

            .page,
            .layout,
            .poster-preview {
                width: 210mm !important;
                height: 297mm !important;

                max-width: none !important;

                margin: 0 !important;
                padding: 0 !important;

                border: 0 !important;
                border-radius: 0 !important;

                overflow: hidden !important;

                display: block !important;
            }

            .observation-poster {
                width: 210mm !important;
                height: 297mm !important;

                padding:
                    14mm
                    18mm !important;

                box-sizing: border-box !important;
            }

            .poster-brand {
                font-size: 16pt !important;
            }

            .poster-title {
                margin-top: 10mm !important;

                font-size: 27pt !important;
            }

            .poster-subtitle {
                width: 145mm !important;

                margin:
                    8mm
                    auto
                    0 !important;

                font-size: 13pt !important;
            }

            .poster-qr,
            .poster-qr svg,
            .poster-qr img {
                width: 90mm !important;
                height: 90mm !important;
            }

            .poster-qr {
                margin:
                    10mm
                    auto
                    0 !important;
            }

            .poster-scan {
                margin-top: 6mm !important;

                font-size: 18pt !important;
            }

            .poster-items {
                width: 100mm !important;

                margin:
                    6mm
                    auto
                    0 !important;

                font-size: 12pt !important;
            }

            .poster-login {
                margin-top: 8mm !important;

                font-size: 11pt !important;
            }

            .poster-thanks {
                margin-top: 9mm !important;

                font-size: 10pt !important;
            }
        }

    </style>

</head>

<body>

<div class="page">

    <h1>
        QR kod - Prijava zapažanja
    </h1>

    <div class="subtitle">
        Upravljanje javnim QR kodom za prijavu
        Near Miss i negativnih zapažanja.
    </div>


    @if(session('qr_success'))

        <div class="success">
            {{ session('qr_success') }}
        </div>

    @endif


    <div class="toolbar">

        <button
            type="button"
            class="button dark"
            onclick="window.print()"
        >
            Ispiši A4 poster
        </button>

        <a
            href="{{ route(
                'observation.qr.download.pdf'
            ) }}"
            class="button gray"
        >
            Preuzmi PDF
        </a>

        @if($qrCode->is_active)

            <a
                href="{{ $publicUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="button gray"
            >
                Testiraj javni obrazac
            </a>

        @endif

        <form
            method="POST"
            action="{{ route(
                'observation.qr.regenerate'
            ) }}"
            onsubmit="
                return confirm(
                    'Regeneriranjem QR koda stari poster odmah prestaje raditi. Nastaviti?'
                );
            "
        >

            @csrf

            <button
                type="submit"
                class="button warning"
            >
                Regeneriraj QR kod
            </button>

        </form>


        @if($qrCode->is_active)

            <form
                method="POST"
                action="{{ route(
                    'observation.qr.deactivate'
                ) }}"
                onsubmit="
                    return confirm(
                        'Deaktivirati QR kod? Postojeći poster više neće otvarati obrazac.'
                    );
                "
            >

                @csrf

                <button
                    type="submit"
                    class="button danger"
                >
                    Deaktiviraj QR kod
                </button>

            </form>

        @else

            <form
                method="POST"
                action="{{ route(
                    'observation.qr.activate'
                ) }}"
            >

                @csrf

                <button
                    type="submit"
                    class="button green"
                >
                    Aktiviraj QR kod
                </button>

            </form>

        @endif

    </div>


    <div class="layout">

        <div class="poster-preview">

            @php
                $qrImageHtml =
                    $svg;
            @endphp

            @include(
                'qr.partials.observation-poster',
                [
                    'qrImageHtml' =>
                        $qrImageHtml,
                ]
            )

        </div>


        <div class="details">

            <div class="card">

                <h2>
                    Status QR koda
                </h2>

                <div class="row">

                    <div class="label">
                        Status
                    </div>

                    <div class="value">

                        @if($qrCode->is_active)

                            <span class="badge active">
                                Aktivan
                            </span>

                        @else

                            <span class="badge inactive">
                                Deaktiviran
                            </span>

                        @endif

                    </div>

                </div>


                <div class="row">

                    <div class="label">
                        Ukupno skeniranja
                    </div>

                    <div class="value">
                        {{
                            number_format(
                                $qrCode->scan_count ?? 0,
                                0,
                                ',',
                                '.'
                            )
                        }}
                    </div>

                </div>


                <div class="row">

                    <div class="label">
                        Zadnje skeniranje
                    </div>

                    <div class="value">

                        @if($qrCode->last_scanned_at)

                            {{
                                $qrCode
                                    ->last_scanned_at
                                    ->timezone(
                                        'Europe/Zagreb'
                                    )
                                    ->format(
                                        'd.m.Y. H:i'
                                    )
                            }}

                        @else

                            Nikada

                        @endif

                    </div>

                </div>


                <div class="row">

                    <div class="label">
                        QR kreiran
                    </div>

                    <div class="value">
                        {{
                            $qrCode
                                ->created_at
                                ?->timezone(
                                    'Europe/Zagreb'
                                )
                                ->format(
                                    'd.m.Y. H:i'
                                )
                        }}
                    </div>

                </div>

            </div>


            <div class="card">

                <h2>
                    Javna poveznica
                </h2>

                <div class="url">
                    {{ $publicUrl }}
                </div>

            </div>


            <div class="card">

                <h2>
                    Važno
                </h2>

                <p style="
                    margin:0;
                    color:#6b7280;
                    font-size:13px;
                    line-height:1.6;
                ">
                    QR kod je univerzalan za organizaciju.
                    Lokaciju prijavitelj upisuje ručno.
                    Regeneriranjem QR koda svi prethodno
                    ispisani posteri prestaju raditi.
                </p>

            </div>

        </div>

    </div>

</div>

</body>

</html>