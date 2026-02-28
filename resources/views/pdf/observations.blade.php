<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>Zapažanja</title>
    @include('pdf.partials.styles')
</head>
<body>
@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $today = Carbon::today();

    $typeLabel = fn ($state) => match ($state) {
        'Near Miss' => 'NM - Skoro nezgoda',
        'Negative Observation' => 'Negativno zapažanje',
        'Positive Observation' => 'Pozitivno zapažanje',
        default => (string) $state,
    };

    $statusLabel = fn ($state) => match ($state) {
        'Not started' => 'Nije započeto',
        'In progress' => 'U tijeku',
        'Complete' => 'Završeno',
        default => (string) $state,
    };
@endphp

<h1>Zapažanja</h1>
<div class="meta">
    Datum izvoza: {{ now()->format('d.m.Y. H:i') }}
</div>

<table>
    <thead>
    <tr>
        <th class="center" style="width: 35px;">Red.br.</th>
        <th class="center" style="width: 70px;">Datum</th>
        <th style="width: 140px;">Vrsta zapažanja</th>
        <th style="width: 130px;">Lokacija</th>
        <th>Opis</th>
        <th style="width: 160px;">Vrsta opasnosti</th>
        <th style="width: 150px;">Potrebna radnja</th>
        <th style="width: 130px;">Odgovorna osoba</th>
        <th class="center" style="width: 85px;">Rok</th>
        <th style="width: 90px;">Status</th>
        <th style="width: 140px;">Komentar</th>
        <th class="center" style="width: 70px;">Slika</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($observations as $o)
        @php
            $incident = $o->incident_date ? Carbon::parse($o->incident_date) : null;
            $target   = $o->target_date ? Carbon::parse($o->target_date) : null;

            // boje roka (samo ako nije Complete)
            $rokClass = '';
            if ($target && ($o->status !== 'Complete')) {
                if ($target->lt($today)) {
                    $rokClass = 'rok-expired';
                } elseif ($target->lte($today->copy()->addDays(30))) {
                    $rokClass = 'rok-soon';
                }
            }

            // ✅ dompdf-friendly slika (base64) iz storage/app/public/...
            $imgFullPath = $o->picture_path
                ? storage_path('app/public/' . $o->picture_path)
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
        @endphp

        <tr>
            <td class="center">{{ $loop->iteration }}</td>
            <td class="center">{{ $incident ? $incident->format('d.m.Y.') : '' }}</td>
            <td>{{ $typeLabel($o->observation_type) }}</td>
            <td>{{ $o->location }}</td>
            <td>{{ $o->item }}</td>
            <td>{{ $o->potential_incident_type }}</td>
            <td>{{ Str::limit((string) $o->action, 130) }}</td>
            <td>{{ $o->responsible }}</td>
            <td class="center {{ $rokClass }}">{{ $target ? $target->format('d.m.Y.') : '' }}</td>
            <td>{{ $statusLabel($o->status) }}</td>
            <td>{{ Str::limit((string) $o->comments, 90) }}</td>
            <td class="center">
                @if ($imgDataUri)
                    <img src="{{ $imgDataUri }}" style="width:55px; height:auto; border-radius:3px;">
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- ✅ Broj stranice (kao Machines) --}}
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