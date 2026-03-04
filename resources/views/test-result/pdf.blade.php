{{-- resources/views/test-result/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Rješenje testa</title>

    <style>
        @page {
            margin: 14mm 12mm 16mm 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color:#111827;
        }

        /* ====== HEADER / FOOTER (mPDF) ====== */
        .header {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-table { width:100%; border-collapse: collapse; }
        .header-left { width: 55%; vertical-align: top; }
        .header-right { width: 45%; vertical-align: top; text-align: right; }

        .doc-title { font-size: 16px; font-weight: 800; margin: 0; }
        .doc-sub { margin: 2px 0 0 0; color:#6b7280; font-size: 11px; }

        .badge {
            display:inline-block;
            padding:6px 10px;
            border-radius:999px;
            font-weight:800;
            font-size:12px;
            border:1px solid #d1d5db;
        }
        .badge.ok { border-color:#22c55e; background:#eaffea; color:#14532d; }
        .badge.bad{ border-color:#ef4444; background:#ffeaea; color:#7f1d1d; }

        .meta {
            margin: 8px 0 10px 0;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
        }
        .meta-table { width:100%; border-collapse: collapse; }
        .meta-table td { padding: 2px 0; vertical-align: top; }
        .meta-k { width: 32%; color:#374151; font-weight: 700; }
        .meta-v { width: 68%; }

        .hr { border:none; border-top:1px solid #e5e7eb; margin: 10px 0 12px 0; }

        /* ====== QUESTIONS ====== */
        .question {
            margin-bottom: 12px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;

            /* ✅ ne lomi pitanje+odgovore preko stranice */
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .q-title {
            font-size: 14px;
            font-weight: 800;
            margin: 0 0 8px 0;
        }

        .q-image {
            max-height: 220px;
            margin: 8px 0 10px 0;
            display:block;
        }

        .answers {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;

            page-break-inside: avoid;
            break-inside: avoid;
        }

        .answer {
            width: 48%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            text-align: left;

            page-break-inside: avoid;
            break-inside: avoid;
        }

        .correct { background-color: #eaffea; border-color: #22c55e; }
        .wrong   { background-color: #ffeaea; border-color: #ef4444; }

        .a-image { max-height: 120px; margin-bottom: 6px; display:block; }

        .ok  { color: #166534; font-weight: bold; margin-top:6px; }
        .bad { color: #991b1b; font-weight: bold; margin-top:6px; }
        .hint{ color: #166534; margin-top:6px; }
        .muted { color:#6b7280; }

        /* ====== FOOTER BLOCK ====== */
        .sign {
            margin-top: 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .sign-table { width:100%; border-collapse: collapse; }
        .sign-cell { width: 50%; vertical-align: top; }
        .sign-line {
            margin-top: 28px;
            border-top: 1px solid #9ca3af;
            width: 85%;
        }

        /* Print niceties */
        .small { font-size: 10.5px; }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Carbon;

    // ✅ PDF: samo lokalne slike -> base64 (bez asset/http fallbacka)
    $imgSrc = function (?string $relPath) {
        if (!$relPath) return null;

        $clean = ltrim($relPath, '/');
        $cleanNoStorage = preg_replace('#^storage/#', '', $clean);

        $candidates = [
            public_path($clean),
            public_path('storage/' . $cleanNoStorage),
            storage_path('app/public/' . $cleanNoStorage),
        ];

        $abs = null;
        foreach ($candidates as $cand) {
            if (is_file($cand)) { $abs = $cand; break; }
        }

        if (! $abs) return null;

        // ✅ skip prevelike slike (ubrzava PDF)
        if (@filesize($abs) > 700000) return null;

        $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg','jpeg','png','gif'], true)) {
            $mime = $ext === 'jpg' ? 'image/jpeg' : "image/{$ext}";
            $data = @file_get_contents($abs);
            return $data ? "data:{$mime};base64," . base64_encode($data) : null;
        }

        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            $im = @imagecreatefromwebp($abs);
            if ($im) {
                ob_start(); imagepng($im); $png = ob_get_clean(); imagedestroy($im);
                return $png ? 'data:image/png;base64,' . base64_encode($png) : null;
            }
        }

        return null;
    };

    $testName  = $attempt->test->naziv ?? '—';
    $datumRodj = $attempt->datum_rodjenja ? Carbon::parse($attempt->datum_rodjenja)->format('d.m.Y.') : '-';
    $rezultat  = is_numeric($attempt->rezultat) ? number_format((float) $attempt->rezultat, 2) : '-';
    $bodovi    = $attempt->bodovi_osvojeni ?? '-';
    $poslano   = optional($attempt->created_at)->format('d.m.Y. H:i') ?? '-';
    $statusTxt = $attempt->prolaz ? 'PROLAZ' : 'PAD';
@endphp

{{-- ===== HEADER ===== --}}
<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-left">
                <p class="doc-title">Zapisnik o provedenom testiranju</p>
                <p class="doc-sub">
                    Test: <strong>{{ $testName }}</strong><br>
                    Evidencija: #{{ $attempt->id ?? '—' }} • Datum ispisa: {{ now()->format('d.m.Y. H:i') }}
                </p>
            </td>
            <td class="header-right">
                <span class="badge {{ $attempt->prolaz ? 'ok' : 'bad' }}">
                    {{ $statusTxt }} <span class="muted">({{ $rezultat }}%)</span>
                </span>
            </td>
        </tr>
    </table>
</div>

{{-- ===== META ===== --}}
<div class="meta">
    <table class="meta-table">
        <tr>
            <td class="meta-k">Ime i prezime:</td>
            <td class="meta-v">{{ $attempt->ime_prezime ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-k">Radno mjesto:</td>
            <td class="meta-v">{{ $attempt->radno_mjesto ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-k">Datum rođenja:</td>
            <td class="meta-v">{{ $datumRodj }}</td>
        </tr>
        <tr>
            <td class="meta-k">Bodovi:</td>
            <td class="meta-v">{{ $bodovi }}</td>
        </tr>
        <tr>
            <td class="meta-k">Rezultat:</td>
            <td class="meta-v">{{ $rezultat }}%</td>
        </tr>
        <tr>
            <td class="meta-k">Datum slanja:</td>
            <td class="meta-v">{{ $poslano }}</td>
        </tr>
        <tr>
            <td class="meta-k">Korisnik (sustav):</td>
            <td class="meta-v">{{ $attempt->user->name ?? '-' }}</td>
        </tr>
    </table>
</div>

<div class="hr"></div>

{{-- ===== QUESTIONS ===== --}}
@foreach (($attempt->test->questions ?? []) as $index => $question)
    @php
        $selectedIds = $attempt->odgovori
            ->where('question_id', $question->id)
            ->pluck('answer_id')
            ->map(fn($id) => (int) $id)
            ->all();
    @endphp

    <div class="question">
        <p class="q-title">{{ $index + 1 }}. {{ $question->tekst ?? '' }}</p>

        @if (!empty($question->slika_path))
            @php $src = $imgSrc($question->slika_path); @endphp
            @if ($src)
                <img class="q-image" src="{{ $src }}" alt="Slika pitanja">
            @endif
        @endif

        <div class="answers">
            @foreach (($question->answers ?? []) as $answer)
                @php
                    $isSelected = in_array((int) $answer->id, $selectedIds, true);
                    $isCorrect  = (bool) ($answer->is_correct ?? false);

                    $class = 'answer';
                    if ($isSelected && $isCorrect)      $class .= ' correct';
                    elseif ($isSelected && !$isCorrect) $class .= ' wrong';
                @endphp

                <div class="{{ $class }}">
                    @if (!empty($answer->slika_path))
                        @php $src = $imgSrc($answer->slika_path); @endphp
                        @if ($src)
                            <img class="a-image" src="{{ $src }}" alt="Slika odgovora">
                        @endif
                    @endif

                    <div><strong>{{ $answer->tekst ?? '' }}</strong></div>

                    @if ($isSelected && $isCorrect)
                        <div class="ok">✔ Točan odgovor</div>
                    @elseif ($isSelected && !$isCorrect)
                        <div class="bad">✖ Netočan odgovor</div>
                    @elseif (!$isSelected && $isCorrect)
                        <div class="hint">(Točan, nije označen)</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endforeach

{{-- ===== SIGNATURES ===== --}}
<div class="sign">
    <table class="sign-table">
        <tr>
            <td class="sign-cell">
                <div class="small muted">Ispitanik</div>
                <div class="sign-line"></div>
            </td>
            <td class="sign-cell">
                <div class="small muted">Ovlaštena osoba / administrator</div>
                <div class="sign-line"></div>
            </td>
        </tr>
    </table>
</div>

{{-- ===== FOOTER NOTE ===== --}}
<p class="small muted" style="margin-top:10px;">
    Napomena: Ovaj zapisnik je generiran automatski iz aplikacije ZNR LIDER. Svi prikazani odgovori odnose se na pokušaj testa #{{ $attempt->id ?? '—' }}.
</p>

</body>
</html>