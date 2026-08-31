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
        | NALJEPNICA 70 × 70 mm
        |--------------------------------------------------------------------------
        */

        .risk-qr-label {
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
        | NASLOV
        |--------------------------------------------------------------------------
        */

        .risk-qr-label-type {
            margin: 0;

            font-size: 6.3pt;
            font-weight: bold;

            line-height: 1;

            letter-spacing: 0.3pt;
        }


        /*
        |--------------------------------------------------------------------------
        | TVRTKA
        |--------------------------------------------------------------------------
        */

        .risk-qr-label-company {
            width: 100%;

            margin:
                0.5mm
                0
                0;

            font-size: 9pt;
            font-weight: bold;

            line-height: 1;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        /*
        |--------------------------------------------------------------------------
        | BROJ PROCJENE
        |--------------------------------------------------------------------------
        */

        .risk-qr-label-number {
            height: 2.6mm;

            margin-top: 0.4mm;

            color: #4b5563;

            font-size: 6.2pt;
            font-weight: bold;

            line-height: 1;
        }


        /*
        |--------------------------------------------------------------------------
        | QR KOD 50 × 50 mm
        |--------------------------------------------------------------------------
        */

        .risk-qr-label-code {
            width: 50mm;
            height: 50mm;

            margin:
                0.2mm
                auto
                0;
        }

        .risk-qr-label-code img {
            display: block;

            width: 50mm;
            height: 50mm;

            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | UPUTA
        |--------------------------------------------------------------------------
        */

        .risk-qr-label-instruction {
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

            $qrImageHtml =
                '<img src="'
                . $qrDataUri
                . '" alt="QR kod">';

        @endphp

        @include(
            'qr.partials.risk-assessment-label',
            [
                'riskAssessment' =>
                    $riskAssessment,

                'qrImageHtml' =>
                    $qrImageHtml,
            ]
        )

    </div>

</div>

</body>

</html>