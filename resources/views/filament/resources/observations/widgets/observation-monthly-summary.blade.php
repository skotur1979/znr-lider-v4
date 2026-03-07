<x-filament-widgets::widget>
    <style>
        .obs-monthly-wrap{
            --obs-bg: #ffffff;
            --obs-border: #dbe2ea;
            --obs-head-bg: #f3f6fa;
            --obs-row-alt: #f8fafc;
            --obs-text: #0f172a;
            --obs-muted: #64748b;
            --obs-divider: #e5e7eb;

            --obs-total: #0f172a;
            --obs-nm: #0ea5e9;
            --obs-neg: #ef4444;
            --obs-pos: #10b981;
            --obs-not-started: #ef4444;
            --obs-progress: #f59e0b;
            --obs-complete: #22c55e;
        }

        .dark .obs-monthly-wrap{
            --obs-bg: #151922;
            --obs-border: rgba(255,255,255,.08);
            --obs-head-bg: rgba(255,255,255,.05);
            --obs-row-alt: rgba(255,255,255,.03);
            --obs-text: #ffffff;
            --obs-muted: #9ca3af;
            --obs-divider: rgba(255,255,255,.06);

            --obs-total: #ffffff;
            --obs-nm: #38bdf8;
            --obs-neg: #fb7185;
            --obs-pos: #34d399;
            --obs-not-started: #f87171;
            --obs-progress: #fbbf24;
            --obs-complete: #4ade80;
        }

        .obs-monthly-title{
            font-size: 24px;
            font-weight: 700;
            color: var(--obs-text);
            margin: 0 0 6px 0;
        }

        .obs-monthly-subtitle{
            font-size: 14px;
            color: var(--obs-muted);
            margin: 0;
        }

        .obs-monthly-card{
            overflow-x: auto;
            border: 1px solid var(--obs-border);
            border-radius: 16px;
            background: var(--obs-bg);
        }

        .obs-monthly-table{
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
            font-size: 14px;
        }

        .obs-monthly-table thead tr{
            background: var(--obs-head-bg);
        }

        .obs-monthly-table th{
            padding: 12px 14px;
            border-bottom: 1px solid var(--obs-border);
            font-weight: 700;
            white-space: nowrap;
        }

        .obs-monthly-table td{
            padding: 11px 14px;
            border-bottom: 1px solid var(--obs-divider);
        }

        .obs-monthly-table tbody tr:nth-child(even){
            background: var(--obs-row-alt);
        }

        .obs-monthly-table tfoot tr{
            background: var(--obs-head-bg);
        }

        .obs-left{
            text-align: left;
            color: var(--obs-text);
        }

        .obs-center{
            text-align: center;
        }

        .obs-month{
            color: var(--obs-text);
            font-weight: 600;
            font-size: 15px;
            white-space: nowrap;
        }

        .obs-num-total{
            color: var(--obs-total);
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .3px;
        }

        .obs-num-nm{
            color: var(--obs-nm);
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .3px;
        }

        .obs-num-neg{
            color: var(--obs-neg);
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .3px;
        }

        .obs-num-pos{
            color: var(--obs-pos);
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .3px;
        }

        .obs-num-not-started{
            color: var(--obs-not-started);
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .3px;
        }

        .obs-num-progress{
            color: var(--obs-progress);
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .3px;
        }

        .obs-num-complete{
            color: var(--obs-complete);
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .3px;
        }

        .obs-foot-label{
            color: var(--obs-text);
            font-weight: 800;
            font-size: 15px;
        }

        .obs-foot-num{
            font-weight: 800;
            font-size: 17px;
            letter-spacing: .3px;
        }
    </style>

    <x-filament::section>
        <div class="obs-monthly-wrap">
            <div style="margin-bottom: 18px;">
                <h2 class="obs-monthly-title">Pregled zapažanja po mjesecima</h2>
                <p class="obs-monthly-subtitle">Odabrana godina: {{ $year }}</p>
            </div>

            <div class="obs-monthly-card">
                <table class="obs-monthly-table">
                    <thead>
                        <tr>
                            <th class="obs-left">Mjesec</th>
                            <th class="obs-center" style="color: var(--obs-text);">Ukupno</th>
                            <th class="obs-center" style="color: var(--obs-nm);">NM</th>
                            <th class="obs-center" style="color: var(--obs-neg);">Negativna</th>
                            <th class="obs-center" style="color: var(--obs-pos);">Pozitivna</th>
                            <th class="obs-center" style="color: var(--obs-not-started);">Nije započeto</th>
                            <th class="obs-center" style="color: var(--obs-progress);">U tijeku</th>
                            <th class="obs-center" style="color: var(--obs-complete);">Završeno</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="obs-left obs-month">{{ $row['month'] }}</td>
                                <td class="obs-center obs-num-total">{{ $row['total'] }}</td>
                                <td class="obs-center obs-num-nm">{{ $row['nm_total'] }}</td>
                                <td class="obs-center obs-num-neg">{{ $row['negative_total'] }}</td>
                                <td class="obs-center obs-num-pos">{{ $row['positive_total'] }}</td>
                                <td class="obs-center obs-num-not-started">{{ $row['not_started_total'] }}</td>
                                <td class="obs-center obs-num-progress">{{ $row['in_progress_total'] }}</td>
                                <td class="obs-center obs-num-complete">{{ $row['complete_total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <td class="obs-left obs-foot-label">UKUPNO</td>
                            <td class="obs-center obs-num-total obs-foot-num">{{ collect($rows)->sum('total') }}</td>
                            <td class="obs-center obs-num-nm obs-foot-num">{{ collect($rows)->sum('nm_total') }}</td>
                            <td class="obs-center obs-num-neg obs-foot-num">{{ collect($rows)->sum('negative_total') }}</td>
                            <td class="obs-center obs-num-pos obs-foot-num">{{ collect($rows)->sum('positive_total') }}</td>
                            <td class="obs-center obs-num-not-started obs-foot-num">{{ collect($rows)->sum('not_started_total') }}</td>
                            <td class="obs-center obs-num-progress obs-foot-num">{{ collect($rows)->sum('in_progress_total') }}</td>
                            <td class="obs-center obs-num-complete obs-foot-num">{{ collect($rows)->sum('complete_total') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>