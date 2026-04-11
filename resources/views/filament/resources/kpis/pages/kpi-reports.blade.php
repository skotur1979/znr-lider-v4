<x-filament-panels::page>
<style>
    .kpi-wrap {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .kpi-sheet {
        background: #ffffff;
        color: #111827;
        border: 1px solid #d1d5db;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
    }

    .dark .kpi-sheet {
        background: #111827;
        color: #f9fafb;
        border-color: rgba(255,255,255,.10);
        box-shadow: 0 10px 28px rgba(0, 0, 0, .35);
    }

    .kpi-title-top {
        text-align: center;
        padding: 18px 20px 10px;
        border-bottom: 1px solid #d1d5db;
    }

    .dark .kpi-title-top {
        border-bottom-color: rgba(255,255,255,.10);
    }

    .kpi-suptitle {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .kpi-title-main {
        font-size: 28px;
        font-weight: 800;
        line-height: 1.15;
    }

    .kpi-filter-bar {
        padding: 16px;
        border-bottom: 1px solid #d1d5db;
    }

    .dark .kpi-filter-bar {
        border-bottom-color: rgba(255,255,255,.10);
    }

    .kpi-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        opacity: .75;
        margin-bottom: 6px;
    }

    .kpi-input {
        width: 260px;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        background: #fff;
        color: #111827;
        padding: 10px 12px;
        font-size: 14px;
        font-weight: 600;
    }

    .dark .kpi-input {
        background: #0f172a;
        color: #f8fafc;
        border-color: rgba(255,255,255,.10);
    }

    .kpi-section-title {
        text-align: center;
        font-size: 15px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 10px 16px;
        border-top: 1px solid #d1d5db;
        border-bottom: 1px solid #d1d5db;
        background: #f9fafb;
    }

    .dark .kpi-section-title {
        background: rgba(255,255,255,.03);
        border-color: rgba(255,255,255,.10);
    }

    .kpi-table-wrap {
        overflow-x: auto;
    }

    .kpi-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .kpi-table th,
    .kpi-table td {
        border: 1px solid #d1d5db;
        padding: 8px 10px;
        font-size: 13px;
        vertical-align: top;
    }

    .dark .kpi-table th,
    .dark .kpi-table td {
        border-color: rgba(255,255,255,.10);
    }

    .kpi-table thead th {
        background: #f3f4f6;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .02em;
        text-align: center;
    }

    .dark .kpi-table thead th {
        background: rgba(255,255,255,.04);
    }

    .kpi-left { text-align: left; }
    .kpi-center { text-align: center; }
    .kpi-right { text-align: right; font-variant-numeric: tabular-nums; }

    .cell-success {
        background: rgba(34, 197, 94, .10);
        color: #166534;
        font-weight: 700;
    }

    .cell-danger {
        background: rgba(239, 68, 68, .10);
        color: #991b1b;
        font-weight: 700;
    }

    .cell-warning {
        background: rgba(245, 158, 11, .10);
        color: #92400e;
        font-weight: 700;
    }

    .dark .cell-success { color: #86efac; }
    .dark .cell-danger { color: #fca5a5; }
    .dark .cell-warning { color: #fcd34d; }
</style>

<div class="kpi-wrap">
    <div class="kpi-sheet">
        <div class="kpi-title-top">
            <div class="kpi-suptitle">KPI modul</div>
            <div class="kpi-title-main">KPI Izvještaji</div>
        </div>

        <div class="kpi-filter-bar">
            <label class="kpi-label">Godina</label>
            <select wire:model.live="year" class="kpi-input">
                @for ($y = now()->year - 5; $y <= now()->year + 2; $y++)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>

        @forelse ($groups as $category => $rows)
            <div class="kpi-section-title">{{ $category }}</div>

            <div class="kpi-table-wrap">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th class="kpi-left" style="width: 260px;">KPI</th>
                            <th class="kpi-center" style="width: 90px;">Jedinica</th>
                            <th class="kpi-right" style="width: 90px;">Cilj</th>
                            @for ($m = 1; $m <= 12; $m++)
                                <th class="kpi-center" style="width: 72px;">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</th>
                            @endfor
                            <th class="kpi-right" style="width: 90px;">Prosjek</th>
                            <th class="kpi-right" style="width: 90px;">Ukupno</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="kpi-left">{{ $row['name'] }}</td>
                                <td class="kpi-center">{{ $row['unit'] ?: '-' }}</td>
                                <td class="kpi-right">{{ $row['formatted_target'] }}</td>

                                @for ($m = 1; $m <= 12; $m++)
                                    @php
                                        $value = $row['values'][$m];
                                        $status = $row['statuses'][$m] ?? 'neutral';

                                        $class = match($status) {
                                            'success' => 'cell-success',
                                            'warning' => 'cell-warning',
                                            'danger' => 'cell-danger',
                                            default => '',
                                        };
                                    @endphp

                                    <td class="kpi-center {{ $class }}">
                                        {{ $value !== null ? number_format($value, 2, ',', '.') : '-' }}
                                    </td>
                                @endfor

                                <td class="kpi-right">
                                    {{ $row['average'] !== null ? number_format($row['average'], 2, ',', '.') : '-' }}
                                </td>

                                <td class="kpi-right">
                                    {{ $row['total'] !== null ? number_format($row['total'], 2, ',', '.') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div style="padding: 24px; text-align:center; color:#6b7280;">
                Nema podataka za odabranu godinu.
            </div>
        @endforelse
    </div>
</div>
</x-filament-panels::page>