@php
    use Illuminate\Support\Carbon;

    $percentage = (float) $zone->percentage;

    $percentageClass = match (true) {
        $percentage < 40 => 'score-red',
        $percentage < 60 => 'score-orange',
        $percentage < 80 => 'score-yellow',
        default => 'score-green',
    };

    $answers = $zone->answers
        ->sortBy(fn ($a) => $a->question?->sort_order ?? $a->question?->id ?? $a->id)
        ->values();
@endphp

<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>5S izvještaj - {{ $zone->name }}</title>

    <style>
        @page { margin: 18px 14px 30px 14px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #111827;
        }

        h1 {
            margin: 0 0 6px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .meta {
            margin-bottom: 10px;
            font-size: 8px;
            color: #374151;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .summary td {
            border: 1px solid #444;
            padding: 5px;
            font-weight: bold;
        }

        .score-box {
            display: inline-block;
            min-width: 45px;
            padding: 5px 8px;
            text-align: center;
            font-weight: bold;
            color: #fff;
        }

        .score-red { background: #ff0000; color: #ffffff; }
        .score-orange { background: #f59e0b; color: #111827; }
        .score-yellow { background: #ffff00; color: #000000; }
        .score-green { background: #00b050; color: #ffffff; }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #444;
            padding: 4px;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
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
    </style>
</head>

<body>
    <h1>5S izvještaj - {{ $zone->name }}</h1>

    <div class="meta">
        Datum izvoza: {{ now()->format('d.m.Y. H:i') }}
    </div>

    <table class="summary">
        <tr>
            <td>Zona: {{ $zone->name }}</td>
            <td>Ukupno bodova: {{ $zone->total_points }}</td>
            <td>Maksimalno bodova: {{ $zone->max_points }}</td>
            <td>
                Rezultat:
                <span class="score-box {{ $percentageClass }}">
                    {{ number_format($percentage, 0) }}%
                </span>
            </td>
        </tr>

        @if(filled($zone->note))
            <tr>
                <td colspan="4">Napomena zone: {{ $zone->note }}</td>
            </tr>
        @endif
    </table>

    <table>
        <thead>
            <tr>
                <th class="br">Br.</th>
                <th class="section">Skupina</th>
                <th class="question">Pitanje</th>
                <th class="score">Ocjena</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($answers as $answer)
                @php
                    $question = $answer->question;

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

                    $score = (int) ($answer->score ?? 0);
                @endphp

                <tr>
                    <td class="br">{{ $loop->iteration }}</td>
                    <td>{{ $group }}</td>
                    <td class="question">{{ $questionText }}</td>
                    <td class="score-cell score-{{ $score }}">{{ $score }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center">Nema odgovora za ovu zonu.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script('
                $font = $fontMetrics->get_font("DejaVu Sans", "normal");
                $size = 8;

                $pageText = "Str. " . $PAGE_NUM . " / " . $PAGE_COUNT;
                $dateText = "Ispis: {{ now()->format("d.m.Y.") }}";

                $pdf->text(18, 575, $dateText, $font, $size);

                $width = $fontMetrics->get_text_width($pageText, $font, $size);
                $pdf->text(820 - $width, 575, $pageText, $font, $size);
            ');
        }
    </script>
</body>
</html>