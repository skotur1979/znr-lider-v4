<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>Kemikalije</title>

    {{-- Ako već koristiš ovaj partial svugdje --}}
    @include('pdf.partials.styles')

    {{-- Dodatno (može i u partial ako želiš) --}}
    <style>
        /* spriječi “curenje” teksta u susjedne kolone */
        td, th { overflow: hidden; }

        /* za H/P oznake: poštuj \n i lijepo lomi red */
        .hp { white-space: pre-line; word-wrap: break-word; overflow-wrap: anywhere; }

        /* piktogrami u 3 po redu */
        .pikt-wrap{
            display: grid;
            grid-template-columns: repeat(3, 18px);
            gap: 4px;
            justify-content: center;
            align-content: center;
            width: 62px;      /* 3*18 + 2*4 = 62 */
            max-width: 62px;
            margin: 0 auto;
        }
        .pikt-wrap img{
            width: 18px;
            height: 18px;
            display: block;
            object-fit: contain;
        }
    </style>
</head>

<body>
@php
    use Illuminate\Support\Carbon;

    // helper: normaliziraj listu iz array/string (CSV)
    $toList = function ($value): array {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        $value = trim((string) $value);
        if ($value === '') return [];

        $parts = preg_split('/\s*,\s*/', $value);
        return array_values(array_filter($parts));
    };

    // helper: chunk po N u red, vrati string s \n
    $chunkLines = function (array $list, int $perLine): string {
        $chunks = array_chunk($list, $perLine);
        return collect($chunks)
            ->map(fn ($chunk) => implode(', ', $chunk))
            ->implode("\n");
    };

    // helper: kandidati putanja za GHS
    $candidates = function (string $code): array {
        $code = strtoupper(trim($code));

        return [
            "images/ghs/{$code}.gif",
            "images/ghs/{$code}.png",
            "images/ghs/{$code}.svg",
            "images/ghs/{$code}.webp",

            "piktogrami/{$code}.gif",
            "piktogrami/{$code}.png",
            "piktogrami/{$code}.svg",
            "piktogrami/{$code}.webp",

            "images/piktogrami/{$code}.gif",
            "images/piktogrami/{$code}.png",
            "images/piktogrami/{$code}.svg",
            "images/piktogrami/{$code}.webp",
        ];
    };
@endphp

<h1>Kemikalije</h1>
<div class="meta">
    Datum izvoza: {{ now()->format('d.m.Y. H:i') }}
</div>

<table>
    <thead>
    <tr>
        <th class="center" style="width: 32px;">Red.br.</th>
        <th style="width: 150px;">Ime proizvoda</th>
        <th class="center" style="width: 90px;">CAS</th>
        <th class="center" style="width: 110px;">UFI</th>
        <th class="center" style="width: 70px;">Piktogrami</th>
        <th class="center" style="width: 90px;">H oznake</th>
        <th class="center" style="width: 150px;">P oznake</th>
        <th style="width: 110px;">Mjesto upotrebe</th>
        <th class="center" style="width: 70px;">Količina</th>
        <th class="center" style="width: 70px;">GVI / KGVI</th>
        <th class="center" style="width: 55px;">VOC</th>
        <th class="center" style="width: 75px;">STL – HZJZ</th>
        <th class="center" style="width: 40px;">Prilozi</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($chemicals as $c)
        @php
            // GHS piktogrami
            $ghs = $toList($c->hazard_pictograms);

            // H / P
            $hList = $toList($c->h_statements);
            $pList = $toList($c->p_statements);

            // H: možeš stavit 1 po redu ili 2 po redu; ja ostavljam 1 po redu da bude preglednije
            $hText = $chunkLines($hList, 2);

            // P: ✅ 2 po redu (ovo si tražio)
            $pText = $chunkLines($pList, 2);

            $stl = $c->stl_hzjz ? Carbon::parse($c->stl_hzjz)->format('d.m.Y.') : '';

            $attCount = is_array($c->attachments) ? count($c->attachments) : 0;
        @endphp

        <tr>
            <td class="center">{{ $loop->iteration }}</td>

            <td class="wrap">{{ $c->product_name }}</td>

            <td class="center wrap">{{ $c->cas_number }}</td>

            <td class="center wrap">{{ $c->ufi_number }}</td>

            <td class="center">
    <div class="pikt-wrap">
        @foreach ($ghs as $code)
            @php
                $src = null;

                foreach ($candidates($code) as $path) {
                    $fullPath = public_path($path);

                    if (file_exists($fullPath)) {
                        $src = $fullPath;   // ✅ BITNO: filesystem path, ne asset()
                        break;
                    }
                }
            @endphp

            @if ($src)
                <img src="{{ $src }}" alt="{{ $code }}">
            @endif
        @endforeach
    </div>
</td>

            <td class="hp center">{{ $hText }}</td>

            <td class="hp">{{ $pText }}</td>

            <td class="wrap">{{ $c->usage_location }}</td>

            <td class="center wrap">{{ $c->annual_quantity }}</td>

            <td class="center wrap">{{ $c->gvi_kgvi }}</td>

            <td class="center wrap">{{ $c->voc }}</td>

            <td class="center">{{ $stl }}</td>

            <td class="center">{{ $attCount }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- Broj stranice + datum ispisa (kao kod Machines) --}}
<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script('
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 9;

        $pageText = "Str. " . $PAGE_NUM . "/" . $PAGE_COUNT;
        $dateText = "Ispis: {{ now()->format("d.m.Y.") }}";

        // lijevo datum
        $pdf->text(18, 570, $dateText, $font, $size);

        // desno broj stranice (landscape A4 širina ~ 820)
        $width = $fontMetrics->get_text_width($pageText, $font, $size);
        $pdf->text(820 - $width, 570, $pageText, $font, $size);
    ');
}
</script>

</body>
</html>