<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>ZNR LIDER - Upravljački HSE izvještaj</title>

    <style>
        @page { margin: 14px; }
        * { font-family: "DejaVu Sans", sans-serif; box-sizing: border-box; }
        body { margin: 0; color: #0f172a; font-size: 10px; line-height: 1.35; }
        .container { width: 100%; background: #ffffff; }

        .header {
            background: #111827;
            color: #ffffff;
            padding: 18px 24px 16px;
            border-radius: 12px;
            margin-bottom: 12px;
        }
        .brand {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 1.3px;
            color: #f59e0b;
            text-transform: uppercase;
        }
        .title {
            margin-top: 6px;
            font-size: 23px;
            line-height: 28px;
            font-weight: 900;
        }
        .meta { margin-top: 5px; font-size: 10px; color: #cbd5e1; }
        .intro { font-size: 11px; line-height: 16px; color: #334155; margin-bottom: 9px; }

        .smart-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 11px;
            padding: 10px 12px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .label {
            font-size: 9px;
            font-weight: 900;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 5px;
        }

        .status-box {
            border-radius: 13px;
            padding: 12px 14px;
            margin: 10px 0 11px;
            text-align: center;
            page-break-inside: avoid;
        }
        .status-critical { background: #dc2626; color: #ffffff; border: 2px solid #991b1b; }
        .status-warning { background: #f97316; color: #ffffff; border: 2px solid #c2410c; }
        .status-ok { background: #16a34a; color: #ffffff; border: 2px solid #15803d; }
        .status-small { font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        .status-main { font-size: 21px; font-weight: 900; margin-top: 4px; }
        .status-text { margin-top: 4px; font-size: 10.5px; font-weight: 700; }

        .stats {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin: 0 -6px 5px -6px;
        }
        .stat {
            border-radius: 10px;
            padding: 8px 9px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            page-break-inside: avoid;
            vertical-align: top;
        }
        .stat-red { background: #fff1f2; border-color: #fecdd3; }
        .stat-yellow { background: #fffbeb; border-color: #fde68a; }
        .stat-blue { background: #eff6ff; border-color: #bfdbfe; }
        .stat-green { background: #ecfdf5; border-color: #bbf7d0; }
        .stat-purple { background: #f5f3ff; border-color: #ddd6fe; }
        .stat-slate { background: #f8fafc; border-color: #cbd5e1; }
        .stat-label { font-size: 8px; font-weight: 900; color: #64748b; text-transform: uppercase; margin-bottom: 3px; }
        .stat-value { font-size: 20px; line-height: 22px; font-weight: 900; }
        .stat-note { margin-top: 2px; font-size: 8px; color: #64748b; font-weight: 700; }

        .red { color: #dc2626; }
        .yellow { color: #d97706; }
        .blue { color: #2563eb; }
        .green { color: #16a34a; }
        .purple { color: #7c3aed; }
        .slate { color: #334155; }

        .section-title {
            font-size: 14px;
            font-weight: 900;
            margin: 12px 0 6px;
            padding-bottom: 4px;
            border-bottom: 2px solid #f59e0b;
            page-break-after: avoid;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
        }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }
        table.data th {
            background: #f1f5f9;
            padding: 6px 8px;
            font-size: 9px;
            font-weight: 900;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        table.data td {
            padding: 5px 8px;
            font-size: 9.5px;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .center { text-align: center; }
        .right { text-align: right; }

        .badge {
            display: inline-block;
            min-width: 34px;
            border-radius: 999px;
            padding: 3px 7px;
            font-weight: 900;
            text-align: center;
        }
        .badge-red { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-yellow { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .badge-green { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }

        .management-box {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 10px;
            padding: 10px 12px;
            page-break-inside: avoid;
        }
        .management-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin: 0 -6px;
        }
        .management-cell {
            vertical-align: top;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 8px;
        }
        .management-title { font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: 900; }
        .management-value { margin-top: 4px; font-size: 12px; font-weight: 900; }

        .priority-high { color: #dc2626; font-weight: 900; }
        .priority-medium { color: #d97706; font-weight: 900; }
        .priority-low { color: #15803d; font-weight: 900; }

        .actions-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            padding: 9px 12px;
            page-break-inside: avoid;
        }
        .actions { margin: 0; padding-left: 16px; }
        .actions li { margin-bottom: 4px; font-size: 10px; line-height: 15px; }

        .conclusion {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 5px solid #2563eb;
            border-radius: 11px;
            padding: 10px 12px;
            color: #1e3a8a;
            font-size: 10px;
            line-height: 15px;
            page-break-inside: avoid;
        }
        .director-box {
            margin-top: 10px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 5px solid #f59e0b;
            border-radius: 11px;
            padding: 10px 12px;
            page-break-inside: avoid;
        }

        .page-break-before { page-break-before: always; }
        .avoid-break { page-break-inside: avoid; }
        .footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 8.5px;
            line-height: 13px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $access = $access ?? [];

    $canEmployees = (bool) ($access['employees'] ?? false);
    $canFires = (bool) ($access['fires'] ?? false);
    $canPpe = (bool) ($access['ppe_logs'] ?? false);
    $canObservations = (bool) ($access['observations'] ?? false);
    $canInspections = (bool) ($access['inspections'] ?? false);
    $canIncidents = (bool) ($access['incidents'] ?? false);
    $canWorkTasks = (bool) ($access['work_tasks'] ?? false);
    $canExpenses = (bool) ($access['expenses'] ?? false);
    $canBudgets = (bool) ($access['budgets'] ?? false);
    $canKpis =
    (bool) (
        $access['kpis']
        ?? false
    );

    $canOntoRecords =
        (bool) (
            $access['onto_records']
            ?? false
        );

    /*
    |--------------------------------------------------------------------------
    | KPI ZA MANAGEMENT REPORT
    |--------------------------------------------------------------------------
    |
    | U PDF prikazujemo samo KPI-je označene za dashboard,
    | a od njih prvenstveno ZNR i Okoliš.
    |
    */

        $managementKpiRows =
        collect(
            $managementKpis
            ?? []
        )
            ->flatten(1)
            ->filter(
                fn (array $row): bool =>
                    in_array(
                        $row['category']
                            ?? '',
                        [
                            'ZNR',
                            'Okoliš',
                        ],
                        true
                    )
            )
            ->values();

    /*
    |--------------------------------------------------------------------------
    | Sažetak KPI statusa
    |--------------------------------------------------------------------------
    |
    | U upravljačkom HSE izvještaju ne trebamo ponovno prikazivati
    | cijeli KPI izvještaj.
    |
    | Detaljni KPI-i već postoje u KPI modulu.
    | Ovdje prikazujemo samo stanje KPI pokazatelja.
    |
    */

    $kpiSuccessCount =
        $managementKpiRows
            ->filter(
                fn (array $row): bool =>
                    ($row['status'] ?? null)
                        === 'success'
            )
            ->count();

    $kpiWarningCount =
        $managementKpiRows
            ->filter(
                fn (array $row): bool =>
                    ($row['status'] ?? null)
                        === 'warning'
            )
            ->count();

    $kpiDangerCount =
        $managementKpiRows
            ->filter(
                fn (array $row): bool =>
                    ($row['status'] ?? null)
                        === 'danger'
            )
            ->count();

    $kpiUnratedCount =
        $managementKpiRows
            ->filter(
                fn (array $row): bool =>
                    ! in_array(
                        $row['status'] ?? 'neutral',
                        [
                            'success',
                            'warning',
                            'danger',
                        ],
                        true
                    )
            )
            ->count();

    $hasPeoplePage = $canEmployees || $canPpe;
    $hasTechnicalPage = count($rows ?? []) > 0 || $canFires || $canInspections || $canObservations || $canWorkTasks;
    $hasCriticalPage = count($rows ?? []) > 0 || $canInspections || $canObservations || $canWorkTasks;
    $hasFinancePage = $canBudgets || $canExpenses;

    $totalOpenActivities =
        ($canInspections ? ($openInspectionFindings ?? 0) : 0)
        + ($canObservations ? ($openObservations ?? 0) : 0)
        + ($canWorkTasks ? ($openWorkTasks ?? 0) : 0);

    $totalSafetyEvents = $canIncidents
        ? (($ltaCount ?? 0) + ($mtaCount ?? 0) + ($faaCount ?? 0))
        : 0;

    $totalWorkTasks = $canWorkTasks
        ? (($openWorkTasks ?? 0) + ($closedWorkTasks ?? 0))
        : 0;

    $workTaskClosureRate = $canWorkTasks
        ? ($totalWorkTasks > 0
            ? round((($closedWorkTasks ?? 0) / $totalWorkTasks) * 100)
            : 100)
        : null;

    $criticalRows = collect($rows ?? [])
        ->filter(fn ($row) => (($row['expired'] ?? 0) > 0))
        ->sortByDesc(fn ($row) => $row['expired'] ?? 0)
        ->values();

    $soonRows = collect($rows ?? [])
        ->filter(fn ($row) => (($row['soon'] ?? 0) > 0))
        ->sortByDesc(fn ($row) => $row['soon'] ?? 0)
        ->values();

    $criticalCategoryCount = $criticalRows->count();
    $topCritical = $criticalRows->first();

    $managementPriority = ($totalExpired ?? 0) > 0
        ? 'VISOK'
        : ((($totalSoon ?? 0) > 0 || $totalOpenActivities > 0) ? 'SREDNJI' : 'NIZAK');

    $managementPriorityClass = $managementPriority === 'VISOK'
        ? 'priority-high'
        : ($managementPriority === 'SREDNJI' ? 'priority-medium' : 'priority-low');

    $statusClass = ($totalExpired ?? 0) > 0
        ? 'status-critical'
        : (($totalSoon ?? 0) > 0 ? 'status-warning' : 'status-ok');

    $statusIcon = ($totalExpired ?? 0) > 0
        ? '!'
        : (($totalSoon ?? 0) > 0 ? '!' : 'OK');

    $statusMessage = ($totalExpired ?? 0) > 0
        ? 'Sustav zahtijeva hitnu reakciju zbog isteklih stavki u dostupnim modulima.'
        : (($totalSoon ?? 0) > 0
            ? 'Sustav zahtijeva planiranje aktivnosti koje uskoro istječu u dostupnim modulima.'
            : 'Trenutno nema kritičnih isteklih stavki u dostupnim modulima.');
@endphp

<div class="container">

    {{-- 1. STRANICA - UPRAVLJAČKI PREGLED --}}
    <div class="header">
        <div class="brand">ZNR LIDER</div>
        <div class="title">
            Upravljački HSE izvještaj
        </div>

        <div class="meta">
        Mjesečni podaci: {{ rtrim($reportPeriodLabel, '.') }}
        · Trenutni status i rokovi: stanje na {{ $reportDate }}
        · Trendovi i financije: {{ $currentYear }}. godina
    </div>
    </div>

    <div class="intro">
        Izvještaj kombinira
        <strong>mjesečne HSE pokazatelje</strong> za
        {{ rtrim($reportPeriodLabel, '.') }},
        <strong>trenutačno stanje sustava i rokova</strong>
        na datum {{ $reportDate }}
        te <strong>godišnje trendove i financijske pokazatelje</strong>
        za {{ $currentYear }}. godinu.
        Prikazuju se samo podaci iz modula kojima korisnik ima pravo pristupa.
    </div>

    <div class="smart-box">
        <div class="label">Sažetak za rukovodstvo</div>
        Sustav je trenutno u statusu <strong>{{ mb_strtoupper($systemStatus) }}</strong>.
        Evidentirano je
        <strong>{{ $totalExpired }}</strong>
        isteklih stavki, dok broj stavki koje istječu unutar 30 dana iznosi
        <strong>{{ $totalSoon }}</strong>
        u modulima kojima korisnik ima pristup.
        @if ($canInspections || $canObservations || $canWorkTasks)
            Aktivno je <strong>{{ $totalOpenActivities }}</strong> otvorenih aktivnosti.
        @endif
        @if ($canIncidents)
            Trenutačni niz bez LTA ozljede iznosi <strong>{{ $daysWithoutLta }} dana</strong>.
        @endif
    </div>

    <div class="status-box {{ $statusClass }}">
        <div class="status-small">Status dostupnog dijela sustava</div>
        <div class="status-main">{{ $statusIcon }} {{ mb_strtoupper($systemStatus) }}</div>
        <div class="status-text">{{ $statusMessage }}</div>
    </div>

    <table class="stats">
        <tr>
            <td class="stat stat-red" style="width:33.33%;">
                <div class="stat-label">Isteklo</div>
                <div class="stat-value red">{{ $totalExpired }}</div>
                <div class="stat-note">zahtijeva postupanje</div>
            </td>
            <td class="stat stat-yellow" style="width:33.33%;">
                <div class="stat-label">U 30 dana</div>
                <div class="stat-value yellow">{{ $totalSoon }}</div>
                <div class="stat-note">potrebno planirati</div>
            </td>
            <td class="stat stat-slate" style="width:33.33%;">
                <div class="stat-label">Praćena područja</div>
                <div class="stat-value slate">{{ count($rows) }}</div>
                <div class="stat-note">dostupne kategorije rokova</div>
            </td>
        </tr>
    </table>

    @if ($canIncidents || $canEmployees || $canInspections || $canObservations || $canWorkTasks)
        <table class="stats">
            <tr>
                @if ($canIncidents)
                    <td class="stat stat-green">
                        <div class="stat-label">Bez LTA</div>
                        <div class="stat-value green">{{ $daysWithoutLta }}</div>
                        <div class="stat-note">dana</div>
                    </td>
                @endif

                @if ($canEmployees)
                    <td class="stat stat-blue">
                        <div class="stat-label">Zaposlenici</div>
                        <div class="stat-value blue">{{ $employeesTotal }}</div>
                        <div class="stat-note">evidentirani zapisi</div>
                    </td>
                @endif

                @if ($canInspections || $canObservations || $canWorkTasks)
                    <td class="stat stat-slate">
                        <div class="stat-label">Otvorene aktivnosti</div>
                        <div class="stat-value slate">{{ $totalOpenActivities }}</div>
                        <div class="stat-note">samo dostupni moduli</div>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    <div class="section-title">Upravljačka procjena</div>
    <table class="management-grid">
        <tr>
            <td class="management-cell">
                <div class="management-title">Prioritet Uprave</div>
                <div class="management-value {{ $managementPriorityClass }}">{{ $managementPriority }}</div>
            </td>
            <td class="management-cell">
                <div class="management-title">Kritična područja</div>
                <div class="management-value red">{{ $criticalCategoryCount }}</div>
            </td>
            <td class="management-cell">
                <div class="management-title">Otvorene aktivnosti</div>
                <div class="management-value blue">{{ $totalOpenActivities }}</div>
            </td>
        </tr>
    </table>

    @if ($canIncidents || $canInspections || $canObservations || $canWorkTasks)
        <div class="section-title">
            Ključni sigurnosni pokazatelji - {{ $currentYear }}. godina
        </div>
        <table class="data">
            <thead>
            <tr>
                <th>Pokazatelj</th>
                <th class="center" style="width:90px;">Vrijednost</th>
                <th>Upravljačko značenje</th>
            </tr>
            </thead>
            <tbody>
            @if ($canIncidents)
                <tr><td><strong>LTA ozljede</strong></td><td class="center"><span class="badge badge-red">{{ $ltaCount }}</span></td><td>Ozljede na radu s izgubljenim radnim danima.</td></tr>
                <tr><td><strong>MTA događaji</strong></td><td class="center"><span class="badge badge-yellow">{{ $mtaCount }}</span></td><td>Događaji koji su zahtijevali medicinsku obradu izvan tvrtke.</td></tr>
                <tr><td><strong>FAA događaji</strong></td><td class="center"><span class="badge badge-blue">{{ $faaCount }}</span></td><td>Događaji riješeni pružanjem prve pomoći unutar tvrtke.</td></tr>
            @endif
            @if ($canInspections)
                <tr><td><strong>Otvoreni nalazi nadzora</strong></td><td class="center"><span class="badge badge-red">{{ $openInspectionFindings }}</span></td><td>Nalazi nadzora koji još zahtijevaju postupanje.</td></tr>
            @endif
            @if ($canObservations)
                <tr><td><strong>Otvorena zapažanja</strong></td><td class="center"><span class="badge badge-yellow">{{ $openObservations }}</span></td><td>Korektivne ili preventivne aktivnosti koje nisu završene.</td></tr>
            @endif
            @if ($canWorkTasks)
                <tr><td><strong>Otvoreni radni zadaci</strong></td><td class="center"><span class="badge badge-yellow">{{ $openWorkTasks }}</span></td><td>Dodijeljene aktivnosti koje još nisu završene.</td></tr>
            @endif
            </tbody>
        </table>
    @endif
        {{-- ============================================================= --}}
        {{-- NOVA STRANICA - MJESEČNI HSE PREGLED                         --}}
        {{-- ============================================================= --}}

        @if (
            $canIncidents
            || $canObservations
            || $canInspections
            || $canKpis
            || $canOntoRecords
        )
            <div class="page-break-before">

                <div class="header">
                    <div class="brand">
                        ZNR LIDER
                    </div>

                    <div class="title">
                        Mjesečni HSE pregled
                    </div>

                    <div class="meta">
                        Mjesečno razdoblje:
                        {{ rtrim($reportPeriodLabel, '.') }}
                        · Podaci na ovoj stranici odnose se isključivo na odabrani mjesec
                    </div>
                </div>


                {{-- ===================================================== --}}
                {{-- SIGURNOSNI DOGAĐAJI                                  --}}
                {{-- ===================================================== --}}

                @if ($canIncidents)

                    <div class="section-title">
                        Sigurnosni događaji
                    </div>

                    <table class="stats">
                        <tr>

                            <td class="stat stat-red">
                                <div class="stat-label">
                                    LTA
                                </div>

                                <div class="stat-value red">
                                    {{ $periodLta }}
                                </div>

                                <div class="stat-note">
                                    ozljede s izgubljenim danima
                                </div>
                            </td>


                            <td class="stat stat-yellow">
                                <div class="stat-label">
                                    MTA
                                </div>

                                <div class="stat-value yellow">
                                    {{ $periodMta }}
                                </div>

                                <div class="stat-note">
                                    medicinska obrada
                                </div>
                            </td>


                            <td class="stat stat-blue">
                                <div class="stat-label">
                                    FAA
                                </div>

                                <div class="stat-value blue">
                                    {{ $periodFaa }}
                                </div>

                                <div class="stat-note">
                                    prva pomoć
                                </div>
                            </td>


                            <td class="stat stat-red">
                                <div class="stat-label">
                                    Izgubljeni dani
                                </div>

                                <div class="stat-value red">
                                    {{ $periodLostDays }}
                                </div>

                                <div class="stat-note">
                                    zbog LTA ozljeda
                                </div>
                            </td>

                        </tr>
                    </table>


                    @if (
                        $canKpis
                        && (
                            $periodAfr !== null
                            || $periodAsr !== null
                        )
                    )

                        <table class="stats">
                            <tr>

                                <td class="stat stat-blue">
                                    <div class="stat-label">
                                        AFR
                                    </div>

                                    <div class="stat-value blue">
                                        {{
                                            $periodAfr !== null
                                                ? number_format(
                                                    (float) $periodAfr,
                                                    2,
                                                    ',',
                                                    '.'
                                                )
                                                : '—'
                                        }}
                                    </div>

                                    <div class="stat-note">
                                        stopa učestalosti ozljeda
                                    </div>
                                </td>


                                <td class="stat stat-blue">
                                    <div class="stat-label">
                                        ASR
                                    </div>

                                    <div class="stat-value blue">
                                        {{
                                            $periodAsr !== null
                                                ? number_format(
                                                    (float) $periodAsr,
                                                    2,
                                                    ',',
                                                    '.'
                                                )
                                                : '—'
                                        }}
                                    </div>

                                    <div class="stat-note">
                                        stopa težine ozljeda
                                    </div>
                                </td>

                            </tr>
                        </table>

                    @endif

                @endif


                {{-- ===================================================== --}}
                {{-- ZAPAŽANJA                                            --}}
                {{-- ===================================================== --}}

                @if ($canObservations)

                    <div class="section-title">
                        Zapažanja
                    </div>

                    <table class="stats">
                        <tr>

                            <td class="stat stat-blue">
                                <div class="stat-label">
                                    Near Miss
                                </div>

                                <div class="stat-value blue">
                                    {{ $periodNearMiss }}
                                </div>

                                <div class="stat-note">
                                    skoro nezgode
                                </div>
                            </td>


                            <td class="stat stat-red">
                                <div class="stat-label">
                                    Negativna
                                </div>

                                <div class="stat-value red">
                                    {{ $periodNegative }}
                                </div>

                                <div class="stat-note">
                                    negativna zapažanja
                                </div>
                            </td>


                            <td class="stat stat-green">
                                <div class="stat-label">
                                    Pozitivna
                                </div>

                                <div class="stat-value green">
                                    {{ $periodPositive }}
                                </div>

                                <div class="stat-note">
                                    pozitivna zapažanja
                                </div>
                            </td>


                            <td class="stat stat-slate">
                                <div class="stat-label">
                                    Ukupno
                                </div>

                                <div class="stat-value slate">
                                    {{ $periodObservationTotal }}
                                </div>

                                <div class="stat-note">
                                    prijavljena zapažanja
                                </div>
                            </td>

                        </tr>
                    </table>

                @endif


                {{-- ===================================================== --}}
                {{-- NADZORI                                              --}}
                {{-- ===================================================== --}}

                @if ($canInspections)

                    <div class="section-title">
                        Nadzori i nalazi
                    </div>

                    <table class="stats">
                        <tr>

                            <td class="stat stat-blue">
                                <div class="stat-label">
                                    Provedeni nadzori
                                </div>

                                <div class="stat-value blue">
                                    {{ $periodInspections }}
                                </div>

                                <div class="stat-note">
                                    u odabranom mjesecu
                                </div>
                            </td>


                            <td class="stat stat-slate">
                                <div class="stat-label">
                                    Ukupno nalaza
                                </div>

                                <div class="stat-value slate">
                                    {{ $periodFindingsTotal }}
                                </div>

                                <div class="stat-note">
                                    nalazi nadzora
                                </div>
                            </td>


                            <td class="stat stat-red">
                                <div class="stat-label">
                                    Otvoreni nalazi
                                </div>

                                <div class="stat-value red">
                                    {{ $periodFindingsOpen }}
                                </div>

                                <div class="stat-note">
                                    zahtijevaju postupanje
                                </div>
                            </td>


                            <td class="stat stat-red">
                                <div class="stat-label">
                                    Zakašnjeli nalazi
                                </div>

                                <div class="stat-value red">
                                    {{ $periodFindingsOverdue }}
                                </div>

                                <div class="stat-note">
                                    rok provedbe prekoračen
                                </div>
                            </td>

                        </tr>
                    </table>

                @endif


                {{-- ===================================================== --}}
                {{-- KOREKTIVNE RADNJE                                    --}}
                {{-- ===================================================== --}}

                @if ($canInspections)

                    <div class="section-title">
                        Korektivne radnje
                    </div>

                    <table class="stats">
                        <tr>

                            <td class="stat stat-red">
                                <div class="stat-label">
                                    Otvorene
                                </div>

                                <div class="stat-value red">
                                    {{ $periodCorrectiveOpen }}
                                </div>

                                <div class="stat-note">
                                    korektivne radnje
                                </div>
                            </td>


                            <td class="stat stat-yellow">
                                <div class="stat-label">
                                    U tijeku
                                </div>

                                <div class="stat-value yellow">
                                    {{ $periodCorrectiveInProgress }}
                                </div>

                                <div class="stat-note">
                                    aktivna provedba
                                </div>
                            </td>


                            <td class="stat stat-green">
                                <div class="stat-label">
                                    Zatvorene
                                </div>

                                <div class="stat-value green">
                                    {{ $periodCorrectiveClosed }}
                                </div>

                                <div class="stat-note">
                                    provedene radnje
                                </div>
                            </td>


                            <td class="stat stat-yellow">
                                <div class="stat-label">
                                    Dani kašnjenja
                                </div>

                                <div class="stat-value yellow">
                                    {{ $periodCorrectiveDelayDays }}
                                </div>

                                <div class="stat-note">
                                    ukupno zakašnjenje
                                </div>
                            </td>

                        </tr>
                    </table>

                @endif


                {{-- ===================================================== --}}
                {{-- OTPAD / OKOLIŠ                                       --}}
                {{-- ===================================================== --}}

                @if ($canOntoRecords)

                    <div class="section-title">
                        Okoliš i otpad
                    </div>

                    <table class="stats">
                        <tr>

                            <td class="stat stat-green">
                                <div class="stat-label">
                                    Neopasni otpad
                                </div>

                                <div class="stat-value green">
                                    {{
                                        number_format(
                                            (float) $nonHazardousWasteKg,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </div>

                                <div class="stat-note">
                                    kg
                                </div>
                            </td>


                            <td class="stat stat-red">
                                <div class="stat-label">
                                    Opasni otpad
                                </div>

                                <div class="stat-value red">
                                    {{
                                        number_format(
                                            (float) $hazardousWasteKg,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </div>

                                <div class="stat-note">
                                    kg
                                </div>
                            </td>


                            <td class="stat stat-blue">
                                <div class="stat-label">
                                    Komunalni otpad
                                </div>

                                <div class="stat-value blue">
                                    {{
                                        number_format(
                                            (float) $municipalWasteKg,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </div>

                                <div class="stat-note">
                                    kg
                                </div>
                            </td>

                        </tr>
                    </table>

                @endif


                {{-- ===================================================== --}}
                {{-- KPI SAŽETAK                                          --}}
                {{-- ===================================================== --}}

                @if (
                    $canKpis
                    && $managementKpiRows->isNotEmpty()
                )

                    <div class="section-title">
                        KPI status
                    </div>

                    <table class="stats">
                        <tr>

                            <td class="stat stat-green">
                                <div class="stat-label">
                                    U cilju
                                </div>

                                <div class="stat-value green">
                                    {{ $kpiSuccessCount }}
                                </div>

                                <div class="stat-note">
                                    KPI pokazatelja
                                </div>
                            </td>


                            <td class="stat stat-yellow">
                                <div class="stat-label">
                                    Upozorenje
                                </div>

                                <div class="stat-value yellow">
                                    {{ $kpiWarningCount }}
                                </div>

                                <div class="stat-note">
                                    zahtijeva praćenje
                                </div>
                            </td>


                            <td class="stat stat-red">
                                <div class="stat-label">
                                    Izvan cilja
                                </div>

                                <div class="stat-value red">
                                    {{ $kpiDangerCount }}
                                </div>

                                <div class="stat-note">
                                    zahtijeva postupanje
                                </div>
                            </td>


                            <td class="stat stat-blue">
                                <div class="stat-label">
                                    Bez ocjene
                                </div>

                                <div class="stat-value blue">
                                    {{ $kpiUnratedCount }}
                                </div>

                                <div class="stat-note">
                                    nema rezultata ili cilja
                                </div>
                            </td>

                        </tr>
                    </table>

                @endif


                {{-- ===================================================== --}}
                {{-- SAŽETAK                                              --}}
                {{-- ===================================================== --}}

                <div
                    class="conclusion"
                    style="margin-top:12px;"
                >
                    Mjesečni HSE pregled za
                        <strong>{{ rtrim($reportPeriodLabel, '.') }}.</strong>

                    @if ($canIncidents)
                        Evidentirano je
                        <strong>
                            {{
                                $periodLta
                                + $periodMta
                                + $periodFaa
                            }}
                        </strong>
                        sigurnosnih događaja i
                        <strong>
                            {{ $periodLostDays }}
                        </strong>
                        izgubljenih radnih dana.
                    @endif

                    @if ($canObservations)
                        Evidentirano je
                        <strong>
                            {{ $periodObservationTotal }}
                        </strong>
                        zapažanja.
                    @endif

                    @if ($canInspections)
                        Provedeno je
                        <strong>
                            {{ $periodInspections }}
                        </strong>
                        nadzora.
                    @endif
                </div>

            </div>
        @endif

    {{-- 2. STRANICA - LJUDI I OSPOSOBLJENOST --}}
    @if ($hasPeoplePage)
        <div class="page-break-before">
            <div class="header">
                <div class="brand">ZNR LIDER</div>
                <div class="title">Ljudi i osposobljenost</div>
                <div class="meta">Podaci iz modula Zaposlenici i/ili Upisnik OZO, prema pravima korisnika</div>
            </div>

            @if ($canEmployees)
                <div class="section-title">Zaposlenici i zdravstvena sposobnost</div>
                <table class="stats">
                    <tr>
                        <td class="stat stat-blue"><div class="stat-label">Zaposlenici</div><div class="stat-value blue">{{ $employeesTotal }}</div><div class="stat-note">evidentirani zaposlenici</div></td>
                        <td class="stat stat-red"><div class="stat-label">Liječnički isteklo</div><div class="stat-value red">{{ $medicalExpired }}</div><div class="stat-note">potrebno hitno postupanje</div></td>
                        <td class="stat stat-yellow"><div class="stat-label">Liječnički u 30 dana</div><div class="stat-value yellow">{{ $medicalSoon }}</div><div class="stat-note">potrebno planirati pregled</div></td>
                    </tr>
                </table>

                <div class="section-title">Pregled osposobljavanja</div>
                <table class="data">
                    <thead><tr><th>Područje</th><th class="center">Uredno / evidentirano</th><th class="center">Uskoro</th><th class="center">Isteklo / prekoračeno</th></tr></thead>
                    <tbody>
                    <tr><td><strong>Zaštita na radu</strong></td><td class="center"><span class="badge badge-green">{{ $znrRecorded }}</span></td><td class="center"><span class="badge badge-yellow">{{ $znrSoon }}</span></td><td class="center"><span class="badge badge-red">{{ $znrExpired }}</span></td></tr>
                    <tr><td><strong>Liječnički pregledi</strong></td><td class="center"><span class="badge badge-green">{{ $medicalValid }}</span></td><td class="center"><span class="badge badge-yellow">{{ $medicalSoon }}</span></td><td class="center"><span class="badge badge-red">{{ $medicalExpired }}</span></td></tr>
                    <tr><td><strong>Ostale edukacije / certifikati</strong></td><td class="center">—</td><td class="center"><span class="badge badge-yellow">{{ $otherTrainingSoon }}</span></td><td class="center"><span class="badge badge-red">{{ $otherTrainingExpired }}</span></td></tr>
                    </tbody>
                </table>
            @endif

            @if ($canPpe)
                <div class="section-title">Osobna zaštitna oprema</div>
                <table class="stats">
                    <tr>
                        <td class="stat stat-blue"><div class="stat-label">Aktivno izdano</div><div class="stat-value blue">{{ $ppeActive }}</div><div class="stat-note">OZO bez evidentiranog vraćanja</div></td>
                        <td class="stat stat-green"><div class="stat-label">Važeće</div><div class="stat-value green">{{ $ppeValid }}</div><div class="stat-note">uredan rok uporabe</div></td>
                        <td class="stat stat-yellow"><div class="stat-label">U 30 dana</div><div class="stat-value yellow">{{ $ppeSoon }}</div><div class="stat-note">približava se rok uporabe</div></td>
                        <td class="stat stat-red"><div class="stat-label">Isteklo</div><div class="stat-value red">{{ $ppeExpired }}</div><div class="stat-note">aktivna OZO s isteklim rokom</div></td>
                    </tr>
                </table>
            @endif

            @if ($canEmployees)
                <div class="conclusion" style="margin-top:12px;">
                    Sustav raspolaže s ukupno <strong>{{ $employeesTotal }}</strong> evidentiranih zaposlenika.
                    Trenutačno je
                    <strong>{{ $medicalExpired }}</strong>
                    liječničkih pregleda isteklo, dok broj pregleda koji istječu unutar sljedećih 30 dana iznosi
                    <strong>{{ $medicalSoon }}</strong>.
                    Broj zaposlenika s prekoračenim rokom za osposobljavanje iznosi
                    <strong>{{ $znrExpired }}</strong>.
                </div>
            @endif
        </div>
    @endif

    {{-- 3. STRANICA - ROKOVI I TEHNIČKA SIGURNOST --}}
    @if ($hasTechnicalPage)
        <div class="page-break-before">
            <div class="header">
                <div class="brand">ZNR LIDER</div>
                <div class="title">Rokovi i tehnička sigurnost</div>
                <div class="meta">Status obveza iz modula kojima korisnik ima pristup</div>
            </div>

            @if (count($rows) > 0)
                <div class="section-title">Pregled rokova i valjanosti</div>
                <table class="data">
                    <thead><tr><th>Područje / evidencija</th><th class="center" style="width:90px;">Isteklo</th><th class="center" style="width:90px;">U 30 dana</th><th class="center" style="width:100px;">Prioritet</th></tr></thead>
                    <tbody>
                    @foreach ($rows as $row)
                        @php
                            $expired = $row['expired'] ?? 0;
                            $soon = $row['soon'] ?? 0;
                            $rowPriority = $expired > 0 ? 'VISOK' : ($soon > 0 ? 'SREDNJI' : 'UREDNO');
                        @endphp
                        <tr>
                            <td>
                                <strong>
                                    {{
                                        ($row['label'] ?? '') === 'Vatrogasni aparati'
                                            ? 'Vatrogasni aparati – rokovi pregleda/servisa'
                                            : $row['label']
                                    }}
                                </strong>
                            </td>
                            <td class="center"><span class="badge badge-red">{{ $expired }}</span></td>
                            <td class="center"><span class="badge badge-yellow">{{ $soon }}</span></td>
                            <td class="center">
                                @if ($rowPriority === 'VISOK') <span class="priority-high">VISOK</span>
                                @elseif ($rowPriority === 'SREDNJI') <span class="priority-medium">SREDNJI</span>
                                @else <span class="priority-low">UREDNO</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif

            @if ($canFires)
                <div class="section-title">Vatrogasni aparati</div>
                <table class="stats">
                    <tr>
                        <td class="stat stat-blue"><div class="stat-label">Ukupno aparata</div><div class="stat-value blue">{{ $firesTotal }}</div><div class="stat-note">evidentirani aparati</div></td>
                        <td class="stat stat-green"><div class="stat-label">Uredno</div><div class="stat-value green">{{ $firesValid }}</div><div class="stat-note">izvan upozorenja 30 dana</div></td>
                        <td class="stat stat-yellow"><div class="stat-label">U 30 dana</div><div class="stat-value yellow">{{ $firesSoon }}</div><div class="stat-note">potrebno planirati</div></td>
                        <td class="stat stat-red"><div class="stat-label">Isteklo</div><div class="stat-value red">{{ $firesExpired }}</div><div class="stat-note">potreban pregled / servis</div></td>
                    </tr>
                </table>
            @endif

            @if ($canInspections || $canObservations || $canWorkTasks)
                <div class="section-title">Operativne korektivne aktivnosti</div>
                <table class="data">
                    <thead><tr><th>Područje</th><th class="center">Otvoreno</th><th>Upravljačko značenje</th></tr></thead>
                    <tbody>
                    @if ($canInspections)
                        <tr><td><strong>Nalazi nadzora</strong></td><td class="center"><span class="badge badge-red">{{ $openInspectionFindings }}</span></td><td>Nalazi nadzora koji još nisu zatvoreni ili riješeni.</td></tr>
                    @endif
                    @if ($canObservations)
                        <tr><td><strong>Zapažanja</strong></td><td class="center"><span class="badge badge-yellow">{{ $openObservations }}</span></td><td>Otvorene preventivne i korektivne aktivnosti.</td></tr>
                    @endif
                    @if ($canWorkTasks)
                        <tr><td><strong>Radni zadaci</strong></td><td class="center"><span class="badge badge-yellow">{{ $openWorkTasks }}</span></td><td>Dodijeljeni zadaci koji čekaju završetak.</td></tr>
                    @endif
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    {{-- 5. STRANICA - TREND OZLJEDA --}}
    @if ($canIncidents)
        <div class="page-break-before">
            <div class="header">
                <div class="brand">ZNR LIDER</div>
                <div class="title">Trend sigurnosti i ozljeda</div>
                <div class="meta">Usporedba {{ $currentYear }}. i {{ $previousYear }}. godine</div>
            </div>

            <table class="stats"><tr>
                <td class="stat stat-red"><div class="stat-label">LTA ukupno</div><div class="stat-value red">{{ $ltaCount }}</div><div class="stat-note">ozljede s izgubljenim danima</div></td>
                <td class="stat stat-yellow"><div class="stat-label">MTA ukupno</div><div class="stat-value yellow">{{ $mtaCount }}</div><div class="stat-note">medicinska obrada</div></td>
                <td class="stat stat-blue"><div class="stat-label">FAA ukupno</div><div class="stat-value blue">{{ $faaCount }}</div><div class="stat-note">prva pomoć</div></td>
            </tr></table>

            <table class="stats"><tr>
                <td class="stat stat-green"><div class="stat-label">Bez LTA</div><div class="stat-value green">{{ $daysWithoutLta }}</div><div class="stat-note">dana</div></td>
                <td class="stat stat-purple"><div class="stat-label">Rekord bez LTA</div><div class="stat-value purple">{{ $ltaRecordDays }}</div><div class="stat-note">dana</div></td>
                <td class="stat stat-slate"><div class="stat-label">Razlika godina</div><div class="stat-value {{ $incidentDifference > 0 ? 'red' : ($incidentDifference < 0 ? 'green' : 'slate') }}">{{ $incidentDifference > 0 ? '+' : '' }}{{ $incidentDifference }}</div><div class="stat-note">događaja</div></td>
            </tr></table>

            <div class="section-title">Mjesečni trend sigurnosnih događaja</div>
            <table class="data">
                <thead><tr><th>Mjesec</th><th class="center">{{ $currentYear }}</th><th class="center">{{ $previousYear }}</th><th class="center">LTA</th><th class="center">MTA</th><th class="center">FAA</th></tr></thead>
                <tbody>
                @foreach ($incidentTrend as $row)
                    <tr>
                        <td><strong>{{ $row['month'] }}</strong></td>
                        <td class="center">{{ $row['current_total'] }}</td>
                        <td class="center">{{ $row['previous_total'] }}</td>
                        <td class="center"><span class="badge badge-red">{{ $row['lta'] }}</span></td>
                        <td class="center"><span class="badge badge-yellow">{{ $row['mta'] }}</span></td>
                        <td class="center"><span class="badge badge-blue">{{ $row['faa'] }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="conclusion" style="margin-top:12px;">
                Ukupan broj sigurnosnih događaja u
                <strong>{{ $currentYear }}.</strong>
                godini iznosi
                <strong>{{ $currentYearIncidents }}</strong>,
                dok je u
                <strong>{{ $previousYear }}.</strong>
                godini iznosio
                <strong>{{ $previousYearIncidents }}</strong>.

                @if ($incidentDifference > 0)
                    Broj evidentiranih događaja veći je za
                    <strong>{{ $incidentDifference }}</strong>.
                @elseif ($incidentDifference < 0)
                    Broj evidentiranih događaja manji je za
                    <strong>{{ abs($incidentDifference) }}</strong>.
                @else
                    Broj evidentiranih događaja jednak je prethodnoj godini.
                @endif
            </div>
        </div>
    @endif

    {{-- 6. STRANICA - TROŠKOVI I BUDŽET --}}
    @if ($hasFinancePage)
        <div class="page-break-before">
            <div class="header">
                <div class="brand">ZNR LIDER</div>
                <div class="title">Financijski pregled</div>
                <div class="meta">Podaci za {{ $currentYear }}. godinu prema pravima korisnika</div>
            </div>

            <table class="stats"><tr>
                @if ($canBudgets)
                    <td class="stat stat-blue"><div class="stat-label">Budžet</div><div class="stat-value blue">{{ number_format($budgetAmount, 2, ',', '.') }} €</div><div class="stat-note">planirana sredstva</div></td>
                @endif
                @if ($canExpenses)
                    <td class="stat stat-yellow"><div class="stat-label">Realizirani troškovi</div><div class="stat-value yellow">{{ number_format($expensesRealized, 2, ',', '.') }} €</div><div class="stat-note">evidentirani realizirani troškovi</div></td>
                @endif
                @if ($canBudgets && $canExpenses)
                    <td class="stat {{ $budgetRemaining >= 0 ? 'stat-green' : 'stat-red' }}"><div class="stat-label">Preostalo</div><div class="stat-value {{ $budgetRemaining >= 0 ? 'green' : 'red' }}">{{ number_format($budgetRemaining, 2, ',', '.') }} €</div><div class="stat-note">raspoloživa sredstva</div></td>
                @endif
            </tr></table>

            @if ($canBudgets && $canExpenses)
                <div class="section-title">Iskorištenost budžeta</div>
                <div class="management-box">
                    <table style="width:100%; border-collapse:collapse;">
                        <tr><td style="width:220px; padding:5px; font-weight:bold;">Iskorištenost budžeta</td><td style="padding:5px;"><strong class="{{ $budgetUsagePercent >= 100 ? 'red' : ($budgetUsagePercent >= 80 ? 'yellow' : 'green') }}" style="font-size:16px;">{{ $budgetUsagePercent }} %</strong></td></tr>
                        <tr><td style="padding:5px; font-weight:bold;">Status budžeta</td><td style="padding:5px;">
                            @if ($budgetAmount <= 0)
                                Nema evidentiranog budžeta za {{ $currentYear }}. godinu.
                            @elseif ($budgetRemaining < 0)
                                <span class="priority-high">PREKORAČENJE</span> — troškovi su veći od planiranog budžeta.
                            @elseif ($budgetUsagePercent >= 90)
                                <span class="priority-medium">VISOKA ISKORIŠTENOST</span> — potrebno pratiti planirane troškove do kraja godine.
                            @else
                                <span class="priority-low">UREDNO</span> — budžet je unutar planiranih vrijednosti.
                            @endif
                        </td></tr>
                    </table>
                </div>
            @endif

            @if ($canExpenses)
                <div class="section-title">Najveće kategorije troškova</div>
                <table class="data">
                    <thead><tr><th>Kategorija</th><th class="right">Iznos</th><th class="center" style="width:100px;">Broj stavki</th></tr></thead>
                    <tbody>
                    @forelse ($topExpenseCategories as $row)
                        <tr><td><strong>{{ $row['label'] }}</strong></td><td class="right">{{ number_format($row['total'], 2, ',', '.') }} €</td><td class="center">{{ $row['count'] }}</td></tr>
                    @empty
                        <tr><td colspan="3">Nema evidentiranih realiziranih troškova za {{ $currentYear }}. godinu.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            @endif

            <div class="conclusion" style="margin-top:12px;">
                @if ($canBudgets && $canExpenses)
                    Za {{ $currentYear }}. godinu planirani budžet iznosi <strong>{{ number_format($budgetAmount, 2, ',', '.') }} €</strong>,
                    a realizirani troškovi <strong>{{ number_format($expensesRealized, 2, ',', '.') }} €</strong>.
                    Trenutačno je raspoloživo <strong>{{ number_format($budgetRemaining, 2, ',', '.') }} €</strong>,
                    odnosno iskorišteno je <strong>{{ $budgetUsagePercent }} %</strong> ukupnog planiranog budžeta.
                @elseif ($canBudgets)
                    Za {{ $currentYear }}. godinu evidentirani budžet iznosi <strong>{{ number_format($budgetAmount, 2, ',', '.') }} €</strong>.
                    Podaci o troškovima nisu prikazani jer korisnik nema pravo pregleda modula Troškovi.
                @elseif ($canExpenses)
                    Realizirani troškovi za {{ $currentYear }}. godinu iznose <strong>{{ number_format($expensesRealized, 2, ',', '.') }} €</strong>.
                    Podaci o budžetu nisu prikazani jer korisnik nema pravo pregleda modula Budžet.
                @endif
            </div>
        </div>
    @endif

    {{-- ZAVRŠNA STRANICA - UPRAVLJAČKE AKTIVNOSTI --}}
    <div class="page-break-before">
        <div class="header">
            <div class="brand">ZNR LIDER</div>
            <div class="title">Upravljačke aktivnosti</div>
            <div class="meta">Prioriteti, preporuke i zaključak izvještaja</div>
        </div>

        @if ($canInspections || $canObservations || $canWorkTasks || $canIncidents)
            <div class="section-title">Operativno stanje</div>
            <table class="stats"><tr>
                @if ($canInspections)<td class="stat stat-red"><div class="stat-label">Otvoreni nalazi</div><div class="stat-value red">{{ $openInspectionFindings }}</div><div class="stat-note">nalazi nadzora</div></td>@endif
                @if ($canObservations)<td class="stat stat-yellow"><div class="stat-label">Otvorena zapažanja</div><div class="stat-value yellow">{{ $openObservations }}</div><div class="stat-note">potrebno zatvaranje</div></td>@endif
                @if ($canWorkTasks)<td class="stat stat-blue"><div class="stat-label">Otvoreni zadaci</div><div class="stat-value blue">{{ $openWorkTasks }}</div><div class="stat-note">aktivne obveze</div></td>@endif
                @if ($canIncidents)<td class="stat stat-slate"><div class="stat-label">Sigurnosni događaji</div><div class="stat-value slate">{{ $totalSafetyEvents }}</div><div class="stat-note">LTA + MTA + FAA</div></td>@endif
            </tr></table>

            @if ($canWorkTasks)
                <table class="stats"><tr>
                    <td class="stat stat-green"><div class="stat-label">Zatvoreni zadaci</div><div class="stat-value green">{{ $closedWorkTasks }}</div><div class="stat-note">završene aktivnosti</div></td>
                    <td class="stat stat-purple"><div class="stat-label">Stopa zatvaranja</div><div class="stat-value purple">{{ $workTaskClosureRate }} %</div><div class="stat-note">radni zadaci</div></td>
                </tr></table>
            @endif
        @endif

        <div class="section-title">Prioriteti za Upravu</div>
        <div class="management-box">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:200px; padding:5px; font-weight:bold;">1. Istekle obveze</td>
                    <td style="padding:5px;">
                        @if ($totalExpired > 0)
                            <span class="priority-high">HITNO</span> — riješiti {{ $totalExpired }} isteklih stavki u dostupnim modulima.
                        @else
                            <span class="priority-low">UREDNO</span> — nema isteklih stavki u dostupnim modulima.
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:5px; font-weight:bold;">
                        2. Rokovi u 30 dana
                    </td>

                    <td style="padding:5px;">
                        @if ($totalSoon > 0)
                            <span class="priority-medium">
                                PLANIRATI
                            </span>
                            — broj rokova koji istječu unutar 30 dana iznosi
                            <strong>{{ $totalSoon }}</strong>.
                        @else
                            <span class="priority-low">
                                UREDNO
                            </span>
                            — nema kritičnih rokova u sljedećih 30 dana.
                        @endif
                    </td>
                </tr>
                @if ($canInspections)
                <tr>
                    <td style="padding:5px; font-weight:bold;">
                        Nalazi nadzora
                    </td>

                    <td style="padding:5px;">
                        Otvoreni nalazi nadzora:
                        <strong>{{ $openInspectionFindings }}</strong>.
                        Potrebno je pratiti odgovornosti i rokove sanacije.
                    </td>
                </tr>
            @endif
                @if ($canEmployees)
                    <tr><td style="padding:5px; font-weight:bold;">Liječnički pregledi</td><td style="padding:5px;">{{ $medicalExpired }} pregleda je isteklo, a {{ $medicalSoon }} istječe unutar 30 dana.</td></tr>
                    <tr>
                        <td style="padding:5px; font-weight:bold;">
                            Osposobljavanje ZNR
                        </td>

                        <td style="padding:5px;">
                            Zaposlenici s prekoračenim rokom osposobljavanja:
                            <strong>{{ $znrExpired }}</strong>.
                            Zaposlenici kojima se rok približava:
                            <strong>{{ $znrSoon }}</strong>.
                        </td>
                    </tr>
                @endif
                @if ($canPpe)
                    <tr>
                        <td style="padding:5px; font-weight:bold;">
                            OZO
                        </td>

                        <td style="padding:5px;">
                            Aktivno izdane OZO stavke s isteklim rokom uporabe:
                            <strong>{{ $ppeExpired }}</strong>.
                            OZO stavke kojima rok istječe unutar 30 dana:
                            <strong>{{ $ppeSoon }}</strong>.
                        </td>
                    </tr>
                @endif
                @if ($canObservations)
                    <tr>
                        <td style="padding:5px; font-weight:bold;">
                            Zapažanja
                        </td>

                        <td style="padding:5px;">
                            Broj otvorenih zapažanja koja zahtijevaju praćenje iznosi
                            <strong>{{ $openObservations }}</strong>.
                        </td>
                    </tr>
                @endif
                @if ($canWorkTasks)
                    <tr><td style="padding:5px; font-weight:bold;">Radni zadaci</td><td style="padding:5px;">{{ $openWorkTasks }} otvorenih zadataka; stopa zatvaranja iznosi {{ $workTaskClosureRate }} %.</td></tr>
                @endif
                @if ($canIncidents)
                    <tr>
                        <td style="padding:5px; font-weight:bold;">
                            Sigurnosni događaji
                        </td>

                        <td style="padding:5px;">
                            Ukupan broj evidentiranih LTA/MTA/FAA događaja u dostupnom razdoblju sažetka iznosi
                            <strong>{{ $totalSafetyEvents }}</strong>.
                        </td>
                    </tr>
                @endif
                @if ($canBudgets && $canExpenses)
                    <tr><td style="padding:5px; font-weight:bold;">Budžet</td><td style="padding:5px;">Iskorištenost godišnjeg budžeta iznosi <strong>{{ $budgetUsagePercent }} %</strong>.</td></tr>
                @elseif ($canBudgets)
                    <tr><td style="padding:5px; font-weight:bold;">Budžet</td><td style="padding:5px;">Evidentirani godišnji budžet iznosi <strong>{{ number_format($budgetAmount, 2, ',', '.') }} €</strong>.</td></tr>
                @elseif ($canExpenses)
                    <tr><td style="padding:5px; font-weight:bold;">Troškovi</td><td style="padding:5px;">Realizirani troškovi iznose <strong>{{ number_format($expensesRealized, 2, ',', '.') }} €</strong>.</td></tr>
                @endif
            </table>
        </div>

        @php
            /*
            |--------------------------------------------------------------------------
            | Sažete preporuke za Upravu
            |--------------------------------------------------------------------------
            |
            | Management report ne ponavlja sve pojedinačne sistemske preporuke.
            | Prikazujemo samo najvažnije upravljačke aktivnosti.
            |
            */

            $managementActions = [];

            if ($totalExpired > 0) {
                $text =
                    'Prioritetno riješiti '
                    . $totalExpired
                    . ' isteklih obveza u dostupnim modulima.';

                if ($topCritical) {
                    $text .=
                        ' Najizraženije područje je '
                        . $topCritical['label']
                        . ' (broj isteklih stavki: '
                        . $topCritical['expired']
                        . ').';
                }

                $managementActions[] =
                    $text;
            }

            if ($totalSoon > 0) {
                $managementActions[] =
                    'Planirati aktivnosti za stavke koje istječu unutar sljedećih 30 dana (ukupno: '
                    . $totalSoon
                    . ').';
            }

            if (
                $canEmployees
                && (
                    $medicalExpired > 0
                    || $znrExpired > 0
                )
            ) {
                $parts = [];

                if ($medicalExpired > 0) {
                    $parts[] =
                        'istekli liječnički pregledi: '
                        . $medicalExpired;
                }

                if ($znrExpired > 0) {
                    $parts[] =
                        'prekoračeni rokovi ZNR osposobljavanja: '
                        . $znrExpired;
                }

                $managementActions[] =
                    'Prioritetno riješiti obveze zaposlenika — '
                    . implode(
                        '; ',
                        $parts
                    )
                    . '.';
            }

            if (
                $canPpe
                && $ppeExpired > 0
            ) {
                $managementActions[] =
                    'Pregledati i riješiti '
                    . $ppeExpired
                    . ' aktivnih OZO stavki s isteklim evidentiranim rokom uporabe.';
            }

           if (
                $canInspections
                && $openInspectionFindings > 0
            ) {
                $managementActions[] =
                    'Pregledati odgovornosti i rokove te zatvoriti otvorene nalaze nadzora (ukupno: '
                    . $openInspectionFindings
                    . ').';
            }

            if (
                $canObservations
                && $openObservations > 0
            ) {
                $managementActions[] =
                    'Pratiti provedbu i zatvaranje '
                    . $openObservations
                    . ' otvorenih zapažanja.';
            }

            if (
                $canWorkTasks
                && $openWorkTasks > 0
            ) {
                $managementActions[] =
                    'Pratiti provedbu '
                    . $openWorkTasks
                    . ' otvorenih radnih zadataka; trenutačna stopa zatvaranja iznosi '
                    . $workTaskClosureRate
                    . ' %.';
            }

            /*
            * Ako je sve uredno, ipak želimo jednu jasnu poruku.
            */
            if (
                empty(
                    $managementActions
                )
            ) {
                $managementActions[] =
                    'Nisu utvrđene kritične otvorene aktivnosti koje zahtijevaju posebno postupanje Uprave.';
            }
        @endphp


        <div class="section-title">
            Preporučene akcije
        </div>

        <div class="actions-box">
            <ol class="actions">

                @foreach (
                    array_slice(
                        $managementActions,
                        0,
                        7
                    )
                    as $action
                )
                    <li>
                        {{ $action }}
                    </li>
                @endforeach

            </ol>
        </div>

        <div class="section-title">Zaključak</div>
        <div class="conclusion">
            {{ $summary }}
            <br><br>
            Preporuke i zaključak ovog izvještaja temelje se isključivo na podacima iz modula kojima trenutačni korisnik ima pravo pregleda.
        </div>

    </div>

</div>
</body>
</html>
