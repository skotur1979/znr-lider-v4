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

    .kpi-filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        padding: 16px;
        border-bottom: 1px solid #d1d5db;
    }

    .dark .kpi-filter-grid {
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
</style>

<div class="kpi-wrap">
    <div class="kpi-sheet">
        <div class="kpi-title-top">
            <div class="kpi-suptitle">KPI modul</div>
            <div class="kpi-title-main">Bulk unos ručnih KPI-eva</div>
        </div>

        <div class="kpi-filter-grid">
            <div>
                <label class="kpi-label">Mjesec</label>
                <select wire:model.live="month" class="kpi-input">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="kpi-label">Godina</label>
                <input type="number" wire:model.live="year" class="kpi-input">
            </div>
        </div>

        <div class="kpi-table-wrap">
            <table class="kpi-table">
                <thead>
                    <tr>
                        <th class="kpi-left" style="width: 260px;">KPI</th>
                        <th class="kpi-center" style="width: 120px;">Kategorija</th>
                        <th class="kpi-center" style="width: 90px;">Jedinica</th>
                        <th class="kpi-center" style="width: 140px;">Vrijednost</th>
                        <th class="kpi-center" style="width: 140px;">Izvor</th>
                        <th class="kpi-left">Komentar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $index => $row)
                        <tr>
                            <td class="kpi-left">{{ $row['name'] }}</td>
                            <td class="kpi-center">{{ $row['category'] }}</td>
                            <td class="kpi-center">{{ $row['unit'] ?: '-' }}</td>
                            <td>
                                <input type="number" step="0.0001" wire:model.defer="rows.{{ $index }}.value" class="kpi-input">
                            </td>
                            <td>
                                <input type="text" wire:model.defer="rows.{{ $index }}.source_label" class="kpi-input">
                            </td>
                            <td>
                                <input type="text" wire:model.defer="rows.{{ $index }}.note" class="kpi-input">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 24px; text-align:center; color:#6b7280;">
                                Nema ručnih KPI-eva za bulk unos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>