<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>KPI izvještaji</title>

    <style>
        @page { margin: 16px 12px 28px 12px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 6.5px;
            color: #111827;
        }

        h1 {
            margin: 0 0 6px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .meta {
            margin-bottom: 8px;
            font-size: 8px;
            color: #374151;
        }

        .group-title {
            background: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            margin-top: 10px;
            font-size: 9px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #444;
            padding: 2px;
            vertical-align: middle;
            overflow: hidden;
            word-wrap: break-word;
        }

        th {
            background: #e5e7eb;
            font-weight: bold;
            text-align: center;
        }

        .center { text-align: center; }

        .kpi-name {
            font-weight: bold;
        }

        .value-success {
            background: #00b050;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }

        .value-warning {
            background: #ffff00;
            color: #000000;
            font-weight: bold;
            text-align: center;
        }

        .value-danger {
            background: #ff0000;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }

        .value-neutral {
            text-align: center;
        }
    </style>
</head>

<body>
@php
    $monthLabels = [
        1 => '01',
        2 => '02',
        3 => '03',
        4 => '04',
        5 => '05',
        6 => '06',
        7 => '07',
        8 => '08',
        9 => '09',
        10 => '10',
        11 => '11',
        12 => '12',
    ];

    $formatNumber = function ($value) {
        if ($value === null || $value === '') {
            return '-';
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, 2, ',', '.');
    };

    $cellClass = function (?string $status) {
        return match ($status) {
            'success' => 'value-success',
            'warning' => 'value-warning',
            'danger' => 'value-danger',
            default => 'value-neutral',
        };
    };

    $getMonthData = function (array $row, int $month) {
        $months = $row['months'] ?? $row['monthly'] ?? $row['values'] ?? [];

        return $months[$month] ?? $months[(string) $month] ?? null;
    };

    $getMonthValue = function (array $row, int $month) use ($getMonthData, $formatNumber) {
        $monthData = $getMonthData($row, $month);

        if (is_array($monthData)) {
            return $monthData['formatted']
                ?? $monthData['formatted_value']
                ?? $monthData['value_formatted']
                ?? $formatNumber($monthData['value'] ?? null);
        }

        return $formatNumber($monthData);
    };

    $getMonthStatus = function (array $row, int $month) use ($getMonthData) {
        $monthData = $getMonthData($row, $month);

        if (is_array($monthData)) {
            return $monthData['status'] ?? null;
        }

        return null;
    };

    $getAverage = function (array $row) use ($formatNumber) {
        return $row['formatted_average']
            ?? $row['average_formatted']
            ?? $row['avg_formatted']
            ?? $formatNumber($row['average'] ?? $row['avg'] ?? null);
    };

    $getTotal = function (array $row) use ($formatNumber) {
        return $row['formatted_total']
            ?? $row['total_formatted']
            ?? $formatNumber($row['total'] ?? null);
    };
@endphp

<h1>KPI izvještaji</h1>

<div class="meta">
    Godina: {{ $year }} | Datum izvoza: {{ now()->format('d.m.Y. H:i') }}
</div>

@forelse ($groups as $category => $rows)
    <div class="group-title">{{ $category }}</div>

    <table>
        <thead>
        <tr>
            <th style="width: 24%;">KPI</th>
            <th style="width: 5%;">Jedinica</th>
            <th style="width: 5%;">Cilj</th>

            @foreach ($monthLabels as $label)
                <th style="width: 4.3%;">{{ $label }}</th>
            @endforeach

            <th style="width: 5%;">Prosjek</th>
            <th style="width: 5%;">Ukupno</th>
        </tr>
        </thead>

        <tbody>
        @forelse ($rows as $row)
            <tr>
                <td class="kpi-name">{{ $row['name'] ?? '' }}</td>

                <td class="center">{{ $row['unit'] ?? '' }}</td>

                <td class="center">
                    {{ $row['formatted_target'] ?? $row['target_formatted'] ?? $formatNumber($row['target_value'] ?? $row['target'] ?? null) }}
                </td>

                @foreach ($monthLabels as $month => $label)
                    @php
                        $status = $getMonthStatus($row, $month);
                        $value = $getMonthValue($row, $month);
                    @endphp

                    <td class="{{ $cellClass($status) }}">
                        {{ $value }}
                    </td>
                @endforeach

                <td class="center">{{ $getAverage($row) }}</td>
                <td class="center">{{ $getTotal($row) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="17" class="center">Nema KPI zapisa.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@empty
    <table>
        <tr>
            <td class="center">Nema podataka za izvoz.</td>
        </tr>
    </table>
@endforelse

<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script('
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 8;

        $pageText = "Str. " . $PAGE_NUM . " / " . $PAGE_COUNT;
        $dateText = "Ispis: {{ now()->format("d.m.Y.") }}";

        $pdf->text(18, 575, $dateText, $font, $size);

        $width = $fontMetrics->get_text_width($pageText, $font, $size);
        $pdf->text(820 - $width, 575, $pageText, $font, $size);
    ');
}
</script>

</body>
</html>