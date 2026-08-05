<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">

    <title>Izvještaj zapažanja</title>

    <style>
        @page {
            margin: 18px;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            color: #111827;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 22px;
        }

        h2 {
            margin: 18px 0 7px;
            padding: 7px;
            font-size: 13px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
        }

        .meta {
            margin-bottom: 14px;
            color: #4b5563;
        }

        .cards {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
        }

        .card {
            padding: 9px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
        }

        .label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
        }

        .value {
            margin-top: 3px;
            font-size: 18px;
            font-weight: bold;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
        }

        table.report th,
        table.report td {
            border: 1px solid #d1d5db;
            padding: 5px;
        }

        table.report th {
            background: #f3f4f6;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .danger {
            color: #b91c1c;
            font-weight: bold;
        }

        .warning {
            color: #b45309;
            font-weight: bold;
        }

        .success {
            color: #15803d;
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
        $summary = $report['summary'] ?? [];
    @endphp

    <h1>Izvještaj zapažanja</h1>

    <div class="meta">
        Godina:
        {{ ($filters['year'] ?? 'all') === 'all'
            ? 'Sve godine'
            : $filters['year']
        }}

        · Izrađeno:
        {{ now()->format('d.m.Y. H:i') }}
    </div>

    <table class="cards">
        <tr>
            @foreach ([
                'Ukupno' => $summary['total'] ?? 0,
                'Near Miss' => $summary['nearMiss'] ?? 0,
                'Negativna' => $summary['negative'] ?? 0,
                'Pozitivna' => $summary['positive'] ?? 0,
                'Završeno' => $summary['completed'] ?? 0,
            ] as $label => $value)
                <td class="card">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value }}</div>
                </td>
            @endforeach
        </tr>

        <tr>
            @foreach ([
                'Nije započeto' => $summary['notStarted'] ?? 0,
                'U tijeku' => $summary['inProgress'] ?? 0,
                'Isteklo' => $summary['expired'] ?? 0,
                'Ističe u 30 dana' => $summary['expiring'] ?? 0,
                'Prosjek zatvaranja' =>
                    ($summary['averageClosingDays'] ?? '-') . ' dana',
            ] as $label => $value)
                <td class="card">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <h2>Zapažanja po mjesecima</h2>

    <table class="report">
        <thead>
            <tr>
                <th>Mjesec</th>
                <th>Ukupno</th>
                <th>NM</th>
                <th>Negativna</th>
                <th>Pozitivna</th>
                <th>Nije započeto</th>
                <th>U tijeku</th>
                <th>Završeno</th>
            </tr>
        </thead>

        <tbody>
            @foreach (($report['monthly'] ?? []) as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="center">{{ $row['total'] }}</td>
                    <td class="center">{{ $row['near_miss'] }}</td>
                    <td class="center">{{ $row['negative'] }}</td>
                    <td class="center">{{ $row['positive'] }}</td>
                    <td class="center danger">{{ $row['not_started'] }}</td>
                    <td class="center warning">{{ $row['in_progress'] }}</td>
                    <td class="center success">{{ $row['completed'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Top 10 najčešćih opasnosti</h2>

    <table class="report">
        <thead>
            <tr>
                <th>Vrsta opasnosti</th>
                <th style="width:90px;">Broj</th>
            </tr>
        </thead>

        <tbody>
            @forelse (($report['topHazards'] ?? []) as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="center">{{ $row['count'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Nema podataka.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Top 10 lokacija s najviše zapažanja</h2>

    <table class="report">
        <thead>
            <tr>
                <th>Lokacija</th>
                <th style="width:90px;">Broj</th>
            </tr>
        </thead>

        <tbody>
            @forelse (($report['topLocations'] ?? []) as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="center">{{ $row['count'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Nema podataka.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Odgovorne osobe s najviše otvorenih radnji</h2>

    <table class="report">
        <thead>
            <tr>
                <th>Odgovorna osoba</th>
                <th>Otvoreno</th>
                <th>Nije započeto</th>
                <th>U tijeku</th>
                <th>Isteklo</th>
            </tr>
        </thead>

        <tbody>
            @forelse (($report['topResponsibleOpen'] ?? []) as $row)
                <tr>
                    <td>{{ $row['responsible'] }}</td>
                    <td class="center">{{ $row['open'] }}</td>
                    <td class="center danger">{{ $row['not_started'] }}</td>
                    <td class="center warning">{{ $row['in_progress'] }}</td>
                    <td class="center danger">{{ $row['expired'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nema otvorenih radnji.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Prosječno vrijeme zatvaranja po mjesecima</h2>

    <table class="report">
        <thead>
            <tr>
                <th>Mjesec</th>
                <th>Broj završenih</th>
                <th>Prosječno vrijeme zatvaranja</th>
            </tr>
        </thead>

        <tbody>
            @foreach (($report['averageClosingByMonth'] ?? []) as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="center">{{ $row['completed'] }}</td>
                    <td class="center">
                        {{ $row['average_days'] !== null
                            ? number_format(
                                $row['average_days'],
                                1,
                                ',',
                                '.'
                            ) . ' dana'
                            : '-'
                        }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>