@php 
    $barClass = match ($state) {
        'critical' => 'tsb-critical',
        'warning' => 'tsb-warning',
        default => 'tsb-ok',
    };
@endphp

<x-filament-widgets::widget>
    <div class="tsb-wrap {{ $barClass }}">
        <div class="tsb-accent"></div>

        <div class="tsb-main">
            <div class="tsb-left">
                <div class="tsb-header">
                    <span class="tsb-dot"></span>
                    <span class="tsb-overline">Status sustava</span>

                    <span class="tsb-state-pill tsb-state-pill-{{ $state }}">
                        {{ $title }}
                    </span>
                </div>

                <div class="tsb-summary-row">
                    <div class="tsb-alert-icon">!</div>

                    <div class="tsb-summary-copy">
                        @if ($totalExpired > 0)
                            <div class="tsb-summary-title">
                                Sustav je kritičan zbog <strong>{{ $totalExpired }}</strong> isteklih stavki.
                            </div>
                            <div class="tsb-summary-desc">
                                Pregledajte i riješite istekle obaveze kako biste osigurali usklađenost i smanjili rizike.
                            </div>
                        @elseif ($totalSoon > 0)
                            <div class="tsb-summary-title">
                                Sustav zahtijeva pažnju — <strong>{{ $totalSoon }}</strong> stavki uskoro istječe.
                            </div>
                            <div class="tsb-summary-desc">
                                Planirajte aktivnosti prije isteka rokova.
                            </div>
                        @else
                            <div class="tsb-summary-title">Sustav je uredan.</div>
                            <div class="tsb-summary-desc">
                                Trenutno nema isteklih ni skorih rokova.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="tsb-right">
                <a
    href="{{ route('znr.general-report.pdf') }}"
    target="_blank"
    class="tsb-report-card"
>
    <x-filament::icon icon="heroicon-o-document-chart-bar" class="tsb-report-icon" />

    <div class="tsb-report-text">
        <span class="tsb-report-label">PDF</span>
        <span class="tsb-report-title">Generalni izvještaj</span>
    </div>
</a>
                <div class="tsb-score-card tsb-score-danger">
                    <div class="tsb-score-icon">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="tsb-score-svg" />
                    </div>
                    <div>
                        <span class="tsb-score-label">Isteklo</span>
                        <span class="tsb-score-value">{{ $totalExpired }}</span>
                    </div>
                </div>

                <div class="tsb-score-card tsb-score-warning">
                    <div class="tsb-score-icon">
                        <x-filament::icon icon="heroicon-o-clock" class="tsb-score-svg" />
                    </div>
                    <div>
                        <span class="tsb-score-label">Uskoro</span>
                        <span class="tsb-score-value">{{ $totalSoon }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if (count($rows))
            <div class="tsb-modules">
                @foreach ($rows as $row)
                    <div class="tsb-module {{ ($row['expired_count'] ?? 0) > 0 ? 'tsb-module-expired' : '' }}">
                        <div class="tsb-module-inner">
                            <span class="tsb-module-icon">{{ $row['icon'] }}</span>

                            <div class="tsb-module-text">
                                <span class="tsb-module-label">{{ $row['label'] }}</span>

                                <div class="tsb-module-stats">
                                    @if (($row['expired_count'] ?? 0) > 0 && ! empty($row['expired_url']))
                                        <a
                                            href="{{ $row['expired_url'] }}"
                                            class="tsb-expired-link tsb-expired-blink"
                                        >
                                            Isteklo {{ $row['expired_count'] }}
                                        </a>
                                    @else
                                        <span class="tsb-expired-text">
                                            Isteklo {{ $row['expired_count'] }}
                                        </span>
                                    @endif

                                    <span class="tsb-sep">/</span>

                                    <span class="tsb-soon-text">
                                        Uskoro {{ $row['soon_count'] }}
                                    </span>
                                </div>
                            </div>

                            <span class="tsb-chevron">›</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        .tsb-wrap{
            position:relative;
            overflow:hidden;
            border-radius:20px;
            padding:22px;
            margin-bottom:16px;
            border:1px solid rgba(148,163,184,.16);
            background:#ffffff;
            box-shadow:0 10px 26px rgba(15,23,42,.06);
        }

        .dark .tsb-wrap{
            background:
                radial-gradient(circle at 0% 0%, rgba(37,99,235,.22), transparent 34%),
                radial-gradient(circle at 100% 0%, rgba(239,68,68,.10), transparent 30%),
                linear-gradient(180deg, rgba(8,18,40,.98), rgba(4,10,24,.98));
            border:1px solid rgba(96,165,250,.18);
            box-shadow:0 18px 42px rgba(0,0,0,.24);
        }

        .tsb-accent{
            position:absolute;
            left:0;
            top:0;
            bottom:0;
            width:5px;
            background:#22c55e;
            box-shadow:0 0 22px rgba(34,197,94,.35);
        }

        .tsb-critical .tsb-accent{
            background:#ef4444;
            box-shadow:0 0 24px rgba(239,68,68,.45);
        }

        .tsb-warning .tsb-accent{
            background:#f59e0b;
            box-shadow:0 0 24px rgba(245,158,11,.40);
        }

        .tsb-main{
            display:flex;
            justify-content:space-between;
            gap:26px;
            align-items:center;
            flex-wrap:wrap;
        }

        .tsb-left{
            min-width:280px;
            flex:1;
        }

        .tsb-header{
            display:flex;
            align-items:center;
            gap:9px;
            margin-bottom:14px;
            flex-wrap:wrap;
        }

        .tsb-dot{
            width:11px;
            height:11px;
            border-radius:999px;
            background:#22c55e;
            box-shadow:0 0 0 5px rgba(34,197,94,.12);
            flex-shrink:0;
        }

        .tsb-warning .tsb-dot{
            background:#f59e0b;
            box-shadow:0 0 0 5px rgba(245,158,11,.12);
        }

        .tsb-critical .tsb-dot{
            background:#ef4444;
            box-shadow:0 0 0 5px rgba(239,68,68,.15);
        }

        .tsb-overline{
            font-size:.72rem;
            font-weight:900;
            letter-spacing:.08em;
            text-transform:uppercase;
            color:#64748b;
        }

        .dark .tsb-overline{
            color:#bfdbfe;
        }

        .tsb-state-pill{
            display:inline-flex;
            align-items:center;
            border-radius:999px;
            padding:4px 10px;
            font-size:.72rem;
            font-weight:900;
            line-height:1;
            border:1px solid transparent;
        }

        .tsb-state-pill-ok{
            color:#15803d;
            background:rgba(34,197,94,.10);
            border-color:rgba(34,197,94,.22);
        }

        .tsb-state-pill-warning{
            color:#d97706;
            background:rgba(245,158,11,.10);
            border-color:rgba(245,158,11,.22);
        }

        .tsb-state-pill-critical{
            color:#dc2626;
            background:rgba(239,68,68,.12);
            border-color:rgba(239,68,68,.24);
        }

        .dark .tsb-state-pill-ok{
            color:#86efac;
            background:rgba(34,197,94,.12);
            border-color:rgba(34,197,94,.24);
        }

        .dark .tsb-state-pill-warning{
            color:#fde68a;
            background:rgba(245,158,11,.12);
            border-color:rgba(245,158,11,.26);
        }

        .dark .tsb-state-pill-critical{
            color:#fecaca;
            background:rgba(239,68,68,.18);
            border-color:rgba(239,68,68,.32);
        }

        .tsb-summary-row{
            display:flex;
            align-items:center;
            gap:15px;
        }

        .tsb-alert-icon{
            width:56px;
            height:56px;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-shrink:0;
            border-radius:18px;
            font-size:1.9rem;
            line-height:1;
            font-weight:1000;
            color:#ef4444;
            background:rgba(239,68,68,.12);
            border:1px solid rgba(239,68,68,.28);
            box-shadow:0 0 24px rgba(239,68,68,.16);
        }

        .dark .tsb-alert-icon{
            color:#fca5a5;
            background:rgba(239,68,68,.15);
            border-color:rgba(239,68,68,.30);
            box-shadow:0 0 28px rgba(239,68,68,.18);
        }

        .tsb-summary-copy{
            min-width:0;
        }

        .tsb-summary-title{
            font-size:1.14rem;
            line-height:1.22;
            font-weight:950;
            color:#0f172a;
            letter-spacing:-.015em;
        }

        .dark .tsb-summary-title{
            color:#ffffff;
        }

        .tsb-summary-title strong{
            color:#ef4444;
            font-weight:1000;
        }

        .tsb-summary-desc{
            margin-top:6px;
            max-width:820px;
            font-size:.88rem;
            line-height:1.45;
            color:#64748b;
        }

        .dark .tsb-summary-desc{
            color:#dbeafe;
        }

        .tsb-right{
            display:flex;
            align-items:center;
            gap:12px;
            flex-wrap:wrap;
        }

        .tsb-score-card{
            min-width:136px;
            display:flex;
            align-items:center;
            gap:11px;
            padding:12px 14px;
            border-radius:15px;
            border:1px solid rgba(148,163,184,.16);
            background:rgba(255,255,255,.70);
            box-shadow:inset 0 1px 0 rgba(255,255,255,.05);
        }

        .dark .tsb-score-card{
            background:rgba(255,255,255,.045);
            border-color:rgba(148,163,184,.18);
        }

        .tsb-score-danger{
            border-color:rgba(239,68,68,.30);
            background:rgba(239,68,68,.075);
        }

        .tsb-score-warning{
            border-color:rgba(245,158,11,.30);
            background:rgba(245,158,11,.075);
        }

        .tsb-score-icon{
            width:34px;
            height:34px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:12px;
            flex-shrink:0;
        }

        .tsb-score-danger .tsb-score-icon{
            color:#ef4444;
            background:rgba(239,68,68,.12);
            border:1px solid rgba(239,68,68,.25);
        }

        .tsb-score-warning .tsb-score-icon{
            color:#f59e0b;
            background:rgba(245,158,11,.12);
            border:1px solid rgba(245,158,11,.25);
        }

        .tsb-score-svg{
            width:18px;
            height:18px;
        }

        .tsb-score-label{
            display:block;
            font-size:.75rem;
            line-height:1;
            font-weight:850;
            color:#64748b;
        }

        .dark .tsb-score-label{
            color:#cbd5e1;
        }

        .tsb-score-value{
            display:block;
            margin-top:5px;
            font-size:1.42rem;
            line-height:1;
            font-weight:1000;
            color:#0f172a;
            letter-spacing:-.02em;
        }

        .dark .tsb-score-value{
            color:#ffffff;
        }

        .tsb-modules{
            margin-top:18px;
            display:grid;
            grid-template-columns:repeat(4, minmax(0, 1fr));
            gap:10px;
        }

        .tsb-module{
            min-height:72px;
            padding:13px 14px;
            border-radius:15px;
            border:1px solid rgba(148,163,184,.16);
            background:rgba(255,255,255,.64);
            transition:transform .15s ease, border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }

        .dark .tsb-module{
            background:rgba(255,255,255,.035);
            border-color:rgba(148,163,184,.14);
        }

        .tsb-module:hover{
            transform:translateY(-2px);
            border-color:rgba(96,165,250,.36);
            box-shadow:0 10px 18px rgba(0,0,0,.10);
        }

        .tsb-module-expired{
            border-color:rgba(239,68,68,.34);
            background:rgba(239,68,68,.045);
        }

        .dark .tsb-module-expired{
            border-color:rgba(239,68,68,.30);
            background:rgba(239,68,68,.065);
        }

        .tsb-module-inner{
            display:flex;
            align-items:center;
            gap:11px;
            min-width:0;
            width:100%;
        }

        .tsb-module-icon{
            width:28px;
            height:28px;
            flex-shrink:0;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.15rem;
            line-height:1;
        }

        .tsb-module-text{
            min-width:0;
            flex:1;
        }

        .tsb-module-label{
            display:block;
            font-size:.88rem;
            line-height:1.15;
            font-weight:950;
            color:#0f172a;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .dark .tsb-module-label{
            color:#ffffff;
        }
        .tsb-report-card{
    min-width:280px; /* 👉 duplo šira */
    height:65px; /* 👉 ista visina kao desne kartice */
    
    display:flex;
    align-items:center;
    justify-content:flex-start;

    gap:12px;
    padding:0 18px;

    border-radius:16px;
    border:1px solid rgba(37,99,235,.40);
    background:rgba(37,99,235,.16);

    color:#1d4ed8;
    text-decoration:none;

    transition:all .15s ease;
}

.tsb-report-card:hover{
    transform:translateY(-2px);
    border-color:rgba(37,99,235,.70);
    background:rgba(37,99,235,.22);
    box-shadow:0 12px 22px rgba(37,99,235,.18);
}

.dark .tsb-report-card{
    background:rgba(37,99,235,.18);
    border-color:rgba(96,165,250,.45);
    color:#bfdbfe;
}

.dark .tsb-report-card:hover{
    background:rgba(37,99,235,.28);
    border-color:rgba(96,165,250,.70);
}

.tsb-report-icon{
    width:22px;
    height:22px;
    color:#2563eb;
}

.dark .tsb-report-icon{
    color:#60a5fa;
}

.tsb-report-text{
    display:flex;
    flex-direction:column;
    line-height:1.1;
}

.tsb-report-label{
    font-size:.72rem;
    font-weight:800;
    color:#2563eb;
}

.dark .tsb-report-label{
    color:#bfdbfe;
}

.tsb-report-title{
    font-size:.9rem;
    font-weight:950;
    color:#1e3a8a;
}

.dark .tsb-report-title{
    color:#ffffff;
}

.tsb-report-label{
    display:block;
    font-size:.75rem;
    line-height:1;
    font-weight:850;
    color:#2563eb;
}

.dark .tsb-report-label{
    color:#bfdbfe;
}

.tsb-report-title{
    display:block;
    margin-top:5px;
    font-size:.78rem;
    line-height:1.05;
    font-weight:950;
    color:#1e3a8a;
    white-space:nowrap;
}

.dark .tsb-report-title{
    color:#ffffff;
}

        .tsb-module-stats{
            display:flex;
            align-items:center;
            gap:6px;
            flex-wrap:wrap;
            margin-top:7px;
            font-size:.79rem;
            line-height:1;
            font-weight:900;
        }

        .tsb-expired-link,
        .tsb-expired-text{
            color:#ef4444;
            font-weight:1000;
        }

        .tsb-expired-link{
            text-decoration:none;
            cursor:pointer;
            transition:opacity .15s ease;
        }

        .tsb-expired-link:hover{
            opacity:.82;
            text-decoration:none;
        }

        .tsb-expired-blink{
            animation:tsbTextPulse 2.2s ease-in-out infinite;
        }

        .tsb-soon-text{
            color:#f59e0b;
            font-weight:1000;
        }

        .tsb-sep{
            color:#94a3b8;
            font-weight:900;
        }

        .tsb-chevron{
            margin-left:auto;
            flex-shrink:0;
            color:#93c5fd;
            font-size:1.45rem;
            line-height:1;
            opacity:.9;
        }

        @keyframes tsbTextPulse{
            0%{ text-shadow:0 0 0 rgba(239,68,68,0); }
            50%{ text-shadow:0 0 10px rgba(239,68,68,.75); }
            100%{ text-shadow:0 0 0 rgba(239,68,68,0); }
        }

        @media (max-width:1280px){
            .tsb-modules{
                grid-template-columns:repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width:700px){
            .tsb-wrap{
                padding:18px 16px;
            }

            .tsb-right{
                width:100%;
            }

            .tsb-score-card{
                min-width:0;
                flex:1;
            }

            .tsb-summary-row{
                align-items:flex-start;
            }

            .tsb-alert-icon{
                width:48px;
                height:48px;
                font-size:1.6rem;
            }

            .tsb-modules{
                grid-template-columns:1fr;
            }
        }

        @media (prefers-reduced-motion:reduce){
            .tsb-expired-blink,
            .tsb-module{
                animation:none;
                transition:none;
            }
        }
    </style>
</x-filament-widgets::widget>