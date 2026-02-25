<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>Izvještaj - Svi ormarići prve pomoći</title>
    @include('pdf.partials.styles')

    <style>
        h1 {
            font-size: 18px;
            text-align: center;
            margin: 0 0 12px 0;
        }

        .kit {
            margin: 0 0 18px 0;
            page-break-inside: avoid;
        }

        .kit-title {
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            margin: 6px 0 6px 0;
        }

        .kit-meta {
            font-size: 10px;
            margin: 0 0 6px 0;
        }

        .kit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .kit-table th,
        .kit-table td {
            border: 1px solid #111;
            padding: 4px 6px;
            vertical-align: top;
        }

        .kit-table th {
            font-weight: 700;
            text-align: left;
            background: #f2f2f2;
        }

        .center { text-align: center; }
        .wrap { white-space: normal; }

        /* Rok boje (isto kao tvoj employees template) */
        .rok-expired { background: #ff0000; } /* crveno */
        .rok-soon    { background: #fff000; } /* žuto */
    </style>
</head>
<body>
@php
    use Illuminate\Support\Carbon;

    $today = Carbon::today();
    $fmt = fn($d) => $d ? Carbon::parse($d)->format('d.m.Y.') : '';

    $rokClass = function ($d) use ($today) {
        if (! $d) return '';
        $dt = $d instanceof \DateTimeInterface ? Carbon::instance($d) : Carbon::parse($d);

        if ($dt->lt($today)) return 'rok-expired';
        if ($dt->lte($today->copy()->addDays(30))) return 'rok-soon';
        return '';
    };
@endphp

<h1>Izvještaj - Svi ormarići prve pomoći</h1>

@foreach ($kits as $kit)
    @php
        $items = collect($kit->items ?? [])
            ->sortBy(fn($i) => $i->valid_until ? Carbon::parse($i->valid_until)->timestamp : PHP_INT_MAX)
            ->values();
    @endphp

    <div class="kit">
        <div class="kit-title">
            Ormarić: {{ $kit->location }}
        </div>

        <div class="kit-meta">
            <strong>Pregled obavljen:</strong> {{ $fmt($kit->inspected_at) }}
        </div>

        <table class="kit-table">
            <thead>
                <tr>
                    <th style="width: 38%;">Vrsta materijala</th>
                    <th style="width: 38%;">Namjena</th>
                    <th class="center" style="width: 24%;">Vrijedi do</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td class="wrap">{{ $item->material_type }}</td>
                        <td class="wrap">{{ $item->purpose }}</td>

                        @php
                            $until = $item->valid_until;
                            $cls = $rokClass($until);
                        @endphp

                        <td class="center {{ $cls }}">
                            {{ $fmt($until) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="center">Nema stavki.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endforeach

{{-- ✅ Broj stranice + datum ispisa (isto kao Employees/Machines) --}}
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