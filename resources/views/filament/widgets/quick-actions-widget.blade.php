<x-filament-widgets::widget>
    <style>
        .znr-quick-wrap{
            border-radius: 18px;
            padding: 14px 16px;
            border: 1px solid #dbe3f0;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            margin-bottom: 16px;
        }

        .dark .znr-quick-wrap{
            border: 1px solid rgba(148, 163, 184, .14);
            background: linear-gradient(180deg, rgba(10, 24, 52, .95), rgba(4, 10, 24, .95));
            box-shadow: 0 10px 22px rgba(0, 0, 0, .14);
        }

        .znr-quick-top{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            margin-bottom:12px;
        }

        .znr-quick-top-left{
            min-width:0;
        }

        .znr-quick-title{
            margin:0;
            font-size:1rem;
            font-weight:800;
            color:#111827;
            letter-spacing:-0.02em;
        }

        .dark .znr-quick-title{
            color:#fff;
        }

        .znr-quick-subtitle{
            margin-top:2px;
            font-size:.78rem;
            color:#64748b;
        }

        .dark .znr-quick-subtitle{
            color:#93c5fd;
        }

        .znr-quick-edit-btn{
            border:1px solid #d5deec;
            background:#f8fbff;
            color:#334155;
            border-radius:10px;
            padding:7px 10px;
            font-size:.78rem;
            font-weight:700;
            cursor:pointer;
            transition:all .15s ease;
        }

        .znr-quick-edit-btn:hover{
            background:#eef4fb;
            border-color:#c7d5ea;
        }

        .dark .znr-quick-edit-btn{
            border:1px solid rgba(96,165,250,.28);
            background:rgba(59,130,246,.12);
            color:#bfdbfe;
        }

        .dark .znr-quick-edit-btn:hover{
            background:rgba(59,130,246,.18);
            border-color:rgba(96,165,250,.40);
        }

        .znr-quick-grid{
            display:grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap:12px;
        }

        @media (max-width: 1200px){
            .znr-quick-grid{
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px){
            .znr-quick-grid{
                grid-template-columns: 1fr;
            }
        }

        .znr-quick-card{
            display:flex;
            align-items:flex-start;
            gap:12px;
            text-decoration:none;
            border-radius:16px;
            padding:12px 14px;
            border:1px solid #dbe3f0;
            background:#ffffff;
            transition:all .16s ease;
            min-height:72px;
            box-shadow:0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .znr-quick-card:hover{
            transform:translateY(-2px);
            border-color:#c7d5ea;
            box-shadow:0 10px 22px rgba(15, 23, 42, 0.08);
        }

        .dark .znr-quick-card{
            border:1px solid rgba(148,163,184,.14);
            background:rgba(7,18,40,.80);
            box-shadow:none;
        }

        .dark .znr-quick-card:hover{
            box-shadow:0 10px 18px rgba(0,0,0,.14);
        }

        .znr-quick-icon{
            width:38px;
            height:38px;
            border-radius:11px;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-shrink:0;
        }

        .znr-quick-body{
            min-width:0;
            flex:1;
        }

        .znr-quick-label{
            font-size:.92rem;
            font-weight:700;
            color:#0f172a;
            line-height:1.25;
        }

        .dark .znr-quick-label{
            color:#fff;
        }

        .znr-quick-desc{
            margin-top:4px;
            font-size:.76rem;
            color:#64748b;
            line-height:1.35;
        }

        .dark .znr-quick-desc{
            color:#a5b4fc;
        }

        .znr-quick-arrow{
            flex-shrink:0;
            color:#94a3b8;
            margin-top:4px;
        }

        .dark .znr-quick-arrow{
            color:#93c5fd;
        }

        .znr-quick-card.sky .znr-quick-icon{
            background: rgba(56, 189, 248, .10);
            color: #0284c7;
            border: 1px solid rgba(56, 189, 248, .20);
        }

        .znr-quick-card.indigo .znr-quick-icon{
            background: rgba(99, 102, 241, .10);
            color: #4f46e5;
            border: 1px solid rgba(99, 102, 241, .20);
        }

        .znr-quick-card.amber .znr-quick-icon{
            background: rgba(245, 158, 11, .10);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, .20);
        }

        .znr-quick-card.emerald .znr-quick-icon{
            background: rgba(34, 197, 94, .10);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, .20);
        }

        .znr-quick-card.rose .znr-quick-icon{
            background: rgba(244, 63, 94, .10);
            color: #e11d48;
            border: 1px solid rgba(244, 63, 94, .20);
        }

        .znr-quick-card.violet .znr-quick-icon{
            background: rgba(139, 92, 246, .10);
            color: #7c3aed;
            border: 1px solid rgba(139, 92, 246, .20);
        }

        .znr-quick-card.blue .znr-quick-icon{
            background: rgba(59, 130, 246, .10);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, .20);
        }

        .znr-quick-card.teal .znr-quick-icon{
            background: rgba(20, 184, 166, .10);
            color: #0f766e;
            border: 1px solid rgba(20, 184, 166, .20);
        }

        .znr-quick-card.orange .znr-quick-icon{
            background: rgba(249, 115, 22, .10);
            color: #ea580c;
            border: 1px solid rgba(249, 115, 22, .20);
        }

        .znr-quick-card.lime .znr-quick-icon{
            background: rgba(132, 204, 22, .10);
            color: #65a30d;
            border: 1px solid rgba(132, 204, 22, .20);
        }

        .znr-quick-card.purple .znr-quick-icon{
            background: rgba(168, 85, 247, .10);
            color: #9333ea;
            border: 1px solid rgba(168, 85, 247, .20);
        }

        .dark .znr-quick-card.sky .znr-quick-icon{
            background: rgba(56, 189, 248, .15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, .25);
        }

        .dark .znr-quick-card.indigo .znr-quick-icon{
            background: rgba(99, 102, 241, .15);
            color: #818cf8;
            border: 1px solid rgba(129, 140, 248, .25);
        }

        .dark .znr-quick-card.amber .znr-quick-icon{
            background: rgba(245, 158, 11, .15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, .25);
        }

        .dark .znr-quick-card.emerald .znr-quick-icon{
            background: rgba(34, 197, 94, .15);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, .25);
        }

        .dark .znr-quick-card.rose .znr-quick-icon{
            background: rgba(244, 63, 94, .15);
            color: #f43f5e;
            border: 1px solid rgba(244, 63, 94, .25);
        }

        .dark .znr-quick-card.violet .znr-quick-icon{
            background: rgba(139, 92, 246, .15);
            color: #8b5cf6;
            border: 1px solid rgba(139, 92, 246, .25);
        }

        .dark .znr-quick-card.blue .znr-quick-icon{
            background: rgba(59, 130, 246, .15);
            color: #60a5fa;
            border: 1px solid rgba(96, 165, 250, .25);
        }

        .dark .znr-quick-card.teal .znr-quick-icon{
            background: rgba(20, 184, 166, .15);
            color: #2dd4bf;
            border: 1px solid rgba(45, 212, 191, .25);
        }

        .dark .znr-quick-card.orange .znr-quick-icon{
            background: rgba(249, 115, 22, .15);
            color: #fb923c;
            border: 1px solid rgba(251, 146, 60, .25);
        }

        .dark .znr-quick-card.lime .znr-quick-icon{
            background: rgba(132, 204, 22, .15);
            color: #a3e635;
            border: 1px solid rgba(163, 230, 53, .25);
        }

        .dark .znr-quick-card.purple .znr-quick-icon{
            background: rgba(168, 85, 247, .15);
            color: #c084fc;
            border: 1px solid rgba(192, 132, 252, .25);
        }

        .znr-quick-modal{
            position:fixed;
            inset:0;
            z-index:9999;
            background:rgba(2, 6, 23, .45);
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
        }

        .dark .znr-quick-modal{
            background:rgba(2, 6, 23, .72);
        }

        .znr-quick-modal-box{
            width:min(680px, 100%);
            border-radius:18px;
            border:1px solid #dbe3f0;
            background:#ffffff;
            box-shadow:0 20px 40px rgba(15, 23, 42, .16);
            overflow:hidden;
        }

        .dark .znr-quick-modal-box{
            border:1px solid rgba(148,163,184,.16);
            background:linear-gradient(180deg, rgba(10, 24, 52, .98), rgba(4, 10, 24, .98));
            box-shadow:0 20px 40px rgba(0,0,0,.28);
        }

        .znr-quick-modal-head{
            padding:16px 18px 10px 18px;
            border-bottom:1px solid #e6edf7;
        }

        .dark .znr-quick-modal-head{
            border-bottom:1px solid rgba(148,163,184,.12);
        }

        .znr-quick-modal-title{
            margin:0;
            font-size:1.05rem;
            font-weight:800;
            color:#111827;
        }

        .dark .znr-quick-modal-title{
            color:#fff;
        }

        .znr-quick-modal-subtitle{
            margin-top:4px;
            font-size:.82rem;
            color:#64748b;
        }

        .dark .znr-quick-modal-subtitle{
            color:#93c5fd;
        }

        .znr-quick-modal-body{
            padding:16px 18px;
        }

        .znr-quick-editor-grid{
            display:grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap:10px;
        }

        @media (max-width: 700px){
            .znr-quick-editor-grid{
                grid-template-columns: 1fr;
            }
        }

        .znr-quick-option{
            display:flex;
            align-items:flex-start;
            gap:10px;
            border:1px solid #dbe3f0;
            background:#ffffff;
            border-radius:14px;
            padding:12px;
            cursor:pointer;
        }

        .dark .znr-quick-option{
            border:1px solid rgba(148,163,184,.12);
            background:rgba(7,18,40,.72);
        }

        .znr-quick-option.active{
            border-color:#bfdbfe;
            background:#f5f9ff;
        }

        .dark .znr-quick-option.active{
            border-color:rgba(96,165,250,.45);
            background:rgba(59,130,246,.10);
        }

        .znr-quick-option input{
            margin-top:2px;
        }

        .znr-quick-option-title{
            color:#111827;
            font-size:.9rem;
            font-weight:700;
        }

        .dark .znr-quick-option-title{
            color:#fff;
        }

        .znr-quick-option-desc{
            color:#64748b;
            font-size:.76rem;
            margin-top:3px;
            line-height:1.35;
        }

        .dark .znr-quick-option-desc{
            color:#a5b4fc;
        }

        .znr-quick-limit{
            margin-top:12px;
            font-size:.8rem;
            color:#475569;
        }

        .dark .znr-quick-limit{
            color:#cbd5e1;
        }

        .znr-quick-modal-footer{
            padding:14px 18px 18px 18px;
            display:flex;
            justify-content:flex-end;
            gap:10px;
        }

        .znr-btn{
            border-radius:10px;
            padding:8px 12px;
            font-size:.82rem;
            font-weight:700;
            cursor:pointer;
            border:1px solid transparent;
        }

        .znr-btn-secondary{
            background:#f8fafc;
            color:#334155;
            border-color:#dbe3f0;
        }

        .dark .znr-btn-secondary{
            background:rgba(15,23,42,.9);
            color:#cbd5e1;
            border-color:rgba(148,163,184,.18);
        }

        .znr-btn-primary{
            background:#f59e0b;
            color:#111827;
        }
    </style>

    <div class="znr-quick-wrap">
        <div class="znr-quick-top">
            <div class="znr-quick-top-left">
                <div class="znr-quick-title">Moje brze akcije</div>
                <div class="znr-quick-subtitle">Odaberi do 4 gumba koje želiš imati gore.</div>
            </div>

            <button type="button" class="znr-quick-edit-btn" wire:click="openEditor">
                Uredi
            </button>
        </div>

        <div class="znr-quick-grid">
            @foreach ($this->selectedActions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="znr-quick-card {{ $action['color'] }}"
                    title="Napravi novo: {{ $action['label'] }}"
                >
                    <div class="znr-quick-icon">
                        <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                    </div>

                    <div class="znr-quick-body">
                        <div class="znr-quick-label">{{ $action['label'] }}</div>
                        <div class="znr-quick-desc">{{ $action['description'] }}</div>
                    </div>

                    <div class="znr-quick-arrow">
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4" />
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    @if ($showEditor)
        <div class="znr-quick-modal">
            <div class="znr-quick-modal-box">
                <div class="znr-quick-modal-head">
                    <h3 class="znr-quick-modal-title">Uredi brze akcije</h3>
                    <div class="znr-quick-modal-subtitle">
                        Označi do 4 akcije koje želiš prikazivati na nadzornoj ploči.
                    </div>
                </div>

                <div class="znr-quick-modal-body">
                    <div class="znr-quick-editor-grid">
                        @foreach ($this->availableActions as $action)
                            @php
                                $active = in_array($action['key'], $editorSelection, true);
                            @endphp

                            <label class="znr-quick-option {{ $active ? 'active' : '' }}">
                                <input
                                    type="checkbox"
                                    wire:click="toggleAction('{{ $action['key'] }}')"
                                    @checked($active)
                                >

                                <div>
                                    <div class="znr-quick-option-title">{{ $action['label'] }}</div>
                                    <div class="znr-quick-option-desc">{{ $action['description'] }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="znr-quick-limit">
                        Odabrano: {{ count($editorSelection) }} / 4
                    </div>
                </div>

                <div class="znr-quick-modal-footer">
                    <button type="button" class="znr-btn znr-btn-secondary" wire:click="closeEditor">
                        Odustani
                    </button>

                    <button type="button" class="znr-btn znr-btn-primary" wire:click="saveQuickActions">
                        Spremi
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>