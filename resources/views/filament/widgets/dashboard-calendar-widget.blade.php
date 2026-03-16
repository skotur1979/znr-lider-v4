<x-filament-widgets::widget>
    <style>
        .znr-cal-wrap{
            margin-top: 2px;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #d8dde6;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15,23,42,.05);
        }

        .dark .znr-cal-wrap{
            background: rgba(10, 20, 40, .96);
            border-color: rgba(59,130,246,.18);
            box-shadow: none;
        }

        .znr-cal-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:10px 14px;
            border-bottom:1px solid #e5e7eb;
        }

        .dark .znr-cal-head{
            border-color: rgba(255,255,255,.08);
        }

        .znr-cal-title{
            font-size: 22px;
            font-weight: 700;
            color:#0f172a;
        }

        .dark .znr-cal-title{
            color:#fff;
        }

        .znr-cal-nav{
            display:flex;
            gap:6px;
        }

        .znr-cal-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:32px;
            height:32px;
            border-radius:8px;
            border:1px solid #d1d5db;
            background:#fff;
            color:#0f172a;
            font-weight:700;
            font-size:14px;
            cursor:pointer;
        }

        .znr-cal-btn:hover{
            border-color:#f59e0b;
            transform:translateY(-1px);
        }

        .dark .znr-cal-btn{
            background:rgba(255,255,255,.04);
            border-color:rgba(255,255,255,.10);
            color:#fff;
        }

        .znr-cal-legend{
            display:flex;
            flex-wrap:wrap;
            gap:10px 18px;
            padding:10px 14px;
            border-bottom:1px solid #e5e7eb;
            background:#f8fafc;
        }

        .dark .znr-cal-legend{
            background:rgba(255,255,255,.02);
            border-color:rgba(255,255,255,.08);
        }

        .znr-legend-item{
            display:flex;
            align-items:center;
            gap:8px;
            font-size:12px;
            font-weight:700;
            color:#334155;
        }

        .dark .znr-legend-item{
            color:#cbd5e1;
        }

        .znr-legend-icon{
            width:15px;
            height:15px;
            flex-shrink:0;
        }

        .znr-legend-dot{
            width:10px;
            height:10px;
            border-radius:999px;
            flex-shrink:0;
        }

        .znr-cal-grid{
            display:grid;
            grid-template-columns:repeat(7, minmax(0, 1fr));
        }

        .znr-cal-weekday{
            padding:10px 4px;
            text-align:center;
            font-size:14px;
            font-weight:800;
            color:#64748b;
            border-bottom:1px solid #e5e7eb;
            background:#f8fafc;
        }

        .dark .znr-cal-weekday{
            color:#cbd5e1;
            background:rgba(255,255,255,.02);
            border-color:rgba(255,255,255,.08);
        }

        .znr-cal-cell{
            min-height:84px;
            padding:4px;
            border-right:1px solid #eef2f7;
            border-bottom:1px solid #eef2f7;
            background:#fff;
            transition:background .15s ease, box-shadow .15s ease;
        }

        .znr-cal-cell:nth-child(7n){
            border-right:none;
        }

        .dark .znr-cal-cell{
            background:transparent;
            border-color:rgba(255,255,255,.06);
        }

        .znr-cal-cell.muted{
            background:#f8fafc;
        }

        .dark .znr-cal-cell.muted{
            background:rgba(255,255,255,.02);
        }

        .znr-cal-cell.today{
            background:#eff6ff;
            box-shadow: inset 0 0 0 2px rgba(59,130,246,.25);
        }

        .dark .znr-cal-cell.today{
            background:rgba(37,99,235,.10);
            box-shadow: inset 0 0 0 2px rgba(96,165,250,.30);
        }

        .znr-cal-day{
            font-size:15px;
            font-weight:800;
            color:#0f172a;
            margin-bottom:4px;
        }

        .dark .znr-cal-day{
            color:#fff;
        }

        .muted .znr-cal-day{
            opacity:.45;
        }

        .today .znr-cal-day{
            color:#2563eb;
        }

        .dark .today .znr-cal-day{
            color:#93c5fd;
        }

        .znr-event{
            display:block;
            width:100%;
            margin-bottom:2px;
            padding:2px 5px;
            border-radius:5px;
            text-decoration:none;
            font-size:9px;
            line-height:1.1;
            color:#fff;
            overflow:hidden;
            white-space:nowrap;
            text-overflow:ellipsis;
            transition:transform .12s ease, filter .12s ease;
        }

        .znr-event:hover{
            transform:translateY(-1px);
            filter:brightness(1.05);
        }

        .znr-more{
            font-size:9px;
            color:#64748b;
            margin-top:2px;
            font-weight:600;
        }

        .dark .znr-more{
            color:#cbd5e1;
        }

        /* LIJEČNIČKI - plava */
        .medical{
            background:#2563eb;
            border-left:3px solid #93c5fd;
            box-shadow:0 0 0 1px rgba(37,99,235,.30);
        }
        .medical-soon{
            background:#3b82f6;
            border-left:3px solid #bfdbfe;
        }
        .medical-expired{
            background:#1d4ed8;
            border-left:3px solid #dbeafe;
        }

        /* OSTALI ROKOVI ZAPOSLENIKA - narančasta */
        .certificate{
            background:#f97316;
            border-left:3px solid #fdba74;
        }
        .certificate-soon{
            background:#fb923c;
            border-left:3px solid #fed7aa;
        }
        .certificate-expired{
            background:#c2410c;
            border-left:3px solid #fdba74;
        }

        /* STROJEVI - žuta */
        .machine{
            background:#facc15;
            color:#111827;
            border-left:3px solid #fde047;
        }
        .machine-soon{
            background:#fde047;
            color:#111827;
            border-left:3px solid #fef08a;
        }
        .machine-expired{
            background:#eab308;
            color:#111827;
            border-left:3px solid #fde047;
        }

        /* VATROGASNI APARATI - crvena */
        .fire{
            background:#ef4444;
            border-left:3px solid #fca5a5;
        }
        .fire-soon{
            background:#f87171;
            border-left:3px solid #fecaca;
        }
        .fire-expired{
            background:#b91c1c;
            border-left:3px solid #fca5a5;
        }

        /* OSTALA ISPITIVANJA - zelena */
        .misc{
            background:#22c55e;
            border-left:3px solid #86efac;
        }
        .misc-soon{
            background:#4ade80;
            color:#052e16;
            border-left:3px solid #bbf7d0;
        }
        .misc-expired{
            background:#15803d;
            border-left:3px solid #86efac;
        }

        @media (max-width: 1024px){
            .znr-cal-cell{
                min-height:72px;
                padding:3px;
            }

            .znr-cal-day{
                font-size:14px;
            }

            .znr-cal-weekday{
                font-size:13px;
            }

            .znr-event{
                font-size:8px;
            }
        }

        @media (max-width: 768px){
            .znr-cal-title{
                font-size:18px;
            }

            .znr-cal-weekday{
                font-size:12px;
            }

            .znr-cal-cell{
                min-height:60px;
                padding:3px;
            }

            .znr-cal-day{
                font-size:13px;
            }

            .znr-cal-legend{
                gap:8px 10px;
            }

            .znr-legend-item{
                font-size:11px;
            }

            .znr-legend-icon{
                width:13px;
                height:13px;
            }
        }
    </style>

    <div class="znr-cal-wrap">
        <div class="znr-cal-head">
            <div class="znr-cal-title">{{ \Illuminate\Support\Str::ucfirst($monthLabel) }}</div>

            <div class="znr-cal-nav">
                <button type="button" class="znr-cal-btn" wire:click="previousMonth">‹</button>
                <button type="button" class="znr-cal-btn" wire:click="nextMonth">›</button>
            </div>
        </div>

        <div class="znr-cal-legend">
            <div class="znr-legend-item">
                <span class="znr-legend-dot medical"></span>
                @svg('heroicon-m-users', 'znr-legend-icon')
                <span>Liječnički pregledi</span>
            </div>

            <div class="znr-legend-item">
                <span class="znr-legend-dot certificate"></span>
                @svg('heroicon-m-users', 'znr-legend-icon')
                <span>Ostali rokovi zaposlenika</span>
            </div>

            <div class="znr-legend-item">
                <span class="znr-legend-dot machine"></span>
                @svg('heroicon-m-cog-6-tooth', 'znr-legend-icon')
                <span>Strojevi</span>
            </div>

            <div class="znr-legend-item">
                <span class="znr-legend-dot fire"></span>
                @svg('heroicon-m-fire', 'znr-legend-icon')
                <span>Vatrogasni aparati</span>
            </div>

            <div class="znr-legend-item">
                <span class="znr-legend-dot misc"></span>
                @svg('heroicon-m-wrench-screwdriver', 'znr-legend-icon')
                <span>Ostala ispitivanja</span>
            </div>
        </div>

        <div class="znr-cal-grid">
            @foreach($weekdays as $weekday)
                <div class="znr-cal-weekday">{{ $weekday }}</div>
            @endforeach
        </div>

        @foreach($days as $week)
            <div class="znr-cal-grid">
                @foreach($week as $day)
                    <div class="znr-cal-cell {{ $day['in_month'] ? '' : 'muted' }} {{ $day['date']->isToday() ? 'today' : '' }}">
                        <div class="znr-cal-day">{{ $day['date']->day }}</div>

                        @foreach($day['items'] as $item)
                            <a
                                href="{{ $item['url'] }}"
                                class="znr-event {{ $item['class'] }}"
                                title="{{ $item['title'] }}"
                            >
                                {{ $item['title'] }}
                            </a>
                        @endforeach

                        @if($day['extra_count'] > 0)
                            <div class="znr-more">+{{ $day['extra_count'] }} više</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>