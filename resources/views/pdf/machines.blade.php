<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>Radna oprema</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 10px 0; }
        .meta { font-size: 10px; margin-bottom: 10px; color: #333; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #222; padding: 4px 6px; vertical-align: middle; }
        th { background: #eee; font-weight: bold; text-align: left; }

        .center { text-align: center; }
        .right  { text-align: right; }

        /* jake boje kao marker */
        .rok-expired { background: #ff0000; color: #ffffff; font-weight: bold; }
        .rok-soon    { background: #ffff00; color: #000000; font-weight: bold; }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Carbon;
    $today = Carbon::today();
@endphp

<h1>Radna oprema</h1>
<div class="meta">
    Datum izvoza: {{ now()->format('d.m.Y. H:i') }}
</div>

<table>
    <thead>
    <tr>
        <th class="center" style="width: 35px;">Red.br.</th>
        <th>Naziv</th>
        <th>Proizvođač</th>
        <th class="center">Tvornički broj</th>
        <th class="center">Inventarni broj</th>
        <th class="center">Vrijedi od</th>
        <th class="center">Vrijedi do</th>
        <th>Ispitao</th>
        <th class="center">Broj izvještaja</th>
        <th>Lokacija</th>
        <th>Napomena</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($machines as $m)
        @php
            $from  = $m->examination_valid_from ? Carbon::parse($m->examination_valid_from) : null;
            $until = $m->examination_valid_until ? Carbon::parse($m->examination_valid_until) : null;

            $rokClass = '';
            if ($until) {
                if ($until->lt($today)) {
                    $rokClass = 'rok-expired';
                } elseif ($until->lte($today->copy()->addDays(30))) {
                    $rokClass = 'rok-soon';
                }
            }
        @endphp

        <tr>
            <td class="center">{{ $loop->iteration }}</td>
            <td>{{ $m->name }}</td>
            <td>{{ $m->manufacturer }}</td>
            <td class="center">{{ $m->factory_number }}</td>
            <td class="center">{{ $m->inventory_number }}</td>
            <td class="center">{{ $from ? $from->format('d.m.Y.') : '' }}</td>
            <td class="center {{ $rokClass }}">{{ $until ? $until->format('d.m.Y.') : '' }}</td>
            <td>{{ $m->examined_by }}</td>
            <td class="center">{{ $m->report_number }}</td>
            <td>{{ $m->location }}</td>
            <td>{{ $m->remark }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- ✅ Broj stranice bez utjecaja na layout tablice --}}
<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script('
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 9;

        $pageText = "Str. " . $PAGE_NUM . "/" . $PAGE_COUNT;
        $dateText = "Ispis: {{ now()->format("d.m.Y.") }}";

        // lijevo datum
        $pdf->text(18, 570, $dateText, $font, $size);

        // desno broj stranice (x/y)
        $width = $fontMetrics->get_text_width($pageText, $font, $size);
        $pdf->text(820 - $width, 570, $pageText, $font, $size);
    ');
}
</script>

</body>
</html>

