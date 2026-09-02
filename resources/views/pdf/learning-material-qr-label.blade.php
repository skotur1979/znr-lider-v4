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
        }

        .label-position {
            position: absolute;

            top: 10mm;
            left: 10mm;

            width: 70mm;
            height: 70mm;
        }

        .learning-qr-label {
            position: relative;

            width: 70mm;
            height: 70mm;

            padding:
                1.5mm
                2mm;

            border:
                .3mm
                solid
                #111827;

            background: #ffffff;

            text-align: center;

            overflow: hidden;
        }

        .learning-qr-label-type {
            margin: 0;

            font-size: 6pt;
            font-weight: bold;

            line-height: 1;

            letter-spacing: .2pt;
        }

        .learning-qr-label-name {
            width: 100%;
            height: 6.2mm;

            margin:
                .3mm
                0
                0;

            padding: 0;

            font-size: 8.2pt;
            font-weight: bold;

            line-height: 1.05;

            text-align: center;

            white-space: normal;
            overflow: hidden;

            word-break: normal;
        }

        .learning-qr-label-name.long {
            font-size: 7.2pt;
        }

        .learning-qr-label-name.very-long {
            font-size: 6.3pt;
        }

        .learning-qr-label-category {
            height: 2.5mm;

            margin-top: .2mm;

            color: #4b5563;

            font-size: 5.8pt;
            font-weight: bold;

            line-height: 1;

            overflow: hidden;
        }

        .learning-qr-label-code {
            width: 50mm;
            height: 50mm;

            margin:
                .1mm
                auto
                0;
        }

        .learning-qr-label-code img {
            display: block;

            width: 50mm;
            height: 50mm;

            margin: 0;
            padding: 0;
        }

        .learning-qr-label-scope {
            margin: 0;

            font-size: 5.3pt;
            font-weight: bold;

            line-height: 1;
        }

        .learning-qr-label-instruction {
            position: absolute;

            left: 2mm;
            right: 2mm;
            bottom: .7mm;

            margin: 0;

            color: #374151;

            font-size: 4pt;

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
            'qr.partials.learning-material-label',
            [
                'learningMaterial' =>
                    $learningMaterial,

                'qrImageHtml' =>
                    $qrImageHtml,
            ]
        )

    </div>

</div>

</body>

</html>