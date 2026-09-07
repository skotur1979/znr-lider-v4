<x-filament-panels::page>
    @php
        $summary =
            $report['summary'] ?? [];

        $monthly =
            $report['monthly'] ?? [];

        $byCategory =
            $report['by_category'] ?? [];

        $bySupplier =
            $report['by_supplier'] ?? [];

        $byYear =
            $report['by_year'] ?? [];

        $comparison =
            $report['comparison'] ?? [];

        $topExpenses =
            $report['top_expenses'] ?? [];

        $options =
            $report['options'] ?? [];

        $maxMonthly =
            max(
                1,
                collect($monthly)
                    ->max('total')
                    ?? 0
            );

        $maxCategory =
            max(
                1,
                collect($byCategory)
                    ->max('total')
                    ?? 0
            );

        $maxSupplier =
            max(
                1,
                collect($bySupplier)
                    ->max('total')
                    ?? 0
            );

        $maxYear =
            max(
                1,
                collect($byYear)
                    ->max('total')
                    ?? 0
            );

        $comparisonMax =
            max(
                1,
                collect(
                    $comparison['months']
                    ?? []
                )->max(
                    fn ($row) =>
                        max(
                            $row['current'],
                            $row['comparison']
                        )
                )
                ?? 0
            );

        $money =
            fn ($value) =>
                number_format(
                    (float) $value,
                    2,
                    ',',
                    '.'
                ) . ' €';

        $months = [
            'Siječanj',
            'Veljača',
            'Ožujak',
            'Travanj',
            'Svibanj',
            'Lipanj',
            'Srpanj',
            'Kolovoz',
            'Rujan',
            'Listopad',
            'Studeni',
            'Prosinac',
        ];
    @endphp

    <style>
        .expense-report {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .er-panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
        }

        .dark .er-panel {
            background: rgb(17 24 39);
            border-color: rgb(55 65 81);
        }

        .er-panel-title {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: .88rem;
            font-weight: 800;
            color: #111827;
        }

        .dark .er-panel-title {
            color: white;
            border-color: rgb(55 65 81);
        }

        .er-filters {
            padding: 14px;
            display: grid;
            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );
            gap: 10px;
        }

        .er-filter label {
            display: block;
            margin-bottom: 4px;
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
        }

        .dark .er-filter label {
            color: #94a3b8;
        }

        .er-select {
            width: 100%;
            min-height: 38px;
            padding: 7px 10px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: white;
            color: #111827;
            font-size: .82rem;
        }

        .dark .er-select {
            background: #111827;
            border-color: #475569;
            color: white;
        }

        .er-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 7px 14px;
            border: 0;
            border-radius: 8px;
            background: #f59e0b;
            color: #111827;
            font-size: .78rem;
            font-weight: 800;
            cursor: pointer;
        }

        .er-cards {
            display: grid;
            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );
            gap: 10px;
        }

        .er-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 13px 14px;
        }

        .dark .er-card {
            background: rgb(17 24 39);
            border-color: rgb(55 65 81);
        }

        .er-card-label {
            font-size: .67rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
        }

        .dark .er-card-label {
            color: #94a3b8;
        }

        .er-card-value {
            margin-top: 5px;
            font-size: 1.25rem;
            line-height: 1.1;
            font-weight: 900;
            color: #111827;
        }

        .dark .er-card-value {
            color: white;
        }

        .er-card-value.success {
            color: #059669;
        }

        .er-card-value.danger {
            color: #dc2626;
        }

        .er-grid-2 {
            display: grid;
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
            gap: 12px;
        }

        .er-bars {
            padding: 12px 14px;
        }

        .er-bar-row {
            display: grid;
            grid-template-columns:
                115px 1fr 105px;
            gap: 10px;
            align-items: center;
            min-height: 27px;
        }

        .er-bar-label {
            font-size: .75rem;
            font-weight: 650;
            color: #334155;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .er-bar-label {
            color: #cbd5e1;
        }

        .er-track {
            height: 8px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
        }

        .dark .er-track {
            background: #374151;
        }

        .er-fill {
            height: 100%;
            border-radius: 999px;
            background: #f59e0b;
        }

        .er-bar-value {
            text-align: right;
            font-size: .75rem;
            font-weight: 800;
            color: #111827;
            white-space: nowrap;
        }

        .dark .er-bar-value {
            color: white;
        }

        .er-comparison {
            padding: 12px 14px;
        }

        .er-comparison-row {
            display: grid;
            grid-template-columns:
                100px 1fr 105px;
            gap: 10px;
            align-items: center;
            padding: 5px 0;
        }

        .er-double-bars {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .er-current {
            background: #f59e0b;
        }

        .er-previous {
            background: #3b82f6;
        }

        .er-comparison-values {
            text-align: right;
            font-size: .7rem;
            line-height: 1.45;
            white-space: nowrap;
        }

        .er-comparison-legend {
            padding: 0 14px 12px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            font-size: .72rem;
            font-weight: 700;
        }

        .er-legend {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .er-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
        }

        .er-table-wrap {
            overflow-x: auto;
        }

        .er-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .75rem;
        }

        .er-table th,
        .er-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        .dark .er-table th,
        .dark .er-table td {
            border-color: #374151;
        }

        .er-table th {
            font-size: .67rem;
            text-transform: uppercase;
            color: #64748b;
            background: #f8fafc;
        }

        .dark .er-table th {
            background: #0f172a;
            color: #94a3b8;
        }

        .er-table td {
            color: #334155;
        }

        .dark .er-table td {
            color: #e2e8f0;
        }

        .er-badge {
            display: inline-flex;
            padding: 3px 7px;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 800;
        }

        .er-badge.success {
            color: #166534;
            background: #dcfce7;
        }

        .er-badge.gray {
            color: #475569;
            background: #f1f5f9;
        }

        @media (
            max-width: 900px
        ) {
            .er-filters {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

            .er-cards {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

            .er-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (
            max-width: 600px
        ) {
            .er-filters {
                grid-template-columns: 1fr;
            }

            .er-cards {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

            .er-card-value {
                font-size: 1rem;
            }

            .er-bar-row {
                grid-template-columns:
                    90px 1fr 85px;
                gap: 6px;
            }

            .er-comparison-row {
                grid-template-columns:
                    80px 1fr 85px;
                gap: 6px;
            }
        }
    </style>

    <div class="expense-report">

        {{-- FILTRI --}}
        <div class="er-panel">
            <div class="er-panel-title">
                Filtri izvještaja
            </div>

            <div class="er-filters">

                <div class="er-filter">
                    <label>Godina</label>

                    <select
                        wire:model.live="year"
                        class="er-select"
                    >
                        <option value="all">
                            Sve godine
                        </option>

                        @foreach (
                            $options['years'] ?? []
                            as $value => $label
                        )
                            <option
                                value="{{ $value }}"
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="er-filter">
                    <label>
                        Usporedi s godinom
                    </label>

                    <select
                        wire:model.live="comparison_year"
                        class="er-select"
                    >
                        <option value="none">
                            Bez usporedbe
                        </option>

                        @foreach (
                            $options['years'] ?? []
                            as $value => $label
                        )
                            @if (
                                (string) $value
                                !== (string) $year
                            )
                                <option
                                    value="{{ $value }}"
                                >
                                    {{ $label }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="er-filter">
                    <label>Mjesec</label>

                    <select
                        wire:model.live="month"
                        class="er-select"
                    >
                        <option value="all">
                            Svi mjeseci
                        </option>

                        @foreach (
                            $months
                            as $monthName
                        )
                            <option
                                value="{{ $monthName }}"
                            >
                                {{ $monthName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="er-filter">
                    <label>Kategorija</label>

                    <select
                        wire:model.live="category_id"
                        class="er-select"
                    >
                        <option value="">
                            Sve kategorije
                        </option>

                        @foreach (
                            $options['categories'] ?? []
                            as $value => $label
                        )
                            <option
                                value="{{ $value }}"
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="er-filter">
                    <label>Dobavljač</label>

                    <select
                        wire:model.live="supplier"
                        class="er-select"
                    >
                        <option value="">
                            Svi dobavljači
                        </option>

                        @foreach (
                            $options['suppliers'] ?? []
                            as $value => $label
                        )
                            <option
                                value="{{ $value }}"
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="er-filter">
                    <label>Realizirano</label>

                    <select
                        wire:model.live="realized"
                        class="er-select"
                    >
                        <option value="all">
                            Sve
                        </option>

                        <option value="1">
                            Da
                        </option>

                        <option value="0">
                            Ne
                        </option>
                    </select>
                </div>

                <div>
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="er-reset"
                    >
                        Poništi filtre
                    </button>
                </div>
            </div>
        </div>

        {{-- KPI --}}
        <div class="er-cards">

            <div class="er-card">
                <div class="er-card-label">
                    Ukupno evidentirano
                </div>

                <div class="er-card-value">
                    {{
                        $money(
                            $summary[
                                'total_amount'
                            ] ?? 0
                        )
                    }}
                </div>
            </div>

            <div class="er-card">
                <div class="er-card-label">
                    Realizirano
                </div>

                <div class="er-card-value">
                    {{
                        $money(
                            $summary[
                                'realized_amount'
                            ] ?? 0
                        )
                    }}
                </div>
            </div>

            <div class="er-card">
                <div class="er-card-label">
                    Nerealizirano
                </div>

                <div class="er-card-value">
                    {{
                        $money(
                            $summary[
                                'unrealized_amount'
                            ] ?? 0
                        )
                    }}
                </div>
            </div>

            <div class="er-card">
                <div class="er-card-label">
                    Broj troškova
                </div>

                <div class="er-card-value">
                    {{
                        $summary[
                            'count'
                        ] ?? 0
                    }}
                </div>
            </div>

            <div class="er-card">
                <div class="er-card-label">
                    Budžet
                </div>

                <div class="er-card-value">
                    @if (
                        $summary[
                            'budget_amount'
                        ] !== null
                    )
                        {{
                            $money(
                                $summary[
                                    'budget_amount'
                                ]
                            )
                        }}
                    @else
                        —
                    @endif
                </div>
            </div>

            <div class="er-card">
                <div class="er-card-label">
                    Preostalo
                </div>

                @php
                    $remaining =
                        $summary[
                            'remaining_budget'
                        ] ?? null;
                @endphp

                <div
                    class="er-card-value
                        {{
                            $remaining === null
                                ? ''
                                : (
                                    $remaining >= 0
                                        ? 'success'
                                        : 'danger'
                                )
                        }}"
                >
                    {{
                        $remaining === null
                            ? '—'
                            : $money(
                                $remaining
                            )
                    }}
                </div>
            </div>

            <div class="er-card">
                <div class="er-card-label">
                    Prosječni trošak
                </div>

                <div class="er-card-value">
                    {{
                        $money(
                            $summary[
                                'average'
                            ] ?? 0
                        )
                    }}
                </div>
            </div>

            <div class="er-card">
                <div class="er-card-label">
                    Najveći trošak
                </div>

                <div class="er-card-value">
                    {{
                        $money(
                            $summary[
                                'max_amount'
                            ] ?? 0
                        )
                    }}
                </div>
            </div>
        </div>

        {{-- MJESECI / KATEGORIJE --}}
        <div class="er-grid-2">

            <div class="er-panel">
                <div class="er-panel-title">
                    Troškovi po mjesecima
                </div>

                <div class="er-bars">
                    @foreach (
                        $monthly
                        as $row
                    )
                        <div class="er-bar-row">
                            <div class="er-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="er-track">
                                <div
                                    class="er-fill"
                                    style="
                                        width:
                                        {{
                                            min(
                                                100,
                                                (
                                                    $row['total']
                                                    / $maxMonthly
                                                ) * 100
                                            )
                                        }}%;
                                    "
                                ></div>
                            </div>

                            <div class="er-bar-value">
                                {{
                                    $money(
                                        $row['total']
                                    )
                                }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="er-panel">
                <div class="er-panel-title">
                    Troškovi po kategorijama
                </div>

                <div class="er-bars">
                    @forelse (
                        $byCategory
                        as $row
                    )
                        <div class="er-bar-row">
                            <div
                                class="er-bar-label"
                                title="{{ $row['label'] }}"
                            >
                                {{ $row['label'] }}
                            </div>

                            <div class="er-track">
                                <div
                                    class="er-fill"
                                    style="
                                        width:
                                        {{
                                            min(
                                                100,
                                                (
                                                    $row['total']
                                                    / $maxCategory
                                                ) * 100
                                            )
                                        }}%;
                                    "
                                ></div>
                            </div>

                            <div class="er-bar-value">
                                {{
                                    $money(
                                        $row['total']
                                    )
                                }}
                            </div>
                        </div>
                    @empty
                        <div
                            style="
                                padding:10px 0;
                                color:#64748b;
                                font-size:.8rem;
                            "
                        >
                            Nema podataka.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- USPOREDBA GODINA --}}
        @if (
            $comparison[
                'enabled'
            ] ?? false
        )
            <div class="er-panel">
                <div class="er-panel-title">
                    Usporedba troškova:
                    {{ $comparison['year'] }}
                    /
                    {{
                        $comparison[
                            'comparison_year'
                        ]
                    }}
                </div>

                <div class="er-comparison">
                    @foreach (
                        $comparison['months']
                        as $row
                    )
                        <div
                            class="er-comparison-row"
                        >
                            <div class="er-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="er-double-bars">
                                <div class="er-track">
                                    <div
                                        class="er-fill er-current"
                                        style="
                                            width:
                                            {{
                                                min(
                                                    100,
                                                    (
                                                        $row['current']
                                                        / $comparisonMax
                                                    ) * 100
                                                )
                                            }}%;
                                        "
                                    ></div>
                                </div>

                                <div class="er-track">
                                    <div
                                        class="er-fill er-previous"
                                        style="
                                            width:
                                            {{
                                                min(
                                                    100,
                                                    (
                                                        $row['comparison']
                                                        / $comparisonMax
                                                    ) * 100
                                                )
                                            }}%;
                                        "
                                    ></div>
                                </div>
                            </div>

                            <div
                                class="er-comparison-values"
                            >
                                <div>
                                    {{
                                        $money(
                                            $row[
                                                'current'
                                            ]
                                        )
                                    }}
                                </div>

                                <div>
                                    {{
                                        $money(
                                            $row[
                                                'comparison'
                                            ]
                                        )
                                    }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div
                    class="er-comparison-legend"
                >
                    <span class="er-legend">
                        <span
                            class="er-legend-dot"
                            style="
                                background:#f59e0b;
                            "
                        ></span>

                        {{
                            $comparison[
                                'year'
                            ]
                        }}
                    </span>

                    <span class="er-legend">
                        <span
                            class="er-legend-dot"
                            style="
                                background:#3b82f6;
                            "
                        ></span>

                        {{
                            $comparison[
                                'comparison_year'
                            ]
                        }}
                    </span>

                    <span>
                        Razlika:
                        <strong>
                            {{
                                $money(
                                    $comparison[
                                        'difference'
                                    ]
                                    ?? 0
                                )
                            }}
                        </strong>
                    </span>
                </div>
            </div>
        @endif

        {{-- GODINE / DOBAVLJAČI --}}
        <div class="er-grid-2">

            <div class="er-panel">
                <div class="er-panel-title">
                    Usporedba ukupnih troškova po godinama
                </div>

                <div class="er-bars">
                    @forelse (
                        $byYear
                        as $row
                    )
                        <div class="er-bar-row">
                            <div class="er-bar-label">
                                {{ $row['label'] }}
                            </div>

                            <div class="er-track">
                                <div
                                    class="er-fill"
                                    style="
                                        width:
                                        {{
                                            min(
                                                100,
                                                (
                                                    $row['total']
                                                    / $maxYear
                                                ) * 100
                                            )
                                        }}%;
                                    "
                                ></div>
                            </div>

                            <div class="er-bar-value">
                                {{
                                    $money(
                                        $row['total']
                                    )
                                }}
                            </div>
                        </div>
                    @empty
                        <div
                            style="
                                padding:10px 0;
                            "
                        >
                            Nema podataka.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="er-panel">
                <div class="er-panel-title">
                    Top dobavljači
                </div>

                <div class="er-bars">
                    @forelse (
                        $bySupplier
                        as $row
                    )
                        <div class="er-bar-row">
                            <div
                                class="er-bar-label"
                                title="{{ $row['label'] }}"
                            >
                                {{ $row['label'] }}
                            </div>

                            <div class="er-track">
                                <div
                                    class="er-fill"
                                    style="
                                        width:
                                        {{
                                            min(
                                                100,
                                                (
                                                    $row['total']
                                                    / $maxSupplier
                                                ) * 100
                                            )
                                        }}%;
                                    "
                                ></div>
                            </div>

                            <div class="er-bar-value">
                                {{
                                    $money(
                                        $row['total']
                                    )
                                }}
                            </div>
                        </div>
                    @empty
                        <div
                            style="
                                padding:10px 0;
                            "
                        >
                            Nema podataka.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- TOP TROŠKOVI --}}
        <div class="er-panel">
            <div class="er-panel-title">
                Najveći pojedinačni troškovi
            </div>

            <div class="er-table-wrap">
                <table class="er-table">
                    <thead>
                        <tr>
                            <th>Godina</th>
                            <th>Mjesec</th>
                            <th>Kategorija</th>
                            <th>Naziv troška</th>
                            <th>Dobavljač</th>
                            <th>Iznos</th>
                            <th>Realizirano</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse (
                            $topExpenses
                            as $row
                        )
                            <tr>
                                <td>
                                    {{ $row['year'] }}
                                </td>

                                <td>
                                    {{ $row['month'] }}
                                </td>

                                <td>
                                    {{ $row['category'] }}
                                </td>

                                <td>
                                    <strong>
                                        {{ $row['name'] }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $row['supplier'] }}
                                </td>

                                <td>
                                    <strong>
                                        {{
                                            $money(
                                                $row['amount']
                                            )
                                        }}
                                    </strong>
                                </td>

                                <td>
                                    @if (
                                        $row['realized']
                                    )
                                        <span
                                            class="er-badge success"
                                        >
                                            Da
                                        </span>
                                    @else
                                        <span
                                            class="er-badge gray"
                                        >
                                            Ne
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    Nema troškova za odabrane filtre.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>