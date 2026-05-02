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

    .znr-cards-grid{
        display:grid !important;
        grid-template-columns:1fr !important;
        gap:1rem !important;
    }

    @media (min-width:768px){
        .znr-cards-grid{
            grid-template-columns:repeat(3, minmax(0, 1fr)) !important;
        }
    }

    .znr-big-number{
        font-size:2rem !important;
        font-weight:600 !important;
        letter-spacing:.5px;
    }

    .znr-preostalo-pos{
        color:#047857 !important;
        font-weight:600 !important;
    }

    .znr-preostalo-neg{
        color:#dc2626 !important;
        font-weight:600 !important;
    }

    .dark .znr-preostalo-pos{
        color:#34d399 !important;
    }

    .dark .znr-preostalo-neg{
        color:#f87171 !important;
    }

    .znr-month-list{
        list-style:none;
        margin:0;
        padding:0;
    }

    .znr-month-row{
        display:flex;
        align-items:center;
        gap:14px;
        padding:14px 16px;
        border-bottom:1px solid #e2e8f0;
    }

    .znr-month-row:last-child{
        border-bottom:0;
    }

    .dark .znr-month-row{
        border-bottom-color:#334155;
    }

    .znr-month-name{
        min-width:120px;
        color:#475569;
        font-weight:600;
    }

    .dark .znr-month-name{
        color:#94a3b8;
    }

    .znr-month-line{
        flex:1;
        height:1px;
        background:#cbd5e1;
    }

    .dark .znr-month-line{
        background:#475569;
    }

    .znr-month-amount{
        min-width:140px;
        text-align:right;
        font-weight:700;
        color:#0f172a;
    }

    .dark .znr-month-amount{
        color:#f8fafc;
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
    <h3 class="text-lg font-semibold mb-3">Troškovi po mjesecima</h3>

    <div class="znr-cards mb-6">
        <div class="card p-0 overflow-hidden">
            <ul class="znr-month-list">
                @foreach ($grupiraniTroskovi as $mjesec)
                    <li class="znr-month-row">
                        <span class="znr-month-name">{{ $mjesec->mjesec }}</span>
                        <span class="znr-month-line"></span>
                        <span class="znr-month-amount">{{ $fmt($mjesec->ukupno) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif