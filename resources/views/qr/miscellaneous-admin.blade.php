<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        QR - {{ $miscellaneous->name }}
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

            font-size: 14px;
            font-weight: 700;
        }

        .toolbar {
            margin-bottom: 22px;

            display: flex;
            flex-wrap: wrap;

            gap: 10px;
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

        /*
        |--------------------------------------------------------------------------
        | NALJEPNICA 70 × 70 mm
        |--------------------------------------------------------------------------
        */

        .misc-qr-label {
            position: relative;

            width: 70mm;
            height: 70mm;

            margin: 0 auto;

            padding:
                1.5mm
                2mm;

            border:
                0.3mm
                solid
                #111827;

            border-radius: 8px;

            background: #ffffff;

            text-align: center;

            overflow: hidden;
        }

        .misc-qr-label-type {
            margin: 0;

            font-size: 6.2pt;
            font-weight: bold;

            line-height: 1;

            letter-spacing: 0.25pt;
        }

        .misc-qr-label-name {
            width: 100%;

            margin:
                0.4mm
                0
                0;

            font-size: 9pt;
            font-weight: bold;

            line-height: 1;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .misc-qr-label-category {
            height: 2.5mm;

            margin-top: 0.25mm;

            color: #4b5563;

            font-size: 6.2pt;
            font-weight: bold;

            line-height: 1;
        }

        .misc-qr-label-code {
            width: 50mm;
            height: 50mm;

            margin:
                0.15mm
                auto
                0;

            display: flex;

            align-items: center;
            justify-content: center;
        }

        .misc-qr-label-code svg,
        .misc-qr-label-code img {
            display: block;

            width: 50mm !important;
            height: 50mm !important;

            max-width: none !important;
            max-height: none !important;

            margin: 0;
            padding: 0;
        }

        .misc-qr-label-identifier {
            margin: 0;

            font-size: 6.1pt;
            font-weight: bold;

            line-height: 1;
        }

        .misc-qr-label-instruction {
            position: absolute;

            left: 2mm;
            right: 2mm;
            bottom: 0.7mm;

            margin: 0;

            color: #374151;

            font-size: 4.1pt;

            line-height: 1;

            text-align: center;
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

            box-shadow:
                0
                1px
                3px
                rgba(0, 0, 0, .06);
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

        @media (max-width: 760px) {

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
            size: auto;
            margin: 0;
        }

        @media print {

            html,
            body {
                margin: 0 !important;
                padding: 0 !important;

                background: #ffffff !important;
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

                margin: 0 !important;
                padding: 0 !important;
            }

            .label-wrapper {
                margin:
                    10mm
                    0
                    0
                    10mm !important;

                padding: 0 !important;

                width: 70mm !important;
                height: 70mm !important;

                overflow: visible !important;
            }

            .misc-qr-label {
                width: 70mm !important;
                height: 70mm !important;

                margin: 0 !important;

                padding:
                    1.5mm
                    2mm !important;

                border:
                    0.3mm
                    solid
                    #111827 !important;

                border-radius: 0 !important;

                box-shadow: none !important;

                overflow: hidden !important;
            }

            .misc-qr-label-code {
                width: 50mm !important;
                height: 50mm !important;
            }

            .misc-qr-label-code svg,
            .misc-qr-label-code img {
                width: 50mm !important;
                height: 50mm !important;

                max-width: none !important;
                max-height: none !important;
            }
        }

    </style>

</head>

<body>

<div class="page">

    <div class="page-title">

        <h1>
            QR kod - {{ $miscellaneous->name }}
        </h1>

        <p>
            Upravljanje QR kodom ostalog ispitivanja
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
                    'miscellaneous.qr.download.pdf',
                    [
                        'miscellaneous' =>
                            $miscellaneous,
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
                    'miscellaneous.qr.regenerate',
                    [
                        'miscellaneous' =>
                            $miscellaneous,
                    ]
                )
            }}"
            onsubmit="
                return confirm(
                    'Jesi li siguran da želiš regenerirati QR kod? Stara naljepnica odmah će prestati raditi.'
                );
            "
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
                        'miscellaneous.qr.deactivate',
                        [
                            'miscellaneous' =>
                                $miscellaneous,
                        ]
                    )
                }}"
                onsubmit="
                    return confirm(
                        'Jesi li siguran da želiš deaktivirati QR kod? Postojeća naljepnica više neće otvarati podatke.'
                    );
                "
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
                        'miscellaneous.qr.activate',
                        [
                            'miscellaneous' =>
                                $miscellaneous,
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

                $qrImageHtml =
                    $svg;

            @endphp

            @include(
                'qr.partials.miscellaneous-label',
                [
                    'miscellaneous' =>
                        $miscellaneous,

                    'qrImageHtml' =>
                        $qrImageHtml,
                ]
            )

            <div class="size-note">
                Veličina naljepnice za ispis:
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


                <div class="info-row">

                    <div class="info-label">
                        QR kreiran
                    </div>

                    <div class="info-value">

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
                    Podaci o ispitivanju
                </h2>

                <div class="info-row">

                    <div class="info-label">
                        Naziv
                    </div>

                    <div class="info-value">
                        {{ $miscellaneous->name }}
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Kategorija
                    </div>

                    <div class="info-value">

                        {{
                            $miscellaneous
                                ->category
                                ?->name
                            ?? '—'
                        }}

                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Broj izvještaja
                    </div>

                    <div class="info-value">

                        {{
                            $miscellaneous
                                ->report_number
                            ?: '—'
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


            <div class="card">

                <h2>
                    Važno
                </h2>

                <div
                    style="
                        color:#6b7280;
                        font-size:13px;
                        line-height:1.6;
                    "
                >
                    QR kod daje javni pristup samo
                    ovom ispitivanju i njegovim
                    prilozima.

                    <br><br>

                    Regeneriranjem QR koda stara
                    naljepnica odmah prestaje raditi.

                    <br><br>

                    Deaktiviranjem QR koda javni
                    pristup se privremeno onemogućuje.

                    <br><br>

                    Ponovnim aktiviranjem isti QR
                    kod ponovno postaje dostupan.
                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>