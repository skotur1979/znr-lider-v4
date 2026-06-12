<x-filament-panels::page>
@php
    $chartData = collect($chartRows ?? [])
        ->filter(fn ($row) => $row['current_value'] !== null || $row['compare_value'] !== null)
        ->values();

    $barMax = $chartData->flatMap(function ($row) {
        return array_filter([
            $row['current_value'],
            $row['compare_value'],
        ], fn ($v) => $v !== null);
    })->max();

    $barMax = ($barMax && $barMax > 0) ? $barMax : 1;

    $hasCompare = filled($compareYear) && ($viewMode === 'year' || filled($compareMonth));

    $categories = collect($availableKpis ?? [])
        ->pluck('category')
        ->filter()
        ->unique()
        ->values();
@endphp

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

    .kpi-filter-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        padding: 16px;
        border-bottom: 1px solid #d1d5db;
    }

    .dark .kpi-filter-grid {
        border-bottom-color: rgba(255,255,255,.10);
    }

    .kpi-chart-filter-wrap {
        padding: 16px;
        border-top: 1px solid #d1d5db;
        border-bottom: 1px solid #d1d5db;
        background: rgba(255,255,255,.02);
    }

    .dark .kpi-chart-filter-wrap {
        border-color: rgba(255,255,255,.10);
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
        width: 100%;
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
        vertical-align: middle;
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

    .kpi-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .kpi-badge.success {
        background: rgba(34, 197, 94, .12);
        color: #15803d;
    }

    .kpi-badge.warning {
        background: rgba(245, 158, 11, .12);
        color: #b45309;
    }

    .kpi-badge.danger {
        background: rgba(239, 68, 68, .12);
        color: #b91c1c;
    }

    .kpi-badge.neutral {
        background: rgba(107, 114, 128, .12);
        color: #4b5563;
    }

    .dark .kpi-badge.success { color: #86efac; }
    .dark .kpi-badge.warning { color: #fcd34d; }
    .dark .kpi-badge.danger { color: #fca5a5; }
    .dark .kpi-badge.neutral { color: #d1d5db; }

    .kpi-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 14px;
    }

    .kpi-toolbar-btn {
        appearance: none;
        border: 1px solid rgba(255,255,255,.10);
        background: #0f172a;
        color: #f8fafc;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: .15s ease;
    }

    .kpi-toolbar-btn:hover {
        transform: translateY(-1px);
        border-color: rgba(96, 165, 250, .45);
    }

    .kpi-checkbox-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .kpi-checkbox-card {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 12px;
        padding: 10px 12px;
        background: #0f172a;
        cursor: pointer;
        transition: all .15s ease;
    }

    .kpi-checkbox-card:hover {
        border-color: rgba(96, 165, 250, .45);
        box-shadow: 0 0 0 1px rgba(96, 165, 250, .10);
    }

    .kpi-checkbox-input {
        width: 16px;
        height: 16px;
        accent-color: #2563eb;
        flex: 0 0 auto;
    }

    .kpi-checkbox-text {
        font-size: 13px;
        font-weight: 600;
        line-height: 1.25;
    }

    .kpi-chart-wrap {
        padding: 16px;
    }

    .kpi-chart-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .kpi-chart-title {
        font-size: 15px;
        font-weight: 800;
    }

    .kpi-chart-subtitle {
        font-size: 13px;
        opacity: .75;
        margin-top: 4px;
    }

    .kpi-legend {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }

    .kpi-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 700;
    }

    .kpi-legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        display: inline-block;
    }

    .kpi-legend-dot.current {
        background: linear-gradient(90deg, #3b82f6, #06b6d4);
    }

    .kpi-legend-dot.compare {
        background: linear-gradient(90deg, #22c55e, #84cc16);
    }

    .kpi-bars {
        display: grid;
        gap: 16px;
    }

    .kpi-bar-row {
        display: grid;
        grid-template-columns: 260px 1fr 110px 110px;
        gap: 12px;
        align-items: center;
    }

    .kpi-bar-label {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.25;
    }

    .kpi-bar-stack {
        display: grid;
        gap: 7px;
    }

    .kpi-bar-track {
        width: 100%;
        height: 16px;
        border-radius: 999px;
        background: rgba(148, 163, 184, .16);
        overflow: hidden;
        position: relative;
    }

    .kpi-bar-fill-current {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #3b82f6, #06b6d4);
        box-shadow: 0 0 10px rgba(59, 130, 246, .25);
    }

    .kpi-bar-fill-compare {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #22c55e, #84cc16);
        box-shadow: 0 0 10px rgba(34, 197, 94, .20);
    }

    .kpi-bar-value {
        text-align: right;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .kpi-bar-muted {
        opacity: .8;
    }

    .kpi-target-btn {
        margin-top: 6px;
        padding: 4px 8px;
        border: none;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        background: rgba(59,130,246,.12);
        color: #2563eb;
    }

    .dark .kpi-target-btn {
        background: rgba(59,130,246,.18);
        color: #93c5fd;
    }

    .kpi-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .55);
        z-index: 9998;
    }

    .kpi-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: min(560px, calc(100vw - 32px));
        background: #ffffff;
        color: #111827;
        border-radius: 18px;
        border: 1px solid #d1d5db;
        box-shadow: 0 20px 60px rgba(0,0,0,.28);
        z-index: 9999;
        overflow: hidden;
    }

    .dark .kpi-modal {
        background: #111827;
        color: #f9fafb;
        border-color: rgba(255,255,255,.10);
    }

    .kpi-modal-head {
        padding: 18px 20px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 18px;
        font-weight: 800;
    }

    .dark .kpi-modal-head {
        border-bottom-color: rgba(255,255,255,.10);
    }

    .kpi-modal-body {
        padding: 20px;
        display: grid;
        gap: 14px;
    }

    .kpi-modal-actions {
        padding: 0 20px 20px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 1150px) {
        .kpi-filter-grid {
            grid-template-columns: 1fr 1fr;
        }

        .kpi-bar-row {
            grid-template-columns: 1fr;
        }

        .kpi-bar-value {
            text-align: left;
        }

        .kpi-checkbox-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .kpi-filter-grid {
            grid-template-columns: 1fr;
        }

        .kpi-checkbox-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="kpi-wrap">
    <div class="kpi-sheet">
        <div class="kpi-title-top">
            <div class="kpi-suptitle">KPI modul</div>
            <div class="kpi-title-main">KPI Dashboard</div>
        </div>

        <div class="kpi-filter-grid">
            <div>
                <label class="kpi-label">Način pregleda</label>
                <select wire:model.live="viewMode" class="kpi-input">
                    <option value="month">Po mjesecu</option>
                    <option value="year">Cijela godina</option>
                </select>
            </div>

            <div>
                <label class="kpi-label">Godina</label>
                <select wire:model.live="year" class="kpi-input">
                    @for ($y = now()->year - 5; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            @if($viewMode === 'month')
                <div>
                    <label class="kpi-label">Mjesec</label>
                    <select wire:model.live="month" class="kpi-input">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="kpi-label">Usporedni mjesec</label>
                    <select wire:model.live="compareMonth" class="kpi-input">
                        <option value="">— bez usporedbe —</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="kpi-label">Usporedna godina</label>
                    <select wire:model.live="compareYear" class="kpi-input">
                        <option value="">— bez usporedbe —</option>
                        @for ($y = now()->year - 5; $y <= now()->year + 2; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            @else
                <div>
                    <label class="kpi-label">Usporedna godina</label>
                    <select wire:model.live="compareYear" class="kpi-input">
                        <option value="">— bez usporedbe —</option>
                        @for ($y = now()->year - 5; $y <= now()->year + 2; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div></div>
            @endif
        </div>

        @forelse ($groups as $category => $rows)
            <div class="kpi-section-title">{{ $category }}</div>

            <div class="kpi-table-wrap">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th class="kpi-left" style="width: 240px;">KPI</th>
                            <th class="kpi-center" style="width: 90px;">Jedinica</th>
                            <th class="kpi-right" style="width: 120px;">Odabrani period</th>
                            <th class="kpi-right" style="width: 120px;">Usporedni period</th>
                            <th class="kpi-right" style="width: 90px;">Razlika</th>
                            <th class="kpi-right" style="width: 110px;">Cilj</th>
                            <th class="kpi-center" style="width: 120px;">Status</th>
                            <th class="kpi-center" style="width: 120px;">Akcija</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $statusText = match($row['status']) {
                                    'success' => 'U cilju',
                                    'warning' => 'Upozorenje',
                                    'danger' => 'Izvan cilja',
                                    default => 'Bez cilja',
                                };

                                $statusClass = match($row['status']) {
                                    'success' => 'success',
                                    'warning' => 'warning',
                                    'danger' => 'danger',
                                    default => 'neutral',
                                };
                            @endphp
                            <tr>
                                <td class="kpi-left">{{ $row['name'] }}</td>
                                <td class="kpi-center">{{ $row['unit'] ?: '-' }}</td>
                                <td class="kpi-right">{{ $row['formatted_current'] }}</td>
                                <td class="kpi-right">{{ $row['formatted_compare'] }}</td>
                                <td class="kpi-right">{{ $row['delta'] !== null ? number_format($row['delta'], 2, ',', '.') : '-' }}</td>
                                <td class="kpi-right">{{ $row['formatted_target'] }}</td>
                                <td class="kpi-center">
                                    <span class="kpi-badge {{ $statusClass }}">{{ $statusText }}</span>
                                </td>
                                <td class="kpi-center">
                                @if(!auth()->user()?->isSuperAdmin())
                                    <button
                                        type="button"
                                        wire:click="openTargetModal({{ $row['id'] }})"
                                        class="kpi-target-btn"
                                    >
                                        Cilj
                                    </button>
                                @else
                                    -
                                @endif
                            </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div style="padding: 24px; text-align:center; color:#6b7280;">
                Nema KPI podataka za prikaz.
            </div>
        @endforelse

        <div class="kpi-chart-filter-wrap">
            <label class="kpi-label">KPI-evi za graf</label>

            <div class="kpi-toolbar">
                <button type="button" wire:click="selectAllKpis" class="kpi-toolbar-btn">Odaberi sve</button>
                <button type="button" wire:click="clearSelectedKpis" class="kpi-toolbar-btn">Makni sve</button>

                @foreach($categories as $category)
                    <button type="button" wire:click="selectCategory('{{ $category }}')" class="kpi-toolbar-btn">
                        Samo {{ $category }}
                    </button>
                @endforeach
            </div>

            <div class="kpi-checkbox-grid">
                @foreach($availableKpis as $item)
                    <label class="kpi-checkbox-card">
                        <input
                            type="checkbox"
                            value="{{ $item['id'] }}"
                            wire:model.live="selectedChartKpis"
                            class="kpi-checkbox-input"
                        >
                        <span class="kpi-checkbox-text">{{ $item['name'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="kpi-chart-wrap">
            <div class="kpi-chart-head">
                <div>
                    <div class="kpi-chart-title">Graf usporedbe KPI-eva</div>
                    <div class="kpi-chart-subtitle">
                        @if($viewMode === 'year')
                            Godišnji pregled odabranih KPI-eva
                        @else
                            Mjesečni pregled odabranih KPI-eva
                        @endif
                    </div>
                </div>

                <div class="kpi-legend">
                    <span class="kpi-legend-item">
                        <span class="kpi-legend-dot current"></span>
                        Odabrani period
                    </span>

                    @if($hasCompare)
                        <span class="kpi-legend-item">
                            <span class="kpi-legend-dot compare"></span>
                            Usporedni period
                        </span>
                    @endif
                </div>
            </div>

            <div class="kpi-bars">
                @forelse ($chartData as $row)
                    @php
                        $currentWidth = $row['current_value'] !== null
                            ? max((($row['current_value'] / $barMax) * 100), 2)
                            : 0;

                        $compareWidth = $row['compare_value'] !== null
                            ? max((($row['compare_value'] / $barMax) * 100), 2)
                            : 0;
                    @endphp

                    <div class="kpi-bar-row">
                        <div class="kpi-bar-label">{{ $row['name'] }}</div>

                        <div class="kpi-bar-stack">
                            <div class="kpi-bar-track">
                                <div class="kpi-bar-fill-current" style="width: {{ $currentWidth }}%;"></div>
                            </div>

                            @if($hasCompare)
                                <div class="kpi-bar-track">
                                    <div class="kpi-bar-fill-compare" style="width: {{ $compareWidth }}%;"></div>
                                </div>
                            @endif
                        </div>

                        <div class="kpi-bar-value">
                            {{ $row['formatted_current'] }}
                        </div>

                        <div class="kpi-bar-value kpi-bar-muted">
                            @if($hasCompare)
                                {{ $row['formatted_compare'] }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="color:#6b7280;">Nema podataka za graf.</div>
                @endforelse
            </div>
        </div>
    </div>

    @if($showTargetModal)
    <div class="kpi-modal-backdrop" wire:click="closeTargetModal"></div>

    <div class="kpi-modal">
        <div class="kpi-modal-head">
            Postavi cilj: {{ $targetKpiName }}
        </div>

        <form wire:submit="saveTargetOverride">
            <div class="kpi-modal-body">
                <div style="font-size:13px; line-height:1.5; opacity:.9; padding:10px 12px; border-radius:12px; background:rgba(59,130,246,.08); border:1px solid rgba(59,130,246,.16);">
                    @if($targetUsesOverride)
                        Za ovaj period već postoji <strong>poseban cilj organizacije</strong>.
                        Promjena vrijedi od <strong>{{ str_pad((string) $targetMonth, 2, '0', STR_PAD_LEFT) }}/{{ $targetYear }}</strong>.
                        <br>
                        Globalni cilj je <strong>{{ $globalTargetValue !== null ? number_format((float) $globalTargetValue, 2, ',', '.') : '-' }}</strong>,
                        a globalna tolerancija je <strong>{{ $globalWarningOffset !== null ? number_format((float) $globalWarningOffset, 2, ',', '.') : '-' }}</strong>.
                    @else
                        Postavit će se <strong>poseban cilj organizacije</strong> koji vrijedi od odabranog mjeseca i godine nadalje.
                        <br>
                        Globalni cilj je <strong>{{ $globalTargetValue !== null ? number_format((float) $globalTargetValue, 2, ',', '.') : '-' }}</strong>,
                        a globalna tolerancija je <strong>{{ $globalWarningOffset !== null ? number_format((float) $globalWarningOffset, 2, ',', '.') : '-' }}</strong>.
                    @endif
                </div>

                <div>
                    <label class="kpi-label">Cilj</label>
                    <input type="number" step="0.0001" wire:model="targetValue" class="kpi-input">
                    @error('targetValue')
                        <div style="color:#dc2626;font-size:12px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="kpi-label">Tolerancija upozorenja</label>
                    <input type="number" step="0.0001" wire:model="warningOffset" class="kpi-input">
                    @error('warningOffset')
                        <div style="color:#dc2626;font-size:12px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="kpi-label">Vrijedi od mjeseca</label>
                    <select wire:model="targetMonth" class="kpi-input">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                    @error('targetMonth')
                        <div style="color:#dc2626;font-size:12px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="kpi-label">Vrijedi od godine</label>
                    <select wire:model="targetYear" class="kpi-input">
                        @for ($y = now()->year - 5; $y <= now()->year + 2; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                    @error('targetYear')
                        <div style="color:#dc2626;font-size:12px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="kpi-modal-actions">
                @if($targetUsesOverride)
                    <x-filament::button type="button" color="danger" wire:click="resetTargetOverride">
                        Ukloni cilj za ovaj period
                    </x-filament::button>
                @endif

                <x-filament::button type="button" color="gray" wire:click="closeTargetModal">
                    Odustani
                </x-filament::button>

                <x-filament::button type="submit">
                    Spremi
                </x-filament::button>
            </div>
        </form>
    </div>
@endif
</div>
</x-filament-panels::page>