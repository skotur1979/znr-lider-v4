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
        | A4 WRAPPER
        |--------------------------------------------------------------------------
        |
        | Isto kao kod Radne opreme:
        | wrapper nema height: 297mm.
        |
        | Tako DomPDF ne radi dodatnu stranicu.
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
        | POZICIJA POSTERA
        |--------------------------------------------------------------------------
        |
        | Poster je širok 174 mm.
        |
        | A4 = 210 mm
        | 210 - 174 = 36 mm
        | 36 / 2 = 18 mm
        |
        | Zato left: 18mm daje stvarno centriran poster.
        |
        */

        .poster-position {
            position: absolute;

            top: 12mm;
            left: 18mm;

            width: 174mm;
            height: 273mm;

            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | POSTER
        |--------------------------------------------------------------------------
        */

        .observation-poster {
            position: relative;

            width: 174mm;
            height: 273mm;

            margin: 0;

            padding:
                8mm
                0;

            box-sizing: border-box;

            background: #ffffff;

            text-align: center;

            overflow: hidden;
        }


        /*
        |--------------------------------------------------------------------------
        | ZNR LIDER
        |--------------------------------------------------------------------------
        */

        .poster-brand {
            margin: 0;

            font-size: 16pt;
            font-weight: bold;

            line-height: 1;

            letter-spacing: 1.2pt;
        }


        /*
        |--------------------------------------------------------------------------
        | GLAVNI NASLOV
        |--------------------------------------------------------------------------
        */

        .poster-title {
            margin-top: 10mm;

            font-size: 27pt;
            font-weight: bold;

            line-height: 1.08;
        }


        /*
        |--------------------------------------------------------------------------
        | PODNASLOV
        |--------------------------------------------------------------------------
        */

        .poster-subtitle {
            width: 145mm;

            margin:
                8mm
                auto
                0;

            color: #4b5563;

            font-size: 13pt;

            line-height: 1.4;

            text-align: center;
        }


        /*
        |--------------------------------------------------------------------------
        | QR KOD 90 × 90 mm
        |--------------------------------------------------------------------------
        */

        .poster-qr {
            width: 90mm;
            height: 90mm;

            margin:
                10mm
                auto
                0;
        }

        .poster-qr img {
            display: block;

            width: 90mm;
            height: 90mm;

            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | SKENIRAJ I PRIJAVI
        |--------------------------------------------------------------------------
        */

        .poster-scan {
            margin-top: 6mm;

            font-size: 18pt;
            font-weight: bold;

            line-height: 1;
        }


        /*
        |--------------------------------------------------------------------------
        | VRSTE PRIJAVA
        |--------------------------------------------------------------------------
        */

        .poster-items {
            width: 100mm;

            margin:
                6mm
                auto
                0;

            font-size: 12pt;

            line-height: 1.65;

            text-align: left;
        }


        /*
        |--------------------------------------------------------------------------
        | LOGIN PORUKA
        |--------------------------------------------------------------------------
        */

        .poster-login {
        margin-top: 8mm;

        font-size: 11pt;
        font-weight: bold;

        line-height: 1.25;

        text-align: center;
    }


        /*
        |--------------------------------------------------------------------------
        | ZAHVALA
        |--------------------------------------------------------------------------
        */

        .poster-thanks {
        position: static;

        margin:
            9mm
            0
            0;

        color: #4b5563;

        font-size: 10pt;

        line-height: 1.3;

        text-align: center;
    }

    </style>

</head>

<body>

<div class="pdf-page">

    <div class="poster-position">

        @php

            /*
             * DomPDF koristi isti QR kao
             * base64 SVG sliku.
             */

            $qrImageHtml =
                '<img src="'
                . $qrDataUri
                . '" alt="QR kod">';

        @endphp

        @include(
            'qr.partials.observation-poster',
            [
                'qrImageHtml' =>
                    $qrImageHtml,
            ]
        )

    </div>

</div>

</body>

</html>