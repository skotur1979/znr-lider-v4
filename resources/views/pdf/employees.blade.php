<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>Zaposlenici</title>
    @include('pdf.partials.styles')
</head>
<body>
@php
    use Illuminate\Support\Carbon;
    $today = Carbon::today();

    $fmt = fn($d) => $d ? Carbon::parse($d)->format('d.m.Y.') : '';

    $rokClass = function ($d) use ($today) {
        if (! $d) return '';
        $dt = Carbon::parse($d);

        if ($dt->lt($today)) return 'rok-expired';
        if ($dt->lte($today->copy()->addDays(30))) return 'rok-soon';

        return '';
    };

    $certSummary = function ($e) {
        $certs = $e->certificates?->sortBy('valid_until') ?? collect();
        if ($certs->isEmpty()) return '';

        $parts = [];
        foreach ($certs as $c) {
            $title = trim((string) $c->title);
            if ($title === '') continue;

            $until = $c->valid_until ? \Illuminate\Support\Carbon::parse($c->valid_until) : null;
            $parts[] = $until ? ($title . ' (do ' . $until->format('d.m.Y.') . ')') : $title;

            // rez da ne pobjegne (PDF)
            if (mb_strlen(implode(', ', $parts)) > 170) {
                $parts[] = '…';
                break;
            }
        }

        return implode(', ', $parts);
    };
@endphp

<h1>Zaposlenici</h1>
<div class="meta">
    Datum izvoza: {{ now()->format('d.m.Y. H:i') }}
</div>

<table>
    <thead>
    <tr>
        <th class="center" style="width: 35px;">Red.br.</th>

        <th style="width: 170px;">Ime i prezime</th>
        <th style="width: 160px;">Radno mjesto</th>

        <th class="center" style="width: 85px;">Liječnički (do)</th>
        <th style="width: 150px;">Članak 3. točke</th>

        {{-- ✅ nova polja --}}
        <th class="center" style="width: 85px;">Zaštita na radu (od)</th>
        <th class="center" style="width: 85px;">Prva pomoć (od)</th>

        <th class="center" style="width: 95px;">Toksikologija (do)</th>
        <th class="center" style="width: 110px;">Ovlaštenik ZNR (do)</th>

        <th>Ostale edukacije</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($employees as $e)
        @php
            $medDo  = $e->medical_examination_valid_until;
            $toxDo  = $e->toxicology_valid_until;
            $authDo = $e->employers_authorization_valid_until;
        @endphp

        <tr>
            <td class="center">{{ $loop->iteration }}</td>

            <td class="wrap"><strong>{{ $e->name }}</strong></td>
            <td class="wrap">{{ $e->workplace }}</td>

            <td class="center {{ $rokClass($medDo) }}">{{ $fmt($medDo) }}</td>
            <td class="wrap">{{ $e->article }}</td>

            <td class="center">{{ $fmt($e->occupational_safety_valid_from) }}</td>
            <td class="center">{{ $fmt($e->first_aid_valid_from) }}</td>

            <td class="center {{ $rokClass($toxDo) }}">{{ $fmt($toxDo) }}</td>
            <td class="center {{ $rokClass($authDo) }}">{{ $fmt($authDo) }}</td>

            <td class="wrap">{{ $certSummary($e) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- ✅ Broj stranice bez utjecaja na layout tablice (isto kao Machines) --}}
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