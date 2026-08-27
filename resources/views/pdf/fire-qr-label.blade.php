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
        | Namjerno NE postavljamo height: 297mm na body
        | niti dodatni element pune visine stranice.
        |
        | DomPDF je zbog kombinacije pune A4 visine
        | i paddinga znao napraviti prvu praznu stranicu.
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
        | 10 mm od gornjeg i lijevog ruba.
        |
        */

        .label-position {
            position: absolute;

            top: 10mm;
            left: 10mm;

            width: 50mm;
            height: 50mm;

            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | NALJEPNICA 50 × 50 mm
        |--------------------------------------------------------------------------
        */

        .fire-qr-label {
            position: relative;

            width: 50mm;
            height: 50mm;

            margin: 0;

            padding:
                1.1mm
                1.5mm;

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
        }

        .fire-qr-label-code img {
            display: block;

            width: 31mm;
            height: 31mm;

            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | TVORNIČKI BROJ / GODINA
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
        | UPUTA
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

    </style>

</head>

<body>

<div class="pdf-page">

    <div class="label-position">

        @php

            $qrImageHtml =
                '<img src="'
                . $qrDataUri
                . '" alt="QR kod">';

        @endphp

        @include(
            'qr.partials.fire-label',
            [
                'fire' => $fire,
                'qrImageHtml' => $qrImageHtml,
            ]
        )

    </div>

</div>

</body>

</html>