<x-filament-widgets::widget>
    <style>
        .znr-quick-wrap{
            border-radius: 18px;
            padding: 14px 16px;
            border: 1px solid rgba(148, 163, 184, .14);
            background: linear-gradient(180deg, rgba(10, 24, 52, .95), rgba(4, 10, 24, .95));
            box-shadow: 0 10px 22px rgba(0, 0, 0, .14);
            margin-bottom: 16px;
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
            color:#fff;
            letter-spacing:-0.02em;
        }

        .znr-quick-subtitle{
            margin-top:2px;
            font-size:.78rem;
            color:#93c5fd;
        }

        .znr-quick-edit-btn{
            border:1px solid rgba(96,165,250,.28);
            background:rgba(59,130,246,.12);
            color:#bfdbfe;
            border-radius:10px;
            padding:7px 10px;
            font-size:.78rem;
            font-weight:700;
            cursor:pointer;
            transition:all .15s ease;
        }

        .znr-quick-edit-btn:hover{
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
            border:1px solid rgba(148,163,184,.14);
            background:rgba(7,18,40,.80);
            transition:all .16s ease;
            min-height:72px;
        }

        .znr-quick-card:hover{
            transform:translateY(-2px);
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
            color:#fff;
            line-height:1.25;
        }

        .znr-quick-desc{
            margin-top:4px;
            font-size:.76rem;
            color:#a5b4fc;
            line-height:1.35;
        }

        .znr-quick-arrow{
            flex-shrink:0;
            color:#93c5fd;
            margin-top:4px;
        }

        .znr-quick-card.sky .znr-quick-icon{
            background: rgba(56, 189, 248, .15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, .25);
        }

        .znr-quick-card.indigo .znr-quick-icon{
            background: rgba(99, 102, 241, .15);
            color: #818cf8;
            border: 1px solid rgba(129, 140, 248, .25);
        }

        .znr-quick-card.amber .znr-quick-icon{
            background: rgba(245, 158, 11, .15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, .25);
        }

        .znr-quick-card.emerald .znr-quick-icon{
            background: rgba(34, 197, 94, .15);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, .25);
        }

        .znr-quick-card.rose .znr-quick-icon{
            background: rgba(244, 63, 94, .15);
            color: #f43f5e;
            border: 1px solid rgba(244, 63, 94, .25);
        }

        .znr-quick-card.violet .znr-quick-icon{
            background: rgba(139, 92, 246, .15);
            color: #8b5cf6;
            border: 1px solid rgba(139, 92, 246, .25);
        }

        .znr-quick-card.blue .znr-quick-icon{
            background: rgba(59, 130, 246, .15);
            color: #60a5fa;
            border: 1px solid rgba(96, 165, 250, .25);
        }

        .znr-quick-card.teal .znr-quick-icon{
            background: rgba(20, 184, 166, .15);
            color: #2dd4bf;
            border: 1px solid rgba(45, 212, 191, .25);
        }

        .znr-quick-card.orange .znr-quick-icon{
            background: rgba(249, 115, 22, .15);
            color: #fb923c;
            border: 1px solid rgba(251, 146, 60, .25);
        }

        .znr-quick-card.lime .znr-quick-icon{
            background: rgba(132, 204, 22, .15);
            color: #a3e635;
            border: 1px solid rgba(163, 230, 53, .25);
        }

        .znr-quick-card.purple .znr-quick-icon{
            background: rgba(168, 85, 247, .15);
            color: #c084fc;
            border: 1px solid rgba(192, 132, 252, .25);
        }

        .znr-quick-modal{
            position:fixed;
            inset:0;
            z-index:9999;
            background:rgba(2, 6, 23, .72);
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
        }

        .znr-quick-modal-box{
            width:min(680px, 100%);
            border-radius:18px;
            border:1px solid rgba(148,163,184,.16);
            background:linear-gradient(180deg, rgba(10, 24, 52, .98), rgba(4, 10, 24, .98));
            box-shadow:0 20px 40px rgba(0,0,0,.28);
            overflow:hidden;
        }

        .znr-quick-modal-head{
            padding:16px 18px 10px 18px;
            border-bottom:1px solid rgba(148,163,184,.12);
        }

        .znr-quick-modal-title{
            margin:0;
            font-size:1.05rem;
            font-weight:800;
            color:#fff;
        }

        .znr-quick-modal-subtitle{
            margin-top:4px;
            font-size:.82rem;
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
            border:1px solid rgba(148,163,184,.12);
            background:rgba(7,18,40,.72);
            border-radius:14px;
            padding:12px;
            cursor:pointer;
        }

        .znr-quick-option.active{
            border-color:rgba(96,165,250,.45);
            background:rgba(59,130,246,.10);
        }

        .znr-quick-option input{
            margin-top:2px;
        }

        .znr-quick-option-title{
            color:#fff;
            font-size:.9rem;
            font-weight:700;
        }

        .znr-quick-option-desc{
            color:#a5b4fc;
            font-size:.76rem;
            margin-top:3px;
            line-height:1.35;
        }

        .znr-quick-limit{
            margin-top:12px;
            font-size:.8rem;
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