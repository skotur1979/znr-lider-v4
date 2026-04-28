<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>ZNR LIDER - Izvještaj o stanju sustava</title>

    <style>
        @page { margin: 22px; }

        * {
            font-family: "DejaVu Sans", sans-serif;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f7;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.45;
        }

        .container {
            width: 100%;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid #dbe2ea;
        }

        .header {
            background: #111827;
            padding: 28px 34px 24px;
            color: #ffffff;
        }

        .brand {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.4px;
            color: #f59e0b;
            text-transform: uppercase;
        }

        .title {
            margin-top: 8px;
            font-size: 28px;
            font-weight: 900;
            line-height: 34px;
        }

        .meta {
            margin-top: 8px;
            font-size: 13px;
            color: #cbd5e1;
        }

        .content {
            padding: 28px 34px 30px;
        }

        .intro {
            font-size: 14px;
            line-height: 22px;
            color: #334155;
            margin-bottom: 18px;
        }

        .label {
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: #92400e;
            margin-bottom: 8px;
        }

        .smart-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 15px;
            padding: 17px 18px;
            margin-bottom: 18px;
            color: #111827;
        }

        .smart-box p {
            margin: 0;
            font-size: 14px;
            line-height: 22px;
        }

        .status-box {
            border-radius: 16px;
            padding: 18px 20px;
            margin: 18px 0 20px;
            text-align: center;
        }

        .status-critical {
            background: #dc2626;
            color: #ffffff;
            border: 2px solid #991b1b;
        }

        .status-warning {
            background: #f97316;
            color: #ffffff;
            border: 2px solid #c2410c;
        }

        .status-ok {
            background: #16a34a;
            color: #ffffff;
            border: 2px solid #15803d;
        }

        .status-small {
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-main {
            font-size: 30px;
            font-weight: 900;
            margin-top: 7px;
        }

        .status-text {
            margin-top: 8px;
            font-size: 14px;
            font-weight: 700;
        }

        .stats {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
            margin: 0 -10px 20px -10px;
        }

        .stat {
            width: 33.33%;
            border-radius: 16px;
            padding: 16px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .stat-red {
            background: #fff1f2;
            border-color: #fecdd3;
        }

        .stat-yellow {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .stat-blue {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 900;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .stat-value {
            font-size: 30px;
            line-height: 32px;
            font-weight: 900;
            color: #0f172a;
        }

        .red { color: #dc2626; }
        .yellow { color: #d97706; }
        .blue { color: #2563eb; }

        .section-title {
            font-size: 19px;
            font-weight: 900;
            color: #0f172a;
            margin: 24px 0 12px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
        }

        table.data th {
            background: #f1f5f9;
            padding: 12px 14px;
            font-size: 13px;
            font-weight: 900;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        table.data td {
            padding: 11px 14px;
            font-size: 13px;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
        }

        table.data tr:nth-child(even) td {
            background: #f8fafc;
        }

        .center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            min-width: 42px;
            border-radius: 999px;
            padding: 5px 12px;
            font-weight: 900;
            text-align: center;
        }

        .badge-red {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .badge-yellow {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .actions-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 18px;
        }

        .actions {
            margin: 0;
            padding-left: 18px;
        }

        .actions li {
            margin-bottom: 7px;
            font-size: 13px;
            line-height: 20px;
        }

        .conclusion {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 6px solid #2563eb;
            border-radius: 14px;
            padding: 15px 17px;
            color: #1e3a8a;
            font-size: 13px;
            line-height: 21px;
        }

        .footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            line-height: 18px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>

<body>
@php
    $statusClass = $totalExpired > 0
        ? 'status-critical'
        : ($totalSoon > 0 ? 'status-warning' : 'status-ok');

    $statusIcon = $totalExpired > 0
        ? '🚨'
        : ($totalSoon > 0 ? '⚠️' : '✅');

    $statusMessage = $totalExpired > 0
        ? 'Sustav zahtijeva hitnu reakciju zbog isteklih stavki.'
        : ($totalSoon > 0
            ? 'Sustav zahtijeva planiranje aktivnosti koje uskoro istječu.'
            : 'Trenutno nema kritičnih isteklih stavki.');
@endphp

<div class="container">

    <div class="header">
        <div class="brand">ZNR LIDER</div>
        <div class="title">Izvještaj o stanju sustava</div>
        <div class="meta">
            Datum izvještaja: {{ $reportDate }} · Automatski PDF izvještaj
        </div>
    </div>

    <div class="content">

        <div class="intro">
            Profesionalni pregled trenutnog stanja sustava, rokova, valjanosti i preporučenih aktivnosti.
        </div>

        <div class="smart-box">
            <div class="label">Pametni sažetak</div>
            <p>
                Sustav je trenutno u statusu <strong>{{ $systemStatus }}</strong>.
                Evidentirano je <strong>{{ $totalExpired }}</strong> isteklih stavki i
                <strong>{{ $totalSoon }}</strong> stavki koje uskoro istječu.
            </p>
        </div>

        <div class="status-box {{ $statusClass }}">
            <div class="status-small">Status sustava</div>
            <div class="status-main">{{ $statusIcon }} {{ mb_strtoupper($systemStatus) }}</div>
            <div class="status-text">{{ $statusMessage }}</div>
        </div>

        <table class="stats">
            <tr>
                <td class="stat stat-red">
                    <div class="stat-label">Isteklo</div>
                    <div class="stat-value red">{{ $totalExpired }}</div>
                </td>

                <td class="stat stat-yellow">
                    <div class="stat-label">U 30 dana</div>
                    <div class="stat-value yellow">{{ $totalSoon }}</div>
                </td>

                <td class="stat stat-blue">
                    <div class="stat-label">Kategorije</div>
                    <div class="stat-value blue">{{ count($rows) }}</div>
                </td>
            </tr>
        </table>

        <div class="section-title">Rokovi i valjanosti</div>

        <table class="data">
            <thead>
            <tr>
                <th>Stavka</th>
                <th class="center" style="width:110px;">Isteklo</th>
                <th class="center" style="width:110px;">U 30 dana</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td><strong>{{ $row['label'] }}</strong></td>
                    <td class="center">
                        <span class="badge badge-red">{{ $row['expired'] }}</span>
                    </td>
                    <td class="center">
                        <span class="badge badge-yellow">{{ $row['soon'] }}</span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="section-title">Preporučene akcije</div>

        <div class="actions-box">
            <ol class="actions">
                @foreach ($actions as $action)
                    <li>{{ $action }}</li>
                @endforeach
            </ol>
        </div>

        <div class="section-title">Zaključak</div>

        <div class="conclusion">
            {{ $summary }}
            <br><br>
            Preporučuje se prioritetno rješavanje isteklih stavki, a zatim planiranje aktivnosti koje istječu unutar 30 dana.
        </div>

        <div class="footer">
            Ovaj izvještaj je automatski generiran iz sustava <strong>ZNR LIDER</strong>.<br>
            Ako podaci odstupaju od očekivanih, provjerite evidencije i statuse unutar aplikacije.
        </div>

    </div>
</div>
</body>
</html>