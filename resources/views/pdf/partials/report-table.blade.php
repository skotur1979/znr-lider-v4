<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Izvještaj' }}</title>

    <style>
        @page { margin: 16px 12px 28px 12px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7px;
            color: #111827;
        }

        h1 {
            margin: 0 0 6px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .meta {
            margin-bottom: 8px;
            font-size: 8px;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #444;
            padding: 3px;
            vertical-align: middle;
            overflow: hidden;
            word-wrap: break-word;
        }

        th {
            background: #eeeeee;
            font-weight: bold;
        }

        .br {
            width: 16px;
            text-align: center;
            font-size: 6px;
            padding: 1px;
            white-space: nowrap;
        }

        .center { text-align: center; }
        .wrap { word-break: break-word; }
        .pre-line { white-space: pre-line; line-height: 1.25; }

        .pikt-wrap {
            text-align: center;
            line-height: 1;
        }

        .pikt-wrap img {
            width: 16px;
            height: 16px;
            margin: 1px;
        }

        @isset($extraStyles)
            {!! $extraStyles !!}
        @endisset
    </style>
</head>

<body>

<h1>{{ $title ?? 'Izvještaj' }}</h1>

<div class="meta">
    Datum izvoza: {{ now()->format('d.m.Y. H:i') }}
</div>

<table>
    <thead>
    <tr>
        <th class="br">Br.</th>

        @foreach ($columns as $column)
            <th class="{{ $column['class'] ?? '' }}" style="{{ isset($column['width']) ? 'width:' . $column['width'] . ';' : '' }}">
                {{ $column['label'] }}
            </th>
        @endforeach
    </tr>
    </thead>

    <tbody>
    @forelse ($rows as $row)
        <tr>
            <td class="br">{{ $loop->iteration }}</td>

            @foreach ($columns as $column)
                @php
                    $key = $column['key'];
                    $value = $row[$key] ?? '';
                @endphp

                <td class="{{ $column['tdClass'] ?? $column['class'] ?? 'wrap' }}" style="{{ isset($column['width']) ? 'width:' . $column['width'] . ';' : '' }}">
                    {!! $value !!}
                </td>
            @endforeach
        </tr>
    @empty
        <tr>
            <td colspan="{{ count($columns) + 1 }}" class="center">
                Nema podataka za izvoz.
            </td>
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