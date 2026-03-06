<x-filament-panels::page>
<style>

.tests-wrap{
    max-width:1500px;
    margin:0 auto;
}

/* GRID */
.test-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:28px;
}

@media(max-width:1000px){
    .test-grid{
        grid-template-columns:1fr;
    }
}

/* CARD */
.test-card{
    position:relative;
    border-radius:20px;
    padding:34px;
    min-height:190px; /* ✅ sve kartice iste */
    display:flex;
    align-items:center;
    justify-content:space-between;

    border:1px solid rgba(255,255,255,.08);

    background:
        radial-gradient(1200px 320px at 0% 0%, rgba(249,115,22,.18), transparent 55%),
        radial-gradient(900px 280px at 100% 0%, rgba(59,130,246,.16), transparent 55%),
        rgba(17,24,39,.65);

    box-shadow:0 12px 30px rgba(0,0,0,.25);
    transition:.18s;
}

.test-card:hover{
    transform:translateY(-3px);
    border-color:rgba(249,115,22,.35);
    box-shadow:0 20px 45px rgba(0,0,0,.35);
}

/* LIGHT */
.test-card.light{
    border:1px solid rgba(17,24,39,.10);

    background:
        radial-gradient(900px 280px at 0% 0%, rgba(249,115,22,.16), transparent 55%),
        radial-gradient(700px 240px at 100% 0%, rgba(59,130,246,.12), transparent 55%),
        #ffffff;

    box-shadow:0 12px 30px rgba(17,24,39,.08);
}

/* HEADER */
.test-left{
    display:flex;
    gap:16px;
    align-items:flex-start;
}

/* ICON */
.test-icon{
    width:54px;
    height:54px;
    border-radius:16px;

    display:flex;
    align-items:center;
    justify-content:center;

    border:1px solid rgba(249,115,22,.35);
    background:rgba(249,115,22,.10);
}

.test-icon svg{
    width:26px;
    height:26px;
}

/* TITLE */
.test-title{
    font-size:24px;
    font-weight:900;
    margin-bottom:10px;
    color:#fff;
}

.test-card.light .test-title{
    color:#111827;
}

/* META */
.meta-row{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    align-items:center;
}

.meta-label{
    font-size:13px;
    font-weight:700;
    opacity:.75;
}

/* BADGE */
.pill{
    display:inline-flex;
    align-items:center;
    gap:6px;

    padding:8px 14px;
    border-radius:999px;

    font-size:13px;
    font-weight:800;

    border:1px solid rgba(255,255,255,.14);
    background:rgba(255,255,255,.06);
}

.test-card.light .pill{
    background:#f1f5f9;
    border-color:#e2e8f0;
}

.pill.pass{
    border-color:rgba(34,197,94,.35);
    background:rgba(34,197,94,.12);
}

.pill.q{
    border-color:rgba(59,130,246,.35);
    background:rgba(59,130,246,.14);
}

/* HINT */
.meta-hint{
    margin-top:10px;
    font-size:12px;
    opacity:.65;
}

/* BUTTON */
.start-btn{
    display:inline-flex;
    align-items:center;
    gap:10px;

    padding:13px 18px;
    border-radius:12px;

    font-weight:900;
    font-size:14px;

    background:#f59e0b;
    color:#111827;

    box-shadow:0 12px 26px rgba(245,158,11,.25);

    transition:.15s;
}

.start-btn:hover{
    transform:translateY(-2px);
}

.start-btn svg{
    width:18px;
    height:18px;
}

</style>

<div class="tests-wrap">

<div class="test-grid">

@forelse ($tests as $test)

<div class="test-card">

<div class="test-left">

<div class="test-icon">

<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round"
d="M9 5.25h6m-6 0A2.25 2.25 0 0 0 6.75 7.5v12A2.25 2.25 0 0 0 9 21.75h6A2.25 2.25 0 0 0 17.25 19.5v-12A2.25 2.25 0 0 0 15 5.25"/>
</svg>

</div>

<div>

<div class="test-title">
{{ $test->naziv }}
</div>

<div class="meta-row">

<span class="meta-label">Minimalni prolaz</span>

<span class="pill pass">
{{ $test->minimalni_prolaz ?? 75 }}%
</span>

<span class="pill q">
{{ $test->questions_count ?? $test->questions()->count() }} pitanja
</span>

</div>

<div class="meta-hint">
Otvara test u aplikaciji
</div>

</div>

</div>

<a
class="start-btn"
href="{{ \App\Filament\Pages\TestFormPage::getUrl(parameters: ['test' => $test->id]) }}"
>

<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
<path d="M8.5 5.5v13l11-6.5-11-6.5z"/>
</svg>

Pokreni test

</a>

</div>

@empty

<div class="test-card">
Nema dostupnih testova
</div>

@endforelse

</div>
</div>

</x-filament-panels::page>