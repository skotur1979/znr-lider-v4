<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>Izvještaj - Procjene rizika</title>

    @include('pdf.partials.styles')

    <style>
        h1 {
            font-size: 18px;
            text-align: center;
            margin: 0 0 12px 0;
        }

        .meta {
            font-size: 10px;
            margin: 0 0 10px 0;
        }

        .ra {
            margin: 0 0 18px 0;
            page-break-inside: avoid;
        }

        .ra-title {
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            margin: 6px 0 6px 0;
        }

        .ra-meta {
            font-size: 10px;
            margin: 0 0 6px 0;
        }

        .ra-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .ra-table th,
        .ra-table td {
            border: 1px solid #111;
            padding: 4px 6px;
            vertical-align: top;
        }

        .ra-table th {
            font-weight: 700;
            text-align: left;
            background: #f2f2f2;
        }

        .center { text-align: center; }
        .wrap { white-space: normal; }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Carbon;

    $fmt = fn($d) => $d ? Carbon::parse($d)->format('d.m.Y.') : '';
@endphp

<h1>Izvještaj - Procjene rizika</h1>

<div class="meta">
    Datum izvoza: {{ now()->format('d.m.Y. H:i') }}
</div>

@foreach ($riskAssessments as $ra)
    <div class="ra">
        <div class="ra-title">
            Procjena br. {{ $ra->broj_procjene }} — {{ $ra->tvrtka }}
        </div>

        <div class="ra-meta">
            <strong>Datum izrade:</strong> {{ $fmt($ra->datum_izrade) }}
            &nbsp; | &nbsp;
            <strong>Vrsta:</strong> {{ $ra->vrsta_procjene }}
        </div>

        {{-- PODACI --}}
        <table class="ra-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Tvrtka</th>
                    <th style="width: 32%;">{{ $ra->tvrtka }}</th>
                    <th style="width: 18%;">OIB tvrtke</th>
                    <th style="width: 32%;">{{ $ra->oib_tvrtke }}</th>
                </tr>
                <tr>
                    <th>Adresa tvrtke</th>
                    <td class="wrap">{{ $ra->adresa_tvrtke }}</td>
                    <th>Vrsta procjene</th>
                    <td>{{ $ra->vrsta_procjene }}</td>
                </tr>
                <tr>
                    <th>Broj procjene</th>
                    <td>{{ $ra->broj_procjene }}</td>
                    <th>Datum izrade</th>
                    <td>{{ $fmt($ra->datum_izrade) }}</td>
                </tr>
            </thead>
        </table>

        {{-- SUDIONICI --}}
        <div class="ra-meta" style="margin-top: 8px;"><strong>Sudionici izrade</strong></div>
        <table class="ra-table">
            <thead>
                <tr>
                    <th style="width: 36%;">Ime i prezime</th>
                    <th style="width: 22%;">Uloga</th>
                    <th style="width: 42%;">Napomena</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ra->participants as $p)
                    <tr>
                        <td class="wrap">{{ $p->ime_prezime }}</td>
                        <td class="wrap">{{ $p->uloga }}</td>
                        <td class="wrap">{{ $p->napomena }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="center">Nema sudionika.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- REVIZIJE --}}
        <div class="ra-meta" style="margin-top: 8px;"><strong>Revizije</strong></div>
        <table class="ra-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Broj revizije</th>
                    <th style="width: 70%;">Datum izrade</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ra->revisions as $r)
                    <tr>
                        <td>{{ $r->revizija_broj }}</td>
                        <td>{{ $fmt($r->datum_izrade) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="center">Nema revizija.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- PRILOZI --}}
        <div class="ra-meta" style="margin-top: 8px;"><strong>Prilozi</strong></div>
        <table class="ra-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Naziv dokumenta</th>
                    <th style="width: 55%;">Dokument (putanja)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ra->attachments as $a)
                    <tr>
                        <td class="wrap">{{ $a->naziv }}</td>
                        <td class="wrap">{{ $a->file_path }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="center">Nema priloga.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endforeach

{{-- ✅ Broj stranice + datum ispisa (identično first aid) --}}
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