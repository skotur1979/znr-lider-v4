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
                <span class="tsb-dot"></span>

                <div class="tsb-text">
                    <div class="tsb-title-row">
                        <span class="tsb-overline">Status sustava</span>
                        <span class="tsb-state-pill tsb-state-pill-{{ $state }}">
                            {{ $title }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="tsb-right">
                <span class="tsb-badge tsb-badge-danger {{ $totalExpired > 0 ? 'tsb-badge-blink' : '' }}">
                    Isteklo: {{ $totalExpired }}
                </span>

                <span class="tsb-badge tsb-badge-warning">
                    Uskoro: {{ $totalSoon }}
                </span>
            </div>
        </div>

        @if (count($rows))
            <div class="tsb-modules">
                @foreach ($rows as $row)
                    <div class="tsb-module {{ $row['expired_count'] > 0 ? 'tsb-module-expired-active' : '' }}">
                        <div class="tsb-module-top">
                            <span class="tsb-module-icon">{{ $row['icon'] }}</span>
                            <span class="tsb-module-label">{{ $row['label'] }}</span>
                        </div>

                        <div class="tsb-module-bottom">
                            @if (($row['expired_count'] ?? 0) > 0 && ! empty($row['expired_url']))
                                <a
                                    href="{{ $row['expired_url'] }}"
                                    class="tsb-module-stat tsb-module-stat-expired tsb-module-link {{ $row['expired_count'] > 0 ? 'tsb-stat-blink' : '' }}"
                                >
                                    Isteklo {{ $row['expired_count'] }}
                                </a>
                            @else
                                <span class="tsb-module-stat tsb-module-stat-expired">
                                    Isteklo {{ $row['expired_count'] }}
                                </span>
                            @endif

                            <span class="tsb-module-sep">/</span>

                            <span class="tsb-module-stat tsb-module-stat-soon">
                                Uskoro {{ $row['soon_count'] }}
                            </span>
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
            border-radius:18px;
            padding:14px 16px 14px 16px;
            margin-bottom:16px;
            border:1px solid #dbe3f0;
            background:#ffffff;
            box-shadow:0 1px 2px rgba(15,23,42,.04);
        }

        .dark .tsb-wrap{
            border:1px solid rgba(148,163,184,.12);
            background:linear-gradient(180deg, rgba(8,18,40,.96), rgba(4,10,24,.96));
            box-shadow:0 10px 22px rgba(0,0,0,.14);
        }

        .tsb-accent{
            position:absolute;
            left:0;
            top:0;
            bottom:0;
            width:4px;
            border-radius:18px 0 0 18px;
            background:#22c55e;
        }

        .tsb-warning .tsb-accent{
            background:#f59e0b;
        }

        .tsb-critical .tsb-accent{
            background:#ef4444;
        }

        .tsb-main{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:14px;
            flex-wrap:wrap;
        }

        .tsb-left{
            display:flex;
            align-items:center;
            gap:12px;
            min-width:0;
            flex:1;
        }

        .tsb-dot{
            width:12px;
            height:12px;
            border-radius:999px;
            flex-shrink:0;
            background:#22c55e;
            box-shadow:0 0 0 4px rgba(34,197,94,.12);
        }

        .tsb-warning .tsb-dot{
            background:#f59e0b;
            box-shadow:0 0 0 4px rgba(245,158,11,.12);
        }

        .tsb-critical .tsb-dot{
            background:#ef4444;
            box-shadow:0 0 0 4px rgba(239,68,68,.12);
        }

        .tsb-text{
            min-width:0;
        }

        .tsb-title-row{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
        }

        .tsb-overline{
            font-size:.72rem;
            font-weight:800;
            letter-spacing:.08em;
            text-transform:uppercase;
            color:#94a3b8;
        }

        .dark .tsb-overline{
            color:#93c5fd;
        }

        .tsb-state-pill{
            display:inline-flex;
            align-items:center;
            justify-content:center;
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
            border-color:rgba(34,197,94,.20);
        }

        .tsb-state-pill-warning{
            color:#d97706;
            background:rgba(245,158,11,.10);
            border-color:rgba(245,158,11,.20);
        }

        .tsb-state-pill-critical{
            color:#dc2626;
            background:rgba(239,68,68,.10);
            border-color:rgba(239,68,68,.20);
        }

        .dark .tsb-state-pill-ok{
            color:#86efac;
            background:rgba(34,197,94,.10);
            border-color:rgba(34,197,94,.22);
        }

        .dark .tsb-state-pill-warning{
            color:#fde68a;
            background:rgba(245,158,11,.12);
            border-color:rgba(245,158,11,.22);
        }

        .dark .tsb-state-pill-critical{
            color:#fca5a5;
            background:rgba(239,68,68,.12);
            border-color:rgba(239,68,68,.24);
        }

        .tsb-right{
            display:flex;
            align-items:center;
            gap:8px;
            flex-wrap:wrap;
        }

        .tsb-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:999px;
            padding:7px 11px;
            font-size:.76rem;
            font-weight:800;
            line-height:1;
            border:1px solid transparent;
        }

        .tsb-badge-danger{
            background:rgba(239,68,68,.10);
            color:#dc2626;
            border-color:rgba(239,68,68,.20);
        }

        .dark .tsb-badge-danger{
            color:#fca5a5;
            background:rgba(239,68,68,.12);
            border-color:rgba(239,68,68,.24);
        }

        .tsb-badge-warning{
            background:rgba(245,158,11,.10);
            color:#d97706;
            border-color:rgba(245,158,11,.20);
        }

        .dark .tsb-badge-warning{
            color:#fde68a;
            background:rgba(245,158,11,.12);
            border-color:rgba(245,158,11,.24);
        }

        .tsb-modules{
            margin-top:12px;
            padding-top:12px;
            border-top:1px solid rgba(148,163,184,.12);
            display:grid;
            grid-template-columns:repeat(4, minmax(0, 1fr));
            gap:8px;
        }

        .tsb-module{
            display:flex;
            flex-direction:column;
            justify-content:center;
            gap:6px;
            min-height:54px;
            padding:8px 10px;
            border-radius:14px;
            background:rgba(255,255,255,.03);
            border:1px solid rgba(148,163,184,.14);
            font-size:.75rem;
        }

        .dark .tsb-module{
            background:rgba(255,255,255,.02);
            border:1px solid rgba(148,163,184,.12);
        }

        .tsb-module-expired-active{
            border-color:rgba(239,68,68,.22);
            background:rgba(239,68,68,.04);
        }

        .dark .tsb-module-expired-active{
            border-color:rgba(239,68,68,.22);
            background:rgba(239,68,68,.06);
        }

        .tsb-module-top{
            display:flex;
            align-items:center;
            gap:7px;
            min-width:0;
        }

        .tsb-module-icon{
            font-size:.84rem;
            line-height:1;
            flex-shrink:0;
        }

        .tsb-module-label{
            color:#0f172a;
            font-weight:800;
            line-height:1.1;
            min-width:0;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .dark .tsb-module-label{
            color:#ffffff;
        }

        .tsb-module-bottom{
            display:flex;
            align-items:center;
            gap:6px;
            flex-wrap:wrap;
        }

        .tsb-module-stat{
            font-weight:800;
            line-height:1;
        }

        .tsb-module-stat-expired{
            color:#ef4444;
            font-weight:900;
            background:transparent !important;
            padding:0 !important;
            border:none !important;
        }

        .tsb-module-stat-soon{
            color:#f59e0b;
        }

        .tsb-module-sep{
            color:#94a3b8;
            font-weight:700;
        }

        .tsb-module-link{
            text-decoration:none;
            cursor:pointer;
            transition:opacity .15s ease, transform .15s ease;
        }

        .tsb-module-link:hover{
            opacity:.9;
            transform:translateY(-1px);
            text-decoration:none;
        }

        .tsb-module-link:focus{
            outline:none;
            text-decoration:none;
        }

        .tsb-badge-blink{
            animation: tsbSoftBlink 1.8s ease-in-out infinite;
        }

        .tsb-stat-blink{
            animation: tsbTextPulse 2.2s ease-in-out infinite;
        }

        @keyframes tsbTextPulse{
            0%{
                opacity:1;
                text-shadow:0 0 0 rgba(239,68,68,0);
            }
            50%{
                opacity:1;
                text-shadow:0 0 8px rgba(239,68,68,.55);
            }
            100%{
                opacity:1;
                text-shadow:0 0 0 rgba(239,68,68,0);
            }
        }

        @keyframes tsbSoftBlink{
            0%{
                opacity:1;
                transform:scale(1);
                box-shadow:0 0 0 0 rgba(239,68,68,0);
                filter:brightness(1);
            }
            50%{
                opacity:1;
                transform:scale(1.04);
                box-shadow:0 0 0 6px rgba(239,68,68,.14);
                filter:brightness(1.08);
            }
            100%{
                opacity:1;
                transform:scale(1);
                box-shadow:0 0 0 0 rgba(239,68,68,0);
                filter:brightness(1);
            }
        }

        @media (max-width: 1280px){
            .tsb-modules{
                grid-template-columns:repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px){
            .tsb-main{
                flex-direction:column;
                align-items:flex-start;
            }

            .tsb-modules{
                grid-template-columns:repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px){
            .tsb-wrap{
                padding:12px 12px 10px 12px;
            }

            .tsb-modules{
                grid-template-columns:1fr;
            }

            .tsb-module{
                min-height:50px;
                padding:8px 9px;
            }
        }

        @media (prefers-reduced-motion: reduce){
            .tsb-badge-blink,
            .tsb-stat-blink{
                animation:none;
            }
        }
    </style>
</x-filament-widgets::widget>