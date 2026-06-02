<x-filament-widgets::widget>
    <div class="dms-wrap">
        <div class="dms-header">
            <div>
                <div class="dms-title">Status modula</div>
                <div class="dms-subtitle">Pregled ukupnih, isteklih i uskoro isteklih stavki po modulima.</div>
            </div>
        </div>

        <div class="dms-grid">
            @foreach ($rows as $row)
                @php
                    $expiredCount = (int) ($row['expired_count'] ?? 0);
                    $soonCount = (int) ($row['soon_count'] ?? 0);
                    $totalCount = (int) ($row['total_count'] ?? 0);

                    $expiredLabel = $row['expired_label'] ?? 'Isteklo';
                    $soonLabel = $row['soon_label'] ?? 'Uskoro';

                    $moduleStatusClass = match (true) {
                        $expiredCount > 5 => 'dms-card-danger',
                        $expiredCount > 0 => 'dms-card-warning',
                        default => 'dms-card-ok',
                    };
                @endphp

                <div class="dms-card {{ $moduleStatusClass }}">
                    <div class="dms-card-top">
                        <span class="dms-icon">{{ $row['icon'] }}</span>
                        <span class="dms-label">{{ $row['display_label'] ?? $row['label'] }}</span>
                        <span class="dms-chevron">›</span>
                    </div>

                    <div class="dms-stats">
                        <div class="dms-stat">
                            @if (! empty($row['total_url']))
                            <a href="{{ $row['total_url'] }}" class="dms-stat-label dms-total-link">
                                {{ $row['total_label'] ?? 'Ukupno' }}
                            </a>
                        @else
                            <span class="dms-stat-label dms-total-label">
                                {{ $row['total_label'] ?? 'Ukupno' }}
                            </span>
                        @endif
                            <span class="dms-stat-value dms-total-value">{{ $totalCount }}</span>
                        </div>

                        <div class="dms-stat">
                            @if (! empty($row['expired_url']))
                                <a href="{{ $row['expired_url'] }}" class="dms-stat-label dms-expired-link">
                                    {{ $expiredLabel }}
                                </a>
                            @else
                                <span class="dms-stat-label dms-expired-label">{{ $expiredLabel }}</span>
                            @endif

                            <span class="dms-stat-value dms-expired-value">{{ $expiredCount }}</span>
                        </div>

                        <div class="dms-stat">
                           @if (! empty($row['soon_url']))
                                <a href="{{ $row['soon_url'] }}" class="dms-stat-label dms-soon-link">
                                    {{ $soonLabel }}
                                </a>
                            @else
                                <span class="dms-stat-label dms-soon-label">{{ $soonLabel }}</span>
                            @endif

                            <span class="dms-stat-value dms-soon-value">{{ $soonCount }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .dms-wrap{
            border-radius:20px;
            padding:16px 18px;
            margin-top:-20px;
            margin-bottom:12px;
            border:1px solid rgba(148,163,184,.16);
            background:#ffffff;
            box-shadow:0 10px 26px rgba(15,23,42,.06);
        }

        .dark .dms-wrap{
            background:
                radial-gradient(circle at 0% 0%, rgba(37,99,235,.16), transparent 34%),
                linear-gradient(180deg, rgba(8,18,40,.98), rgba(4,10,24,.98));
            border:1px solid rgba(96,165,250,.18);
            box-shadow:0 18px 42px rgba(0,0,0,.22);
        }

        .dms-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:12px;
        }

        .dms-title{
            font-size:1rem;
            line-height:1.15;
            font-weight:950;
            color:#0f172a;
        }

        .dark .dms-title{
            color:#ffffff;
        }

        .dms-subtitle{
            margin-top:3px;
            font-size:.78rem;
            line-height:1.35;
            color:#64748b;
        }

        .dark .dms-subtitle{
            color:#bfdbfe;
        }

        .dms-grid{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            gap:10px;
        }

        .dms-card{
            min-height:94px;
            border-radius:16px;
            padding:13px 14px;
            border:1px solid rgba(148,163,184,.16);
            background:rgba(255,255,255,.70);
            transition:transform .15s ease,border-color .15s ease,box-shadow .15s ease;
        }

        .dark .dms-card{
            background:rgba(255,255,255,.035);
            border-color:rgba(148,163,184,.14);
        }

        .dms-card:hover{
            transform:translateY(-2px);
            border-color:rgba(96,165,250,.36);
            box-shadow:0 10px 18px rgba(0,0,0,.10);
        }

        .dms-card-warning{
            border-color:rgba(245,158,11,.95) !important;
            background:#fff0c2 !important;
            box-shadow:0 8px 22px rgba(245,158,11,.18) !important;
        }

        .dark .dms-card-warning{
            background:rgba(245,158,11,.24) !important;
            box-shadow:0 8px 22px rgba(245,158,11,.22) !important;
        }

        .dms-card-danger{
            border-color:rgba(239,68,68,.95) !important;
            background:#ffd6d6 !important;
            box-shadow:0 10px 26px rgba(239,68,68,.22) !important;
        }

        .dark .dms-card-danger{
            background:rgba(239,68,68,.30) !important;
            box-shadow:0 10px 26px rgba(239,68,68,.25) !important;
        }

        .dms-card-top{
            display:flex;
            align-items:center;
            gap:10px;
            min-width:0;
        }

        .dms-icon{
            width:28px;
            height:28px;
            flex-shrink:0;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.12rem;
        }

        .dms-label{
            flex:1;
            min-width:0;
            font-size:.92rem;
            line-height:1.15;
            font-weight:950;
            color:#0f172a;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .dark .dms-label{
            color:#ffffff;
        }

        .dms-chevron{
            color:#93c5fd;
            font-size:1.45rem;
            line-height:1;
            opacity:.9;
        }

        .dms-stats{
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:8px;
        margin-top:12px;
        align-items:center;
        }

        .dms-stat{
            min-width:0;
            text-align:center;
        }

        .dms-stat:first-child{
            text-align:left;
        }

        .dms-stat:last-child{
            text-align:right;
        }

        .dms-stat-label{
        display:block;
        font-size:.68rem;
        line-height:1;
        font-weight:950;
        text-decoration:none;
        white-space:nowrap;
        min-height:12px;
    }

        .dms-stat-value{
        display:block;
        margin-top:5px;
        font-size:1.08rem;
        line-height:1;
        font-weight:1000;
        letter-spacing:-.02em;
    }

        .dms-total-label,
.dms-total-link,
.dms-total-value{
    color:#22c55e;
}

.dms-total-link:hover{
    text-decoration:underline;
    opacity:.9;
}

        .dms-expired-label,
        .dms-expired-link,
        .dms-expired-value{
            color:#ef4444;
        }

        .dms-soon-label,
        .dms-soon-link,
        .dms-soon-value{
            color:#f59e0b;
        }

        .dms-expired-link:hover,
        .dms-soon-link:hover{
            text-decoration:underline;
            opacity:.9;
        }

        @media (max-width:1280px){
            .dms-grid{
                grid-template-columns:repeat(2,minmax(0,1fr));
            }
        }

        @media (max-width:700px){
            .dms-wrap{
                padding:15px 14px;
            }

            .dms-grid{
                grid-template-columns:1fr;
            }
        }
    </style>
</x-filament-widgets::widget>