<x-filament-panels::page>
@php
    $formatNumber = fn ($value) => $value !== null ? number_format((float) $value, 2, ',', '.') : '-';
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

    .kpi-filters {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        padding: 16px;
        border-bottom: 1px solid #d1d5db;
    }

    .dark .kpi-filters {
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

    .kpi-select {
        width: 100%;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        background: #fff;
        color: #111827;
        padding: 10px 12px;
        font-size: 14px;
        font-weight: 600;
    }

    .dark .kpi-select {
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
        border-bottom: 1px solid #d1d5db;
        background: #f9fafb;
    }

    .dark .kpi-section-title {
        background: rgba(255,255,255,.03);
        border-bottom-color: rgba(255,255,255,.10);
    }

    .kpi-cards {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        padding: 16px;
    }

    .kpi-card {
        border: 1px solid #d1d5db;
        border-radius: 14px;
        padding: 14px;
        background: #ffffff;
    }

    .dark .kpi-card {
        background: rgba(255,255,255,.02);
        border-color: rgba(255,255,255,.10);
    }

    .kpi-card.success {
        background: rgba(34, 197, 94, .08);
        border-color: rgba(34, 197, 94, .25);
    }

    .kpi-card.warning {
        background: rgba(245, 158, 11, .08);
        border-color: rgba(245, 158, 11, .25);
    }

    .kpi-card.danger {
        background: rgba(239, 68, 68, .08);
        border-color: rgba(239, 68, 68, .25);
    }

    .kpi-card-title {
        font-size: 14px;
        font-weight: 800;
        margin-bottom: 4px;
    }

    .kpi-card-unit {
        font-size: 12px;
        opacity: .75;
        margin-bottom: 8px;
    }

    .kpi-card-value {
        font-size: 30px;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 10px;
    }

    .kpi-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .kpi-mini {
        border: 1px solid #d1d5db;
        border-radius: 10px;
        padding: 8px 10px;
        background: rgba(255,255,255,.55);
        font-size: 12px;
    }

    .dark .kpi-mini {
        border-color: rgba(255,255,255,.08);
        background: rgba(0,0,0,.18);
    }

    .kpi-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        margin-top: 8px;
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

    @media (max-width: 1200px) {
        .kpi-cards {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .kpi-filters,
        .kpi-cards {
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

        <div class="kpi-filters">
            <div>
                <label class="kpi-label">Mjesec</label>
                <select wire:model.live="month" class="kpi-select">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="kpi-label">Godina</label>
                <select wire:model.live="year" class="kpi-select">
                    @for ($y = now()->year - 5; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>

        @forelse ($groups as $category => $cards)
            <div class="kpi-section-title">{{ $category }}</div>

            <div class="kpi-cards">
                @foreach ($cards as $card)
                    @php
                        $statusText = match($card['status']) {
                            'success' => 'U cilju',
                            'warning' => 'Upozorenje',
                            'danger' => 'Izvan cilja',
                            default => 'Bez cilja',
                        };
                    @endphp

                    <div class="kpi-card {{ $card['status'] }}">
                        <div class="kpi-card-title">{{ $card['name'] }}</div>
                        <div class="kpi-card-unit">{{ $card['unit'] ?: 'bez jedinice' }}</div>
                        <div class="kpi-card-value">{{ $card['formatted_value'] }}</div>

                        <div class="kpi-meta">
                            <div class="kpi-mini">
                                <strong>Cilj:</strong><br>{{ $card['formatted_target'] }}
                            </div>
                            <div class="kpi-mini">
                                <strong>Preth. mj.:</strong><br>{{ $formatNumber($card['previous_value']) }}
                            </div>
                            <div class="kpi-mini">
                                <strong>Isti mj. lani:</strong><br>{{ $formatNumber($card['last_year_value']) }}
                            </div>
                            <div class="kpi-mini">
                                <strong>Trend:</strong><br>
                                Mj: {{ match($card['trend_month']) { 'up' => '▲', 'down' => '▼', 'same' => '•', default => '—' } }}
                                &nbsp; God: {{ match($card['trend_year']) { 'up' => '▲', 'down' => '▼', 'same' => '•', default => '—' } }}
                            </div>
                        </div>

                        <div class="kpi-badge {{ $card['status'] }}">
                            {{ $statusText }}
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div style="padding: 24px; text-align:center; color:#6b7280;">
                Nema KPI podataka za prikaz.
            </div>
        @endforelse
    </div>
</div>
</x-filament-panels::page>