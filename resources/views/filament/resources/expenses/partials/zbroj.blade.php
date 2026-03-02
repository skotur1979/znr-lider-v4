<style>
    .znr-cards .card{
        background:#ffffff !important;
        color:#0f172a !important;
        border:1px solid #e2e8f0 !important;
        border-radius:0.75rem;
        padding:1.25rem;
    }

    .dark .znr-cards .card{
        background:#1f2937 !important;
        color:#f8fafc !important;
        border-color:#334155 !important;
    }

    .znr-cards .muted{ color:#475569 !important; }
    .dark .znr-cards .muted{ color:#94a3b8 !important; }

    /* Grid */
    .znr-cards-grid{
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
    @media (min-width: 768px){
        .znr-cards-grid{
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }

    /* ✅ Veće i jače brojke */
    .znr-big-number{
        font-size: 2rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.5px;
    }

    /* ✅ Preostalo */
    .znr-preostalo-pos{
        color:#047857 !important;
        font-weight:600 !important;
    }

    .znr-preostalo-neg{
        color:#dc2626 !important;   /* jače crveno */
        font-weight:600 !important;
    }

    .dark .znr-preostalo-pos{
        color:#34d399 !important;
    }

    .dark .znr-preostalo-neg{
        color:#f87171 !important;
    }
</style>

@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.') . ' €';
    $isPositive = ((float) $razlika) >= 0;
@endphp

<h2 class="text-xl font-bold mb-4">Godina: {{ $godina ?: 'Sve' }}</h2>

<div class="znr-cards znr-cards-grid mb-6">

    <div class="card shadow-sm">
        <div class="text-sm font-medium muted mb-2">Ukupno troškova:</div>
        <div class="znr-big-number">
            {{ $fmt($ukupnoTroskova) }}
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="text-sm font-medium muted mb-2">Budžet:</div>
        <div class="znr-big-number">
            {{ $fmt($ukupniBudget) }}
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="text-sm font-medium muted mb-2">Preostalo:</div>
        <div class="znr-big-number {{ $isPositive ? 'znr-preostalo-pos' : 'znr-preostalo-neg' }}">
            {{ $fmt($razlika) }}
        </div>
    </div>

</div>

@if (! empty($grupiraniTroskovi) && $grupiraniTroskovi->count())
    <h3 class="text-lg font-semibold mb-2">Troškovi po mjesecima</h3>

    <div class="znr-cards">
        <div class="card p-0 overflow-hidden">
            <ul class="divide-y" style="border-color:#e2e8f0">
                @foreach ($grupiraniTroskovi as $mjesec)
                    <li class="flex items-center justify-between p-3">
                        <span class="muted">{{ $mjesec->mjesec }}</span>
                        <span class="font-medium">{{ $fmt($mjesec->ukupno) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif