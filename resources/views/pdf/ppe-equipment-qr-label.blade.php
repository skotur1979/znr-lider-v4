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

        .ppe-qr-label {
            position: relative;

            width: 70mm;
            height: 70mm;

            padding:
                1.5mm
                2mm;

            border:
                0.3mm
                solid
                #111827;

            background: #ffffff;

            text-align: center;

            overflow: hidden;
        }

        .ppe-qr-label-type {
            margin: 0;

            font-size: 6pt;
            font-weight: bold;

            line-height: 1;

            letter-spacing:
                0.2pt;
        }

        .ppe-qr-label-name {
            width: 100%;
            height: 6.2mm;

            margin:
                0.3mm
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
            overflow-wrap: normal;

            display: block;
        }

        .ppe-qr-label-name.long {
            font-size: 7.2pt;
        }

        .ppe-qr-label-name.very-long {
            font-size: 6.3pt;
        }

        .ppe-qr-label-standard {
            height: 2.5mm;

            margin-top:
                0.2mm;

            color: #4b5563;

            font-size: 5.8pt;
            font-weight: bold;

            line-height: 1;

            overflow: hidden;
        }

        .ppe-qr-label-code {
            width: 50mm;
            height: 50mm;

            margin:
                0.1mm
                auto
                0;
        }

        .ppe-qr-label-code img {
            display: block;

            width: 50mm;
            height: 50mm;

            margin: 0;
            padding: 0;
        }

        .ppe-qr-label-duration {
            margin: 0;

            font-size: 5.8pt;
            font-weight: bold;

            line-height: 1;
        }

        .ppe-qr-label-instruction {
            position: absolute;

            left: 2mm;
            right: 2mm;
            bottom: 0.7mm;

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
            'qr.partials.ppe-equipment-label',
            [
                'ppeEquipment' =>
                    $ppeEquipment,

                'qrImageHtml' =>
                    $qrImageHtml,
            ]
        )

    </div>

</div>

</body>

</html>