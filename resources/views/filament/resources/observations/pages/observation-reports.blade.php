<x-filament-panels::page>
    @php
        $summary = $report['summary'] ?? [];
        $monthly = $report['monthly'] ?? [];
        $types = $report['types'] ?? [];
        $priorities = $report['priorities'] ?? [];
        $statuses = $report['statuses'] ?? [];

        $topHazards = $report['topHazards'] ?? [];
        $topLocations = $report['topLocations'] ?? [];
        $topResponsibleOpen = $report['topResponsibleOpen'] ?? [];
        $averageClosingByMonth = $report['averageClosingByMonth'] ?? [];

        $maxMonthly = max(
            1,
            collect($monthly)->max('total') ?? 1
        );

        $maxHazards = max(
            1,
            collect($topHazards)->max('count') ?? 1
        );

        $maxLocations = max(
            1,
            collect($topLocations)->max('count') ?? 1
        );

        $maxResponsible = max(
            1,
            collect($topResponsibleOpen)->max('open') ?? 1
        );
    @endphp

    <style>
        .obs-report {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .obs-panel {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .06);
        }

        .dark .obs-panel {
            background: #18181b;
            border-color: rgba(255, 255, 255, .10);
        }

        .obs-panel-title {
            padding: 16px 18px;
            border-bottom: 1px solid #d1d5db;
            font-size: 17px;
            font-weight: 800;
        }

        .dark .obs-panel-title {
            border-bottom-color: rgba(255, 255, 255, .10);
        }

        .obs-filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            padding: 18px;
        }

        .obs-filter-label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            opacity: .72;
        }

        .obs-select {
            width: 100%;
            padding: 10px 11px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            color: #111827;
        }

        .dark .obs-select {
            background: #111827;
            color: #f9fafb;
            border-color: rgba(255, 255, 255, .12);
        }

        .obs-reset {
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

        .obs-cards {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
        }

        .obs-card {
            padding: 18px;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            background: #ffffff;
        }

        .dark .obs-card {
            background: #18181b;
            border-color: rgba(255, 255, 255, .10);
        }

        .obs-card-label {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            opacity: .72;
        }

        .obs-card-value {
            margin-top: 8px;
            font-size: 28px;
            font-weight: 900;
        }

        .obs-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .obs-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .obs-content {
            padding: 18px;
        }

        .obs-bar-row {
            display: grid;
            grid-template-columns: 185px 1fr 55px;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .obs-bar-label {
            font-size: 13px;
            font-weight: 650;
            overflow-wrap: anywhere;
        }

        .obs-bar-track {
            height: 22px;
            overflow: hidden;
            border-radius: 999px;
            background: #e5e7eb;
        }

        .dark .obs-bar-track {
            background: rgba(255, 255, 255, .08);
        }

        .obs-bar-fill {
            min-width: 3px;
            height: 100%;
            border-radius: 999px;
            background: #f59e0b;
        }

        .obs-bar-count {
            text-align: right;
            font-weight: 800;
        }

        .obs-table-wrap {
            overflow-x: auto;
        }

        .obs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .obs-table th,
        .obs-table td {
            padding: 9px 10px;
            border: 1px solid #d1d5db;
            font-size: 13px;
        }

        .dark .obs-table th,
        .dark .obs-table td {
            border-color: rgba(255, 255, 255, .10);
        }

        .obs-table th {
            background: #f3f4f6;
            font-weight: 800;
            text-align: center;
        }

        .dark .obs-table th {
            background: rgba(255, 255, 255, .04);
        }

        .obs-center {
            text-align: center;
        }

        .obs-danger {
            color: #dc2626;
            font-weight: 800;
        }

        .obs-warning {
            color: #d97706;
            font-weight: 800;
        }

        .obs-success {
            color: #16a34a;
            font-weight: 800;
        }

        @media (max-width: 1200px) {
            .obs-cards {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .obs-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .obs-grid-3 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 800px) {
            .obs-cards,
            .obs-grid-2,
            .obs-filters {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="obs-report">

        <div class="obs-panel">
            <div class="obs-panel-title">
                Filtri izvještaja
            </div>

            <div class="obs-filters">
                <div>
                    <label class="obs-filter-label">Godina</label>

                    <select wire:model.live="year" class="obs-select">
                        <option value="all">Sve godine</option>

                        @foreach (($report['availableYears'] ?? []) as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="obs-filter-label">Mjesec</label>

                    <select wire:model.live="month" class="obs-select">
                        <option value="all">Svi mjeseci</option>
                        <option value="1">Siječanj</option>
                        <option value="2">Veljača</option>
                        <option value="3">Ožujak</option>
                        <option value="4">Travanj</option>
                        <option value="5">Svibanj</option>
                        <option value="6">Lipanj</option>
                        <option value="7">Srpanj</option>
                        <option value="8">Kolovoz</option>
                        <option value="9">Rujan</option>
                        <option value="10">Listopad</option>
                        <option value="11">Studeni</option>
                        <option value="12">Prosinac</option>
                    </select>
                </div>

                <div>
                    <label class="obs-filter-label">Vrsta zapažanja</label>

                    <select wire:model.live="type" class="obs-select">
                        <option value="">Sve vrste</option>
                        <option value="Near Miss">NM – Skoro nezgoda</option>
                        <option value="Negative Observation">Negativno</option>
                        <option value="Positive Observation">Pozitivno</option>
                    </select>
                </div>

                <div>
                    <label class="obs-filter-label">Prioritet</label>

                    <select wire:model.live="priority" class="obs-select">
                        <option value="">Svi prioriteti</option>
                        <option value="low">Nisko</option>
                        <option value="medium">Srednje</option>
                        <option value="high">Visoko</option>
                        <option value="critical">Kritično</option>
                    </select>
                </div>

                <div>
                    <label class="obs-filter-label">Status</label>

                    <select wire:model.live="status" class="obs-select">
                        <option value="">Svi statusi</option>
                        <option value="Not started">Nije započeto</option>
                        <option value="In progress">U tijeku</option>
                        <option value="Complete">Završeno</option>
                    </select>
                </div>

                <div>
                    <label class="obs-filter-label">Lokacija</label>

                    <select wire:model.live="location" class="obs-select">
                        <option value="">Sve lokacije</option>

                        @foreach (($report['availableLocations'] ?? []) as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="obs-filter-label">Odgovorna osoba</label>

                    <select wire:model.live="responsible" class="obs-select">
                        <option value="">Sve odgovorne osobe</option>

                        @foreach (($report['availableResponsible'] ?? []) as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="obs-filter-label">Vrsta opasnosti</label>

                    <select wire:model.live="hazard" class="obs-select">
                        <option value="">Sve opasnosti</option>

                        @foreach (($report['availableHazards'] ?? []) as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button
                    type="button"
                    wire:click="resetFilters"
                    class="obs-reset"
                >
                    Poništi filtre
                </button>
            </div>
        </div>

        <div class="obs-cards">
            <div class="obs-card">
                <div class="obs-card-label">Ukupno</div>
                <div class="obs-card-value">{{ $summary['total'] ?? 0 }}</div>
            </div>

            <div class="obs-card">
                <div class="obs-card-label">Near Miss</div>
                <div class="obs-card-value">{{ $summary['nearMiss'] ?? 0 }}</div>
            </div>

            <div class="obs-card">
                <div class="obs-card-label">Negativna</div>
                <div class="obs-card-value">{{ $summary['negative'] ?? 0 }}</div>
            </div>

            <div class="obs-card">
                <div class="obs-card-label">Pozitivna</div>
                <div class="obs-card-value">{{ $summary['positive'] ?? 0 }}</div>
            </div>

            <div class="obs-card">
                <div class="obs-card-label">Nije započeto</div>
                <div class="obs-card-value obs-danger">
                    {{ $summary['notStarted'] ?? 0 }}
                </div>
            </div>

            <div class="obs-card">
                <div class="obs-card-label">U tijeku</div>
                <div class="obs-card-value obs-warning">
                    {{ $summary['inProgress'] ?? 0 }}
                </div>
            </div>

            <div class="obs-card">
                <div class="obs-card-label">Završeno</div>
                <div class="obs-card-value obs-success">
                    {{ $summary['completed'] ?? 0 }}
                </div>
            </div>

            <div class="obs-card">
                <div class="obs-card-label">Istekao rok</div>
                <div class="obs-card-value obs-danger">
                    {{ $summary['expired'] ?? 0 }}
                </div>
            </div>

            <div class="obs-card">
                <div class="obs-card-label">Ističe u 30 dana</div>
                <div class="obs-card-value obs-warning">
                    {{ $summary['expiring'] ?? 0 }}
                </div>
            </div>

            <div class="obs-card">
                <div class="obs-card-label">Prosjek zatvaranja</div>
                <div class="obs-card-value">
                    {{ $summary['averageClosingDays'] ?? '-' }}
                </div>
                <div>dana</div>
            </div>
        </div>

        <div class="obs-panel">
            <div class="obs-panel-title">
                Zapažanja po mjesecima
            </div>

            <div class="obs-content">
                @foreach ($monthly as $row)
                    <div class="obs-bar-row">
                        <div class="obs-bar-label">
                            {{ $row['label'] }}
                        </div>

                        <div class="obs-bar-track">
                            <div
                                class="obs-bar-fill"
                                style="width:{{ ($row['total'] / $maxMonthly) * 100 }}%;"
                            ></div>
                        </div>

                        <div class="obs-bar-count">
                            {{ $row['total'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="obs-grid-3">
            @foreach ([
                'Vrste zapažanja' => $types,
                'Prioriteti' => $priorities,
                'Statusi' => $statuses,
            ] as $title => $rows)
                <div class="obs-panel">
                    <div class="obs-panel-title">{{ $title }}</div>

                    <div class="obs-content">
                        @php
                            $max = max(
                                1,
                                collect($rows)->max('count') ?? 1
                            );
                        @endphp

                        @forelse ($rows as $row)
                            <div class="obs-bar-row">
                                <div class="obs-bar-label">
                                    {{ $row['label'] }}
                                </div>

                                <div class="obs-bar-track">
                                    <div
                                        class="obs-bar-fill"
                                        style="width:{{ ($row['count'] / $max) * 100 }}%;"
                                    ></div>
                                </div>

                                <div class="obs-bar-count">
                                    {{ $row['count'] }}
                                </div>
                            </div>
                        @empty
                            Nema podataka.
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        <div class="obs-grid-2">
            <div class="obs-panel">
                <div class="obs-panel-title">
                    Top 10 najčešćih opasnosti
                </div>

                <div class="obs-content">
                    @forelse ($topHazards as $row)
                        <div class="obs-bar-row">
                            <div class="obs-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="obs-bar-track">
                                <div
                                    class="obs-bar-fill"
                                    style="width:{{ ($row['count'] / $maxHazards) * 100 }}%;"
                                ></div>
                            </div>

                            <div class="obs-bar-count">
                                {{ $row['count'] }}
                            </div>
                        </div>
                    @empty
                        Nema podataka.
                    @endforelse
                </div>
            </div>

            <div class="obs-panel">
                <div class="obs-panel-title">
                    Top 10 lokacija s najviše zapažanja
                </div>

                <div class="obs-content">
                    @forelse ($topLocations as $row)
                        <div class="obs-bar-row">
                            <div class="obs-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="obs-bar-track">
                                <div
                                    class="obs-bar-fill"
                                    style="width:{{ ($row['count'] / $maxLocations) * 100 }}%;"
                                ></div>
                            </div>

                            <div class="obs-bar-count">
                                {{ $row['count'] }}
                            </div>
                        </div>
                    @empty
                        Nema podataka.
                    @endforelse
                </div>
            </div>
        </div>

        <div class="obs-panel">
            <div class="obs-panel-title">
                Top 10 odgovornih osoba s najviše otvorenih radnji
            </div>

            <div class="obs-table-wrap">
                <table class="obs-table">
                    <thead>
                        <tr>
                            <th>Odgovorna osoba</th>
                            <th>Otvoreno</th>
                            <th>Nije započeto</th>
                            <th>U tijeku</th>
                            <th>Isteklo</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($topResponsibleOpen as $row)
                            <tr>
                                <td>{{ $row['responsible'] }}</td>
                                <td class="obs-center">{{ $row['open'] }}</td>
                                <td class="obs-center obs-danger">
                                    {{ $row['not_started'] }}
                                </td>
                                <td class="obs-center obs-warning">
                                    {{ $row['in_progress'] }}
                                </td>
                                <td class="obs-center obs-danger">
                                    {{ $row['expired'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="obs-center">
                                    Nema otvorenih radnji.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="obs-panel">
            <div class="obs-panel-title">
                Prosječno vrijeme zatvaranja zapažanja po mjesecima
            </div>

            <div class="obs-table-wrap">
                <table class="obs-table">
                    <thead>
                        <tr>
                            <th>Mjesec</th>
                            <th>Broj završenih zapažanja</th>
                            <th>Prosječno vrijeme zatvaranja</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($averageClosingByMonth as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="obs-center">
                                    {{ $row['completed'] }}
                                </td>
                                <td class="obs-center">
                                    {{ $row['average_days'] !== null
                                        ? number_format(
                                            $row['average_days'],
                                            1,
                                            ',',
                                            '.'
                                        ) . ' dana'
                                        : '-'
                                    }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>