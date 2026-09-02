<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        {{ $chemical->product_name }}
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            padding:
                24px
                15px
                50px;

            background: #f3f4f6;

            color: #111827;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .page {
            width: 100%;
            max-width: 760px;

            margin: 0 auto;
        }

        .header {
            margin-bottom: 14px;

            padding: 20px;

            border-radius: 14px;

            background: #111827;

            color: #ffffff;
        }

        .header-small {
            margin-bottom: 7px;

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .5px;
        }

        .header h1 {
            margin: 0;

            font-size: 24px;
            line-height: 1.2;
        }

        .header-location {
            margin-top: 8px;

            color: #d1d5db;

            font-size: 14px;
        }

        .card {
            margin-bottom: 14px;

            padding: 18px;

            background: #ffffff;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 14px;

            box-shadow:
                0
                1px
                3px
                rgba(0, 0, 0, .05);
        }

        .card h2 {
            margin:
                0
                0
                14px;

            font-size: 17px;
        }

        .info-row {
            display: grid;

            grid-template-columns:
                34%
                66%;

            gap: 10px;

            padding:
                10px
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
            font-size: 13px;
            font-weight: 700;

            word-break: break-word;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;

            gap: 6px;
        }

        .tag {
            display: inline-flex;

            padding:
                5px
                8px;

            border-radius: 7px;

            background: #f3f4f6;

            border:
                1px
                solid
                #d1d5db;

            font-size: 12px;
            font-weight: 700;
        }

        .ghs {
            display: inline-flex;

            padding:
                6px
                9px;

            border:
                2px
                solid
                #ef4444;

            background: #ffffff;

            color: #111827;

            font-size: 12px;
            font-weight: 900;
        }

        .public-ghs-pictograms {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .public-ghs-pictogram {
            display: block;

            width: 44px;
            height: 44px;

            object-fit: contain;
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

            background: #f9fafb;

            color: #1d4ed8;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;

            word-break: break-word;
        }

        .empty {
            color: #6b7280;

            font-size: 13px;
        }

        .footer {
            margin-top: 16px;

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

            .info-row {
                grid-template-columns: 1fr;

                gap: 4px;
            }
        }

    </style>

</head>

<body>

@php

    $pictograms =
        is_array(
            $chemical->hazard_pictograms
        )
            ? $chemical->hazard_pictograms
            : [];

    $hStatements =
        is_array(
            $chemical->h_statements
        )
            ? $chemical->h_statements
            : [];

    $pStatements =
        is_array(
            $chemical->p_statements
        )
            ? $chemical->p_statements
            : [];

    $files =
        is_array(
            $chemical->attachments
        )
            ? array_values(
                $chemical->attachments
            )
            : [];

@endphp


<div class="page">

    <div class="header">

        <div class="header-small">
            ZNR LIDER · KEMIKALIJE
        </div>

        <h1>
            {{ $chemical->product_name }}
        </h1>

        @if(
            filled(
                $chemical->usage_location
            )
        )

            <div class="header-location">
                Mjesto upotrebe:
                {{ $chemical->usage_location }}
            </div>

        @endif

    </div>


    <div class="card">

        <h2>
            Osnovni podatci
        </h2>

        <div class="info-row">

            <div class="info-label">
                Ime proizvoda
            </div>

            <div class="info-value">
                {{ $chemical->product_name }}
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                CAS broj
            </div>

            <div class="info-value">
                {{ $chemical->cas_number ?: '—' }}
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                UFI broj
            </div>

            <div class="info-value">
                {{ $chemical->ufi_number ?: '—' }}
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                Mjesto upotrebe
            </div>

            <div class="info-value">
                {{ $chemical->usage_location ?: '—' }}
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                Godišnja količina
            </div>

            <div class="info-value">
                {{ $chemical->annual_quantity ?: '—' }}
            </div>

        </div>

    </div>


    <div class="card">

        <h2>
            Opasnosti
        </h2>


        <div class="info-row">

            <div class="info-label">
                Piktogrami
            </div>

            <div class="info-value">

                @php
                    $ghsItems = collect(
                        is_array($chemical->hazard_pictograms)
                            ? $chemical->hazard_pictograms
                            : []
                    )
                        ->map(
                            fn ($value) =>
                                strtoupper(
                                    trim(
                                        (string) $value
                                    )
                                )
                        )
                        ->filter()
                        ->unique()
                        ->values();

                    $ghsCandidates =
                        function (
                            string $code
                        ): array {
                            return [
                                "images/ghs/{$code}.gif",
                                "images/ghs/{$code}.png",
                                "images/ghs/{$code}.svg",

                                "piktogrami/{$code}.gif",
                                "piktogrami/{$code}.png",
                                "piktogrami/{$code}.svg",
                            ];
                        };
                @endphp


                @if(
                    $ghsItems->isNotEmpty()
                )

                    <div class="public-ghs-pictograms">

                        @foreach(
                            $ghsItems
                            as $code
                        )

                            @php
                                $src = null;

                                foreach (
                                    $ghsCandidates($code)
                                    as $path
                                ) {
                                    if (
                                        file_exists(
                                            public_path($path)
                                        )
                                    ) {
                                        $src =
                                            asset($path);

                                        break;
                                    }
                                }
                            @endphp


                            @if($src)

                                <img
                                    src="{{ $src }}"
                                    alt="{{ $code }}"
                                    title="{{ $code }}"
                                    loading="lazy"
                                    class="public-ghs-pictogram"
                                >

                            @else

                                <span class="tag">
                                    {{ $code }}
                                </span>

                            @endif

                        @endforeach

                    </div>

                @else

                    —

                @endif

            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                H oznake
            </div>

            <div class="info-value">

                @if(
                    count(
                        $hStatements
                    ) > 0
                )

                    <div class="tags">

                        @foreach(
                            $hStatements
                            as $statement
                        )

                            <span class="tag">
                                {{ $statement }}
                            </span>

                        @endforeach

                    </div>

                @else

                    —

                @endif

            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                P oznake
            </div>

            <div class="info-value">

                @if(
                    count(
                        $pStatements
                    ) > 0
                )

                    <div class="tags">

                        @foreach(
                            $pStatements
                            as $statement
                        )

                            <span class="tag">
                                {{ $statement }}
                            </span>

                        @endforeach

                    </div>

                @else

                    —

                @endif

            </div>

        </div>

    </div>


    <div class="card">

        <h2>
            Izloženost i STL
        </h2>


        <div class="info-row">

            <div class="info-label">
                GVI / KGVI
            </div>

            <div class="info-value">
                {{ $chemical->gvi_kgvi ?: '—' }}
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                VOC
            </div>

            <div class="info-value">
                {{ $chemical->voc ?: '—' }}
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                STL – HZJZ
            </div>

            <div class="info-value">

                {{
                    $chemical
                        ->stl_hzjz
                        ?->format(
                            'd.m.Y.'
                        )
                    ?? '—'
                }}

            </div>

        </div>

    </div>


    <div class="card">

        <h2>
            Sigurnosni listovi i prilozi
        </h2>

        @forelse(
            $files
            as $index => $file
        )

            <a
                href="{{
                    route(
                        'public.chemical.attachment',
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

        @empty

            <div class="empty">
                Za ovu kemikaliju trenutačno nema dostupnih priloga.
            </div>

        @endforelse

    </div>


    <div class="footer">
        Podaci o kemikaliji dostupni su za pregled putem QR koda.
        <br>
        Za izmjene podataka obratite se odgovornoj osobi.
        <br>
        ZNR LIDER
    </div>

</div>

</body>

</html>