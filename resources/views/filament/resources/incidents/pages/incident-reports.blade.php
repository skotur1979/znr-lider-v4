<x-filament-panels::page>

    @php
        $summary = $report['summary'] ?? [];

        $months = $report['months'] ?? [];
        $types = $report['types'] ?? [];
        $employment = $report['employment'] ?? [];

        $topLocations = $report['top_locations'] ?? [];
        $topCauses = $report['top_causes'] ?? [];
        $topInjuryTypes = $report['top_injury_types'] ?? [];
        $topBodyParts = $report['top_body_parts'] ?? [];

        $locationsTable = $report['locations_table'] ?? [];
        $options = $report['options'] ?? [];

        $monthOptions = [
            '1' => 'Siječanj',
            '2' => 'Veljača',
            '3' => 'Ožujak',
            '4' => 'Travanj',
            '5' => 'Svibanj',
            '6' => 'Lipanj',
            '7' => 'Srpanj',
            '8' => 'Kolovoz',
            '9' => 'Rujan',
            '10' => 'Listopad',
            '11' => 'Studeni',
            '12' => 'Prosinac',
        ];

        $maxMonthTotal = max(
            1,
            collect($months)->max('total') ?? 0
        );

        $maxMonthLostDays = max(
            1,
            collect($months)->max('lost_days') ?? 0
        );

        $maxType = max(
            1,
            collect($types)->max('count') ?? 0
        );

        $maxEmployment = max(
            1,
            collect($employment)->max('count') ?? 0
        );

        $maxTopLocation = max(
            1,
            collect($topLocations)->max('count') ?? 0
        );

        $maxTopCause = max(
            1,
            collect($topCauses)->max('count') ?? 0
        );

        $maxTopInjuryType = max(
            1,
            collect($topInjuryTypes)->max('count') ?? 0
        );

        $maxTopBodyPart = max(
            1,
            collect($topBodyParts)->max('count') ?? 0
        );
    @endphp

    <style>
        .inc-report {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .inc-panel {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .06);
        }

        .dark .inc-panel {
            background: #18181b;
            border-color: rgba(255, 255, 255, .10);
        }

        .inc-panel-title {
            padding: 16px 18px;
            border-bottom: 1px solid #d1d5db;
            font-size: 17px;
            font-weight: 800;
        }

        .dark .inc-panel-title {
            border-bottom-color: rgba(255, 255, 255, .10);
        }

        .inc-filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            padding: 18px;
        }

        .inc-filter-label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            opacity: .72;
        }

        .inc-select {
            width: 100%;
            padding: 10px 11px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            color: #111827;
        }

        .dark .inc-select {
            background: #111827;
            color: #f9fafb;
            border-color: rgba(255, 255, 255, .12);
        }

        .inc-reset {
            align-self: end;
            height: 42px;
            border: none;
            border-radius: 10px;
            padding: 0 16px;
            cursor: pointer;
            background: #f59e0b;
            color: #111827;
            font-weight: 800;
        }

        .inc-cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .inc-card {
            padding: 18px;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            background: #ffffff;
        }

        .dark .inc-card {
            background: #18181b;
            border-color: rgba(255, 255, 255, .10);
        }

        .inc-card-label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            opacity: .72;
        }

        .inc-card-value {
            margin-top: 8px;
            font-size: 28px;
            font-weight: 900;
        }

        .inc-blue {
            color: #2563eb;
            font-weight: 900;
        }

        .inc-danger {
            color: #dc2626;
            font-weight: 900;
        }

        .inc-warning {
            color: #d97706;
            font-weight: 900;
        }

        .inc-success {
            color: #16a34a;
            font-weight: 900;
        }

        .inc-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .inc-content {
            padding: 18px;
        }

        .inc-bar-row {
            display: grid;
            grid-template-columns: 185px 1fr 75px;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .inc-bar-label {
            font-size: 13px;
            font-weight: 650;
            overflow-wrap: anywhere;
        }

        .inc-bar-track {
            height: 22px;
            overflow: hidden;
            border-radius: 999px;
            background: #e5e7eb;
        }

        .dark .inc-bar-track {
            background: rgba(255, 255, 255, .08);
        }

        .inc-bar-fill {
            min-width: 3px;
            height: 100%;
            border-radius: 999px;
            background: #f59e0b;
        }

        .inc-bar-fill-danger {
            background: #dc2626;
        }

        .inc-bar-fill-success {
            background: #16a34a;
        }

        .inc-bar-count {
            text-align: right;
            font-weight: 800;
        }

        .inc-table-wrap {
            overflow-x: auto;
        }

        .inc-table {
            width: 100%;
            border-collapse: collapse;
        }

        .inc-table th,
        .inc-table td {
            padding: 9px 10px;
            border: 1px solid #d1d5db;
            font-size: 13px;
        }

        .dark .inc-table th,
        .dark .inc-table td {
            border-color: rgba(255, 255, 255, .10);
        }

        .inc-table th {
            background: #f3f4f6;
            font-weight: 800;
            text-align: center;
        }

        .dark .inc-table th {
            background: rgba(255, 255, 255, .04);
        }

        .inc-center {
            text-align: center;
        }

        @media (max-width: 1200px) {
            .inc-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .inc-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .inc-cards,
            .inc-grid-2,
            .inc-filters {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="inc-report">

        <div class="inc-panel">
            <div class="inc-panel-title">
                Filtri izvještaja
            </div>

            <div class="inc-filters">

                <div>
                    <label class="inc-filter-label">
                        Godina
                    </label>

                    <select wire:model.live="year" class="inc-select">
                        <option value="all">Sve godine</option>

                        @foreach (($options['years'] ?? []) as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="inc-filter-label">
                        Mjesec
                    </label>

                    <select wire:model.live="month" class="inc-select">
                        <option value="all">Svi mjeseci</option>

                        @foreach ($monthOptions as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="inc-filter-label">
                        Vrsta incidenta
                    </label>

                    <select wire:model.live="type" class="inc-select">
                        <option value="">Sve vrste</option>
                        <option value="LTA">LTA – Ozljeda na radu</option>
                        <option value="MTA">MTA – Pružanje PP izvan tvrtke</option>
                        <option value="FAA">FAA – Pružanje PP u tvrtki</option>
                    </select>
                </div>

                <div>
                    <label class="inc-filter-label">
                        Lokacija
                    </label>

                    <select wire:model.live="location" class="inc-select">
                        <option value="">Sve lokacije</option>

                        @foreach (($options['locations'] ?? []) as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="inc-filter-label">
                        Vrsta zaposlenja
                    </label>

                    <select wire:model.live="employment" class="inc-select">
                        <option value="">Sve vrste zaposlenja</option>
                        <option value="Permanent">Stalni</option>
                        <option value="Temporary">Privremeni</option>
                    </select>
                </div>

                <div>
                    <label class="inc-filter-label">
                        Uzrok ozljede
                    </label>

                    <select wire:model.live="cause" class="inc-select">
                        <option value="">Svi uzroci</option>

                        @foreach (($options['causes'] ?? []) as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="inc-filter-label">
                        Tip ozljede
                    </label>

                    <select wire:model.live="injuryType" class="inc-select">
                        <option value="">Svi tipovi ozljede</option>

                        @foreach (($options['injury_types'] ?? []) as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="inc-filter-label">
                        Ozlijeđeni dio tijela
                    </label>

                    <select wire:model.live="bodyPart" class="inc-select">
                        <option value="">Svi dijelovi tijela</option>

                        @foreach (($options['body_parts'] ?? []) as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button
                    type="button"
                    wire:click="resetFilters"
                    class="inc-reset"
                >
                    Poništi filtre
                </button>

            </div>
        </div>


        <div class="inc-cards">

            <div class="inc-card">
                <div class="inc-card-label">Ukupno incidenata</div>
                <div class="inc-card-value inc-blue">
                    {{ $summary['total'] ?? 0 }}
                </div>
            </div>

            <div class="inc-card">
                <div class="inc-card-label">LTA</div>
                <div class="inc-card-value inc-danger">
                    {{ $summary['lta'] ?? 0 }}
                </div>
            </div>

            <div class="inc-card">
                <div class="inc-card-label">MTA</div>
                <div class="inc-card-value inc-warning">
                    {{ $summary['mta'] ?? 0 }}
                </div>
            </div>

            <div class="inc-card">
                <div class="inc-card-label">FAA</div>
                <div class="inc-card-value inc-warning">
                    {{ $summary['faa'] ?? 0 }}
                </div>
            </div>

            <div class="inc-card">
                <div class="inc-card-label">Izgubljeni radni dani</div>
                <div class="inc-card-value inc-success">
                    {{ $summary['lost_days'] ?? 0 }}
                </div>
            </div>

            <div class="inc-card">
                <div class="inc-card-label">Prosjek izgubljenih dana</div>
                <div class="inc-card-value inc-blue">
                    {{
                        number_format(
                            (float) ($summary['average_lost_days'] ?? 0),
                            1,
                            ',',
                            '.'
                        )
                    }}
                </div>
            </div>

            <div class="inc-card">
                <div class="inc-card-label">
                    Najveći broj izgubljenih dana
                </div>

                <div class="inc-card-value inc-warning">
                    {{ $summary['max_lost_days'] ?? 0 }}
                </div>
            </div>

            <div class="inc-card">
                <div class="inc-card-label">
                    Incidenti s izgubljenim danima
                </div>

                <div class="inc-card-value inc-success">
                    {{ $summary['incidents_with_lost_days'] ?? 0 }}
                </div>
            </div>

        </div>


        <div class="inc-grid-2">

            <div class="inc-panel">
                <div class="inc-panel-title">
                    Incidenti po mjesecima
                </div>

                <div class="inc-content">
                    @foreach ($months as $row)
                        <div class="inc-bar-row">

                            <div class="inc-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="inc-bar-track">
                                <div
                                    class="inc-bar-fill"
                                    style="width:{{ ($row['total'] / $maxMonthTotal) * 100 }}%;"
                                ></div>
                            </div>

                            <div class="inc-bar-count">
                                {{ $row['total'] }}
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>


            <div class="inc-panel">
                <div class="inc-panel-title">
                    Izgubljeni radni dani po mjesecima
                </div>

                <div class="inc-content">
                    @foreach ($months as $row)
                        <div class="inc-bar-row">

                            <div class="inc-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="inc-bar-track">
                                <div
                                    class="inc-bar-fill inc-bar-fill-danger"
                                    style="width:{{ ($row['lost_days'] / $maxMonthLostDays) * 100 }}%;"
                                ></div>
                            </div>

                            <div class="inc-bar-count">
                                {{ $row['lost_days'] }}
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>

        </div>


        <div class="inc-grid-2">

            <div class="inc-panel">
                <div class="inc-panel-title">
                    Vrste incidenata
                </div>

                <div class="inc-content">

                    @foreach ($types as $row)
                        <div class="inc-bar-row">

                            <div class="inc-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="inc-bar-track">
                                <div
                                    class="inc-bar-fill"
                                    style="width:{{ ($row['count'] / $maxType) * 100 }}%;"
                                ></div>
                            </div>

                            <div class="inc-bar-count">
                                {{ $row['count'] }}
                                ({{ $row['percentage'] }}%)
                            </div>

                        </div>
                    @endforeach

                </div>
            </div>


            <div class="inc-panel">
                <div class="inc-panel-title">
                    Vrsta zaposlenja
                </div>

                <div class="inc-content">

                    @foreach ($employment as $row)
                        <div class="inc-bar-row">

                            <div class="inc-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="inc-bar-track">
                                <div
                                    class="inc-bar-fill"
                                    style="width:{{ ($row['count'] / $maxEmployment) * 100 }}%;"
                                ></div>
                            </div>

                            <div class="inc-bar-count">
                                {{ $row['count'] }}
                                ({{ $row['percentage'] }}%)
                            </div>

                        </div>
                    @endforeach

                </div>
            </div>

        </div>


        <div class="inc-grid-2">

            <div class="inc-panel">
                <div class="inc-panel-title">
                    Top 5 lokacija
                </div>

                <div class="inc-content">
                    @forelse ($topLocations as $row)

                        <div class="inc-bar-row">

                            <div class="inc-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="inc-bar-track">
                                <div
                                    class="inc-bar-fill"
                                    style="width:{{ ($row['count'] / $maxTopLocation) * 100 }}%;"
                                ></div>
                            </div>

                            <div class="inc-bar-count">
                                {{ $row['count'] }}
                                / {{ $row['lost_days'] }} d.
                            </div>

                        </div>

                    @empty
                        Nema podataka.
                    @endforelse
                </div>
            </div>


            <div class="inc-panel">
                <div class="inc-panel-title">
                    Top 5 uzroka ozljede
                </div>

                <div class="inc-content">
                    @forelse ($topCauses as $row)

                        <div class="inc-bar-row">

                            <div class="inc-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="inc-bar-track">
                                <div
                                    class="inc-bar-fill"
                                    style="width:{{ ($row['count'] / $maxTopCause) * 100 }}%;"
                                ></div>
                            </div>

                            <div class="inc-bar-count">
                                {{ $row['count'] }}
                            </div>

                        </div>

                    @empty
                        Nema podataka.
                    @endforelse
                </div>
            </div>

        </div>


        <div class="inc-grid-2">

            <div class="inc-panel">
                <div class="inc-panel-title">
                    Top 5 tipova ozljede
                </div>

                <div class="inc-content">
                    @forelse ($topInjuryTypes as $row)

                        <div class="inc-bar-row">

                            <div class="inc-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="inc-bar-track">
                                <div
                                    class="inc-bar-fill"
                                    style="width:{{ ($row['count'] / $maxTopInjuryType) * 100 }}%;"
                                ></div>
                            </div>

                            <div class="inc-bar-count">
                                {{ $row['count'] }}
                            </div>

                        </div>

                    @empty
                        Nema podataka.
                    @endforelse
                </div>
            </div>


            <div class="inc-panel">
                <div class="inc-panel-title">
                    Top 5 ozlijeđenih dijelova tijela
                </div>

                <div class="inc-content">
                    @forelse ($topBodyParts as $row)

                        <div class="inc-bar-row">

                            <div class="inc-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="inc-bar-track">
                                <div
                                    class="inc-bar-fill"
                                    style="width:{{ ($row['count'] / $maxTopBodyPart) * 100 }}%;"
                                ></div>
                            </div>

                            <div class="inc-bar-count">
                                {{ $row['count'] }}
                            </div>

                        </div>

                    @empty
                        Nema podataka.
                    @endforelse
                </div>
            </div>

        </div>


        <div class="inc-panel">
            <div class="inc-panel-title">
                Pregled po mjesecima
            </div>

            <div class="inc-table-wrap">
                <table class="inc-table">

                    <thead>
                        <tr>
                            <th>Mjesec</th>
                            <th>Ukupno</th>
                            <th>LTA</th>
                            <th>MTA</th>
                            <th>FAA</th>
                            <th>Izgubljeni radni dani</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($months as $row)

                            <tr>
                                <td>{{ $row['label'] }}</td>

                                <td class="inc-center inc-blue">
                                    {{ $row['total'] }}
                                </td>

                                <td class="inc-center inc-danger">
                                    {{ $row['lta'] }}
                                </td>

                                <td class="inc-center inc-warning">
                                    {{ $row['mta'] }}
                                </td>

                                <td class="inc-center inc-warning">
                                    {{ $row['faa'] }}
                                </td>

                                <td class="inc-center inc-success">
                                    {{ $row['lost_days'] }}
                                </td>
                            </tr>

                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>


        <div class="inc-panel">
            <div class="inc-panel-title">
                Pregled po lokacijama
            </div>

            <div class="inc-table-wrap">
                <table class="inc-table">

                    <thead>
                        <tr>
                            <th>Lokacija</th>
                            <th>Ukupno</th>
                            <th>LTA</th>
                            <th>MTA</th>
                            <th>FAA</th>
                            <th>Izgubljeni radni dani</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($locationsTable as $row)

                            <tr>
                                <td>
                                    {{ $row['location'] }}
                                </td>

                                <td class="inc-center inc-blue">
                                    {{ $row['total'] }}
                                </td>

                                <td class="inc-center inc-danger">
                                    {{ $row['lta'] }}
                                </td>

                                <td class="inc-center inc-warning">
                                    {{ $row['mta'] }}
                                </td>

                                <td class="inc-center inc-warning">
                                    {{ $row['faa'] }}
                                </td>

                                <td class="inc-center inc-success">
                                    {{ $row['lost_days'] }}
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="inc-center"
                                >
                                    Nema podataka.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>

    </div>

</x-filament-panels::page>