<x-filament-panels::page>
    <style>
        .wmr-wrap {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .wmr-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        }

        .wmr-header {
            padding: 18px 20px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            background: #ffffff;
        }

        .wmr-filters {
            padding: 20px;
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .wmr-filter label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            margin-bottom: 8px;
        }

        .wmr-filter select {
            width: 100%;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 14px;
            font-weight: 600;
        }

        .wmr-filter select option {
            background: #ffffff;
            color: #0f172a;
        }

        /*
        |--------------------------------------------------------------------------
        | DESKTOP TABLICA
        |--------------------------------------------------------------------------
        */

        .wmr-desktop {
            display: block;
        }

        .wmr-table-wrap {
            overflow-x: auto;
            overflow-y: auto;
            padding: 0 0 12px 0;
            max-height: 65vh;
            position: relative;
            background: #ffffff;
        }

        .wmr-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1400px;
        }

        .wmr-table th,
        .wmr-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 12px;
            font-size: 14px;
            color: #0f172a;
            background: #ffffff;
        }

        .wmr-table thead th {
            background: #f1f5f9;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 8;
            box-shadow: 0 1px 0 #e5e7eb;
        }

        .wmr-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .wmr-table .left {
            text-align: left;
        }

        .wmr-table .center {
            text-align: center;
        }

        .wmr-table .right {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .wmr-code {
            font-weight: 800;
            white-space: nowrap;
        }

        .wmr-name {
            min-width: 260px;
            font-weight: 700;
        }

        .wmr-badge-danger {
            display: inline-flex;
            align-items: center;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            background: #fee2e2;
            color: #991b1b;
            margin-left: 8px;
        }

        .wmr-code sup {
            font-size: 0.75em;
            vertical-align: baseline;
            position: relative;
            top: -0.35em;
        }

        .wmr-sticky-rbr,
        .wmr-sticky-kb,
        .wmr-sticky-name {
            position: sticky;
            z-index: 4;
            background: #ffffff !important;
        }

        .wmr-table tbody tr:nth-child(even) .wmr-sticky-rbr,
        .wmr-table tbody tr:nth-child(even) .wmr-sticky-kb,
        .wmr-table tbody tr:nth-child(even) .wmr-sticky-name {
            background: #f8fafc !important;
        }

        .wmr-table thead .wmr-sticky-rbr,
        .wmr-table thead .wmr-sticky-kb,
        .wmr-table thead .wmr-sticky-name {
            background: #f1f5f9 !important;
            z-index: 9;
        }

        .wmr-sticky-rbr {
            left: 0;
            width: 70px;
            min-width: 70px;
            max-width: 70px;
            box-shadow: 1px 0 0 #e5e7eb;
        }

        .wmr-sticky-kb {
            left: 70px;
            width: 140px;
            min-width: 140px;
            max-width: 140px;
            box-shadow: 1px 0 0 #e5e7eb;
        }

        .wmr-sticky-name {
            left: 210px;
            min-width: 280px;
            box-shadow: 1px 0 0 #e5e7eb;
        }

        .wmr-table tfoot td {
            position: sticky;
            bottom: 0;
            z-index: 7;
            background: #eaf1fb !important;
            color: #0f172a;
            font-weight: 800;
            border-top: 1px solid #cbd5e1;
            box-shadow: 0 -1px 0 #e5e7eb;
        }

        .wmr-table tfoot .wmr-sticky-rbr,
        .wmr-table tfoot .wmr-sticky-kb,
        .wmr-table tfoot .wmr-sticky-name {
            z-index: 10;
            background: #eaf1fb !important;
        }

        /*
        |--------------------------------------------------------------------------
        | MOBILNI PRIKAZ
        |--------------------------------------------------------------------------
        */

        .wmr-mobile {
            display: none;
        }

        .wmr-mobile-list {
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #f8fafc;
        }

        .wmr-mobile-item {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .wmr-mobile-item-header {
            padding: 14px 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .wmr-mobile-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .wmr-mobile-code {
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
        }

        .wmr-mobile-code sup {
            font-size: .7em;
            position: relative;
            top: -.3em;
        }

        .wmr-mobile-number {
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8;
        }

        .wmr-mobile-name {
            font-size: 15px;
            line-height: 1.4;
            font-weight: 700;
            color: #0f172a;
        }

        .wmr-mobile-badge {
            display: inline-flex;
            margin-top: 8px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            background: #fee2e2;
            color: #991b1b;
        }

        .wmr-mobile-months {
            padding: 5px 15px;
        }

        .wmr-mobile-month {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .wmr-mobile-month:last-child {
            border-bottom: none;
        }

        .wmr-mobile-month-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        .wmr-mobile-month-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .wmr-mobile-no-data {
            padding: 12px 15px;
            font-size: 13px;
            color: #94a3b8;
            text-align: center;
        }

        .wmr-mobile-total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 13px 15px;
            background: #eaf1fb;
            border-top: 1px solid #cbd5e1;
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .wmr-mobile-grand-total {
            margin: 2px 14px 16px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            border-radius: 14px;
            background: #eaf1fb;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            font-weight: 900;
        }

        .wmr-mobile-grand-total-label {
            font-size: 14px;
        }

        .wmr-mobile-grand-total-value {
            font-size: 18px;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .wmr-empty {
            padding: 28px;
            text-align: center;
            color: #64748b;
            background: #ffffff;
        }

        /*
        |--------------------------------------------------------------------------
        | DARK MODE
        |--------------------------------------------------------------------------
        */

        .dark .wmr-card {
            background: #0f172a;
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: none;
        }

        .dark .wmr-header {
            background: #0f172a;
            border-bottom: 1px solid rgba(255,255,255,.08);
            color: #fff;
        }

        .dark .wmr-filters {
            background: #0f172a;
            border-bottom: none;
        }

        .dark .wmr-filter label {
            color: #9ca3af;
        }

        .dark .wmr-filter select {
            background: rgba(255,255,255,.03);
            color: #fff;
            border: 1px solid rgba(255,255,255,.10);
        }

        .dark .wmr-filter select option {
            background: #0f172a;
            color: #ffffff;
        }

        .dark .wmr-table-wrap {
            background: #0f172a;
        }

        .dark .wmr-table th,
        .dark .wmr-table td {
            border-bottom: 1px solid rgba(255,255,255,.08);
            color: #fff;
            background: #0f172a;
        }

        .dark .wmr-table thead th {
            background: #16213a;
            color: #fff;
            box-shadow: 0 1px 0 rgba(255,255,255,.08);
        }

        .dark .wmr-table tbody tr:nth-child(even) td {
            background: #111827;
        }

        .dark .wmr-sticky-rbr,
        .dark .wmr-sticky-kb,
        .dark .wmr-sticky-name {
            background: #0f172a !important;
        }

        .dark .wmr-table tbody tr:nth-child(even) .wmr-sticky-rbr,
        .dark .wmr-table tbody tr:nth-child(even) .wmr-sticky-kb,
        .dark .wmr-table tbody tr:nth-child(even) .wmr-sticky-name {
            background: #111827 !important;
        }

        .dark .wmr-table thead .wmr-sticky-rbr,
        .dark .wmr-table thead .wmr-sticky-kb,
        .dark .wmr-table thead .wmr-sticky-name {
            background: #16213a !important;
        }

        .dark .wmr-sticky-rbr,
        .dark .wmr-sticky-kb,
        .dark .wmr-sticky-name {
            box-shadow: 1px 0 0 rgba(255,255,255,.08);
        }

        .dark .wmr-table tfoot td,
        .dark .wmr-table tfoot .wmr-sticky-rbr,
        .dark .wmr-table tfoot .wmr-sticky-kb,
        .dark .wmr-table tfoot .wmr-sticky-name {
            background: #16213a !important;
            color: #fff;
            border-top: 1px solid rgba(255,255,255,.10);
            box-shadow: 0 -1px 0 rgba(255,255,255,.08);
        }

        .dark .wmr-badge-danger {
            background: rgba(239, 68, 68, .14);
            color: #fca5a5;
        }

        .dark .wmr-empty {
            background: #0f172a;
            color: #9ca3af;
        }

        .dark .wmr-mobile-list {
            background: #09090b;
        }

        .dark .wmr-mobile-item {
            background: #0f172a;
            border-color: rgba(255,255,255,.10);
        }

        .dark .wmr-mobile-item-header {
            border-color: rgba(255,255,255,.08);
        }

        .dark .wmr-mobile-code,
        .dark .wmr-mobile-name,
        .dark .wmr-mobile-month-value {
            color: #ffffff;
        }

        .dark .wmr-mobile-number,
        .dark .wmr-mobile-month-label {
            color: #9ca3af;
        }

        .dark .wmr-mobile-month {
            border-color: rgba(255,255,255,.07);
        }

        .dark .wmr-mobile-badge {
            background: rgba(239,68,68,.14);
            color: #fca5a5;
        }

        .dark .wmr-mobile-total,
        .dark .wmr-mobile-grand-total {
            background: #16213a;
            border-color: rgba(255,255,255,.10);
            color: #ffffff;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {
            .wmr-filters {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .wmr-card {
                border-radius: 16px;
            }

            .wmr-header {
                padding: 16px;
                font-size: 18px;
            }

            .wmr-filters {
                padding: 16px;
                gap: 14px;
            }

            .wmr-desktop {
                display: none;
            }

            .wmr-mobile {
                display: block;
            }
        }
    </style>

    <div class="wmr-wrap">
        <div class="wmr-card">

            <div class="wmr-header">
                Mjesečni izvještaj otpada
            </div>

            <div class="wmr-filters">
                <div class="wmr-filter">
                    <label for="selectedYear">Godina</label>

                    <select
                        id="selectedYear"
                        wire:model.live="selectedYear"
                    >
                        @foreach ($this->getYearOptions() as $yearValue => $yearLabel)
                            <option value="{{ $yearValue }}">
                                {{ $yearLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="wmr-filter">
                    <label for="selectedLocationId">Lokacija</label>

                    <select
                        id="selectedLocationId"
                        wire:model.live="selectedLocationId"
                    >
                        <option value="">Sve lokacije</option>

                        @foreach ($this->getLocationOptions() as $locationValue => $locationLabel)
                            <option value="{{ $locationValue }}">
                                {{ $locationLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (count($this->rows))

                {{-- =========================================================
                    DESKTOP PRIKAZ
                ========================================================== --}}
                <div class="wmr-desktop">
                    <div class="wmr-table-wrap">
                        <table class="wmr-table">

                            <thead>
                                <tr>
                                    <th class="center wmr-sticky-rbr">
                                        R.br.
                                    </th>

                                    <th class="left wmr-sticky-kb">
                                        K.B.
                                    </th>

                                    <th class="left wmr-sticky-name">
                                        Naziv
                                    </th>

                                    @foreach ($this->getMonthLabels() as $monthNo => $monthLabel)
                                        <th class="right">
                                            {{ $monthLabel }}
                                        </th>
                                    @endforeach

                                    <th class="right">
                                        {{ $selectedYear }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($this->rows as $index => $row)
                                    <tr>
                                        <td class="center wmr-sticky-rbr">
                                            {{ $index + 1 }}
                                        </td>

                                        <td class="left wmr-sticky-kb">
                                            <span class="wmr-code">
                                                {!! \App\Support\WasteCodeFormatter::html($row['waste_code']) !!}
                                            </span>
                                        </td>

                                        <td class="left wmr-name wmr-sticky-name">
                                            {{ $row['name'] }}

                                            @if ($row['is_hazardous'])
                                                <span class="wmr-badge-danger">
                                                    Opasan
                                                </span>
                                            @endif
                                        </td>

                                        @foreach ($row['months'] as $monthValue)
                                            <td class="right">
                                                {{ $monthValue > 0
                                                    ? $this->formatKg($monthValue)
                                                    : '0,00' }}
                                            </td>
                                        @endforeach

                                        <td class="right">
                                            {{ $this->formatKg($row['total']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td class="center wmr-sticky-rbr"></td>

                                    <td class="left wmr-sticky-kb"></td>

                                    <td class="left wmr-sticky-name">
                                        Ukupno po mjesecima
                                    </td>

                                    @foreach ($this->totals['months'] as $monthValue)
                                        <td class="right">
                                            {{ $this->formatKg($monthValue) }}
                                        </td>
                                    @endforeach

                                    <td class="right">
                                        {{ $this->formatKg($this->totals['grand_total']) }}
                                    </td>
                                </tr>
                            </tfoot>

                        </table>
                    </div>
                </div>

                {{-- =========================================================
                    MOBILNI PRIKAZ
                ========================================================== --}}
                <div class="wmr-mobile">

                    <div class="wmr-mobile-list">

                        @foreach ($this->rows as $index => $row)

                            @php
                                $hasActiveMonth = collect($row['months'])
                                    ->contains(fn ($value) => (float) $value > 0);
                            @endphp

                            <div class="wmr-mobile-item">

                                <div class="wmr-mobile-item-header">

                                    <div class="wmr-mobile-top">

                                        <div class="wmr-mobile-code">
                                            {!! \App\Support\WasteCodeFormatter::html($row['waste_code']) !!}
                                        </div>

                                        <div class="wmr-mobile-number">
                                            #{{ $index + 1 }}
                                        </div>

                                    </div>

                                    <div class="wmr-mobile-name">
                                        {{ $row['name'] }}
                                    </div>

                                    @if ($row['is_hazardous'])
                                        <span class="wmr-mobile-badge">
                                            Opasan
                                        </span>
                                    @endif

                                </div>

                                @if ($hasActiveMonth)

                                    <div class="wmr-mobile-months">

                                        @foreach ($row['months'] as $monthIndex => $monthValue)

                                            @if ((float) $monthValue > 0)

                                                <div class="wmr-mobile-month">

                                                    <span class="wmr-mobile-month-label">
                                                        {{ $this->getMonthLabels()[$monthIndex + 1] ?? 'Mjesec ' . ($monthIndex + 1) }}
                                                    </span>

                                                    <span class="wmr-mobile-month-value">
                                                        {{ $this->formatKg($monthValue) }} kg
                                                    </span>

                                                </div>

                                            @endif

                                        @endforeach

                                    </div>

                                @else

                                    <div class="wmr-mobile-no-data">
                                        Nema evidentirane količine u {{ $selectedYear }}. godini.
                                    </div>

                                @endif

                                <div class="wmr-mobile-total">

                                    <span>
                                        Ukupno {{ $selectedYear }}.
                                    </span>

                                    <span>
                                        {{ $this->formatKg($row['total']) }} kg
                                    </span>

                                </div>

                            </div>

                        @endforeach

                    </div>

                    <div class="wmr-mobile-grand-total">

                        <span class="wmr-mobile-grand-total-label">
                            Ukupno sav otpad
                        </span>

                        <span class="wmr-mobile-grand-total-value">
                            {{ $this->formatKg($this->totals['grand_total']) }} kg
                        </span>

                    </div>

                </div>

            @else

                <div class="wmr-empty">
                    Nema podataka za odabranu godinu i lokaciju.
                </div>

            @endif

        </div>
    </div>
</x-filament-panels::page>