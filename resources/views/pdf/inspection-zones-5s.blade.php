@php
    $zones = $zones
        ->sortBy('sort_order')
        ->values();

    $totalPoints =
        (float) $zones->sum(
            'total_points'
        );

    $maxPoints =
        (float) $zones->sum(
            'max_points'
        );

    $overallPercentage =
        $maxPoints > 0
            ? ($totalPoints / $maxPoints) * 100
            : 0;

    $overallClass = match (true) {
        $overallPercentage < 40 =>
            'score-red',

        $overallPercentage < 60 =>
            'score-orange',

        $overallPercentage < 80 =>
            'score-yellow',

        default =>
            'score-green',
    };
@endphp

<!doctype html>

<html lang="hr">

<head>

    <meta charset="utf-8">

    <title>
        5S izvještaj nadzora
        {{ $inspection->number ?? $inspection->id }}
    </title>

    <style>

        @page {
            margin: 18px 14px 30px 14px;
        }

        body {
            font-family:
                DejaVu Sans,
                sans-serif;

            font-size: 8px;
            color: #111827;
        }

        h1 {
            margin: 0 0 5px 0;
            font-size: 17px;
            font-weight: bold;
        }

        h2 {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
        }

        .meta {
            margin-bottom: 10px;
            color: #374151;
            font-size: 8px;
        }

        .inspection-summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .inspection-summary td {
            border: 1px solid #444;
            padding: 5px;
            vertical-align: middle;
        }

        .inspection-summary .label {
            font-weight: bold;
            background: #eeeeee;
        }

        .zones-summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .zones-summary th,
        .zones-summary td {
            border: 1px solid #444;
            padding: 5px;
            vertical-align: middle;
        }

        .zones-summary th {
            background: #1f2937;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }

        .zone-block {
            margin-top: 12px;
        }

        .zone-heading {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .zone-heading td {
            border: 1px solid #444;
            padding: 5px;
            vertical-align: middle;
        }

        .zone-title {
            background: #eeeeee;
            font-weight: bold;
            font-size: 11px;
        }

        table.questions {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.questions th,
        table.questions td {
            border: 1px solid #444;
            padding: 4px;
            vertical-align: top;
            word-wrap: break-word;
        }

        table.questions th {
            background: #eeeeee;
            font-weight: bold;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .br {
            width: 4%;
            text-align: center;
            font-size: 7px;
        }

        .section {
            width: 16%;
        }

        .question {
            width: 68%;
            font-weight: bold;
        }

        .score {
            width: 12%;
        }

        .score-box {
            display: inline-block;
            min-width: 45px;
            padding: 5px 8px;
            text-align: center;
            font-weight: bold;
        }

        .score-red {
            background: #991b1b;
            color: #ffffff;
        }

        .score-orange {
            background: #f59e0b;
            color: #111827;
        }

        .score-yellow {
            background: #fde047;
            color: #111827;
        }

        .score-green {
            background: #16a34a;
            color: #ffffff;
        }

        .score-cell {
            font-weight: bold;
            text-align: center;
        }

        .score-0,
        .score-1,
        .score-2 {
            background: #ff0000;
            color: #ffffff;
        }

        .score-3 {
            background: #ffff00;
            color: #000000;
        }

        .score-4,
        .score-5 {
            background: #00b050;
            color: #ffffff;
        }

        .zone-note {
            margin-top: 5px;
            padding: 5px;
            border: 1px solid #444;
        }

        .zone-note strong {
            font-weight: bold;
        }

        /*
         * Svaka nova zona može krenuti
         * na novoj stranici kada je prethodna
         * zona velika.
         */
        .page-break {
            page-break-before: always;
        }

    </style>

</head>

<body>

    <h1>
        5S izvještaj nadzora
        {{ $inspection->number ?? $inspection->id }}
    </h1>

    <div class="meta">
        Datum izvoza:
        {{ now()->format('d.m.Y. H:i') }}
    </div>


    {{-- OSNOVNI PODACI NADZORA --}}

    <table class="inspection-summary">

        <tr>

            <td class="label">
                Broj nadzora
            </td>

            <td>
                {{ $inspection->number ?? '-' }}
            </td>

            <td class="label">
                Datum nadzora
            </td>

            <td>
                {{
                    $inspection->performed_at
                        ? $inspection->performed_at
                            ->format('d.m.Y.')
                        : '-'
                }}
            </td>

        </tr>


        <tr>

            <td class="label">
                Naziv nadzora
            </td>

            <td>
                {{ $inspection->title ?? '-' }}
            </td>

            <td class="label">
                Lokacija
            </td>

            <td>
                {{ $inspection->location ?? '-' }}
            </td>

        </tr>


        <tr>

            <td class="label">
                Ukupno bodova
            </td>

            <td>
                {{
                    number_format(
                        $totalPoints,
                        0
                    )
                }}
            </td>

            <td class="label">
                Maksimalno bodova
            </td>

            <td>
                {{
                    number_format(
                        $maxPoints,
                        0
                    )
                }}
            </td>

        </tr>


        <tr>

            <td class="label">
                Broj zona
            </td>

            <td>
                {{ $zones->count() }}
            </td>

            <td class="label">
                Ukupni 5S rezultat
            </td>

            <td>

                <span
                    class="
                        score-box
                        {{ $overallClass }}
                    "
                >
                    {{
                        number_format(
                            $overallPercentage,
                            0
                        )
                    }}%
                </span>

            </td>

        </tr>

    </table>


    {{-- SAŽETAK REZULTATA ZONA --}}

    <h2>
        Rezultati 5S zona
    </h2>

    <br>

    <table class="zones-summary">

        <thead>

            <tr>

                <th>
                    Zona
                </th>

                <th>
                    Bodovi
                </th>

                <th>
                    Max
                </th>

                <th>
                    Rezultat
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse ($zones as $zone)

                @php
                    $percentage =
                        (float) (
                            $zone->percentage
                            ?? 0
                        );

                    $percentageClass =
                        match (true) {
                            $percentage < 40 =>
                                'score-red',

                            $percentage < 60 =>
                                'score-orange',

                            $percentage < 80 =>
                                'score-yellow',

                            default =>
                                'score-green',
                        };
                @endphp

                <tr>

                    <td>
                        <strong>
                            {{ $zone->name }}
                        </strong>
                    </td>

                    <td class="center">
                        {{
                            $zone->total_points
                            ?? 0
                        }}
                    </td>

                    <td class="center">
                        {{
                            $zone->max_points
                            ?? 0
                        }}
                    </td>

                    <td class="center">

                        <span
                            class="
                                score-box
                                {{ $percentageClass }}
                            "
                        >
                            {{
                                number_format(
                                    $percentage,
                                    0
                                )
                            }}%
                        </span>

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="4"
                        class="center"
                    >
                        Nema 5S zona.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>


    {{-- DETALJI SVIH ZONA --}}

    @foreach ($zones as $zone)

        @php

            $percentage =
                (float) (
                    $zone->percentage
                    ?? 0
                );

            $percentageClass =
                match (true) {
                    $percentage < 40 =>
                        'score-red',

                    $percentage < 60 =>
                        'score-orange',

                    $percentage < 80 =>
                        'score-yellow',

                    default =>
                        'score-green',
                };

            $answers =
                $zone->answers
                    ->sortBy(
                        fn ($answer) =>
                            $answer
                                ->question
                                ?->sort_order
                            ?? $answer
                                ->question
                                ?->id
                            ?? $answer->id
                    )
                    ->values();

        @endphp


        @if (! $loop->first)

            <div class="page-break"></div>

        @endif


        <div class="zone-block">


            <table class="zone-heading">

                <tr>

                    <td
                        class="zone-title"
                        colspan="4"
                    >
                        Zona:
                        {{ $zone->name }}
                    </td>

                </tr>


                <tr>

                    <td>
                        <strong>
                            Bodovi:
                        </strong>

                        {{
                            $zone->total_points
                            ?? 0
                        }}
                    </td>

                    <td>
                        <strong>
                            Max:
                        </strong>

                        {{
                            $zone->max_points
                            ?? 0
                        }}
                    </td>

                    <td colspan="2">

                        <strong>
                            Rezultat:
                        </strong>

                        <span
                            class="
                                score-box
                                {{ $percentageClass }}
                            "
                        >
                            {{
                                number_format(
                                    $percentage,
                                    0
                                )
                            }}%
                        </span>

                    </td>

                </tr>

            </table>


            @if (filled($zone->note))

                <div class="zone-note">

                    <strong>
                        Napomena zone:
                    </strong>

                    {{ $zone->note }}

                </div>

                <br>

            @endif


            <table class="questions">

                <thead>

                    <tr>

                        <th class="br">
                            Br.
                        </th>

                        <th class="section">
                            Skupina
                        </th>

                        <th class="question">
                            Pitanje
                        </th>

                        <th class="score">
                            Ocjena
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse (
                        $answers
                        as $answer
                    )

                        @php

                            $question =
                                $answer
                                    ->question;

                            $questionText =
                                $question->question
                                ?? $question->text
                                ?? $question->title
                                ?? $question->name
                                ?? '';

                            $group =
                                $question->group
                                ?? $question->category
                                ?? $question->section
                                ?? '';

                            $score =
                                (int) (
                                    $answer->score
                                    ?? 0
                                );

                        @endphp


                        <tr>

                            <td class="br">
                                {{
                                    $loop
                                        ->iteration
                                }}
                            </td>

                            <td>
                                {{ $group }}
                            </td>

                            <td class="question">
                                {{
                                    $questionText
                                }}
                            </td>

                            <td
                                class="
                                    score-cell
                                    score-{{ $score }}
                                "
                            >
                                {{ $score }}
                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="4"
                                class="center"
                            >
                                Nema odgovora za ovu zonu.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    @endforeach


    {{-- FOOTER --}}

    <script type="text/php">

        if (isset($pdf)) {

            $pdf->page_script('

                $font =
                    $fontMetrics->get_font(
                        "DejaVu Sans",
                        "normal"
                    );

                $size = 8;

                $pageText =
                    "Str. "
                    . $PAGE_NUM
                    . " / "
                    . $PAGE_COUNT;

                $dateText =
                    "Ispis: {{ now()->format("d.m.Y.") }}";

                $pdf->text(
                    18,
                    575,
                    $dateText,
                    $font,
                    $size
                );

                $width =
                    $fontMetrics
                        ->get_text_width(
                            $pageText,
                            $font,
                            $size
                        );

                $pdf->text(
                    820 - $width,
                    575,
                    $pageText,
                    $font,
                    $size
                );

            ');

        }

    </script>

</body>

</html>