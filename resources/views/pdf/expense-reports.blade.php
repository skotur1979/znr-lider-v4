<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <title>
        Izvještaj troškova
    </title>

    <style>

        @page {
            margin: 18px;
        }

        body {
            font-family:
                "DejaVu Sans",
                sans-serif;

            font-size: 9px;

            color: #111827;
        }

        h1 {
            margin:
                0
                0
                4px;

            font-size: 22px;
        }

        h2 {
            margin:
                16px
                0
                7px;

            padding: 7px;

            font-size: 12px;

            background: #f3f4f6;

            border:
                1px
                solid
                #d1d5db;

            page-break-after: avoid;
        }

        .meta {
            margin-bottom: 12px;

            color: #4b5563;
        }

        /*
        |--------------------------------------------------------------------------
        | KPI kartice
        |--------------------------------------------------------------------------
        */

        .cards {
            width: 100%;

            border-collapse: separate;

            border-spacing: 5px;

            margin-bottom: 8px;
        }

        .card {
            padding: 8px;

            border:
                1px
                solid
                #d1d5db;

            border-radius: 6px;
        }

        .label {
            font-size: 7px;

            font-weight: bold;

            text-transform: uppercase;

            color: #6b7280;
        }

        .value {
            margin-top: 3px;

            font-size: 15px;

            font-weight: bold;
        }

        /*
        |--------------------------------------------------------------------------
        | Tablice
        |--------------------------------------------------------------------------
        */

        table.report {
            width: 100%;

            border-collapse: collapse;
        }

        table.report th,
        table.report td {
            padding: 4px 5px;

            border:
                1px
                solid
                #d1d5db;
        }

        table.report th {
            background: #f3f4f6;

            font-size: 7px;

            text-transform: uppercase;

            text-align: center;
        }

        table.report td {
            vertical-align: middle;
        }

        table.report thead {
            display: table-header-group;
        }

        /*
         * Red tablice nikada se ne dijeli
         * između dvije stranice.
         */
        table.report tr {
            page-break-inside: avoid;
        }

        /*
        |--------------------------------------------------------------------------
        | Kontrola stranica
        |--------------------------------------------------------------------------
        */

        .page-break-before {
            page-break-before: always;
        }

        .keep-together {
            page-break-inside: avoid;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .success {
            color: #15803d;

            font-weight: bold;
        }

        .danger {
            color: #b91c1c;

            font-weight: bold;
        }

        .warning {
            color: #b45309;

            font-weight: bold;
        }

        /*
        |--------------------------------------------------------------------------
        | Trake
        |--------------------------------------------------------------------------
        */

        .bar-track {
            width: 100%;

            height: 7px;

            background: #e5e7eb;
        }

        .bar-fill {
            height: 7px;

            background: #f59e0b;
        }

        .bar-fill-blue {
            height: 7px;

            background: #3b82f6;
        }

        /*
        |--------------------------------------------------------------------------
        | Dva stupca
        |--------------------------------------------------------------------------
        */

        .two-columns {
            width: 100%;

            border-collapse: separate;

            border-spacing: 8px 0;
        }

        .two-columns > tbody > tr > td {
            width: 50%;

            vertical-align: top;
        }

        .section-title {
            margin-bottom: 6px;

            padding: 6px;

            border:
                1px
                solid
                #d1d5db;

            background: #f3f4f6;

            font-size: 11px;

            font-weight: bold;
        }

        .small {
            font-size: 7px;

            color: #6b7280;
        }

    </style>

</head>

<body>

@php

    $summary =
        $report['summary']
        ?? [];

    $monthly =
        $report['monthly']
        ?? [];

    $byCategory =
        $report['by_category']
        ?? [];

    $bySupplier =
        $report['by_supplier']
        ?? [];

    $byYear =
        $report['by_year']
        ?? [];

    $comparison =
        $report['comparison']
        ?? [];

    $topExpenses =
        $report['top_expenses']
        ?? [];

    $money =
        fn ($value) =>
            number_format(
                (float) $value,
                2,
                ',',
                '.'
            )
            . ' €';

    $maxMonthly =
        max(
            1,
            collect($monthly)
                ->max('total')
                ?? 0
        );

    $maxCategory =
        max(
            1,
            collect($byCategory)
                ->max('total')
                ?? 0
        );

    $maxSupplier =
        max(
            1,
            collect($bySupplier)
                ->max('total')
                ?? 0
        );

    $maxYear =
        max(
            1,
            collect($byYear)
                ->max('total')
                ?? 0
        );

    $comparisonMax =
        max(
            1,
            collect(
                $comparison['months']
                ?? []
            )->max(
                fn ($row) =>
                    max(
                        $row['current'],
                        $row['comparison']
                    )
            )
            ?? 0
        );

    $monthLabel =
        ($filters['month'] ?? 'all')
            === 'all'
                ? 'Svi mjeseci'
                : $filters['month'];

    $realizedLabel =
        match (
            $filters['realized']
            ?? 'all'
        ) {
            '1' =>
                'Realizirano',

            '0' =>
                'Nerealizirano',

            default =>
                'Sve',
        };

@endphp


{{-- ============================================================= --}}
{{-- 1. STRANICA --}}
{{-- ============================================================= --}}

<h1>
    Izvještaj troškova
</h1>


<div class="meta">

    Godina:
    {{
        ($filters['year'] ?? 'all')
            === 'all'
                ? 'Sve godine'
                : $filters['year']
    }}

    · Mjesec:
    {{ $monthLabel }}

    · Status:
    {{ $realizedLabel }}

    · Izrađeno:
    {{ now()->format('d.m.Y. H:i') }}

</div>


{{-- KPI - 1. red --}}

<table class="cards">

    <tr>

        <td class="card">

            <div class="label">
                Ukupno evidentirano
            </div>

            <div class="value">
                {{
                    $money(
                        $summary[
                            'total_amount'
                        ]
                        ?? 0
                    )
                }}
            </div>

        </td>


        <td class="card">

            <div class="label">
                Realizirano
            </div>

            <div class="value">
                {{
                    $money(
                        $summary[
                            'realized_amount'
                        ]
                        ?? 0
                    )
                }}
            </div>

        </td>


        <td class="card">

            <div class="label">
                Nerealizirano
            </div>

            <div class="value">
                {{
                    $money(
                        $summary[
                            'unrealized_amount'
                        ]
                        ?? 0
                    )
                }}
            </div>

        </td>


        <td class="card">

            <div class="label">
                Broj troškova
            </div>

            <div class="value">
                {{
                    $summary[
                        'count'
                    ]
                    ?? 0
                }}
            </div>

        </td>

    </tr>

</table>


{{-- KPI - 2. red --}}

<table class="cards">

    <tr>

        <td class="card">

            <div class="label">
                Budžet
            </div>

            <div class="value">

                @if (
                    (
                        $summary[
                            'budget_amount'
                        ]
                        ?? null
                    )
                    !== null
                )

                    {{
                        $money(
                            $summary[
                                'budget_amount'
                            ]
                        )
                    }}

                @else

                    —

                @endif

            </div>

        </td>


        <td class="card">

            <div class="label">
                Preostalo
            </div>

            @php

                $remaining =
                    $summary[
                        'remaining_budget'
                    ]
                    ?? null;

            @endphp

            <div
                class="
                    value
                    {{
                        $remaining === null
                            ? ''
                            : (
                                $remaining >= 0
                                    ? 'success'
                                    : 'danger'
                            )
                    }}
                "
            >

                {{
                    $remaining === null
                        ? '—'
                        : $money(
                            $remaining
                        )
                }}

            </div>

        </td>


        <td class="card">

            <div class="label">
                Prosječni trošak
            </div>

            <div class="value">

                {{
                    $money(
                        $summary[
                            'average'
                        ]
                        ?? 0
                    )
                }}

            </div>

        </td>


        <td class="card">

            <div class="label">
                Najveći trošak
            </div>

            <div class="value">

                {{
                    $money(
                        $summary[
                            'max_amount'
                        ]
                        ?? 0
                    )
                }}

            </div>

        </td>

    </tr>

</table>


<h2>
    Troškovi po mjesecima
</h2>


<table class="report">

    <thead>

        <tr>

            <th style="width:18%;">
                Mjesec
            </th>

            <th style="width:18%;">
                Iznos
            </th>

            <th style="width:12%;">
                Broj stavki
            </th>

            <th>
                Udio
            </th>

        </tr>

    </thead>


    <tbody>

        @foreach (
            $monthly
            as $row
        )

            <tr>

                <td>
                    {{ $row['label'] }}
                </td>


                <td class="right bold">

                    {{
                        $money(
                            $row['total']
                        )
                    }}

                </td>


                <td class="center">
                    {{ $row['count'] }}
                </td>


                <td>

                    <div class="bar-track">

                        <div
                            class="bar-fill"
                            style="
                                width:
                                {{
                                    min(
                                        100,
                                        (
                                            $row['total']
                                            / $maxMonthly
                                        )
                                        * 100
                                    )
                                }}%;
                            "
                        ></div>

                    </div>

                </td>

            </tr>

        @endforeach

    </tbody>

</table>



{{-- ============================================================= --}}
{{-- 2. STRANICA --}}
{{-- ============================================================= --}}

<div class="page-break-before">


    <h2>
        Troškovi po kategorijama
    </h2>


    <table class="report">

        <thead>

            <tr>

                <th>
                    Kategorija
                </th>

                <th style="width:120px;">
                    Iznos
                </th>

                <th style="width:75px;">
                    Broj stavki
                </th>

                <th style="width:35%;">
                    Udio
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse (
                $byCategory
                as $row
            )

                <tr>

                    <td>
                        {{ $row['label'] }}
                    </td>


                    <td class="right bold">

                        {{
                            $money(
                                $row['total']
                            )
                        }}

                    </td>


                    <td class="center">
                        {{ $row['count'] }}
                    </td>


                    <td>

                        <div class="bar-track">

                            <div
                                class="bar-fill"
                                style="
                                    width:
                                    {{
                                        min(
                                            100,
                                            (
                                                $row['total']
                                                / $maxCategory
                                            )
                                            * 100
                                        )
                                    }}%;
                                "
                            ></div>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="4">
                        Nema podataka.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>


    <table class="two-columns">

        <tr>

            {{-- DOBAVLJAČI --}}

            <td>

                <div class="section-title">
                    Top dobavljači
                </div>


                <table class="report">

                    <thead>

                        <tr>

                            <th>
                                Dobavljač
                            </th>

                            <th style="width:95px;">
                                Iznos
                            </th>

                            <th style="width:55px;">
                                Broj
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse (
                            $bySupplier
                            as $row
                        )

                            <tr>

                                <td>
                                    {{ $row['label'] }}
                                </td>


                                <td class="right">

                                    {{
                                        $money(
                                            $row['total']
                                        )
                                    }}

                                </td>


                                <td class="center">
                                    {{ $row['count'] }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="3">
                                    Nema podataka.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </td>


            {{-- GODINE --}}

            <td>

                <div class="section-title">
                    Ukupni troškovi po godinama
                </div>


                <table class="report">

                    <thead>

                        <tr>

                            <th>
                                Godina
                            </th>

                            <th style="width:100px;">
                                Iznos
                            </th>

                            <th style="width:55px;">
                                Broj
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse (
                            $byYear
                            as $row
                        )

                            <tr>

                                <td class="center bold">
                                    {{ $row['label'] }}
                                </td>


                                <td class="right">

                                    {{
                                        $money(
                                            $row['total']
                                        )
                                    }}

                                </td>


                                <td class="center">
                                    {{ $row['count'] }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="3">
                                    Nema podataka.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </td>

        </tr>

    </table>

</div>



{{-- ============================================================= --}}
{{-- 3. STRANICA --}}
{{-- ============================================================= --}}

<div class="page-break-before">


    @if (
        $comparison[
            'enabled'
        ]
        ?? false
    )

        <h2>

            Usporedba troškova:
            {{ $comparison['year'] }}

            /

            {{
                $comparison[
                    'comparison_year'
                ]
            }}

        </h2>


        <table class="report">

            <thead>

                <tr>

                    <th>
                        Mjesec
                    </th>

                    <th>
                        {{ $comparison['year'] }}
                    </th>

                    <th>
                        {{
                            $comparison[
                                'comparison_year'
                            ]
                        }}
                    </th>

                    <th>
                        Razlika
                    </th>

                    <th>
                        Promjena
                    </th>

                </tr>

            </thead>


            <tbody>

                @foreach (
                    $comparison['months']
                    as $row
                )

                    <tr>

                        <td>
                            {{ $row['label'] }}
                        </td>


                        <td class="right">

                            {{
                                $money(
                                    $row[
                                        'current'
                                    ]
                                )
                            }}

                        </td>


                        <td class="right">

                            {{
                                $money(
                                    $row[
                                        'comparison'
                                    ]
                                )
                            }}

                        </td>


                        <td
                            class="
                                right
                                {{
                                    $row['difference']
                                        > 0
                                        ? 'danger'
                                        : (
                                            $row['difference']
                                                < 0
                                                ? 'success'
                                                : ''
                                        )
                                }}
                            "
                        >

                            {{
                                $money(
                                    $row[
                                        'difference'
                                    ]
                                )
                            }}

                        </td>


                        <td class="right">

                            @if (
                                $row[
                                    'percentage'
                                ]
                                !== null
                            )

                                {{
                                    number_format(
                                        $row[
                                            'percentage'
                                        ],
                                        1,
                                        ',',
                                        '.'
                                    )
                                }} %

                            @else

                                —

                            @endif

                        </td>

                    </tr>

                @endforeach


                <tr>

                    <td class="bold">
                        UKUPNO
                    </td>


                    <td class="right bold">

                        {{
                            $money(
                                $comparison[
                                    'current_total'
                                ]
                                ?? 0
                            )
                        }}

                    </td>


                    <td class="right bold">

                        {{
                            $money(
                                $comparison[
                                    'comparison_total'
                                ]
                                ?? 0
                            )
                        }}

                    </td>


                    <td
                        class="
                            right
                            bold
                            {{
                                (
                                    $comparison[
                                        'difference'
                                    ]
                                    ?? 0
                                )
                                > 0
                                    ? 'danger'
                                    : 'success'
                            }}
                        "
                    >

                        {{
                            $money(
                                $comparison[
                                    'difference'
                                ]
                                ?? 0
                            )
                        }}

                    </td>


                    <td></td>

                </tr>

            </tbody>

        </table>

    @endif


    <h2>
        Najveći pojedinačni troškovi
    </h2>


    <table class="report">

        <thead>

            <tr>

                <th style="width:55px;">
                    Godina
                </th>

                <th style="width:70px;">
                    Mjesec
                </th>

                <th>
                    Kategorija
                </th>

                <th>
                    Naziv troška
                </th>

                <th>
                    Dobavljač
                </th>

                <th style="width:85px;">
                    Iznos
                </th>

                <th style="width:65px;">
                    Realizirano
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse (
                $topExpenses
                as $row
            )

                <tr>

                    <td class="center">
                        {{ $row['year'] }}
                    </td>


                    <td class="center">
                        {{ $row['month'] }}
                    </td>


                    <td>
                        {{ $row['category'] }}
                    </td>


                    <td class="bold">
                        {{ $row['name'] }}
                    </td>


                    <td>
                        {{ $row['supplier'] }}
                    </td>


                    <td class="right bold">

                        {{
                            $money(
                                $row['amount']
                            )
                        }}

                    </td>


                    <td
                        class="
                            center
                            {{
                                $row['realized']
                                    ? 'success'
                                    : 'warning'
                            }}
                        "
                    >

                        {{
                            $row['realized']
                                ? 'Da'
                                : 'Ne'
                        }}

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="7">
                        Nema podataka za odabrane filtre.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

</div>


</body>

</html>