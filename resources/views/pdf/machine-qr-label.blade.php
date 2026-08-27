<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <style>

        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;

            font-family:
                "DejaVu Sans",
                sans-serif;

            background: #ffffff;
        }


        /*
        |--------------------------------------------------------------------------
        | A4 STRANICA
        |--------------------------------------------------------------------------
        |
        | Namjerno NE koristimo height: 297mm na wrapperu.
        | Tako DomPDF neće generirati drugu praznu stranicu.
        |
        */

        .pdf-page {
            position: relative;

            width: 210mm;

            margin: 0;
            padding: 0;

            page-break-before: avoid;
            page-break-after: avoid;
            page-break-inside: avoid;
        }


        /*
        |--------------------------------------------------------------------------
        | POZICIJA NALJEPNICE
        |--------------------------------------------------------------------------
        |
        | Naljepnica je 10 mm od lijevog
        | i 10 mm od gornjeg ruba A4 papira.
        |
        */

        .label-position {
            position: absolute;

            top: 10mm;
            left: 10mm;

            width: 70mm;
            height: 70mm;

            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | QR NALJEPNICA 70 × 70 mm
        |--------------------------------------------------------------------------
        */

        .machine-qr-label {
            position: relative;

            width: 70mm;
            height: 70mm;

            margin: 0;

            padding:
                1.5mm
                2mm;

            border:
                0.3mm
                solid
                #111827;

            box-sizing: border-box;

            background: #ffffff;

            text-align: center;

            overflow: hidden;
        }


        /*
        |--------------------------------------------------------------------------
        | ZNR LIDER · RADNA OPREMA
        |--------------------------------------------------------------------------
        */

        .machine-qr-label-type {
            margin: 0;

            font-size: 6.5pt;
            font-weight: bold;

            line-height: 1;

            letter-spacing: 0.35pt;
        }


        /*
        |--------------------------------------------------------------------------
        | NAZIV RADNE OPREME
        |--------------------------------------------------------------------------
        */

        .machine-qr-label-name {
            margin:
                0.4mm
                0
                0;

            font-size: 9.5pt;
            font-weight: bold;

            line-height: 1;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        /*
        |--------------------------------------------------------------------------
        | LOKACIJA
        |--------------------------------------------------------------------------
        */

        .machine-qr-label-location {
            height: 2.5mm;

            margin-top: 0.3mm;
            margin-bottom: 0;

            color: #4b5563;

            font-size: 6.5pt;
            font-weight: bold;

            line-height: 1;
        }


        /*
        |--------------------------------------------------------------------------
        | QR KOD 50 × 50 mm
        |--------------------------------------------------------------------------
        */

        .machine-qr-label-code {
            width: 50mm;
            height: 50mm;

            margin:
                0.2mm
                auto
                0;
        }

        .machine-qr-label-code img {
            display: block;

            width: 50mm;
            height: 50mm;

            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | IDENTIFIKACIJSKI BROJEVI
        |--------------------------------------------------------------------------
        */

        .machine-qr-label-identifiers {
            margin-top: 0;
        }

        .machine-qr-label-identifier {
            margin: 0;

            font-size: 8pt;
            font-weight: bold;

            line-height: 1.08;
        }

        .machine-qr-label-identifier
        + .machine-qr-label-identifier {
            margin-top: 0.1mm;
        }


        /*
        |--------------------------------------------------------------------------
        | UPUTA
        |--------------------------------------------------------------------------
        */

        .machine-qr-label-instruction {
            position: absolute;

            left: 2mm;
            right: 2mm;
            bottom: 0.7mm;

            margin: 0;

            color: #374151;

            font-size: 4.2pt;

            line-height: 1;

            text-align: center;
        }

    </style>

</head>

<body>

<div class="pdf-page">

    <div class="label-position">

        @php

            /*
             * DomPDF koristi QR kao base64 SVG sliku.
             */

            $qrImageHtml =
                '<img src="'
                . $qrDataUri
                . '" alt="QR kod">';

        @endphp

        @include(
            'qr.partials.machine-label',
            [
                'machine' => $machine,
                'qrImageHtml' => $qrImageHtml,
            ]
        )

    </div>

</div>

</body>

</html>