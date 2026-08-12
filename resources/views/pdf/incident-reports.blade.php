<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">

    <title>
        Izvještaj incidenata
    </title>

    <style>
        @page {
            margin: 15mm;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9px;
            color: #111827;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 4px 0;
        }

        h2 {
            font-size: 12px;
            margin: 18px 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #d1d5db;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 14px;
        }

        .filters {
            padding: 8px;
            background: #f3f4f6;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
        }

        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px;
            margin-bottom: 8px;
        }

        .kpi {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: center;
        }

        .kpi-label {
            font-size: 7px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: bold;
        }

        .kpi-value {
            font-size: 17px;
            font-weight: bold;
            margin-top: 4px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #d1d5db;
            padding: 5px;
        }

        .data-table th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .two-columns {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
        }

        .two-columns > tbody > tr > td {
            width: 50%;
            vertical-align: top;
        }

        .top-table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-table td,
        .top-table th {
            border-bottom: 1px solid #e5e7eb;
            padding: 5px;
        }

        .top-table th {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

@php
    $summary = $report['summary'] ?? [];
    $months = $report['months'] ?? [];
    $types = $report['types'] ?? [];
    $employment = $report['employment'] ?? [];

    $topLocations =
        $report['top_locations'] ?? [];

    $topCauses =
        $report['top_causes'] ?? [];

    $topInjuryTypes =
        $report['top_injury_types'] ?? [];

    $topBodyParts =
        $report['top_body_parts'] ?? [];

    $locationsTable =
        $report['locations_table'] ?? [];

    $monthNames = [
        '1' => 'Siječanj',
        '2' => 'Veljača',
        '3' => 'Ožujak',
        '4' => 'Travanj',
        '5' => 'Svibanj',
        '6' => 'Lipanj',
        '7' => 'Srpanj',
        '8' => 'Kolovoz',
        '9' => 'Rujan',
        '10' => 'Listopad',
        '11' => 'Studeni',
        '12' => 'Prosinac',
    ];

    $typeNames = [
        'LTA' => 'LTA – Ozljeda na radu',
        'MTA' => 'MTA – Pružanje PP izvan tvrtke',
        'FAA' => 'FAA – Pružanje PP u tvrtki',
    ];

    $employmentNames = [
        'Permanent' => 'Stalni',
        'Temporary' => 'Privremeni',
    ];
@endphp

<h1>
    IZVJEŠTAJ INCIDENATA
</h1>

<div class="subtitle">
    Izrađeno:
    {{ now()->format('d.m.Y.') }}
</div>

<div class="filters">
    <strong>Aktivni filteri:</strong>

    Godina:
    {{
        ($filters['year'] ?? 'all') === 'all'
            ? 'Sve'
            : $filters['year']
    }}

    &nbsp; | &nbsp;

    Mjesec:
    {{
        ($filters['month'] ?? 'all') === 'all'
            ? 'Svi'
            : (
                $monthNames[
                    (string) $filters['month']
                ] ?? $filters['month']
            )
    }}

    &nbsp; | &nbsp;

    Vrsta:
    {{
        filled($filters['type'] ?? null)
            ? (
                $typeNames[
                    $filters['type']
                ] ?? $filters['type']
            )
            : 'Sve'
    }}

    &nbsp; | &nbsp;

    Lokacija:
    {{
        $filters['location']
            ?? 'Sve'
    }}

    &nbsp; | &nbsp;

    Zaposlenje:
    {{
        filled(
            $filters['employment'] ?? null
        )
            ? (
                $employmentNames[
                    $filters['employment']
                ]
                ?? $filters['employment']
            )
            : 'Sve'
    }}
</div>


<table class="kpi-table">
    <tr>
        <td class="kpi">
            <div class="kpi-label">
                Ukupno incidenata
            </div>

            <div class="kpi-value">
                {{ $summary['total'] ?? 0 }}
            </div>
        </td>

        <td class="kpi">
            <div class="kpi-label">
                LTA
            </div>

            <div class="kpi-value">
                {{ $summary['lta'] ?? 0 }}
            </div>
        </td>

        <td class="kpi">
            <div class="kpi-label">
                MTA
            </div>

            <div class="kpi-value">
                {{ $summary['mta'] ?? 0 }}
            </div>
        </td>

        <td class="kpi">
            <div class="kpi-label">
                FAA
            </div>

            <div class="kpi-value">
                {{ $summary['faa'] ?? 0 }}
            </div>
        </td>
    </tr>
</table>

<table class="kpi-table">
    <tr>
        <td class="kpi">
            <div class="kpi-label">
                Izgubljeni radni dani
            </div>

            <div class="kpi-value">
                {{ $summary['lost_days'] ?? 0 }}
            </div>
        </td>

        <td class="kpi">
            <div class="kpi-label">
                Prosjek izgubljenih dana
            </div>

            <div class="kpi-value">
                {{
                    number_format(
                        (float) (
                            $summary[
                                'average_lost_days'
                            ] ?? 0
                        ),
                        1,
                        ',',
                        '.'
                    )
                }}
            </div>
        </td>

        <td class="kpi">
            <div class="kpi-label">
                Najveći broj izgubljenih dana
            </div>

            <div class="kpi-value">
                {{
                    $summary[
                        'max_lost_days'
                    ] ?? 0
                }}
            </div>
        </td>

        <td class="kpi">
            <div class="kpi-label">
                Incidenti s izgubljenim danima
            </div>

            <div class="kpi-value">
                {{
                    $summary[
                        'incidents_with_lost_days'
                    ] ?? 0
                }}
            </div>
        </td>
    </tr>
</table>


<h2>
    Pregled po mjesecima
</h2>

<table class="data-table">
    <thead>
        <tr>
            <th>Mjesec</th>
            <th class="center">Ukupno</th>
            <th class="center">LTA</th>
            <th class="center">MTA</th>
            <th class="center">FAA</th>
            <th class="center">
                Izgubljeni radni dani
            </th>
        </tr>
    </thead>

    <tbody>
        @foreach($months as $row)
            <tr>
                <td>
                    {{ $row['label'] }}
                </td>

                <td class="center">
                    {{ $row['total'] }}
                </td>

                <td class="center">
                    {{ $row['lta'] }}
                </td>

                <td class="center">
                    {{ $row['mta'] }}
                </td>

                <td class="center">
                    {{ $row['faa'] }}
                </td>

                <td class="center">
                    {{ $row['lost_days'] }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>


<table class="two-columns">
    <tr>
        <td>
            <h2>
                Vrste incidenata
            </h2>

            <table class="top-table">
                @foreach($types as $row)
                    <tr>
                        <td>
                            {{ $row['label'] }}
                        </td>

                        <td class="right">
                            {{ $row['count'] }}
                            ({{ $row['percentage'] }}%)
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>

        <td>
            <h2>
                Vrsta zaposlenja
            </h2>

            <table class="top-table">
                @foreach($employment as $row)
                    <tr>
                        <td>
                            {{ $row['label'] }}
                        </td>

                        <td class="right">
                            {{ $row['count'] }}
                            ({{ $row['percentage'] }}%)
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>


<table class="two-columns">
    <tr>
        <td>
            <h2>
                Top 5 lokacija
            </h2>

            <table class="top-table">
                <tr>
                    <th>Lokacija</th>
                    <th class="right">
                        Incidenti
                    </th>
                    <th class="right">
                        Izg. dani
                    </th>
                </tr>

                @forelse($topLocations as $row)
                    <tr>
                        <td>
                            {{ $row['label'] }}
                        </td>

                        <td class="right">
                            {{ $row['count'] }}
                        </td>

                        <td class="right">
                            {{ $row['lost_days'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            Nema podataka.
                        </td>
                    </tr>
                @endforelse
            </table>
        </td>

        <td>
            <h2>
                Top 5 uzroka ozljede
            </h2>

            <table class="top-table">
                @forelse($topCauses as $row)
                    <tr>
                        <td>
                            {{ $row['label'] }}
                        </td>

                        <td class="right">
                            {{ $row['count'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td>
                            Nema podataka.
                        </td>
                    </tr>
                @endforelse
            </table>
        </td>
    </tr>
</table>


<table class="two-columns">
    <tr>
        <td>
            <h2>
                Top 5 tipova ozljede
            </h2>

            <table class="top-table">
                @forelse(
                    $topInjuryTypes
                    as $row
                )
                    <tr>
                        <td>
                            {{ $row['label'] }}
                        </td>

                        <td class="right">
                            {{ $row['count'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td>
                            Nema podataka.
                        </td>
                    </tr>
                @endforelse
            </table>
        </td>

        <td>
            <h2>
                Top 5 ozlijeđenih dijelova tijela
            </h2>

            <table class="top-table">
                @forelse(
                    $topBodyParts
                    as $row
                )
                    <tr>
                        <td>
                            {{ $row['label'] }}
                        </td>

                        <td class="right">
                            {{ $row['count'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td>
                            Nema podataka.
                        </td>
                    </tr>
                @endforelse
            </table>
        </td>
    </tr>
</table>


<div class="page-break"></div>

<h2>
    Pregled po lokacijama
</h2>

<table class="data-table">
    <thead>
        <tr>
            <th>Lokacija</th>
            <th class="center">Ukupno</th>
            <th class="center">LTA</th>
            <th class="center">MTA</th>
            <th class="center">FAA</th>
            <th class="center">
                Izgubljeni radni dani
            </th>
        </tr>
    </thead>

    <tbody>
        @forelse(
            $locationsTable
            as $row
        )
            <tr>
                <td>
                    {{ $row['location'] }}
                </td>

                <td class="center">
                    {{ $row['total'] }}
                </td>

                <td class="center">
                    {{ $row['lta'] }}
                </td>

                <td class="center">
                    {{ $row['mta'] }}
                </td>

                <td class="center">
                    {{ $row['faa'] }}
                </td>

                <td class="center">
                    {{ $row['lost_days'] }}
                </td>
            </tr>
        @empty
            <tr>
                <td
                    colspan="6"
                    class="center"
                >
                    Nema podataka za
                    odabrane filtere.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

</body>
</html>