<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>Vatrogasni aparati</title>

   @include('pdf.partials.styles')
</head>
<body>
@php
    use Carbon\Carbon;
    $today = Carbon::today();
@endphp

<h2>Vatrogasni aparati</h2>
<div class="meta">Datum izvoza: {{ now()->format('d.m.Y. H:i') }}</div>
<table>
    <thead>
    <tr>
        <th class="center" style="width: 35px;">Br.</th>
        <th>Mjesto</th>
        <th class="center" style="width: 55px;">Tip</th>
        <th class="center" style="width: 85px;">Tvorn. broj</th>
        <th class="center" style="width: 85px;">Ser. broj</th>
        <th class="center" style="width: 85px;">Periodički servis</th>
        <th class="center" style="width: 75px;">Vrijedi do</th>
        <th style="width: 90px;">Serviser</th>
        <th class="center" style="width: 85px;">Redovni pregled</th>
        <th class="center" style="width: 60px;">Uočljivost</th>
        <th style="width: 110px;">Nedostaci</th>
        <th style="width: 120px;">Otklanjanje</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($fires as $f)
        @php
            $serviceFrom  = $f->examination_valid_from ? Carbon::parse($f->examination_valid_from) : null;
            $until        = $f->examination_valid_until ? Carbon::parse($f->examination_valid_until) : null;
            $regularFrom  = $f->regular_examination_valid_from ? Carbon::parse($f->regular_examination_valid_from) : null;

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
            <td>{{ $f->place }}</td>
            <td class="center">{{ $f->type }}</td>
            <td class="center">{{ $f->factory_number_year_of_production }}</td>
            <td class="center">{{ $f->serial_label_number }}</td>
            <td class="center">{{ $serviceFrom ? $serviceFrom->format('d.m.Y.') : '' }}</td>
            <td class="center {{ $rokClass }}">{{ $until ? $until->format('d.m.Y.') : '' }}</td>
            <td>{{ $f->service }}</td>
            <td class="center">{{ $regularFrom ? $regularFrom->format('d.m.Y.') : '' }}</td>
            <td class="center">{{ $f->visible }}</td>
            <td>{{ $f->remark }}</td>
            <td>{{ $f->action }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- ✅ Broj stranice + datum ispisa (bez utjecaja na layout tablice) --}}
<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script('
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 9;

        $pageText = "Str. " . $PAGE_NUM . "/" . $PAGE_COUNT;
        $dateText = "Ispis: {{ now()->format("d.m.Y.") }}";

        // lijevo datum
        $pdf->text(18, 570, $dateText, $font, $size);

        // desno broj stranice
        $width = $fontMetrics->get_text_width($pageText, $font, $size);
        $pdf->text(820 - $width, 570, $pageText, $font, $size);
    ');
}
</script>

</body>
</html>