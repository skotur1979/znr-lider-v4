<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        QR - Vatrogasni aparat
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


        /*
        |--------------------------------------------------------------------------
        | NASLOV STRANICE
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | PORUKE
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | TOOLBAR
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | GLAVNI LAYOUT
        |--------------------------------------------------------------------------
        */

        .content-grid {
            display: grid;

            grid-template-columns:
                230px
                minmax(0, 1fr);

            gap: 22px;

            align-items: start;
        }

        .label-wrapper {
            text-align: center;
        }


        /*
        |--------------------------------------------------------------------------
        | QR NALJEPNICA 50 × 50 mm
        |--------------------------------------------------------------------------
        */

        .fire-qr-label {
            position: relative;

            width: 50mm;
            height: 50mm;

            margin: 0 auto;

            padding:
                1.1mm
                1.5mm;

            border:
                0.3mm
                solid
                #111827;

            border-radius: 7px;

            box-sizing: border-box;

            overflow: hidden;

            text-align: center;

            background: #ffffff;
        }


        /*
        |--------------------------------------------------------------------------
        | NASLOV
        |--------------------------------------------------------------------------
        */

        .fire-qr-label-type {
            margin: 0;

            font-size: 5pt;
            font-weight: bold;

            line-height: 1;

            letter-spacing: 0.10pt;
        }


        /*
        |--------------------------------------------------------------------------
        | TIP APARATA
        |--------------------------------------------------------------------------
        */

        .fire-qr-label-name {
            margin-top: 0.25mm;

            font-size: 6.5pt;
            font-weight: bold;

            line-height: 1;
        }


        /*
        |--------------------------------------------------------------------------
        | MJESTO
        |--------------------------------------------------------------------------
        */

        .fire-qr-label-place {
            height: 2.2mm;

            margin-top: 0.2mm;

            color: #4b5563;

            font-size: 5pt;
            font-weight: bold;

            line-height: 1;
        }


        /*
        |--------------------------------------------------------------------------
        | QR KOD 31 × 31 mm
        |--------------------------------------------------------------------------
        */

        .fire-qr-label-code {
            width: 31mm;
            height: 31mm;

            margin:
                0.15mm
                auto
                0;

            display: flex;

            align-items: center;
            justify-content: center;
        }

        .fire-qr-label-code svg,
        .fire-qr-label-code img {
            display: block;

            width: 31mm !important;
            height: 31mm !important;

            max-width: none !important;
            max-height: none !important;

            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | SERIJSKI BROJ EVIDENCIJSKE NALJEPNICE
        |--------------------------------------------------------------------------
        */

        .fire-qr-label-serial {
            margin-top: 0.15mm;

            font-size: 5.1pt;
            font-weight: bold;

            line-height: 1;
        }


        /*
        |--------------------------------------------------------------------------
        | TVORNIČKI BROJ / GODINA PROIZVODNJE
        |--------------------------------------------------------------------------
        */

        .fire-qr-label-factory {
            margin-top: 0.35mm;

            font-size: 5.2pt;
            font-weight: bold;

            line-height: 1;
        }

        .fire-qr-label-factory-caption {
            margin-top: 0.15mm;

            font-size: 3.7pt;
            font-weight: normal;

            line-height: 1;
        }


        /*
        |--------------------------------------------------------------------------
        | UPUTA NA DNU NALJEPNICE
        |--------------------------------------------------------------------------
        */

        .fire-qr-label-instruction {
            position: absolute;

            left: 1.5mm;
            right: 1.5mm;
            bottom: 0.65mm;

            margin: 0;

            color: #374151;

            font-size: 3.6pt;

            line-height: 1.05;

            text-align: center;
        }


        .size-note {
            margin-top: 10px;

            color: #6b7280;

            font-size: 12px;
        }


        /*
        |--------------------------------------------------------------------------
        | KARTICE
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | INFORMACIJE
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | JAVNA POVEZNICA
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | FORMA REDOVNOG PREGLEDA
        |--------------------------------------------------------------------------
        */

        .form-grid {
            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 14px;
        }

        .field {
            min-width: 0;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .field label {
            display: block;

            margin-bottom: 5px;

            font-size: 13px;
            font-weight: 700;
        }

        .field input {
            width: 100%;

            padding:
                10px
                11px;

            border:
                1px
                solid
                #d1d5db;

            border-radius: 8px;

            font-size: 14px;
        }

        .helper {
            margin-top: 6px;

            color: #6b7280;

            font-size: 12px;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (
            max-width: 760px
        ) {

            body {
                padding: 15px;
            }

            .content-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .toolbar {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }

            .label-wrapper {
                overflow-x: auto;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PRINT
        |--------------------------------------------------------------------------
        |
        | A4 papir.
        | Naljepnica 50 × 50 mm.
        | Naljepnica 10 mm od lijevog i gornjeg ruba.
        |
        */

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


            .page-title,
            .success,
            .toolbar,
            .details,
            .size-note {
                display: none !important;
            }


            .page {
                width: 210mm !important;
                height: 297mm !important;

                max-width: none !important;

                margin: 0 !important;
                padding: 0 !important;

                overflow: hidden !important;
            }


            .content-grid {
                display: block !important;

                width: 210mm !important;
                height: 297mm !important;

                margin: 0 !important;
                padding: 0 !important;

                overflow: hidden !important;
            }


            /*
             * 10 mm odmak od lijevog
             * i gornjeg ruba A4 papira.
             */

            .label-wrapper {
                width: 50mm !important;
                height: 50mm !important;

                margin:
                    10mm
                    0
                    0
                    10mm !important;

                padding: 0 !important;

                text-align: center !important;

                overflow: visible !important;
            }


            .fire-qr-label {
                position: relative !important;

                width: 50mm !important;
                height: 50mm !important;

                margin: 0 !important;

                padding:
                    1.1mm
                    1.5mm !important;

                border:
                    0.3mm
                    solid
                    #111827 !important;

                border-radius: 0 !important;

                box-sizing: border-box !important;

                box-shadow: none !important;

                background: #ffffff !important;

                text-align: center !important;

                overflow: hidden !important;
            }


            .fire-qr-label-type {
                margin: 0 !important;

                font-size: 5pt !important;
                font-weight: bold !important;

                line-height: 1 !important;

                letter-spacing: 0.10pt !important;
            }


            .fire-qr-label-name {
                margin-top: 0.25mm !important;

                font-size: 6.5pt !important;
                font-weight: bold !important;

                line-height: 1 !important;
            }


            .fire-qr-label-place {
                height: 2.2mm !important;

                margin-top: 0.2mm !important;

                color: #4b5563 !important;

                font-size: 5pt !important;
                font-weight: bold !important;

                line-height: 1 !important;
            }


            .fire-qr-label-code {
                width: 31mm !important;
                height: 31mm !important;

                margin:
                    0.15mm
                    auto
                    0 !important;
            }


            .fire-qr-label-code svg,
            .fire-qr-label-code img {
                display: block !important;

                width: 31mm !important;
                height: 31mm !important;

                max-width: none !important;
                max-height: none !important;

                margin: 0 !important;
                padding: 0 !important;
            }


            .fire-qr-label-serial {
                margin-top: 0.15mm !important;

                font-size: 5.1pt !important;
                font-weight: bold !important;

                line-height: 1 !important;
            }


            .fire-qr-label-factory {
                margin-top: 0.35mm !important;

                font-size: 5.2pt !important;
                font-weight: bold !important;

                line-height: 1 !important;
            }


            .fire-qr-label-factory-caption {
                margin-top: 0.15mm !important;

                font-size: 3.7pt !important;
                font-weight: normal !important;

                line-height: 1 !important;
            }


            .fire-qr-label-instruction {
                position: absolute !important;

                left: 1.5mm !important;
                right: 1.5mm !important;
                bottom: 0.65mm !important;

                margin: 0 !important;

                color: #374151 !important;

                font-size: 3.6pt !important;

                line-height: 1.05 !important;

                text-align: center !important;
            }
        }

    </style>

</head>

<body>

<div class="page">

    <div class="page-title">

        <h1>
            QR kod - Vatrogasni aparat
        </h1>

        <p>
            {{ $fire->place }}

            @if(filled($fire->type))
                · {{ $fire->type }}
            @endif
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
            href="{{ route(
                'fire.qr.download.pdf',
                [
                    'fire' => $fire,
                ]
            ) }}"
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
            action="{{ route(
                'fire.qr.regenerate',
                [
                    'fire' => $fire,
                ]
            ) }}"
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
                action="{{ route(
                    'fire.qr.deactivate',
                    [
                        'fire' => $fire,
                    ]
                ) }}"
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
                action="{{ route(
                    'fire.qr.activate',
                    [
                        'fire' => $fire,
                    ]
                ) }}"
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

        {{-- LIJEVO - QR NALJEPNICA --}}

        <div class="label-wrapper">

            @php
                $qrImageHtml = $svg;
            @endphp

            @include(
                'qr.partials.fire-label',
                [
                    'fire' => $fire,
                    'qrImageHtml' => $qrImageHtml,
                ]
            )

            <div class="size-note">
                Veličina naljepnice:
                <strong>5 × 5 cm</strong>
            </div>

        </div>


        {{-- DESNO - INFORMACIJE I PREGLED --}}

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

            </div>


            <div class="card">

                <h2>
                    Evidentiraj redovni pregled
                </h2>


                <form
                    method="POST"
                    action="{{ route(
                        'fire.qr.regular-inspection',
                        [
                            'fire' => $fire,
                        ]
                    ) }}"
                >

                    @csrf


                    <div class="form-grid">

                        <div class="field field-full">

                            <label>
                                Datum redovnog pregleda
                            </label>

                            <input
                                type="date"
                                name="regular_examination_valid_from"
                                value="{{
                                    old(
                                        'regular_examination_valid_from',
                                        now()->format('Y-m-d')
                                    )
                                }}"
                                required
                            >

                            <div class="helper">
                                Novi rok automatski će vrijediti
                                3 mjeseca od ovog datuma.
                            </div>

                        </div>


                        <div class="field">

                            <label>
                                Uočljivost i dostupnost
                            </label>

                            <input
                                type="text"
                                name="visible"
                                maxlength="255"
                                value="{{
                                    old(
                                        'visible',
                                        $fire->visible
                                    )
                                }}"
                            >

                        </div>


                        <div class="field">

                            <label>
                                Uočeni nedostatci
                            </label>

                            <input
                                type="text"
                                name="remark"
                                maxlength="255"
                                value="{{
                                    old(
                                        'remark',
                                        $fire->remark
                                    )
                                }}"
                            >

                        </div>


                        <div class="field field-full">

                            <label>
                                Postupci otklanjanja
                            </label>

                            <input
                                type="text"
                                name="action"
                                maxlength="255"
                                value="{{
                                    old(
                                        'action',
                                        $fire->action
                                    )
                                }}"
                            >

                        </div>

                    </div>


                    <div style="margin-top:16px">

                        <button
                            type="submit"
                            class="button button-success"
                        >
                            Spremi redovni pregled
                        </button>

                    </div>

                </form>

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