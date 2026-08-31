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

        .pdf-page {
            position: relative;

            width: 210mm;

            margin: 0;
            padding: 0;

            page-break-before: avoid;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        .label-position {
            position: absolute;

            top: 10mm;
            left: 10mm;

            width: 70mm;
            height: 70mm;

            margin: 0;
            padding: 0;
        }

        .misc-qr-label {
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
        }

        .misc-qr-label-code img {
            display: block;

            width: 50mm;
            height: 50mm;

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
            'qr.partials.miscellaneous-label',
            [
                'miscellaneous' =>
                    $miscellaneous,

                'qrImageHtml' =>
                    $qrImageHtml,
            ]
        )

    </div>

</div>

</body>

</html>