<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>ZNR izvještaj o stanju sustava</title>

    <style>
        @page { margin: 24px; }

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
        }

        .header {
            background: #0f172a;
            padding: 26px 32px 22px 32px;
            color: #ffffff;
        }

        .brand {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #f59e0b;
            margin-bottom: 10px;
        }

        .title {
            font-size: 27px;
            font-weight: 800;
            line-height: 34px;
            color: #ffffff;
        }

        .meta {
            margin-top: 7px;
            font-size: 13px;
            color: #cbd5e1;
        }

        .content {
            padding: 28px 32px 30px 32px;
        }

        .section-label {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .7px;
            color: #1d4ed8;
            margin-bottom: 8px;
        }

        .smart-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 5px solid #2563eb;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 14px;
            color: #1e3a8a;
            font-size: 14px;
            line-height: 22px;
        }

        .critical-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-left: 5px solid #e11d48;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        .critical-title {
            font-size: 15px;
            font-weight: 800;
            color: #9f1239;
            margin-bottom: 6px;
        }

        .critical-text {
            font-size: 14px;
            line-height: 22px;
            color: #881337;
        }

        .stats {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
            margin: 0 -10px 18px -10px;
        }

        .stat {
            width: 33.33%;
            border-radius: 14px;
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

        .stat-label {
            font-size: 12px;
            font-weight: 800;
            color: #64748b;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 28px;
            line-height: 30px;
            font-weight: 900;
            color: #0f172a;
        }

        .red { color: #dc2626; }
        .yellow { color: #d97706; }

        .section-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 22px 0 14px 0;
        }

        table.data {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
        }

        table.data th {
            background: #f8fafc;
            padding: 12px 14px;
            font-size: 13px;
            font-weight: 800;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        table.data td {
            padding: 12px 14px;
            font-size: 13px;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
        }

        table.data tr:nth-child(even) td {
            background: #fcfdff;
        }

        .center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            min-width: 34px;
            border-radius: 999px;
            padding: 4px 10px;
            font-weight: 800;
            text-align: center;
        }

        .badge-red {
            background: #fee2e2;
            color: #dc2626;
        }

        .badge-yellow {
            background: #fef3c7;
            color: #d97706;
        }

        .actions {
            margin: 0;
            padding-left: 18px;
        }

        .actions li {
            margin-bottom: 7px;
            font-size: 13px;
        }

        .conclusion {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 5px solid #2563eb;
            border-radius: 12px;
            padding: 14px 16px;
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
<div class="container">

    <div class="header">
        <div class="brand">ZNR LIDER</div>
        <div class="title">ZNR izvještaj o stanju sustava</div>
        <div class="meta">
            Datum izvještaja: {{ $reportDate }} · Automatski PDF izvještaj
        </div>
    </div>

    <div class="content">

        <div class="section-label">Pametni sažetak</div>

        <div class="smart-box">
            Sustav je trenutno u statusu <strong>{{ $systemStatus }}</strong>.
            Ukupno je evidentirano <strong>{{ $totalExpired }}</strong> isteklih stavki i
            <strong>{{ $totalSoon }}</strong> stavki koje uskoro istječu.
        </div>

        <div class="critical-box">
            <div class="critical-title">Status sustava: {{ $systemStatus }}</div>
            <div class="critical-text">{{ $summary }}</div>
        </div>

        <table class="stats">
            <tr>
                <td class="stat stat-red">
                    <div class="stat-label">Isteklo</div>
                    <div class="stat-value red">{{ $totalExpired }}</div>
                </td>

                <td class="stat stat-yellow">
                    <div class="stat-label">Uskoro istječe</div>
                    <div class="stat-value yellow">{{ $totalSoon }}</div>
                </td>

                <td class="stat">
                    <div class="stat-label">Kategorija</div>
                    <div class="stat-value">{{ count($rows) }}</div>
                </td>
            </tr>
        </table>

        <div class="section-title">Rokovi i valjanosti</div>

        <table class="data">
            <thead>
                <tr>
                    <th>Stavka</th>
                    <th class="center" style="width:100px;">Isteklo</th>
                    <th class="center" style="width:100px;">Uskoro</th>
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

        <ol class="actions">
            @foreach ($actions as $action)
                <li>{{ $action }}</li>
            @endforeach
        </ol>

        <div class="section-title">Zaključak</div>

        <div class="conclusion">
            Sustav zahtijeva pažnju zbog evidentiranih isteklih stavki.
            Preporučuje se prioritetno rješavanje stavki s isteklim rokovima,
            a zatim planiranje aktivnosti koje uskoro istječu.
        </div>

        <div class="footer">
            Ovaj izvještaj je automatski generiran iz sustava <strong>ZNR LIDER</strong>.<br>
            Ako podaci odstupaju od očekivanih, provjerite evidencije i statuse unutar aplikacije.
        </div>

    </div>
</div>
</body>
</html>