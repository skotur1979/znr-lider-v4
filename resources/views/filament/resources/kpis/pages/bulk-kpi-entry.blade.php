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

        .kpi-subtitle {
            margin-top: 6px;
            font-size: 13px;
            color: #6b7280;
        }

        .dark .kpi-subtitle {
            color: #9ca3af;
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
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
            opacity: .75;
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
            min-width: 820px;
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

        .kpi-left {
            text-align: left;
        }

        .kpi-center {
            text-align: center;
        }

        .kpi-empty {
            padding: 24px;
            text-align: center;
            color: #6b7280;
        }

        .dark .kpi-empty {
            color: #9ca3af;
        }

        @media (max-width: 750px) {
            .kpi-filter-grid {
                grid-template-columns: 1fr;
            }

            .kpi-title-main {
                font-size: 22px;
            }

            .kpi-sheet {
                border-radius: 12px;
            }
        }
    </style>

    <div class="kpi-wrap">
        <div class="kpi-sheet">

            <div class="kpi-title-top">
                <div class="kpi-suptitle">
                    KPI modul
                </div>

                <div class="kpi-title-main">
                    Ručni unos KPI vrijednosti
                </div>

                <div class="kpi-subtitle">
                    Unesite ručne KPI vrijednosti za odabrani mjesec i godinu.
                    Nakon spremanja automatski će se ponovno izračunati povezani KPI pokazatelji poput AFR i ASR.
                </div>
            </div>

            <div class="kpi-filter-grid">
                <div>
                    <label class="kpi-label">
                        Mjesec
                    </label>

                    <select
                        wire:model.live="month"
                        class="kpi-input"
                    >
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">
                                {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="kpi-label">
                        Godina
                    </label>

                    <input
                        type="number"
                        wire:model.live="year"
                        class="kpi-input"
                        min="2000"
                        max="2100"
                    >
                </div>
            </div>

            <div class="kpi-table-wrap">
                <table class="kpi-table">
                    <thead>
                        <tr>
                            <th
                                class="kpi-left"
                                style="width: 260px;"
                            >
                                KPI
                            </th>

                            <th
                                class="kpi-center"
                                style="width: 120px;"
                            >
                                Kategorija
                            </th>

                            <th
                                class="kpi-center"
                                style="width: 100px;"
                            >
                                Jedinica
                            </th>

                            <th
                                class="kpi-center"
                                style="width: 150px;"
                            >
                                Vrijednost
                            </th>

                            <th class="kpi-left">
                                Komentar
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($rows as $index => $row)
                            <tr>
                                <td class="kpi-left">
                                    {{ $row['name'] }}
                                </td>

                                <td class="kpi-center">
                                    {{ $row['category'] }}
                                </td>

                                <td class="kpi-center">
                                    {{ $row['unit'] ?: '-' }}
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        step="0.0001"
                                        wire:model.defer="rows.{{ $index }}.value"
                                        class="kpi-input"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        wire:model.defer="rows.{{ $index }}.note"
                                        class="kpi-input"
                                        placeholder="Opcionalni komentar"
                                    >
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="kpi-empty"
                                >
                                    Nema ručnih KPI-eva za unos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-filament-panels::page>