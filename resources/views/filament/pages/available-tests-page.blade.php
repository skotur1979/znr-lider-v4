<x-filament-panels::page>
    <style>
        .tests-page-wrap{
            max-width: 1180px;
            margin: 0 auto;
        }

        .tests-grid{
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:18px;
        }

        @media (max-width: 1024px){
            .tests-grid{
                grid-template-columns:1fr;
            }
        }

        .test-card{
            position:relative;
            overflow:hidden;
            min-height:185px;
            border-radius:22px;
            padding:22px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            border:1px solid #d8dde6;
            background:
                radial-gradient(circle at 0% 0%, rgba(245,158,11,.12), transparent 34%),
                linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            box-shadow:0 10px 26px rgba(15,23,42,.07);
            transition:all .18s ease;
        }

        .test-card:hover{
            transform:translateY(-3px);
            border-color:#f59e0b;
            box-shadow:0 18px 36px rgba(15,23,42,.13);
        }

        .dark .test-card{
            border-color:rgba(255,255,255,.08);
            background:
                radial-gradient(circle at 0% 0%, rgba(245,158,11,.18), transparent 35%),
                linear-gradient(135deg, #18181b 0%, #0f172a 100%);
            box-shadow:0 16px 34px rgba(0,0,0,.30);
        }

        .test-top{
            display:flex;
            gap:16px;
            min-width:0;
        }

        .test-icon{
            width:48px;
            height:48px;
            border-radius:16px;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-shrink:0;
            color:#f59e0b;
            border:1px solid rgba(245,158,11,.28);
            background:rgba(245,158,11,.10);
        }

        .test-icon svg{
            width:23px;
            height:23px;
        }

        .test-content{
            min-width:0;
            flex:1;
        }

        .test-title{
            font-size:1.7rem;
            line-height:1.12;
            font-weight:900;
            letter-spacing:-.03em;
            color:#0f172a;
            margin:0 0 14px 0;

            display:-webkit-box;
            -webkit-line-clamp:2;
            -webkit-box-orient:vertical;
            overflow:hidden;
        }

        .dark .test-title{
            color:#ffffff;
        }

        .test-meta{
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            gap:8px;
        }

        .meta-label{
            font-size:.78rem;
            font-weight:700;
            color:#64748b;
        }

        .pill{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:999px;
            padding:7px 10px;
            font-size:.76rem;
            font-weight:800;
        }

        .pill-pass{
            color:#166534;
            background:#dcfce7;
        }

        .pill-questions{
            color:#1d4ed8;
            background:#dbeafe;
        }

        .test-bottom{
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-top:18px;
            padding-top:14px;
            border-top:1px solid rgba(148,163,184,.2);
        }

        .test-hint{
            font-size:.82rem;
            color:#64748b;
            font-weight:600;
        }

        .start-btn{
            display:inline-flex;
            align-items:center;
            gap:8px;
            border-radius:14px;
            padding:12px 18px;
            font-size:.9rem;
            font-weight:900;
            color:#111827;
            background:linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
            box-shadow:0 12px 24px rgba(245,158,11,.25);
            text-decoration:none;
        }

        .start-btn:hover{
            transform:translateY(-2px);
        }

        @media (max-width: 640px){
            .tests-grid{
                grid-template-columns:1fr;
            }

            .test-title{
                font-size:1.35rem;
            }

            .test-bottom{
                flex-direction:column;
                gap:10px;
                align-items:stretch;
            }

            .start-btn{
                width:100%;
                justify-content:center;
            }
        }
    </style>

    <div class="tests-page-wrap">
        <div class="tests-grid">
            @forelse ($tests as $test)

                @php
                    $name = mb_strtolower($test->naziv ?? '');

                    $icon = match (true) {
                        str_contains($name, 'požar') => 'fire',
                        str_contains($name, 'zaštita na radu') => 'shield',
                        str_contains($name, 'ovlaštenik') || str_contains($name, 'povjerenik') => 'briefcase',
                        default => 'clipboard',
                    };
                @endphp

                <div class="test-card">
                    <div class="test-top">

                        <div class="test-icon">
                            @if ($icon === 'fire')
                                🔥
                            @elseif ($icon === 'shield')
                                🛡️
                            @elseif ($icon === 'briefcase')
                                💼
                            @else
                                📋
                            @endif
                        </div>

                        <div class="test-content">
                            <div class="test-title" title="{{ $test->naziv }}">
                                {{ $test->naziv }}
                            </div>

                            <div class="test-meta">
                                <span class="meta-label">Minimalni prolaz</span>

                                <span class="pill pill-pass">
                                    {{ $test->minimalni_prolaz ?? 75 }}%
                                </span>

                                <span class="pill pill-questions">
                                    {{ $test->questions_count }} pitanja
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="test-bottom">
                        <div class="test-hint">
                            Otvara test u aplikaciji
                        </div>

                        <a
                            class="start-btn"
                            href="{{ \App\Filament\Pages\TestFormPage::getUrl(['test' => $test->id]) }}"
                        >
                            ▶ Pokreni test
                        </a>
                    </div>
                </div>

            @empty
                <div class="empty-card">
                    Nema dostupnih testova.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>