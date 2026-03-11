<x-filament-panels::page>
    <style>
        .wmr-wrap {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .wmr-card {
            background: #0f172a;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 18px;
            overflow: hidden;
        }

        .wmr-header {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            font-size: 20px;
            font-weight: 800;
            color: #fff;
        }

        .wmr-filters {
            padding: 20px;
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 16px;
        }

        .wmr-filter label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .wmr-filter select {
            width: 100%;
            background: rgba(255,255,255,.03);
            color: #fff;
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 12px;
            padding: 12px 14px;
        }

        .wmr-filter select option {
            background: #0f172a;
            color: #ffffff;
        }

        .wmr-filter select option:hover,
        .wmr-filter select option:focus,
        .wmr-filter select option:checked {
            background: #1d4ed8;
            color: #ffffff;
        }

        .wmr-table-wrap {
            overflow-x: auto;
            overflow-y: auto;
            padding: 0 0 12px 0;
            max-height: 65vh;
            position: relative;
        }

        .wmr-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1400px;
        }

        .wmr-table th,
        .wmr-table td {
            border-bottom: 1px solid rgba(255,255,255,.08);
            padding: 10px 12px;
            font-size: 14px;
            color: #fff;
            background: #0f172a;
        }

        .wmr-table thead th {
            background: #16213a;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 8;
            box-shadow: 0 1px 0 rgba(255,255,255,.08);
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
        }

        .wmr-empty {
            padding: 28px;
            text-align: center;
            color: #9ca3af;
        }

        .wmr-badge-danger {
            display: inline-flex;
            align-items: center;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            background: rgba(239, 68, 68, .14);
            color: #fca5a5;
            margin-left: 8px;
        }

        .wmr-code sup {
            font-size: 0.75em;
            vertical-align: baseline;
            position: relative;
            top: -0.35em;
        }

        /* STICKY PRVI STUPCI */
        .wmr-sticky-rbr,
        .wmr-sticky-kb,
        .wmr-sticky-name {
            position: sticky;
            z-index: 4;
            background: #0f172a !important;
        }

        .wmr-table thead .wmr-sticky-rbr,
        .wmr-table thead .wmr-sticky-kb,
        .wmr-table thead .wmr-sticky-name {
            background: #16213a !important;
            z-index: 9;
        }

        .wmr-sticky-rbr {
            left: 0;
            width: 70px;
            min-width: 70px;
            max-width: 70px;
            box-shadow: 1px 0 0 rgba(255,255,255,.08);
        }

        .wmr-sticky-kb {
            left: 70px;
            width: 140px;
            min-width: 140px;
            max-width: 140px;
            box-shadow: 1px 0 0 rgba(255,255,255,.08);
        }

        .wmr-sticky-name {
            left: 210px;
            min-width: 280px;
            box-shadow: 1px 0 0 rgba(255,255,255,.08);
        }

        /* STICKY FOOTER */
        .wmr-table tfoot td {
            position: sticky;
            bottom: 0;
            z-index: 7;
            background: #16213a !important;
            font-weight: 800;
            border-top: 1px solid rgba(255,255,255,.10);
            box-shadow: 0 -1px 0 rgba(255,255,255,.08);
        }

        .wmr-table tfoot .wmr-sticky-rbr,
        .wmr-table tfoot .wmr-sticky-kb,
        .wmr-table tfoot .wmr-sticky-name {
            z-index: 10;
            background: #16213a !important;
        }

        @media (max-width: 900px) {
            .wmr-filters {
                grid-template-columns: 1fr;
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
                    <select id="selectedYear" wire:model.live="selectedYear">
                        @foreach ($this->getYearOptions() as $yearValue => $yearLabel)
                            <option value="{{ $yearValue }}">{{ $yearLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="wmr-filter">
                    <label for="selectedLocationId">Lokacija</label>
                    <select id="selectedLocationId" wire:model.live="selectedLocationId">
                        <option value="">Sve lokacije</option>
                        @foreach ($this->getLocationOptions() as $locationValue => $locationLabel)
                            <option value="{{ $locationValue }}">{{ $locationLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (count($this->rows))
                <div class="wmr-table-wrap">
                    <table class="wmr-table">
                        <thead>
                            <tr>
                                <th class="center wmr-sticky-rbr">R.br.</th>
                                <th class="left wmr-sticky-kb">K.B.</th>
                                <th class="left wmr-sticky-name">Naziv</th>

                                @foreach ($this->getMonthLabels() as $monthNo => $monthLabel)
                                    <th class="right">{{ $monthLabel }}</th>
                                @endforeach

                                <th class="right">{{ $selectedYear }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($this->rows as $index => $row)
                                <tr>
                                    <td class="center wmr-sticky-rbr">{{ $index + 1 }}</td>

                                    <td class="left wmr-sticky-kb">
                                        <span class="wmr-code">
                                            {!! \App\Support\WasteCodeFormatter::html($row['waste_code']) !!}
                                        </span>
                                    </td>

                                    <td class="left wmr-name wmr-sticky-name">
                                        {{ $row['name'] }}

                                        @if ($row['is_hazardous'])
                                            <span class="wmr-badge-danger">Opasan</span>
                                        @endif
                                    </td>

                                    @foreach ($row['months'] as $monthValue)
                                        <td class="right">
                                            {{ $monthValue > 0 ? $this->formatKg($monthValue) : '0,00' }}
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
                                <td class="left wmr-sticky-name">Ukupno po mjesecima</td>

                                @foreach ($this->totals['months'] as $monthValue)
                                    <td class="right">{{ $this->formatKg($monthValue) }}</td>
                                @endforeach

                                <td class="right">{{ $this->formatKg($this->totals['grand_total']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="wmr-empty">
                    Nema podataka za odabranu godinu i lokaciju.
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>