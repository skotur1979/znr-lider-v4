<x-filament-panels::page>
    <style>
        .tests-page-wrap{
            max-width: 1180px;
            margin: 0 auto;
        }

        .tests-grid{
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
        }

        @media (max-width: 1024px){
            .tests-grid{
                grid-template-columns: 1fr;
            }
        }

        .test-card{
            position: relative;
            overflow: hidden;
            min-height: 220px;
            border-radius: 22px;
            padding: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            border: 1px solid #e2e8f0;
            background:
                radial-gradient(circle at top left, rgba(249,115,22,.10), transparent 32%),
                radial-gradient(circle at top right, rgba(59,130,246,.08), transparent 35%),
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.06),
                0 1px 0 rgba(255,255,255,.7) inset;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .test-card:hover{
            transform: translateY(-3px);
            border-color: #fdba74;
            box-shadow:
                0 16px 36px rgba(15, 23, 42, 0.10),
                0 1px 0 rgba(255,255,255,.8) inset;
        }

        .dark .test-card{
            border-color: rgba(255,255,255,.08);
            background:
                radial-gradient(circle at top left, rgba(249,115,22,.18), transparent 34%),
                radial-gradient(circle at top right, rgba(59,130,246,.14), transparent 36%),
                linear-gradient(180deg, rgba(17,24,39,.98) 0%, rgba(15,23,42,.98) 100%);
            box-shadow: 0 14px 32px rgba(0,0,0,.28);
        }

        .dark .test-card:hover{
            border-color: rgba(249,115,22,.32);
            box-shadow: 0 18px 40px rgba(0,0,0,.35);
        }

        .test-card-left{
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            flex: 1;
            min-width: 0;
        }

        .test-icon{
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #c2410c;
            border: 1px solid rgba(249,115,22,.22);
            background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
            box-shadow: 0 8px 18px rgba(249,115,22,.10);
        }

        .dark .test-icon{
            color: #fdba74;
            border-color: rgba(249,115,22,.30);
            background: rgba(249,115,22,.10);
            box-shadow: none;
        }

        .test-icon svg{
            width: 26px;
            height: 26px;
        }

        .test-content{
            min-width: 0;
            flex: 1;
        }

        .test-title{
            font-size: 1.9rem;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #0f172a;
            margin-bottom: .95rem;
            word-break: break-word;
        }

        .dark .test-title{
            color: #f8fafc;
        }

        .test-meta{
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .75rem;
            margin-bottom: .8rem;
        }

        .meta-label{
            font-size: .84rem;
            font-weight: 700;
            color: #64748b;
        }

        .dark .meta-label{
            color: #cbd5e1;
        }

        .pill{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: .5rem .9rem;
            font-size: .82rem;
            font-weight: 800;
            line-height: 1;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .pill-pass{
            color: #166534;
            background: #dcfce7;
            border-color: #bbf7d0;
        }

        .pill-questions{
            color: #1d4ed8;
            background: #dbeafe;
            border-color: #bfdbfe;
        }

        .dark .pill-pass{
            color: #bbf7d0;
            background: rgba(34,197,94,.14);
            border-color: rgba(34,197,94,.28);
        }

        .dark .pill-questions{
            color: #bfdbfe;
            background: rgba(59,130,246,.14);
            border-color: rgba(59,130,246,.28);
        }

        .test-hint{
            font-size: .87rem;
            color: #64748b;
        }

        .dark .test-hint{
            color: #94a3b8;
        }

        .test-card-right{
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }

        .start-btn{
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            border-radius: 14px;
            padding: .95rem 1.25rem;
            text-decoration: none;
            font-size: .95rem;
            font-weight: 800;
            color: #111827;
            background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
            box-shadow:
                0 12px 24px rgba(245, 158, 11, 0.22),
                0 1px 0 rgba(255,255,255,.35) inset;
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }

        .start-btn:hover{
            transform: translateY(-2px);
            filter: brightness(1.01);
            box-shadow:
                0 16px 28px rgba(245, 158, 11, 0.28),
                0 1px 0 rgba(255,255,255,.35) inset;
        }

        .start-btn svg{
            width: 17px;
            height: 17px;
        }

        .empty-card{
            border-radius: 22px;
            padding: 2rem;
            border: 1px dashed #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }

        .dark .empty-card{
            border-color: #334155;
            background: #111827;
            color: #cbd5e1;
            box-shadow: none;
        }

        @media (max-width: 640px){
            .test-card{
                flex-direction: column;
                align-items: flex-start;
                min-height: unset;
                padding: 1.25rem;
            }

            .test-card-right{
                width: 100%;
            }

            .start-btn{
                width: 100%;
                justify-content: center;
            }

            .test-title{
                font-size: 1.45rem;
            }
        }
    </style>

    <div class="tests-page-wrap">
        <div class="tests-grid">
            @forelse ($tests as $test)
                <div class="test-card">
                    <div class="test-card-left">
                        <div class="test-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25h6m-6 0A2.25 2.25 0 0 0 6.75 7.5v9A2.25 2.25 0 0 0 9 18.75h6a2.25 2.25 0 0 0 2.25-2.25v-9A2.25 2.25 0 0 0 15 5.25m-6 0V4.5A2.25 2.25 0 0 1 11.25 2.25h1.5A2.25 2.25 0 0 1 15 4.5v.75"/>
                            </svg>
                        </div>

                        <div class="test-content">
                            <div class="test-title">
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

                            <div class="test-hint">
                                Otvara test u aplikaciji
                            </div>
                        </div>
                    </div>

                    <div class="test-card-right">
                        <a
                            class="start-btn"
                            href="{{ \App\Filament\Pages\TestFormPage::getUrl(['test' => $test->id]) }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8.5 5.5v13l11-6.5-11-6.5z"/>
                            </svg>
                            Pokreni test
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