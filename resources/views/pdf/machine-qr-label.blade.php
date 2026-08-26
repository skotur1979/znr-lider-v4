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

            width: 210mm;
            height: 297mm;

            font-family:
                "DejaVu Sans",
                sans-serif;

            background: #ffffff;
        }

        /*
        |--------------------------------------------------------------------------
        | A4 PAPIR
        |--------------------------------------------------------------------------
        */

        .pdf-page {
            position: relative;

            width: 210mm;
            height: 297mm;

            margin: 0;

            /*
             * Naljepnica je 10 mm od lijevog
             * i gornjeg ruba A4 papira.
             */
            padding:
                10mm
                0
                0
                10mm;

            overflow: hidden;
        }


        /*
        |--------------------------------------------------------------------------
        | QR NALJEPNICA 80 × 80 mm
        |--------------------------------------------------------------------------
        */

        .machine-qr-label {
            position: relative;

            width: 80mm;
            height: 80mm;

            margin: 0;

            padding:
                2.5mm
                3mm;

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

        .machine-qr-label-type {
            margin: 0;

            font-size: 9pt;
            font-weight: bold;

            line-height: 1.05;

            letter-spacing: 0.5pt;
        }


        /*
        |--------------------------------------------------------------------------
        | NAZIV RADNE OPREME
        |--------------------------------------------------------------------------
        */

        .machine-qr-label-name {
            margin:
                0.6mm
                0
                0;

            font-size: 13pt;
            font-weight: bold;

            line-height: 1.02;
        }


        /*
        |--------------------------------------------------------------------------
        | LOKACIJA
        |--------------------------------------------------------------------------
        */

        .machine-qr-label-location {
            min-height: 4mm;

            margin-top: 0.3mm;
            margin-bottom: 0.5mm;

            color: #4b5563;

            font-size: 10pt;
            font-weight: bold;

            line-height: 1.15;
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
                0mm;

            display: flex;

            align-items: center;
            justify-content: center;
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
        | INVENTARNI / TVORNIČKI BROJ
        |--------------------------------------------------------------------------
        */

        .machine-qr-label-identifier {
            margin-top: 0.9mm;

            font-size: 10pt;
            font-weight: bold;

            line-height: 1.05;
        }


        /*
        |--------------------------------------------------------------------------
        | UPUTA
        |--------------------------------------------------------------------------
        */

        .machine-qr-label-instruction {
            position: absolute;

            left: 3mm;
            right: 3mm;
            bottom: 1.3mm;

            margin: 0;

            color: #374151;

            font-size: 5.6pt;

            line-height: 1.05;

            text-align: center;
        }

    </style>

</head>

<body>

<div class="pdf-page">

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

</body>

</html>