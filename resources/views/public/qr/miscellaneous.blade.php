<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        {{ $miscellaneous->name }}
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
            max-width: 650px;

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

            font-size: 23px;
            line-height: 1.15;
        }

        .header-category {
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

        .badge {
            display: inline-flex;

            padding:
                5px
                9px;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 800;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
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

        .attachment:first-of-type {
            margin-top: 0;
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

        @media (max-width: 500px) {

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

@php

    use Illuminate\Support\Carbon;

    $validUntil =
        $miscellaneous->examination_valid_until
            ? Carbon::parse(
                $miscellaneous->examination_valid_until
            )->startOfDay()
            : null;

    $today =
        Carbon::today();

    if (! $validUntil) {

        $validityLabel =
            'Nema roka';

        $validityClass =
            'badge-warning';

    } elseif (
        $validUntil->lt($today)
    ) {

        $validityLabel =
            'Isteklo';

        $validityClass =
            'badge-danger';

    } elseif (
        $validUntil->lte(
            $today
                ->copy()
                ->addDays(30)
        )
    ) {

        $validityLabel =
            'Uskoro ističe';

        $validityClass =
            'badge-warning';

    } else {

        $validityLabel =
            'Važeće';

        $validityClass =
            'badge-success';

    }

    $files =
        is_array(
            $miscellaneous->pdf
        )
            ? array_values(
                $miscellaneous->pdf
            )
            : [];

@endphp


<div class="page">

    <div class="header">

        <div class="header-small">
            ZNR LIDER · OSTALA ISPITIVANJA
        </div>

        <h1>
            {{ $miscellaneous->name }}
        </h1>

        @if(
            filled(
                $miscellaneous
                    ->category
                    ?->name
            )
        )

            <div class="header-category">
                {{
                    $miscellaneous
                        ->category
                        ->name
                }}
            </div>

        @endif

    </div>


    <div class="card">

        <h2>
            Podaci o ispitivanju
        </h2>

        <div class="row">

            <div class="label">
                Naziv
            </div>

            <div class="value">
                {{ $miscellaneous->name }}
            </div>

        </div>


        @if(
            filled(
                $miscellaneous
                    ->category
                    ?->name
            )
        )

            <div class="row">

                <div class="label">
                    Kategorija
                </div>

                <div class="value">
                    {{
                        $miscellaneous
                            ->category
                            ->name
                    }}
                </div>

            </div>

        @endif


        @if(
            filled(
                $miscellaneous->examiner
            )
        )

            <div class="row">

                <div class="label">
                    Ispitao
                </div>

                <div class="value">
                    {{ $miscellaneous->examiner }}
                </div>

            </div>

        @endif


        @if(
            filled(
                $miscellaneous->report_number
            )
        )

            <div class="row">

                <div class="label">
                    Broj izvještaja
                </div>

                <div class="value">
                    {{ $miscellaneous->report_number }}
                </div>

            </div>

        @endif


        <div class="row">

            <div class="label">
                Datum ispitivanja
            </div>

            <div class="value">

                {{
                    $miscellaneous
                        ->examination_valid_from
                        ?->format('d.m.Y.')
                    ?? '—'
                }}

            </div>

        </div>


        <div class="row">

            <div class="label">
                Ispitivanje vrijedi do
            </div>

            <div class="value">

                {{
                    $miscellaneous
                        ->examination_valid_until
                        ?->format('d.m.Y.')
                    ?? '—'
                }}

            </div>

        </div>


        <div class="row">

            <div class="label">
                Status roka
            </div>

            <div class="value">

                <span class="badge {{ $validityClass }}">
                    {{ $validityLabel }}
                </span>

            </div>

        </div>


        @if(
            filled(
                $miscellaneous->remark
            )
        )

            <div class="row">

                <div class="label">
                    Napomena
                </div>

                <div class="value">
                    {{ $miscellaneous->remark }}
                </div>

            </div>

        @endif

    </div>


    <div class="card">

        <h2>
            Dokumentacija
        </h2>

        @forelse(
            $files
            as $index => $file
        )

            <a
                href="{{
                    route(
                        'public.miscellaneous.attachment',
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
                Za ovo ispitivanje trenutačno
                nema dostupnih priloga.
            </div>

        @endforelse

    </div>


    <div class="footer">
        Podaci su dostupni samo za ovo ispitivanje.
        <br>
        ZNR LIDER
    </div>

</div>

</body>

</html>