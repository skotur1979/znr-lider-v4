<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>Incidenti</title>
    @include('pdf.partials.styles')
</head>
<body>
@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $today = Carbon::today();

    $typeLabel = fn ($state) => match ($state) {
        'LTA' => 'LTA – Ozljeda na radu',
        'MTA' => 'MTA – Pružanje PP izvan tvrtke',
        'FAA' => 'FAA – Pružanje PP u tvrtki',
        default => (string) $state,
    };

    $employmentLabel = fn ($state) => match ($state) {
        'Permanent' => 'Stalni',
        'Temporary' => 'Privremeni',
        default => (string) $state,
    };
@endphp

<h1>Incidenti</h1>
<div class="meta">
    Datum izvoza: {{ now()->format('d.m.Y. H:i') }}
</div>

<table>
    <thead>
    <tr>
        <th class="center" style="width:35px;">Red.br.</th>
        <th style="width:120px;">Lokacija</th>
        <th style="width:150px;">Vrsta incidenta</th>
        <th class="center" style="width:85px;">Datum nastanka</th>
        <th class="center" style="width:95px;">Povratak</th>
        <th class="center" style="width:85px;">Izgubljeni dani</th>
        <th style="width:140px;">Ozlijeđeni dio tijela</th>
        <th style="width:150px;">Uzrok</th>
        <th style="width:150px;">Tip ozljede</th>
        <th style="width:160px;">Napomena</th>
        <th class="center" style="width:70px;">Slika</th>
        <th class="center" style="width:70px;">Prilozi</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($incidents as $i)
        @php
            $occurred = $i->date_occurred ? Carbon::parse($i->date_occurred) : null;
            $return   = $i->date_of_return ? Carbon::parse($i->date_of_return) : null;

            // boja povratka (ako postoji) – crveno ako je prošlo, žuto ako je uskoro (30 dana)
            $returnClass = '';
            if ($return) {
                if ($return->lt($today)) {
                    $returnClass = 'rok-expired';
                } elseif ($return->lte($today->copy()->addDays(30))) {
                    $returnClass = 'rok-soon';
                }
            }

            // ✅ dompdf-friendly slika (base64) iz storage/app/public/...
            $imgFullPath = $i->image_path
                ? storage_path('app/public/' . $i->image_path)
                : null;

            $imgDataUri = null;

            if ($imgFullPath && file_exists($imgFullPath)) {
                $ext = strtolower(pathinfo($imgFullPath, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    default => null,
                };

                if ($mime) {
                    $imgDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($imgFullPath));
                }
            }

            $attachmentsCount = is_array($i->investigation_report) ? count($i->investigation_report) : 0;
        @endphp

        <tr>
            <td class="center">{{ $loop->iteration }}</td>
            <td>{{ $i->location }}</td>
            <td>
                <div style="font-weight:700;">{{ $i->type_of_incident }}</div>
                <div style="font-size:10px; color:#9ca3af;">{{ $typeLabel($i->type_of_incident) }}</div>
            </td>
            <td class="center">{{ $occurred ? $occurred->format('d.m.Y.') : '' }}</td>
            <td class="center {{ $returnClass }}">{{ $return ? $return->format('d.m.Y.') : '' }}</td>
            <td class="center">{{ $i->working_days_lost ?? '' }}</td>
            <td>{{ $i->injured_body_part }}</td>
            <td>{{ Str::limit((string) $i->causes_of_injury, 110) }}</td>
            <td>{{ Str::limit((string) $i->accident_injury_type, 110) }}</td>
            <td>{{ Str::limit((string) $i->other, 120) }}</td>
            <td class="center">
                @if ($imgDataUri)
                    <img src="{{ $imgDataUri }}" style="width:55px; height:auto; border-radius:3px;">
                @endif
            </td>
            <td class="center">{{ $attachmentsCount }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- ✅ Broj stranice (kao Observations/Machines) --}}
<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script('
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 9;

        $pageText = "Str. " . $PAGE_NUM . "/" . $PAGE_COUNT;
        $dateText = "Ispis: {{ now()->format("d.m.Y.") }}";

        $pdf->text(18, 570, $dateText, $font, $size);

        $width = $fontMetrics->get_text_width($pageText, $font, $size);
        $pdf->text(820 - $width, 570, $pageText, $font, $size);
    ');
}
</script>

</body>
</html>