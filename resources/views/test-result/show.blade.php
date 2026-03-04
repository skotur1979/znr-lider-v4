<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rješenje testa</title>

    <style>
        :root{
            --bg:#0b1220;
            --card:#0f172a;
            --border:#1f2937;
            --muted:#94a3b8;
            --text:#e5e7eb;
            --primary:#f97316;

            --ok-bg:#eaffea;
            --ok-border:#22c55e;
            --bad-bg:#ffeaea;
            --bad-border:#ef4444;
        }

        *{ box-sizing:border-box; }
        body{
            margin:0;
            padding:24px;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Liberation Sans", sans-serif;
            background: radial-gradient(1200px 600px at 20% 0%, rgba(249,115,22,.18), transparent 60%),
                        radial-gradient(1200px 600px at 80% 0%, rgba(59,130,246,.10), transparent 60%),
                        var(--bg);
            color:var(--text);
        }

        .container{
            max-width: 1100px;
            margin: 0 auto;
        }

        .topbar{
            display:flex;
            gap:12px;
            align-items:center;
            justify-content:space-between;
            margin-bottom:16px;
        }

        .btn{
            display:inline-flex;
            gap:8px;
            align-items:center;
            padding:10px 14px;
            border-radius:10px;
            border:1px solid rgba(255,255,255,.10);
            color:var(--text);
            text-decoration:none;
            background: rgba(255,255,255,.03);
            transition: .15s;
            font-weight:600;
        }
        .btn:hover{ border-color: rgba(249,115,22,.60); transform: translateY(-1px); }

        .card{
            background: rgba(255,255,255,.03);
            border:1px solid rgba(255,255,255,.10);
            border-radius:16px;
            padding:18px;
        }

        h1{
            margin:0 0 10px 0;
            font-size: 22px;
            line-height: 1.2;
            letter-spacing:.2px;
        }

        .meta{
            display:grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap:10px;
            margin-top:12px;
        }
        .meta .item{
            background: rgba(255,255,255,.03);
            border:1px solid rgba(255,255,255,.08);
            border-radius:12px;
            padding:10px 12px;
        }
        .meta .label{
            font-size:12px;
            color:var(--muted);
            margin-bottom:4px;
        }
        .meta .value{
            font-size:14px;
            font-weight:700;
        }

        .pill{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:6px 10px;
            border-radius:999px;
            font-weight:800;
            font-size:12px;
            border:1px solid rgba(255,255,255,.10);
            background: rgba(255,255,255,.03);
        }
        .pill.ok{ border-color: rgba(34,197,94,.45); }
        .pill.bad{ border-color: rgba(239,68,68,.45); }

        hr{
            border:none;
            border-top:1px solid rgba(255,255,255,.10);
            margin:18px 0;
        }

        .question{
            margin-bottom:18px;
            padding:16px;
            border-radius:16px;
            border:1px solid rgba(255,255,255,.10);
            background: rgba(255,255,255,.02);
        }

        .q-title{
            font-size: 17px;         /* ✅ veće */
            font-weight: 800;        /* ✅ bold */
            line-height: 1.35;
            margin:0 0 10px 0;
            display:flex;
            gap:10px;
            align-items:flex-start;
        }
        .q-index{
            flex:0 0 auto;
            width:28px;
            height:28px;
            border-radius:10px;
            display:grid;
            place-items:center;
            font-weight:900;
            color:#111827;
            background: rgba(249,115,22,.95);
        }

        .q-image{
            display:block;
            max-width: 100%;
            max-height: 260px;
            object-fit:contain;
            margin: 10px 0 14px 0;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(255,255,255,.02);
            padding: 6px;
        }

        .answers{
            display:grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap:12px;
        }

        @media (max-width: 820px){
            .meta{ grid-template-columns: 1fr; }
            .answers{ grid-template-columns: 1fr; }
        }

        .answer{
            border:1px solid rgba(255,255,255,.10);
            border-radius:14px;
            padding:12px;
            background: rgba(255,255,255,.02);
        }

        .answer.correct{
            background: rgba(34,197,94,.12);
            border-color: rgba(34,197,94,.55);
        }
        .answer.wrong{
            background: rgba(239,68,68,.12);
            border-color: rgba(239,68,68,.55);
        }

        .a-image{
            display:block;
            width:100%;
            max-height: 180px;
            object-fit:contain;
            border-radius:12px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(255,255,255,.02);
            padding: 6px;
            margin-bottom:10px;
        }

        .a-text{
            font-size:14px;
            font-weight:700;
            line-height:1.35;
        }

        .badge{
            margin-top:10px;
            font-size:12px;
            font-weight:800;
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:6px 10px;
            border-radius:999px;
            border:1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.03);
        }
        .badge.ok{ border-color: rgba(34,197,94,.55); }
        .badge.bad{ border-color: rgba(239,68,68,.55); }
        .badge.hint{ border-color: rgba(34,197,94,.35); opacity:.9; }

        .muted{ color: var(--muted); }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Carbon;

    /**
     * Vrati src za <img>:
     * 1) base64 data-uri ako nađemo fizičku datoteku (jpg/png/gif + webp->png)
     * 2) inače asset('storage/...') (za web prikaz)
     */
    $imgSrc = function (?string $relPath) {
        if (!$relPath) return null;
        $clean = ltrim($relPath, '/');

        $candidates = [
            public_path($clean),
            public_path('storage/' . $clean),
            storage_path('app/public/' . $clean),
        ];

        $abs = null;
        foreach ($candidates as $cand) {
            if (is_file($cand)) { $abs = $cand; break; }
        }

        if ($abs) {
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
        }

        return asset('storage/' . $clean);
    };

    $datum = $attempt->datum_rodjenja ? Carbon::parse($attempt->datum_rodjenja)->format('d.m.Y.') : '-';
    $rezultat = is_numeric($attempt->rezultat) ? number_format((float) $attempt->rezultat, 2) : '-';
@endphp

<div class="container">
    <div class="topbar">
        <div class="muted">ZNR LIDER • Rješenje testa</div>

        <a class="btn" href="{{ route('test-attempts.download', $attempt) }}" target="_blank" rel="noopener">
            📄 PDF
        </a>
    </div>

    <div class="card">
        <h1>Rješenje testa: {{ $attempt->test->naziv ?? '—' }}</h1>

        <div class="pill {{ $attempt->prolaz ? 'ok' : 'bad' }}">
            {{ $attempt->prolaz ? '✅ PROLAZ' : '❌ PAD' }}
            <span class="muted">({{ $rezultat }}%)</span>
        </div>

        <div class="meta">
            <div class="item">
                <div class="label">Ime i prezime</div>
                <div class="value">{{ $attempt->ime_prezime ?? '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Radno mjesto</div>
                <div class="value">{{ $attempt->radno_mjesto ?? '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Datum rođenja</div>
                <div class="value">{{ $datum }}</div>
            </div>
            <div class="item">
                <div class="label">Bodovi</div>
                <div class="value">{{ $attempt->bodovi_osvojeni ?? '-' }}</div>
            </div>
            <div class="item">
                <div class="label">Rezultat</div>
                <div class="value">{{ $rezultat }}%</div>
            </div>
            <div class="item">
                <div class="label">Datum slanja</div>
                <div class="value">
                    {{ optional($attempt->created_at)->format('d.m.Y. H:i') ?? '-' }}
                </div>
            </div>
        </div>

        <hr>

        @foreach (($attempt->test->questions ?? []) as $index => $question)
            @php
                // samo odgovori za ovo pitanje
                $selectedIds = $attempt->odgovori
                    ->where('question_id', $question->id)
                    ->pluck('answer_id')
                    ->map(fn($id) => (int) $id)
                    ->all();
            @endphp

            <div class="question">
                <div class="q-title">
                    <div class="q-index">{{ $index + 1 }}</div>
                    <div>{{ $question->tekst ?? '' }}</div>
                </div>

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

                            <div class="a-text">{{ $answer->tekst ?? '' }}</div>

                            @if ($isSelected && $isCorrect)
                                <div class="badge ok">✔ Točan odgovor</div>
                            @elseif ($isSelected && !$isCorrect)
                                <div class="badge bad">✖ Netočan odgovor</div>
                            @elseif (!$isSelected && $isCorrect)
                                <div class="badge hint">(Točan, nije označen)</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

    </div>
</div>

</body>
</html>