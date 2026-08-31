<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        {{ $documentationItem->naziv }}
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px 15px;

            background: #f3f4f6;
            color: #111827;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .page {
            width: 100%;
            max-width: 600px;

            margin: 0 auto;
        }

        .header {
            margin-bottom: 14px;

            padding: 18px;

            border-radius: 14px;

            background: #111827;
            color: #ffffff;
        }

        .header-small {
            margin-bottom: 6px;

            font-size: 11px;
            font-weight: 700;

            letter-spacing: .4px;
        }

        .header h1 {
            margin: 0;

            font-size: 22px;
            line-height: 1.15;
        }

        .card {
            margin-bottom: 14px;

            padding: 18px;

            background: #ffffff;

            border: 1px solid #e5e7eb;
            border-radius: 14px;

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
                14px;

            font-size: 17px;
        }

        .row {
            display: grid;

            grid-template-columns:
                42%
                58%;

            gap: 10px;

            padding:
                10px
                0;

            border-bottom:
                1px
                solid
                #f3f4f6;
        }

        .row:last-child {
            border-bottom: 0;
        }

        .label {
            color: #6b7280;

            font-size: 13px;
        }

        .value {
            font-size: 13px;
            font-weight: 700;

            word-break: break-word;
        }

        .attachment {
            display: block;

            margin-top: 8px;

            padding:
                12px
                14px;

            border:
                1px
                solid
                #d1d5db;

            border-radius: 9px;

            background: #ffffff;
            color: #1d4ed8;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;

            word-break: break-word;
        }

        .attachment:first-of-type {
            margin-top: 0;
        }

        .footer {
            padding: 10px;

            color: #6b7280;

            font-size: 11px;

            text-align: center;
        }

        @media (
            max-width: 500px
        ) {

            body {
                padding:
                    15px
                    10px;
            }

            .row {
                grid-template-columns: 1fr;
                gap: 4px;
            }
        }

    </style>

</head>

<body>

<div class="page">

    <div class="header">

        <div class="header-small">
            ZNR LIDER · DOKUMENTACIJA
        </div>

        <h1>
            {{ $documentationItem->naziv }}
        </h1>

    </div>


    <div class="card">

        <h2>
            Podaci o dokumentu
        </h2>

        <div class="row">

            <div class="label">
                Naziv dokumenta
            </div>

            <div class="value">
                {{ $documentationItem->naziv }}
            </div>

        </div>


        @if(
            filled(
                $documentationItem->tvrtka
            )
        )

            <div class="row">

                <div class="label">
                    Tvrtka
                </div>

                <div class="value">
                    {{ $documentationItem->tvrtka }}
                </div>

            </div>

        @endif


        @if(
            $documentationItem->datum_izrade
        )

            <div class="row">

                <div class="label">
                    Datum izrade
                </div>

                <div class="value">
                    {{
                        $documentationItem
                            ->datum_izrade
                            ->format('d.m.Y.')
                    }}
                </div>

            </div>

        @endif


        @if(
            filled(
                $documentationItem->status_napomena
            )
        )

            <div class="row">

                <div class="label">
                    Status / napomena
                </div>

                <div class="value">
                    {{
                        $documentationItem
                            ->status_napomena
                    }}
                </div>

            </div>

        @endif

    </div>


    @php

        $files =
            is_array(
                $documentationItem->prilozi
            )
                ? array_values(
                    $documentationItem->prilozi
                )
                : [];

    @endphp


    @if(count($files))

        <div class="card">

            <h2>
                Prilozi
            </h2>

            @foreach(
                $files
                as $index => $file
            )

                <a
                    href="{{
                        route(
                            'public.documentation.attachment',
                            [
                                'token' =>
                                    $qrCode->token,

                                'index' =>
                                    $index,
                            ]
                        )
                    }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="attachment"
                >
                    📎
                    {{ $index + 1 }}.
                    {{ basename($file) }}
                </a>

            @endforeach

        </div>

    @endif


    <div class="footer">
        Podaci su dostupni samo za ovaj dokument.
    </div>

</div>

</body>

</html>