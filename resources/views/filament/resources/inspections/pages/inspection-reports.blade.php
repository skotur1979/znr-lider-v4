<x-filament-panels::page>

    @php
        $data =
            $this->report;

        $summary =
            $data['summary'] ?? [];

        $months =
            $data['months'] ?? [];

        $locations =
            $data['locations'] ?? [];

        $inspectors =
            $data['inspectors'] ?? [];

        $categories =
            $data['finding_categories'] ?? [];

        $findingTypes =
            $data['finding_types'] ?? [];

        $workflowStatuses =
            $data['workflow_statuses'] ?? [];

        $responsible =
            $data['responsible'] ?? [];

        $inspections =
            $data['inspections'] ?? [];

        $findings =
            $data['findings'] ?? [];

        $options =
            $data['options'] ?? [];

        $maxCount =
            function (
                array $rows
            ): int {
                return max(
                    1,
                    collect($rows)
                        ->max('count')
                    ?? 1
                );
            };

        $findingTypeLabel =
            function (
                ?string $value
            ): string {
                return match ($value) {
                    'ok' =>
                        'Uredno',

                    'recommendation' =>
                        'Preporuka',

                    'noncompliance' =>
                        'Nepravilnost',

                    'critical' =>
                        'Kritična nepravilnost',

                    default =>
                        '-',
                };
            };

        $workflowLabel =
            function (
                ?string $value
            ): string {
                return match ($value) {
                    'open' =>
                        'Nije započeto',

                    'in_progress' =>
                        'U tijeku',

                    'closed' =>
                        'Zatvoreno',

                    'resolved_no_action' =>
                        'Riješeno bez akcija',

                    'converted_to_observation' =>
                        'Pretvoreno u zapažanje',

                    'rejected' =>
                        'Odbačeno',

                    default =>
                        '-',
                };
            };
    @endphp


    <style>

    .inspection-report {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .ir-panel,
    .ir-card {
        background: rgb(255 255 255);
        border: 1px solid rgb(229 231 235);
        border-radius: 14px;
        overflow: hidden;
    }

    .dark .ir-panel,
    .dark .ir-card {
        background: rgb(24 24 27);
        border-color: rgba(255, 255, 255, .10);
    }

    .ir-title {
        padding: 14px 16px;
        border-bottom: 1px solid rgb(229 231 235);
        font-size: 15px;
        font-weight: 800;
    }

    .dark .ir-title {
        border-color: rgba(255, 255, 255, .10);
    }

    .ir-filters {
        display: grid;
        grid-template-columns:
            repeat(
                4,
                minmax(0, 1fr)
            );
        gap: 12px;
        padding: 16px;
    }

    .ir-label {
        display: block;
        margin-bottom: 5px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        color: rgb(107 114 128);
    }

    .ir-select {
        width: 100%;
        min-height: 38px;
        padding: 7px 10px;
        border-radius: 9px;
        border: 1px solid rgb(209 213 219);
        background: white;
        color: #111827;
    }

    .dark .ir-select {
        background: #111827;
        color: #f3f4f6;
        border-color: #374151;
    }

    .ir-actions {
        padding: 0 16px 16px;
    }

    .ir-reset {
        min-height: 38px;
        padding: 0 18px;
        border: 0;
        border-radius: 9px;
        background: #f59e0b;
        color: #111827;
        font-weight: 800;
        cursor: pointer;
    }

    .ir-cards {
        display: grid;
        grid-template-columns:
            repeat(
                4,
                minmax(0, 1fr)
            );
        gap: 12px;
    }

    .ir-card {
        padding: 15px;
        min-height: 98px;
    }

    .ir-card-label {
        font-size: 10px;
        text-transform: uppercase;
        font-weight: 800;
        color: #9ca3af;
    }

    .ir-card-value {
        margin-top: 7px;
        font-size: 26px;
        line-height: 1;
        font-weight: 900;
    }

    .ir-grid {
        display: grid;
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
        gap: 16px;
    }

    .ir-bars {
        padding: 15px;
    }

    .ir-bar-row {
        display: grid;
        grid-template-columns:
            minmax(
                130px,
                220px
            )
            1fr
            45px;

        gap: 10px;
        align-items: center;
        margin-bottom: 10px;
    }

    .ir-bar-label {
        font-size: 12px;
        font-weight: 700;
    }

    .ir-bar-track {
        height: 12px;
        border-radius: 999px;
        background:
            rgba(
                156,
                163,
                175,
                .20
            );
        overflow: hidden;
    }

    .ir-bar-fill {
        height: 100%;
        background: #f59e0b;
        border-radius: 999px;
    }

    .ir-bar-value {
        text-align: right;
        font-weight: 900;
    }

    .ir-table-wrap {
        overflow-x: auto;
    }

    .ir-table {
        width: 100%;
        min-width: 1000px;
        border-collapse: collapse;
        table-layout: auto;
    }

    /*
     * Sve ćelije u tablicama
     * centralno poravnate.
     */
    .ir-table th,
    .ir-table td {
        padding: 10px 12px;
        border-bottom: 1px solid rgb(229 231 235);
        font-size: 12px;
        vertical-align: middle;
        text-align: center;
        line-height: 1.45;
    }

    .dark .ir-table th,
    .dark .ir-table td {
        border-color:
            rgba(
                255,
                255,
                255,
                .08
            );
    }

    .ir-table th {
        font-weight: 800;
        background:
            rgba(
                156,
                163,
                175,
                .08
            );
        white-space: nowrap;
    }

    /*
     * Koristi se gdje želimo
     * izričito centriranje.
     */
    .ir-center {
        text-align: center !important;
    }

    /*
     * Samo duži opis nalaza
     * ostavljamo lijevo radi čitljivosti.
     */
    .ir-description {
        text-align: left !important;
        min-width: 260px;
        max-width: 420px;
        white-space: normal;
        word-break: normal;
        overflow-wrap: anywhere;
    }

    /*
     * Malo više prostora za lokaciju
     * i odgovornu osobu da se tekst
     * ne lomi nepotrebno.
     */
    .ir-location {
        min-width: 130px;
    }

    .ir-responsible {
        min-width: 150px;
    }

    /*
     * Datum, broj i rok ostaju
     * kompaktni i u jednom redu
     * gdje ima dovoljno prostora.
     */
    .ir-nowrap {
        white-space: nowrap;
    }

    .ir-empty {
        padding: 30px;
        text-align: center;
        color: #9ca3af;
    }

    .ir-danger {
        color: #ef4444;
        font-weight: 800;
    }

    @media (
        max-width: 1200px
    ) {
        .ir-cards {
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
        }

        .ir-filters {
            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );
        }
    }

    @media (
        max-width: 750px
    ) {
        .ir-cards,
        .ir-grid,
        .ir-filters {
            grid-template-columns: 1fr;
        }
    }

</style>


    <div class="inspection-report">


        {{-- FILTRI --}}

        <div class="ir-panel">

            <div class="ir-title">
                Filtri izvještaja
            </div>


            <div class="ir-filters">

                <div>

                    <label class="ir-label">
                        Godina
                    </label>

                    <select
                        wire:model.live="year"
                        class="ir-select"
                    >

                        <option value="all">
                            Sve godine
                        </option>

                        @foreach(
                            $options['years']
                            ?? []
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


                <div>

                    <label class="ir-label">
                        Mjesec
                    </label>

                    <select
                        wire:model.live="month"
                        class="ir-select"
                    >

                        <option value="all">
                            Svi mjeseci
                        </option>

                        @foreach([
                            1 => 'Siječanj',
                            2 => 'Veljača',
                            3 => 'Ožujak',
                            4 => 'Travanj',
                            5 => 'Svibanj',
                            6 => 'Lipanj',
                            7 => 'Srpanj',
                            8 => 'Kolovoz',
                            9 => 'Rujan',
                            10 => 'Listopad',
                            11 => 'Studeni',
                            12 => 'Prosinac',
                        ] as $value => $label)

                            <option
                                value="{{ $value }}"
                            >
                                {{ $label }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div>

                    <label class="ir-label">
                        Lokacija
                    </label>

                    <select
                        wire:model.live="location"
                        class="ir-select"
                    >

                        <option value="">
                            Sve lokacije
                        </option>

                        @foreach(
                            $options['locations']
                            ?? []
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


                <div>

                    <label class="ir-label">
                        Proveo nadzor
                    </label>

                    <select
                        wire:model.live="performed_by"
                        class="ir-select"
                    >

                        <option value="">
                            Sve osobe
                        </option>

                        @foreach(
                            $options['performed_by']
                            ?? []
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


                <div>

                    <label class="ir-label">
                        Područje nalaza
                    </label>

                    <select
                        wire:model.live="category"
                        class="ir-select"
                    >

                        <option value="">
                            Sva područja
                        </option>

                        @foreach(
                            $options['categories']
                            ?? []
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


                <div>

                    <label class="ir-label">
                        Vrsta nalaza
                    </label>

                    <select
                        wire:model.live="finding_status"
                        class="ir-select"
                    >

                        <option value="">
                            Sve vrste
                        </option>

                        <option value="ok">
                            Uredno
                        </option>

                        <option value="recommendation">
                            Preporuka
                        </option>

                        <option value="noncompliance">
                            Nepravilnost
                        </option>

                        <option value="critical">
                            Kritična nepravilnost
                        </option>

                    </select>

                </div>


                <div>

                    <label class="ir-label">
                        Status postupanja
                    </label>

                    <select
                        wire:model.live="workflow_status"
                        class="ir-select"
                    >

                        <option value="">
                            Svi statusi
                        </option>

                        <option value="open">
                            Nije započeto
                        </option>

                        <option value="in_progress">
                            U tijeku
                        </option>

                        <option value="closed">
                            Zatvoreno
                        </option>

                        <option value="resolved_no_action">
                            Riješeno bez akcija
                        </option>

                        <option value="converted_to_observation">
                            Pretvoreno u zapažanje
                        </option>

                        <option value="rejected">
                            Odbačeno
                        </option>

                    </select>

                </div>


                <div>

                    <label class="ir-label">
                        Odgovorna osoba
                    </label>

                    <select
                        wire:model.live="responsible_person"
                        class="ir-select"
                    >

                        <option value="">
                            Sve osobe
                        </option>

                        @foreach(
                            $options['responsible']
                            ?? []
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

            </div>


            <div class="ir-actions">

                <button
                    type="button"
                    wire:click="resetFilters"
                    class="ir-reset"
                >
                    Poništi filtre
                </button>

            </div>

        </div>


        {{-- KPI KARTICE --}}

        <div class="ir-cards">

            @foreach([
                [
                    'Ukupno nadzora',
                    'inspections'
                ],
                [
                    'Ukupno nalaza',
                    'findings'
                ],
                [
                    'Uredno',
                    'ok'
                ],
                [
                    'Preporuke',
                    'recommendations'
                ],
                [
                    'Nepravilnosti',
                    'noncompliances'
                ],
                [
                    'Kritični nalazi',
                    'critical'
                ],
                [
                    'Nije započeto',
                    'open'
                ],
                [
                    'U tijeku',
                    'in_progress'
                ],
                [
                    'Zatvoreno',
                    'closed'
                ],
                [
                    'Riješeno bez akcija',
                    'resolved_no_action'
                ],
                [
                    'Pretvoreno u zapažanje',
                    'converted_to_observation'
                ],
                [
                    'Istekao rok',
                    'overdue'
                ],
            ] as [$label, $key])

                <div class="ir-card">

                    <div class="ir-card-label">
                        {{ $label }}
                    </div>

                    <div
                        class="
                            ir-card-value
                            {{
                                $key === 'overdue'
                                || $key === 'critical'
                                    ? 'ir-danger'
                                    : ''
                            }}
                        "
                    >
                        {{
                            $summary[$key]
                            ?? 0
                        }}
                    </div>

                </div>

            @endforeach

        </div>


        {{-- ANALIZE --}}

        <div class="ir-grid">

            @foreach([
                [
                    'Nadzori po mjesecima',
                    $months
                ],
                [
                    'Nadzori po lokacijama',
                    $locations
                ],
                [
                    'Nadzori prema osobi koja ih je provela',
                    $inspectors
                ],
                [
                    'Nalazi po područjima',
                    $categories
                ],
                [
                    'Vrste nalaza',
                    $findingTypes
                ],
                [
                    'Status postupanja',
                    $workflowStatuses
                ],
                [
                    'Nalazi prema odgovornoj osobi',
                    $responsible
                ],
            ] as [$title, $rows])

                <div class="ir-panel">

                    <div class="ir-title">
                        {{ $title }}
                    </div>


                    <div class="ir-bars">

                        @php
                            $max =
                                $maxCount(
                                    $rows
                                );
                        @endphp


                        @forelse(
                            $rows
                            as $row
                        )

                            @php
                                $width =
                                    $max > 0
                                        ? (
                                            (
                                                $row['count']
                                                ?? 0
                                            )
                                            / $max
                                        ) * 100
                                        : 0;
                            @endphp


                            <div class="ir-bar-row">

                                <div class="ir-bar-label">

                                    {{
                                        $row['label']
                                        ?? '-'
                                    }}

                                </div>


                                <div class="ir-bar-track">

                                    <div
                                        class="ir-bar-fill"
                                        style="
                                            width:{{
                                                $width
                                            }}%;
                                        "
                                    ></div>

                                </div>


                                <div class="ir-bar-value">

                                    {{
                                        $row['count']
                                        ?? 0
                                    }}

                                </div>

                            </div>


                        @empty

                            <div class="ir-empty">
                                Nema podataka.
                            </div>

                        @endforelse

                    </div>

                </div>

            @endforeach

        </div>


        {{-- POPIS NADZORA --}}

        <div class="ir-panel">

            <div class="ir-title">
                Pregled nadzora
            </div>


            @if(count($inspections))

                <div class="ir-table-wrap">

                    <table class="ir-table">

                        <thead>

                            <tr>

                                <th>
                                    Datum
                                </th>

                                <th>
                                    Broj
                                </th>

                                <th>
                                    Naziv
                                </th>

                                <th>
                                    Lokacija
                                </th>

                                <th>
                                    Proveo nadzor
                                </th>

                                <th class="ir-center">
                                    Broj nalaza
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach(
                                $inspections
                                as $row
                            )

                                <tr>

                                    <td class="ir-nowrap">
                                        {{
                                            $row['date']
                                                ? \Illuminate\Support\Carbon::parse(
                                                    $row['date']
                                                )->format('d.m.Y.')
                                                : '-'
                                        }}
                                    </td>

                                    <td class="ir-nowrap">
                                        <strong>
                                            {{
                                                $row['number']
                                                ?? '-'
                                            }}
                                        </strong>
                                    </td>

                                    <td>
                                        {{
                                            $row['title']
                                            ?? '-'
                                        }}
                                    </td>

                                    <td class="ir-location">
                                        {{
                                            $row['location']
                                            ?? '-'
                                        }}
                                    </td>

                                    <td class="ir-responsible">
                                        {{
                                            $row['performed_by']
                                            ?? '-'
                                        }}
                                    </td>

                                    <td class="ir-center">
                                        {{
                                            $row['findings_count']
                                            ?? 0
                                        }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


            @else

                <div class="ir-empty">
                    Nema nadzora za odabrane filtre.
                </div>

            @endif

        </div>


        {{-- DETALJNI NALAZI --}}

        <div class="ir-panel">

            <div class="ir-title">
                Pregled nalaza
            </div>


            @if(count($findings))

                <div class="ir-table-wrap">

                    <table class="ir-table">

                        <thead>

                            <tr>

                                <th>
                                    Datum
                                </th>

                                <th>
                                    Nadzor
                                </th>

                                <th>
                                    Lokacija
                                </th>

                                <th>
                                    Područje
                                </th>

                                <th>
                                    Nalaz
                                </th>

                                <th>
                                    Vrsta
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Odgovorna osoba
                                </th>

                                <th>
                                    Rok
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach(
                                $findings
                                as $row
                            )

                                <tr>

                                    <td class="ir-nowrap">
                                        {{
                                            $row['inspection_date']
                                                ? \Illuminate\Support\Carbon::parse(
                                                    $row['inspection_date']
                                                )->format('d.m.Y.')
                                                : '-'
                                        }}
                                    </td>

                                    <td class="ir-nowrap">
                                        <strong>
                                            {{
                                                $row['inspection_number']
                                                ?? '-'
                                            }}
                                        </strong>
                                    </td>

                                    <td class="ir-location">
                                        {{
                                            $row['location']
                                            ?? '-'
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $row['category']
                                            ?? '-'
                                        }}
                                    </td>

                                    <td class="ir-description">
                                        {{
                                            $row['description']
                                            ?? '-'
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $findingTypeLabel(
                                                $row['finding_status']
                                                ?? null
                                            )
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $workflowLabel(
                                                $row['workflow_status']
                                                ?? null
                                            )
                                        }}
                                    </td>

                                    <td class="ir-responsible">
                                        {{
                                            $row['responsible_person']
                                            ?? '-'
                                        }}
                                    </td>

                                    <td class="ir-nowrap">
                                        @if(
                                            ! empty(
                                                $row['due_date']
                                            )
                                        )

                                            @php
                                                $due =
                                                    \Illuminate\Support\Carbon::parse(
                                                        $row['due_date']
                                                    );

                                                $isOverdue =
                                                    ! in_array(
                                                        $row['workflow_status'],
                                                        [
                                                            'closed',
                                                            'resolved_no_action',
                                                            'rejected',
                                                        ],
                                                        true
                                                    )
                                                    && $due
                                                        ->copy()
                                                        ->startOfDay()
                                                        ->lt(
                                                            now()->startOfDay()
                                                        );
                                            @endphp

                                            <span
                                                class="{{
                                                    $isOverdue
                                                        ? 'ir-danger'
                                                        : ''
                                                }}"
                                            >
                                                {{
                                                    $due->format(
                                                        'd.m.Y.'
                                                    )
                                                }}
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

                <div class="ir-empty">
                    Nema nalaza za odabrane filtre.
                </div>

            @endif

        </div>

    </div>

</x-filament-panels::page>