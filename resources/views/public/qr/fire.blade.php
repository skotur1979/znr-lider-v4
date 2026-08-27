<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="robots"
        content="noindex,nofollow,noarchive"
    >

    <title>
        Vatrogasni aparat
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;

            background: #f3f4f6;
            color: #111827;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .container {
            max-width: 720px;

            margin: 0 auto;
        }

        .header {
            margin-bottom: 16px;

            padding: 22px;

            background: #991b1b;
            color: #ffffff;

            border-radius: 16px;
        }

        .header-small {
            margin-bottom: 6px;

            font-size: 13px;
            font-weight: 700;

            letter-spacing: .08em;

            text-transform: uppercase;

            opacity: .8;
        }

        .header h1 {
            margin: 0;

            font-size: 25px;
        }

        .card {
            margin-bottom: 16px;

            padding: 20px;

            background: #ffffff;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 16px;

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
                15px;

            font-size: 18px;
        }

        .row {
            display: grid;

            grid-template-columns:
                minmax(150px, 42%)
                1fr;

            gap: 12px;

            padding:
                11px
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

            font-size: 14px;
        }

        .value {
            font-size: 14px;
            font-weight: 600;

            word-break: break-word;
        }

        .expiry {
            display: inline-block;

            margin-top: 7px;

            padding:
                5px
                9px;

            border-radius: 8px;

            font-size: 12px;
            font-weight: 700;
        }

        .valid {
            background: #dcfce7;
            color: #166534;
        }

        .soon {
            background: #fef3c7;
            color: #92400e;
        }

        .expired {
            background: #fee2e2;
            color: #991b1b;
        }

        .attachment {
            display: block;

            margin-top: 9px;

            padding:
                13px
                14px;

            border:
                1px
                solid
                #d1d5db;

            border-radius: 10px;

            color: #1d4ed8;

            font-size: 14px;
            font-weight: 600;

            text-decoration: none;
        }

        .notice {
            color: #6b7280;

            font-size: 12px;

            line-height: 1.5;

            text-align: center;
        }

        @media (
            max-width: 520px
        ) {

            body {
                padding: 12px;
            }

            .row {
                grid-template-columns: 1fr;

                gap: 4px;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="header">

        <div class="header-small">
            ZNR LIDER · Vatrogasni aparat
        </div>

        <h1>
            {{ $fire->type ?: 'Vatrogasni aparat' }}
        </h1>

    </div>


    <div class="card">

        <h2>
            Podaci o vatrogasnom aparatu
        </h2>

        <div class="row">

            <div class="label">
                Mjesto
            </div>

            <div class="value">
                {{ $fire->place }}
            </div>

        </div>


        @if(filled($fire->type))

            <div class="row">

                <div class="label">
                    Tip aparata
                </div>

                <div class="value">
                    {{ $fire->type }}
                </div>

            </div>

        @endif


        @if(
            filled(
                $fire
                    ->factory_number_year_of_production
            )
        )

            <div class="row">

                <div class="label">
                    Tvornički broj / godina proizvodnje
                </div>

                <div class="value">
                    {{
                        $fire
                            ->factory_number_year_of_production
                    }}
                </div>

            </div>

        @endif


        @if(
            filled(
                $fire->serial_label_number
            )
        )

            <div class="row">

                <div class="label">
                    Serijski broj evidencijske naljepnice
                </div>

                <div class="value">
                    {{ $fire->serial_label_number }}
                </div>

            </div>

        @endif

    </div>


    <div class="card">

        <h2>
            Pregledi i servis
        </h2>


        @if($fire->examination_valid_from)

            <div class="row">

                <div class="label">
                    Datum periodičkog servisa
                </div>

                <div class="value">
                    {{
                        $fire
                            ->examination_valid_from
                            ->format('d.m.Y.')
                    }}
                </div>

            </div>

        @endif


        @if($fire->examination_valid_until)

            @php

                $today =
                    now()->startOfDay();

                $periodicUntil =
                    $fire
                        ->examination_valid_until
                        ->copy()
                        ->startOfDay();

                if (
                    $periodicUntil->lt($today)
                ) {
                    $periodicClass =
                        'expired';

                    $periodicText =
                        'Isteklo';
                } elseif (
                    $periodicUntil->lte(
                        $today
                            ->copy()
                            ->addDays(30)
                    )
                ) {
                    $periodicClass =
                        'soon';

                    $periodicText =
                        'Uskoro ističe';
                } else {
                    $periodicClass =
                        'valid';

                    $periodicText =
                        'Važeće';
                }

            @endphp

            <div class="row">

                <div class="label">
                    Periodički servis vrijedi do
                </div>

                <div class="value">

                    {{
                        $periodicUntil
                            ->format('d.m.Y.')
                    }}

                    <br>

                    <span
                        class="expiry {{ $periodicClass }}"
                    >
                        {{ $periodicText }}
                    </span>

                </div>

            </div>

        @endif


        @if($fire->service)

            <div class="row">

                <div class="label">
                    Serviser
                </div>

                <div class="value">
                    {{ $fire->service }}
                </div>

            </div>

        @endif


        @if(
            $fire
                ->regular_examination_valid_from
        )

            <div class="row">

                <div class="label">
                    Datum redovnog pregleda
                </div>

                <div class="value">

                    {{
                        $fire
                            ->regular_examination_valid_from
                            ->format('d.m.Y.')
                    }}

                </div>

            </div>

        @endif


        @if(
            $fire
                ->regular_examination_valid_until
        )

            @php

                $regularUntil =
                    $fire
                        ->regular_examination_valid_until
                        ->copy()
                        ->startOfDay();

                if (
                    $regularUntil->lt($today)
                ) {
                    $regularClass =
                        'expired';

                    $regularText =
                        'Isteklo';
                } elseif (
                    $regularUntil->lte(
                        $today
                            ->copy()
                            ->addDays(30)
                    )
                ) {
                    $regularClass =
                        'soon';

                    $regularText =
                        'Uskoro ističe';
                } else {
                    $regularClass =
                        'valid';

                    $regularText =
                        'Važeće';
                }

            @endphp

            <div class="row">

                <div class="label">
                    Redovni pregled vrijedi do
                </div>

                <div class="value">

                    {{
                        $regularUntil
                            ->format('d.m.Y.')
                    }}

                    <br>

                    <span
                        class="expiry {{ $regularClass }}"
                    >
                        {{ $regularText }}
                    </span>

                </div>

            </div>

        @endif

    </div>


    @if(
        filled($fire->visible)
        || filled($fire->remark)
        || filled($fire->action)
    )

        <div class="card">

            <h2>
                Stanje aparata
            </h2>


            @if(filled($fire->visible))

                <div class="row">

                    <div class="label">
                        Uočljivost i dostupnost
                    </div>

                    <div class="value">
                        {{ $fire->visible }}
                    </div>

                </div>

            @endif


            @if(filled($fire->remark))

                <div class="row">

                    <div class="label">
                        Uočeni nedostatci
                    </div>

                    <div class="value">
                        {{ $fire->remark }}
                    </div>

                </div>

            @endif


            @if(filled($fire->action))

                <div class="row">

                    <div class="label">
                        Postupci otklanjanja
                    </div>

                    <div class="value">
                        {{ $fire->action }}
                    </div>

                </div>

            @endif

        </div>

    @endif


    @if(
        is_array($fire->pdf)
        && count($fire->pdf) > 0
    )

        <div class="card">

            <h2>
                Prilozi
            </h2>

            @foreach(
                array_values($fire->pdf)
                as $index => $file
            )

                <a
                    class="attachment"
                    href="{{ route(
                        'public.fire.attachment',
                        [
                            'token' =>
                                $qrCode->token,

                            'index' =>
                                $index,
                        ]
                    ) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    📎
                    {{ $index + 1 }}.
                    {{ basename($file) }}
                </a>

            @endforeach

        </div>

    @endif


    <div class="notice">

        Podaci su dostupni samo za ovaj
        vatrogasni aparat.

        <br>

        Javni prikaz ne omogućuje uređivanje
        podataka.

    </div>

</div>

</body>

</html>