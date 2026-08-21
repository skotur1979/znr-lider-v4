<x-filament-panels::page>

    @php
        $data = $this->report;

        $summary = $data['summary'] ?? [];
        $zones = $data['zones'] ?? [];
        $history = $data['history'] ?? [];
        $options = $data['options'] ?? [];

        $scoreStyle = function ($score): string {
            if ($score === null || $score === '') {
                return 'background:#374151;color:#d1d5db;';
            }

            $score = (float) $score;

            return match (true) {
                $score < 40 =>
                    'background:#991b1b;color:#ffffff;',

                $score < 60 =>
                    'background:#f59e0b;color:#111827;',

                $score < 80 =>
                    'background:#fde047;color:#111827;',

                default =>
                    'background:#16a34a;color:#ffffff;',
            };
        };

        $changeStyle = function ($change): string {
            if ($change === null) {
                return 'color:#9ca3af;';
            }

            $change = (float) $change;

            return match (true) {
                $change > 0 =>
                    'color:#22c55e;',

                $change < 0 =>
                    'color:#ef4444;',

                default =>
                    'color:#9ca3af;',
            };
        };

        $changeText = function ($change): string {
            if ($change === null) {
                return '-';
            }

            $change = (float) $change;

            if ($change > 0) {
                return '+'
                    . number_format(
                        $change,
                        1,
                        ',',
                        '.'
                    )
                    . ' p.b.';
            }

            return number_format(
                $change,
                1,
                ',',
                '.'
            ) . ' p.b.';
        };
    @endphp


    <style>
        .five-s-report {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .five-s-panel {
            background: rgb(255 255 255);
            border: 1px solid rgb(229 231 235);
            border-radius: 14px;
            overflow: hidden;
        }

        .dark .five-s-panel {
            background: rgb(24 24 27);
            border-color: rgba(255, 255, 255, .10);
        }

        .five-s-panel-title {
            padding: 14px 16px;
            border-bottom: 1px solid rgb(229 231 235);
            font-weight: 800;
            font-size: 15px;
        }

        .dark .five-s-panel-title {
            border-color: rgba(255, 255, 255, .10);
        }

        .five-s-filters {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 12px;
            padding: 16px;
        }

        .five-s-filter-label {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            line-height: 1.2;
            font-weight: 800;
            text-transform: uppercase;
            color: rgb(75 85 99);
        }

        .dark .five-s-filter-label {
            color: rgb(156 163 175);
        }

        .five-s-select {
            width: 100%;
            min-height: 38px;
            padding: 7px 11px;
            border-radius: 9px;
            border: 1px solid rgb(209 213 219);
            background: rgb(255 255 255);
            color: rgb(17 24 39);
            font-size: 14px;
        }

        .dark .five-s-select {
            background: rgb(17 24 39);
            border-color: rgb(55 65 81);
            color: rgb(243 244 246);
        }

        .five-s-filter-actions {
            padding: 0 16px 16px;
        }

        .five-s-reset-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 0 18px;
            border: 0;
            border-radius: 9px;
            background: #f59e0b;
            color: #111827;
            font-weight: 800;
            cursor: pointer;
        }

        .five-s-cards {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .five-s-card {
            min-height: 110px;
            padding: 16px;
            border-radius: 14px;
            border: 1px solid rgb(229 231 235);
            background: rgb(255 255 255);
        }

        .dark .five-s-card {
            background: rgb(24 24 27);
            border-color: rgba(255, 255, 255, .10);
        }

        .five-s-card-label {
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: rgb(107 114 128);
        }

        .five-s-card-value {
            font-size: 27px;
            line-height: 1;
            font-weight: 900;
        }

        .five-s-card-note {
            margin-top: 8px;
            font-size: 12px;
            color: rgb(107 114 128);
        }

        .five-s-table-wrap {
            overflow-x: auto;
        }

        .five-s-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        .five-s-table th,
        .five-s-table td {
            padding: 11px 12px;
            border-bottom: 1px solid rgb(229 231 235);
            font-size: 13px;
            vertical-align: middle;
        }

        .dark .five-s-table th,
        .dark .five-s-table td {
            border-color: rgba(255, 255, 255, .08);
        }

        .five-s-table th {
            background: rgb(249 250 251);
            font-weight: 800;
            text-align: left;
            white-space: nowrap;
        }

        .dark .five-s-table th {
            background: rgba(255, 255, 255, .04);
        }

        .five-s-center {
            text-align: center !important;
        }

        .five-s-zone-name {
            font-weight: 800;
        }

        .five-s-score {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 68px;
            height: 32px;
            padding: 0 10px;
            border-radius: 9px;
            font-weight: 900;
        }

        .five-s-change {
            font-weight: 900;
        }

        .five-s-arrow {
            margin-right: 4px;
        }

        .five-s-history-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .five-s-help {
            color: rgb(107 114 128);
            font-size: 12px;
            font-weight: 500;
        }

        .five-s-empty {
            padding: 36px 16px;
            text-align: center;
            color: rgb(107 114 128);
        }

        .five-s-location {
            max-width: 240px;
        }

        @media (max-width: 1100px) {
            .five-s-cards {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .five-s-filters {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .five-s-cards,
            .five-s-filters {
                grid-template-columns: 1fr;
            }
        }
    </style>


    <div class="five-s-report">

        {{-- FILTRI --}}
        <div class="five-s-panel">

            <div class="five-s-panel-title">
                Filtri izvještaja
            </div>

            <div class="five-s-filters">

                <div>
                    <label class="five-s-filter-label">
                        Godina
                    </label>

                    <select
                        wire:model.live="year"
                        class="five-s-select"
                    >
                        <option value="all">
                            Sve godine
                        </option>

                        @foreach (
                            ($options['years'] ?? [])
                            as $value => $label
                        )
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label class="five-s-filter-label">
                        Lokacija
                    </label>

                    <select
                        wire:model.live="location"
                        class="five-s-select"
                    >
                        <option value="">
                            Sve lokacije
                        </option>

                        @foreach (
                            ($options['locations'] ?? [])
                            as $value => $label
                        )
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label class="five-s-filter-label">
                        Zona
                    </label>

                    <select
                        wire:model.live="zone"
                        class="five-s-select"
                    >
                        <option value="">
                            Sve zone
                        </option>

                        @foreach (
                            ($options['zones'] ?? [])
                            as $value => $label
                        )
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>


            <div class="five-s-filter-actions">

                <button
                    type="button"
                    wire:click="resetFilters"
                    class="five-s-reset-button"
                >
                    Poništi filtre
                </button>

            </div>

        </div>


        {{-- SAŽETAK --}}
        <div class="five-s-cards">

            <div class="five-s-card">
                <div class="five-s-card-label">
                    Broj 5S nadzora
                </div>

                <div class="five-s-card-value">
                    {{
                        $summary[
                            'inspections'
                        ] ?? 0
                    }}
                </div>

                <div class="five-s-card-note">
                    Broj nadzora u odabranom razdoblju
                </div>
            </div>


            <div class="five-s-card">
                <div class="five-s-card-label">
                    Prosječni rezultat
                </div>

                <div class="five-s-card-value">
                    {{
                        number_format(
                            (float) (
                                $summary['average']
                                ?? 0
                            ),
                            1,
                            ',',
                            '.'
                        )
                    }}%
                </div>

                <div class="five-s-card-note">
                    Prosjek ukupnih 5S rezultata
                </div>
            </div>


            <div class="five-s-card">
                <div class="five-s-card-label">
                    Zadnji rezultat
                </div>

                <div class="five-s-card-value">

                    @if (
                        ($summary['latest'] ?? null)
                        !== null
                    )
                        <span
                            class="five-s-score"
                            style="{{
                                $scoreStyle(
                                    $summary['latest']
                                )
                            }}"
                        >
                            {{
                                number_format(
                                    (float) $summary[
                                        'latest'
                                    ],
                                    0
                                )
                            }}%
                        </span>
                    @else
                        -
                    @endif

                </div>

                <div class="five-s-card-note">
                    Posljednji 5S nadzor
                </div>
            </div>


            <div class="five-s-card">
                <div class="five-s-card-label">
                    Promjena
                </div>

                <div
                    class="five-s-card-value"
                    style="{{
                        $changeStyle(
                            $summary['change']
                            ?? null
                        )
                    }}"
                >
                    @php
                        $summaryChange =
                            $summary['change']
                            ?? null;
                    @endphp

                    @if ($summaryChange !== null)

                        @if ($summaryChange > 0)
                            ↑
                        @elseif ($summaryChange < 0)
                            ↓
                        @else
                            →
                        @endif

                        {{
                            $changeText(
                                $summaryChange
                            )
                        }}

                    @else
                        -
                    @endif
                </div>

                <div class="five-s-card-note">
                    U odnosu na prethodni 5S nadzor
                </div>
            </div>

        </div>


        {{-- REZULTATI PO ZONAMA --}}
        <div class="five-s-panel">

            <div class="five-s-panel-title">
                Rezultati po zonama
            </div>


            @if (count($zones) > 0)

                <div class="five-s-table-wrap">

                    <table class="five-s-table">

                        <thead>
                            <tr>
                                <th>
                                    Zona
                                </th>

                                <th class="five-s-center">
                                    Broj nadzora
                                </th>

                                <th class="five-s-center">
                                    Zadnji rezultat
                                </th>

                                <th class="five-s-center">
                                    Prethodni
                                </th>

                                <th class="five-s-center">
                                    Promjena
                                </th>

                                <th class="five-s-center">
                                    Prosjek
                                </th>

                                <th class="five-s-center">
                                    Najbolji
                                </th>

                                <th class="five-s-center">
                                    Najlošiji
                                </th>
                            </tr>
                        </thead>


                        <tbody>

                            @foreach ($zones as $row)

                                @php
                                    $change =
                                        $row['change']
                                        ?? null;
                                @endphp

                                <tr>

                                    <td class="five-s-zone-name">
                                        {{
                                            $row['zone']
                                            ?? '-'
                                        }}
                                    </td>


                                    <td class="five-s-center">
                                        {{
                                            $row['count']
                                            ?? 0
                                        }}
                                    </td>


                                    <td class="five-s-center">
                                        <span
                                            class="five-s-score"
                                            style="{{
                                                $scoreStyle(
                                                    $row[
                                                        'latest'
                                                    ]
                                                    ?? null
                                                )
                                            }}"
                                        >
                                            {{
                                                number_format(
                                                    (float) (
                                                        $row[
                                                            'latest'
                                                        ]
                                                        ?? 0
                                                    ),
                                                    0
                                                )
                                            }}%
                                        </span>
                                    </td>


                                    <td class="five-s-center">

                                        @if (
                                            ($row['previous'] ?? null)
                                            !== null
                                        )
                                            <span
                                                class="five-s-score"
                                                style="{{
                                                    $scoreStyle(
                                                        $row[
                                                            'previous'
                                                        ]
                                                    )
                                                }}"
                                            >
                                                {{
                                                    number_format(
                                                        (float) $row[
                                                            'previous'
                                                        ],
                                                        0
                                                    )
                                                }}%
                                            </span>
                                        @else
                                            -
                                        @endif

                                    </td>


                                    <td class="five-s-center">

                                        <span
                                            class="five-s-change"
                                            style="{{
                                                $changeStyle(
                                                    $change
                                                )
                                            }}"
                                        >
                                            @if ($change !== null)

                                                @if ($change > 0)
                                                    <span class="five-s-arrow">
                                                        ↑
                                                    </span>
                                                @elseif ($change < 0)
                                                    <span class="five-s-arrow">
                                                        ↓
                                                    </span>
                                                @else
                                                    <span class="five-s-arrow">
                                                        →
                                                    </span>
                                                @endif

                                                {{
                                                    $changeText(
                                                        $change
                                                    )
                                                }}

                                            @else
                                                -
                                            @endif
                                        </span>

                                    </td>


                                    <td class="five-s-center">
                                        {{
                                            number_format(
                                                (float) (
                                                    $row[
                                                        'average'
                                                    ]
                                                    ?? 0
                                                ),
                                                1,
                                                ',',
                                                '.'
                                            )
                                        }}%
                                    </td>


                                    <td class="five-s-center">
                                        <span
                                            style="
                                                color:#22c55e;
                                                font-weight:800;
                                            "
                                        >
                                            {{
                                                number_format(
                                                    (float) (
                                                        $row[
                                                            'best'
                                                        ]
                                                        ?? 0
                                                    ),
                                                    1,
                                                    ',',
                                                    '.'
                                                )
                                            }}%
                                        </span>
                                    </td>


                                    <td class="five-s-center">
                                        <span
                                            style="
                                                color:#ef4444;
                                                font-weight:800;
                                            "
                                        >
                                            {{
                                                number_format(
                                                    (float) (
                                                        $row[
                                                            'worst'
                                                        ]
                                                        ?? 0
                                                    ),
                                                    1,
                                                    ',',
                                                    '.'
                                                )
                                            }}%
                                        </span>
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="five-s-empty">
                    Nema rezultata zona za odabrane filtre.
                </div>

            @endif

        </div>


        {{-- POVIJEST NADZORA --}}
        <div class="five-s-panel">

            <div
                class="
                    five-s-panel-title
                    five-s-history-title
                "
            >
                <span>
                    Usporedba 5S nadzora kroz vrijeme
                </span>

                <span class="five-s-help">
                    Najnoviji nadzori prikazani su prvi
                </span>
            </div>


            @if (count($history) > 0)

                @php
                    $allZoneNames =
                        collect($history)
                            ->flatMap(
                                fn ($item) =>
                                    array_keys(
                                        $item['zones']
                                        ?? []
                                    )
                            )
                            ->unique()
                            ->sort()
                            ->values();
                @endphp


                <div class="five-s-table-wrap">

                    <table class="five-s-table">

                        <thead>

                            <tr>
                                <th>
                                    Datum
                                </th>

                                <th>
                                    Broj nadzora
                                </th>

                                <th>
                                    Naziv
                                </th>

                                <th>
                                    Lokacija
                                </th>

                                @foreach (
                                    $allZoneNames
                                    as $zoneName
                                )
                                    <th class="five-s-center">
                                        {{ $zoneName }}
                                    </th>
                                @endforeach

                                <th class="five-s-center">
                                    Ukupni rezultat
                                </th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach ($history as $inspection)

                                <tr>

                                    <td>
                                        @if (
                                            ! empty(
                                                $inspection[
                                                    'performed_at'
                                                ]
                                            )
                                        )
                                            {{
                                                \Illuminate\Support\Carbon::parse(
                                                    $inspection[
                                                        'performed_at'
                                                    ]
                                                )->format(
                                                    'd.m.Y.'
                                                )
                                            }}
                                        @else
                                            -
                                        @endif
                                    </td>


                                    <td style="font-weight:800;">
                                        {{
                                            $inspection[
                                                'number'
                                            ]
                                            ?? '-'
                                        }}
                                    </td>


                                    <td>
                                        {{
                                            $inspection[
                                                'title'
                                            ]
                                            ?? '-'
                                        }}
                                    </td>


                                    <td class="five-s-location">
                                        {{
                                            $inspection[
                                                'location'
                                            ]
                                            ?? '-'
                                        }}
                                    </td>


                                    @foreach (
                                        $allZoneNames
                                        as $zoneName
                                    )

                                        @php
                                            $zoneScore =
                                                $inspection[
                                                    'zones'
                                                ][$zoneName]
                                                ?? null;
                                        @endphp

                                        <td class="five-s-center">

                                            @if (
                                                $zoneScore
                                                !== null
                                            )

                                                <span
                                                    class="five-s-score"
                                                    style="{{
                                                        $scoreStyle(
                                                            $zoneScore
                                                        )
                                                    }}"
                                                >
                                                    {{
                                                        number_format(
                                                            (float) $zoneScore,
                                                            0
                                                        )
                                                    }}%
                                                </span>

                                            @else
                                                -
                                            @endif

                                        </td>

                                    @endforeach


                                    <td class="five-s-center">

                                        @if (
                                            ($inspection[
                                                'overall'
                                            ] ?? null)
                                            !== null
                                        )

                                            <span
                                                class="five-s-score"
                                                style="{{
                                                    $scoreStyle(
                                                        $inspection[
                                                            'overall'
                                                        ]
                                                    )
                                                }}"
                                            >
                                                {{
                                                    number_format(
                                                        (float) $inspection[
                                                            'overall'
                                                        ],
                                                        0
                                                    )
                                                }}%
                                            </span>

                                        @else
                                            -
                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="five-s-empty">
                    Nema 5S nadzora za odabrane filtre.
                </div>

            @endif

        </div>

    </div>

</x-filament-panels::page>