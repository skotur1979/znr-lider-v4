<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        QR - {{ $chemical->product_name }}
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
            max-width: 900px;

            margin: 0 auto;
        }

        .page-title {
            margin-bottom: 20px;
        }

        .page-title h1 {
            margin: 0;
            font-size: 24px;
        }

        .page-title p {
            margin:
                6px
                0
                0;

            color: #6b7280;
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

            font-weight: 700;

            text-decoration: none;

            cursor: pointer;
        }

        .button-dark {
            background: #111827;
            color: #ffffff;
        }

        .button-gray {
            background: #4b5563;
            color: #ffffff;
        }

        .button-warning {
            background: #f59e0b;
            color: #111827;
        }

        .button-danger {
            background: #dc2626;
            color: #ffffff;
        }

        .button-success {
            background: #16a34a;
            color: #ffffff;
        }

        .content-grid {
            display: grid;

            grid-template-columns:
                300px
                minmax(0, 1fr);

            gap: 22px;

            align-items: start;
        }

        .label-wrapper {
            text-align: center;
        }

        .chemical-qr-label {
            position: relative;

            width: 70mm;
            height: 70mm;

            margin: 0 auto;

            padding:
                1.5mm
                2mm;

            border:
                .3mm
                solid
                #111827;

            border-radius: 8px;

            background: #ffffff;

            text-align: center;

            overflow: hidden;
        }

        .chemical-qr-label-type {
            font-size: 6.2pt;
            font-weight: bold;

            line-height: 1;
        }

        .chemical-qr-label-name {
            width: 100%;
            height: 6.2mm;

            margin:
                .3mm
                0
                0;

            font-size: 8.5pt;
            font-weight: bold;

            line-height: 1.05;

            white-space: normal;
            overflow: hidden;
        }

        .chemical-qr-label-name.long {
            font-size: 7.2pt;
        }

        .chemical-qr-label-name.very-long {
            font-size: 6.3pt;
        }

        .chemical-qr-label-subtitle {
            height: 2.5mm;

            margin-top: .2mm;

            color: #4b5563;

            font-size: 5.8pt;
            font-weight: bold;

            overflow: hidden;
        }

        .chemical-qr-label-code {
            width: 50mm;
            height: 50mm;

            margin:
                .1mm
                auto
                0;

            display: flex;

            align-items: center;
            justify-content: center;
        }

        .chemical-qr-label-code svg,
        .chemical-qr-label-code img {
            display: block;

            width: 50mm !important;
            height: 50mm !important;

            max-width: none !important;
            max-height: none !important;
        }

        .chemical-qr-label-instruction {
            position: absolute;

            left: 2mm;
            right: 2mm;
            bottom: .7mm;

            color: #374151;

            font-size: 4pt;
        }

        .size-note {
            margin-top: 10px;

            color: #6b7280;

            font-size: 12px;
        }

        .card {
            margin-bottom: 18px;

            padding: 20px;

            background: #ffffff;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 12px;
        }

        .card h2 {
            margin:
                0
                0
                15px;

            font-size: 17px;
        }

        .info-row {
            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 15px;

            padding:
                11px
                0;

            border-bottom:
                1px
                solid
                #f3f4f6;
        }

        .info-row:last-child {
            border-bottom: 0;
        }

        .info-label {
            color: #6b7280;

            font-size: 13px;
        }

        .info-value {
            text-align: right;

            font-size: 13px;
            font-weight: 700;

            word-break: break-word;
        }

        .badge {
            display: inline-flex;

            padding:
                5px
                9px;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 800;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .url-box {
            padding: 11px;

            background: #f9fafb;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 8px;

            color: #6b7280;

            font-family: monospace;

            font-size: 10px;

            word-break: break-all;
        }

        @media (
            max-width: 760px
        ) {

            body {
                padding: 15px;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .toolbar {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }

        @page {
            margin: 0;
        }

        @media print {

            html,
            body {
                margin: 0 !important;
                padding: 0 !important;

                background:
                    #ffffff !important;
            }

            .page-title,
            .success,
            .toolbar,
            .details,
            .size-note {
                display: none !important;
            }

            .page {
                margin: 0 !important;
                padding: 0 !important;

                width: auto !important;
                max-width: none !important;
            }

            .content-grid {
                display: block !important;
            }

            .label-wrapper {
                margin:
                    10mm
                    0
                    0
                    10mm !important;

                width: 70mm !important;
                height: 70mm !important;
            }

            .chemical-qr-label {
                width: 70mm !important;
                height: 70mm !important;

                margin: 0 !important;

                border-radius: 0 !important;
            }

            .chemical-qr-label-code,
            .chemical-qr-label-code svg,
            .chemical-qr-label-code img {
                width: 50mm !important;
                height: 50mm !important;
            }
        }

    </style>

</head>

<body>

<div class="page">

    <div class="page-title">

        <h1>
            QR kod - {{ $chemical->product_name }}
        </h1>

        <p>
            Upravljanje QR kodom kemikalije
        </p>

    </div>


    @if(session('qr_success'))

        <div class="success">
            {{ session('qr_success') }}
        </div>

    @endif


    <div class="toolbar">

        <button
            type="button"
            class="button button-dark"
            onclick="window.print()"
        >
            Ispiši QR naljepnicu
        </button>


        <a
            href="{{
                route(
                    'chemical.qr.download.pdf',
                    [
                        'chemical' =>
                            $chemical,
                    ]
                )
            }}"
            class="button button-gray"
        >
            Preuzmi PDF
        </a>


        @if($qrCode->is_active)

            <a
                href="{{ $publicUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="button button-gray"
            >
                Testiraj javni prikaz
            </a>

        @endif


        <form
            method="POST"
            action="{{
                route(
                    'chemical.qr.regenerate',
                    [
                        'chemical' =>
                            $chemical,
                    ]
                )
            }}"
        >

            @csrf

            <button
                type="submit"
                class="button button-warning"
            >
                Regeneriraj QR kod
            </button>

        </form>


        @if($qrCode->is_active)

            <form
                method="POST"
                action="{{
                    route(
                        'chemical.qr.deactivate',
                        [
                            'chemical' =>
                                $chemical,
                        ]
                    )
                }}"
            >

                @csrf

                <button
                    type="submit"
                    class="button button-danger"
                >
                    Deaktiviraj QR kod
                </button>

            </form>

        @else

            <form
                method="POST"
                action="{{
                    route(
                        'chemical.qr.activate',
                        [
                            'chemical' =>
                                $chemical,
                        ]
                    )
                }}"
            >

                @csrf

                <button
                    type="submit"
                    class="button button-success"
                >
                    Aktiviraj QR kod
                </button>

            </form>

        @endif

    </div>


    <div class="content-grid">

        <div class="label-wrapper">

            @php
                $qrImageHtml = $svg;
            @endphp

            @include(
                'qr.partials.chemical-label',
                [
                    'chemical' =>
                        $chemical,

                    'qrImageHtml' =>
                        $qrImageHtml,
                ]
            )

            <div class="size-note">
                Veličina naljepnice:
                <strong>7 × 7 cm</strong>
            </div>

        </div>


        <div class="details">

            <div class="card">

                <h2>
                    Status QR koda
                </h2>

                <div class="info-row">

                    <div class="info-label">
                        Status
                    </div>

                    <div class="info-value">

                        @if($qrCode->is_active)

                            <span class="badge badge-active">
                                Aktivan
                            </span>

                        @else

                            <span class="badge badge-inactive">
                                Deaktiviran
                            </span>

                        @endif

                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Ukupno skeniranja
                    </div>

                    <div class="info-value">
                        {{ $qrCode->scan_count ?? 0 }}
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Zadnje skeniranje
                    </div>

                    <div class="info-value">

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

            </div>


            <div class="card">

                <h2>
                    Podaci o kemikaliji
                </h2>

                <div class="info-row">

                    <div class="info-label">
                        Proizvod
                    </div>

                    <div class="info-value">
                        {{ $chemical->product_name }}
                    </div>

                </div>

                <div class="info-row">

                    <div class="info-label">
                        CAS
                    </div>

                    <div class="info-value">
                        {{ $chemical->cas_number ?: '—' }}
                    </div>

                </div>

                <div class="info-row">

                    <div class="info-label">
                        UFI
                    </div>

                    <div class="info-value">
                        {{ $chemical->ufi_number ?: '—' }}
                    </div>

                </div>

                <div class="info-row">

                    <div class="info-label">
                        Mjesto upotrebe
                    </div>

                    <div class="info-value">
                        {{ $chemical->usage_location ?: '—' }}
                    </div>

                </div>

                <div class="info-row">

                    <div class="info-label">
                        Broj priloga
                    </div>

                    <div class="info-value">

                        {{
                            is_array(
                                $chemical->attachments
                            )
                                ? count(
                                    $chemical->attachments
                                )
                                : 0
                        }}

                    </div>

                </div>

            </div>


            <div class="card">

                <h2>
                    Javna poveznica
                </h2>

                <div class="url-box">
                    {{ $publicUrl }}
                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>