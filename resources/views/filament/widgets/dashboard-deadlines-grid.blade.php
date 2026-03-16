<x-filament-widgets::widget>
    <style>
        .znr-dashboard-grid{
            display:grid;
            grid-template-columns:repeat(5, minmax(0, 1fr));
            gap:16px;
        }

        .znr-dashboard-column{
            display:flex;
            flex-direction:column;
            gap:16px;
        }

        .znr-card{
            display:block;
            border-radius:18px;
            padding:16px 16px 14px;
            text-decoration:none;
            transition:all .18s ease;
            min-height:122px;
            border:1px solid #d8dde6;
            background:#ffffff;
            box-shadow:0 1px 2px rgba(15,23,42,.05);
        }

        .znr-card:hover{
            transform:translateY(-2px);
            border-color:#f59e0b;
            box-shadow:0 10px 24px rgba(15,23,42,.10);
        }

        .dark .znr-card{
            background:rgba(10, 20, 40, .96);
            border:1px solid rgba(59,130,246,.18);
            box-shadow:none;
        }

        .znr-title{
            font-size:12px;
            line-height:1.2;
            font-weight:600;
            color:#64748b;
            margin-bottom:8px;
        }

        .dark .znr-title{
            color:#cbd5e1;
        }

        .znr-row{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
        }

        .znr-left{
            display:flex;
            flex-direction:column;
            gap:8px;
            min-width:0;
        }

        .znr-value{
            font-size:22px;
            line-height:1;
            font-weight:800;
            color:#0f172a;
        }

        .dark .znr-value{
            color:#ffffff;
        }

        .znr-meta{
            font-size:12px;
            line-height:1.2;
            font-weight:700;
            max-width:115px;
        }

        .znr-icon{
            width:30px;
            height:30px;
            flex-shrink:0;
            opacity:.95;
        }

        .znr-success{ color:#22c55e; }
        .znr-warning{ color:#f59e0b; }
        .znr-danger{ color:#f43f5e; }

        @media (max-width: 1535px){
            .znr-dashboard-grid{
                grid-template-columns:repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 1024px){
            .znr-dashboard-grid{
                grid-template-columns:repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px){
            .znr-dashboard-grid{
                grid-template-columns:1fr;
            }
        }
    </style>

    <div class="znr-dashboard-grid">
        @foreach($cards as $column)
            <div class="znr-dashboard-column">
                @foreach($column['items'] as $item)
                    <a href="{{ $item['url'] }}" class="znr-card">
                        <div class="znr-title">{{ $column['title'] }}</div>

                        <div class="znr-row">
                            <div class="znr-left">
                                <div class="znr-value">{{ $item['value'] }}</div>

                                <div class="znr-meta znr-{{ $item['color'] }}">
                                    {{ $item['label'] }}
                                </div>
                            </div>

                            <div class="znr-{{ $item['color'] }}">
                                @svg($item['icon'], 'znr-icon')
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>