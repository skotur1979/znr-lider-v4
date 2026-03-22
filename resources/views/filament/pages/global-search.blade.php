<x-filament-panels::page>
    @php
        $groups = [
            'employees' => [
                'label' => 'Zaposlenici',
                'icon' => '👥',
                'accent' => '#38bdf8',
                'accent_soft' => 'rgba(56, 189, 248, 0.14)',
            ],
            'machines' => [
                'label' => 'Radna oprema',
                'icon' => '⚙️',
                'accent' => '#f59e0b',
                'accent_soft' => 'rgba(245, 158, 11, 0.14)',
            ],
            'fires' => [
                'label' => 'Vatrogasni aparati',
                'icon' => '🔥',
                'accent' => '#f43f5e',
                'accent_soft' => 'rgba(244, 63, 94, 0.14)',
            ],
            'miscellaneous' => [
                'label' => 'Ostala ispitivanja',
                'icon' => '🛠️',
                'accent' => '#22c55e',
                'accent_soft' => 'rgba(34, 197, 94, 0.14)',
            ],
            'chemicals' => [
                'label' => 'Kemikalije',
                'icon' => '🧪',
                'accent' => '#8b5cf6',
                'accent_soft' => 'rgba(139, 92, 246, 0.14)',
            ],
        ];

        $visibleGroups = collect($groups)->filter(fn ($group, $key) => ! empty($results[$key] ?? []));
        $hasAnyResults = $visibleGroups->isNotEmpty();
    @endphp

    <style>
        .znr-search-wrap{
            max-width: 1450px;
            margin: 0 auto;
        }
        .znr-search-hero{
            border-radius: 22px;
            padding: 24px;
            border: 1px solid rgba(148, 163, 184, 0.20);
            background: linear-gradient(135deg, rgba(10,35,81,.98), rgba(1,10,30,.98));
            box-shadow: 0 10px 30px rgba(15,23,42,.20);
        }
        .dark .znr-search-hero{
            border-color: rgba(255,255,255,.08);
            background: linear-gradient(135deg, rgba(10,35,81,.98), rgba(1,10,30,.98));
            box-shadow: 0 10px 30px rgba(0,0,0,.28);
        }
        .znr-search-head{
            display:flex;
            gap:16px;
            align-items:flex-start;
            margin-bottom:18px;
        }
        .znr-search-head-icon{
            width:52px;
            height:52px;
            border-radius:16px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:24px;
            background: rgba(59,130,246,.12);
            color:#60a5fa;
            border:1px solid rgba(96,165,250,.22);
            flex-shrink:0;
        }
        .znr-search-title{
            margin:0;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing:-0.02em;
            color:#ffffff;
        }
        .znr-search-subtitle{
            margin:6px 0 0 0;
            font-size:.95rem;
            line-height:1.55;
            color:#bfdbfe;
        }
        .znr-search-input-wrap{
            position:relative;
        }
        .znr-search-input-icon{
            position:absolute;
            left:14px;
            top:50%;
            transform:translateY(-50%);
            font-size:18px;
            color:#93c5fd;
            pointer-events:none;
        }
        .znr-search-input{
            width:100%;
            height:54px;
            border-radius:18px;
            border:1px solid rgba(96,165,250,.32);
            background:#061a3a;
            color:#ffffff;
            padding:0 16px 0 42px;
            font-size:16px;
            outline:none;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.04), 0 0 0 2px rgba(59,130,246,.10);
        }
        .znr-search-input::placeholder{
            color:#93c5fd;
        }
        .znr-search-input:focus{
            border-color:#60a5fa;
            box-shadow:0 0 0 3px rgba(96,165,250,.18);
        }
        .znr-search-meta{
            margin-top:12px;
            font-size:.95rem;
            color:#cbd5e1;
        }
        .znr-search-meta strong{
            color:#ffffff;
        }
        .znr-search-grid{
            display:grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap:22px;
            margin-top:22px;
        }
        @media (max-width: 1100px){
            .znr-search-grid{
                grid-template-columns: 1fr;
            }
        }
        .znr-search-card{
            border-radius:22px;
            padding:18px;
            border:1px solid rgba(148,163,184,.14);
            background: linear-gradient(180deg, rgba(12,25,50,.96), rgba(5,12,28,.96));
            box-shadow: 0 10px 28px rgba(0,0,0,.22);
        }
        .znr-search-card-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:14px;
            gap:12px;
        }
        .znr-search-card-head-left{
            display:flex;
            align-items:center;
            gap:12px;
            min-width:0;
        }
        .znr-search-card-icon{
            width:44px;
            height:44px;
            border-radius:14px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:20px;
            flex-shrink:0;
        }
        .znr-search-card-title{
            margin:0;
            font-size:1.12rem;
            font-weight:700;
            color:#ffffff;
        }
        .znr-search-card-sub{
            margin-top:3px;
            font-size:.82rem;
            color:#94a3b8;
        }
        .znr-search-badge{
            white-space:nowrap;
            border-radius:999px;
            padding:6px 10px;
            font-size:.78rem;
            font-weight:700;
            border:1px solid transparent;
        }
        .znr-search-items{
            display:flex;
            flex-direction:column;
            gap:10px;
        }
        .znr-search-item{
            display:flex;
            align-items:flex-start;
            gap:12px;
            text-decoration:none;
            border-radius:18px;
            padding:14px;
            border:1px solid rgba(148,163,184,.14);
            background:rgba(7,18,40,.78);
            transition:all .15s ease;
        }
        .znr-search-item:hover{
            transform:translateY(-1px);
            box-shadow:0 8px 20px rgba(0,0,0,.18);
        }
        .znr-search-item-icon{
            width:38px;
            height:38px;
            border-radius:12px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:17px;
            flex-shrink:0;
        }
        .znr-search-item-body{
            min-width:0;
            flex:1;
        }
        .znr-search-item-title{
            font-size:1rem;
            font-weight:700;
            color:#ffffff;
            line-height:1.35;
            word-break:break-word;
        }
        .znr-search-item-subtitle{
            margin-top:4px;
            font-size:.84rem;
            line-height:1.5;
            color:#a5b4fc;
            word-break:break-word;
        }
        .znr-search-item-arrow{
            flex-shrink:0;
            color:#93c5fd;
            font-size:20px;
            line-height:1;
            margin-top:3px;
        }
        .znr-search-empty{
            border-radius:22px;
            padding:42px 24px;
            text-align:center;
            border:1px dashed rgba(148,163,184,.24);
            background: linear-gradient(180deg, rgba(12,25,50,.92), rgba(5,12,28,.94));
            margin-top:22px;
        }
        .znr-search-empty-icon{
            width:58px;
            height:58px;
            border-radius:18px;
            margin:0 auto 14px auto;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:26px;
            background:rgba(148,163,184,.10);
            color:#cbd5e1;
        }
        .znr-search-empty-title{
            font-size:1.08rem;
            font-weight:700;
            color:#ffffff;
        }
        .znr-search-empty-text{
            margin-top:8px;
            font-size:.92rem;
            line-height:1.55;
            color:#94a3b8;
        }
        .znr-mark{
            background: rgba(250, 204, 21, .22);
            color: #fde68a;
            padding: 0 4px;
            border-radius: 6px;
            font-weight: 700;
        }
    </style>

    <div class="znr-search-wrap">
        <div class="znr-search-hero">
            <div class="znr-search-head">
                <div class="znr-search-head-icon">🔎</div>

                <div>
                    <h2 class="znr-search-title">Globalna pretraga</h2>
                    <p class="znr-search-subtitle">
                        Pretraži zaposlenike, radnu opremu, vatrogasne aparate, ostala ispitivanja i kemikalije na jednom mjestu.
                    </p>
                </div>
            </div>

            <div class="znr-search-input-wrap">
                <span class="znr-search-input-icon">⌕</span>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="query"
                    placeholder="Upiši ime, OIB, UFI, CAS, tvornički broj, lokaciju..."
                    class="znr-search-input"
                >
            </div>

            <div class="znr-search-meta">
                @if (mb_strlen(trim($query)) < 2)
                    Upiši najmanje 2 znaka za pretragu.
                @else
                    Pronađeno rezultata: <strong>{{ $this->totalResults }}</strong>
                @endif
            </div>
        </div>

        @if (mb_strlen(trim($query)) >= 2)
            @if ($hasAnyResults)
                <div class="znr-search-grid">
                    @foreach ($visibleGroups as $key => $group)
                        <div class="znr-search-card">
                            <div class="znr-search-card-head">
                                <div class="znr-search-card-head-left">
                                    <div class="znr-search-card-icon"
                                         style="background: {{ $group['accent_soft'] }}; color: {{ $group['accent'] }}; border: 1px solid {{ $group['accent'] }}33;">
                                        {{ $group['icon'] }}
                                    </div>

                                    <div>
                                        <div class="znr-search-card-title">{{ $group['label'] }}</div>
                                        <div class="znr-search-card-sub">Modul pretrage</div>
                                    </div>
                                </div>

                                <div class="znr-search-badge"
                                     style="background: {{ $group['accent_soft'] }}; color: {{ $group['accent'] }}; border-color: {{ $group['accent'] }}33;">
                                    {{ count($results[$key] ?? []) }} rezultata
                                </div>
                            </div>

                            <div class="znr-search-items">
                                @foreach ($results[$key] as $item)
                                    <a
                                        href="{{ $item['url'] }}"
                                        class="znr-search-item"
                                        style="border-left: 4px solid {{ $group['accent'] }};"
                                    >
                                        <div class="znr-search-item-icon"
                                             style="background: {{ $group['accent_soft'] }}; color: {{ $group['accent'] }};">
                                            {{ $group['icon'] }}
                                        </div>

                                        <div class="znr-search-item-body">
                                            <div class="znr-search-item-title">
                                                {!! $this->highlight($item['title']) !!}
                                            </div>

                                            @if (!empty($item['subtitle']))
                                                <div class="znr-search-item-subtitle">
                                                    {!! $this->highlight($item['subtitle']) !!}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="znr-search-item-arrow">›</div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="znr-search-empty">
                    <div class="znr-search-empty-icon">🔎</div>
                    <div class="znr-search-empty-title">
                        Nema rezultata za pojam “{{ $query }}”
                    </div>
                    <div class="znr-search-empty-text">
                        Pokušaj s drugim pojmom, kraćim izrazom, brojem dokumenta, OIB-om, UFI brojem ili lokacijom.
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>