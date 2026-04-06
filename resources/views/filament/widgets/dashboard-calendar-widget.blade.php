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
            align-items:center;
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

        .znr-cal-add-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:32px;
            height:32px;
            border-radius:8px;
            border:1px solid rgba(139,92,246,.30);
            background:rgba(139,92,246,.10);
            color:#7c3aed;
            font-weight:800;
            font-size:16px;
            cursor:pointer;
        }

        .znr-cal-add-btn:hover{
            transform:translateY(-1px);
            background:rgba(139,92,246,.16);
        }

        .dark .znr-cal-add-btn{
            color:#c4b5fd;
            border-color:rgba(139,92,246,.34);
            background:rgba(139,92,246,.14);
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
            position:relative;
            min-height:98px;
            padding:4px;
            border-right:1px solid #eef2f7;
            border-bottom:1px solid #eef2f7;
            background:#fff;
            transition:background .15s ease, box-shadow .15s ease, border-color .15s ease;
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
            background: linear-gradient(180deg, #eef6ff 0%, #e0eeff 100%);
            box-shadow:
                inset 0 0 0 2px rgba(59,130,246,.35),
                inset 0 0 24px rgba(59,130,246,.08);
            border-color: rgba(59,130,246,.30);
        }

        .dark .znr-cal-cell.today{
            background: linear-gradient(180deg, rgba(37,99,235,.18) 0%, rgba(30,64,175,.14) 100%);
            box-shadow:
                inset 0 0 0 2px rgba(96,165,250,.38),
                inset 0 0 28px rgba(96,165,250,.10);
            border-color: rgba(96,165,250,.22);
        }

        .znr-cal-cell-top{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:6px;
            margin-bottom:4px;
        }

        .znr-cal-day{
            font-size:15px;
            font-weight:800;
            color:#0f172a;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:24px;
            height:24px;
            border-radius:999px;
        }

        .dark .znr-cal-day{
            color:#fff;
        }

        .muted .znr-cal-day{
            opacity:.45;
        }

        .today .znr-cal-day{
            color:#1d4ed8;
            background: rgba(255,255,255,.82);
            box-shadow: 0 0 0 1px rgba(59,130,246,.18);
        }

        .dark .today .znr-cal-day{
            color:#dbeafe;
            background: rgba(255,255,255,.10);
            box-shadow: 0 0 0 1px rgba(147,197,253,.22);
        }

        .znr-day-plus{
            width:20px;
            height:20px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:999px;
            border:1px solid rgba(139,92,246,.30);
            background:rgba(139,92,246,.08);
            color:#7c3aed;
            font-size:13px;
            font-weight:800;
            cursor:pointer;
            padding:0;
            line-height:1;
        }

        .znr-day-plus:hover{
            background:rgba(139,92,246,.16);
        }

        .dark .znr-day-plus{
            color:#c4b5fd;
            border-color:rgba(139,92,246,.34);
            background:rgba(139,92,246,.14);
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

        .znr-task-row{
            display:flex;
            align-items:center;
            gap:4px;
            width:100%;
            margin-bottom:2px;
            padding:2px 5px;
            border-radius:5px;
            font-size:9px;
            line-height:1.1;
            overflow:hidden;
            transition:transform .12s ease, filter .12s ease;
        }

        .znr-task-row:hover{
            transform:translateY(-1px);
            filter:brightness(1.04);
        }

        .znr-task-main{
            flex:1;
            min-width:0;
            text-align:left;
            background:transparent;
            border:none;
            padding:0;
            margin:0;
            cursor:pointer;
            overflow:hidden;
            white-space:nowrap;
            text-overflow:ellipsis;
            font-size:9px;
            line-height:1.1;
            color:inherit;
        }

        .znr-task-toggle{
            width:16px;
            height:16px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:999px;
            border:1.5px solid rgba(255,255,255,.72);
            background:rgba(255,255,255,.08);
            cursor:pointer;
            padding:0;
            flex-shrink:0;
            transition:all .14s ease;
        }

        .znr-task-toggle:hover{
            transform:scale(1.08);
            background:rgba(255,255,255,.18);
            border-color:rgba(255,255,255,.90);
        }

        .znr-task-toggle::after{
            content:'';
            width:6px;
            height:6px;
            border-radius:999px;
            background:transparent;
            transform:scale(0);
            transition:all .14s ease;
        }

        .znr-task-delete{
            width:16px;
            height:16px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:none;
            background:transparent;
            color:inherit;
            font-size:11px;
            font-weight:900;
            line-height:1;
            cursor:pointer;
            padding:0;
            flex-shrink:0;
            opacity:.95;
            transition:all .14s ease;
        }

        .znr-task-delete:hover{
            transform:scale(1.08);
            opacity:1;
        }

        .znr-task{
            background:#8b5cf6;
            color:#ffffff;
            border-left:3px solid #c4b5fd;
            box-shadow:0 0 0 1px rgba(139,92,246,.28);
        }

        .dark .znr-task{
            background:#7c3aed;
            color:#f5f3ff;
            border-left-color:#ddd6fe;
            box-shadow:0 0 0 1px rgba(167,139,250,.28);
        }

        .znr-task-overdue{
            background:#ef4444;
            color:#ffffff;
            border-left:3px solid #fecaca;
            box-shadow:0 0 0 1px rgba(239,68,68,.28);
        }

        .dark .znr-task-overdue{
            background:#dc2626;
            color:#fff1f2;
            border-left-color:#fecdd3;
            box-shadow:0 0 0 1px rgba(251,113,133,.28);
        }

        .znr-task-done{
            background:#22c55e;
            color:#ffffff;
            border-left:3px solid #bbf7d0;
            box-shadow:0 0 0 1px rgba(34,197,94,.28);
        }

        .dark .znr-task-done{
            background:#16a34a;
            color:#f0fdf4;
            border-left-color:#dcfce7;
            box-shadow:0 0 0 1px rgba(74,222,128,.28);
        }

        .znr-task-done .znr-task-main{
            text-decoration:line-through;
        }

        .znr-task-done .znr-task-toggle{
            background:#ffffff;
            border-color:#dcfce7;
        }

        .znr-task-done .znr-task-toggle::after{
            width:8px;
            height:8px;
            background:#16a34a;
            transform:scale(1);
            mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white' stroke-width='3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m5 13 4 4L19 7'/%3E%3C/svg%3E") center / contain no-repeat;
            -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white' stroke-width='3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m5 13 4 4L19 7'/%3E%3C/svg%3E") center / contain no-repeat;
        }

        .znr-task:not(.znr-task-done) .znr-task-toggle:hover::after,
        .znr-task-overdue:not(.znr-task-done) .znr-task-toggle:hover::after{
            transform:scale(1);
            background:rgba(255,255,255,.88);
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

        .medical{
            background:#2563eb;
            border-left:3px solid #93c5fd;
            box-shadow:0 0 0 1px rgba(37,99,235,.30);
        }

        .certificate{
            background:#f97316;
            border-left:3px solid #fdba74;
        }

        .machine{
            background:#facc15;
            color:#111827;
            border-left:3px solid #fde047;
        }

        .fire{
            background:#ef4444;
            border-left:3px solid #fca5a5;
        }

        .misc{
            background:#22c55e;
            border-left:3px solid #86efac;
        }

        .znr-task-modal{
            position:fixed;
            inset:0;
            z-index:9998;
            background:rgba(2, 6, 23, .58);
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
        }

        .znr-task-modal-box{
            width:min(680px, 100%);
            border-radius:18px;
            border:1px solid #dbe3f0;
            background:#ffffff;
            box-shadow:0 20px 40px rgba(15, 23, 42, .16);
            overflow:hidden;
        }

        .dark .znr-task-modal-box{
            border:1px solid rgba(148,163,184,.16);
            background:linear-gradient(180deg, rgba(10, 24, 52, .98), rgba(4, 10, 24, .98));
            box-shadow:0 20px 40px rgba(0,0,0,.28);
        }

        .znr-task-modal-head{
            padding:16px 18px 10px 18px;
            border-bottom:1px solid #e6edf7;
        }

        .dark .znr-task-modal-head{
            border-bottom:1px solid rgba(148,163,184,.12);
        }

        .znr-task-modal-title{
            margin:0;
            font-size:1.05rem;
            font-weight:800;
            color:#111827;
        }

        .dark .znr-task-modal-title{
            color:#fff;
        }

        .znr-task-modal-subtitle{
            margin-top:4px;
            font-size:.82rem;
            color:#64748b;
        }

        .dark .znr-task-modal-subtitle{
            color:#93c5fd;
        }

        .znr-task-modal-body{
            padding:16px 18px;
            display:grid;
            gap:14px;
        }

        .znr-task-field label{
            display:block;
            margin-bottom:6px;
            font-size:.84rem;
            font-weight:700;
            color:#334155;
        }

        .dark .znr-task-field label{
            color:#e2e8f0;
        }

        .znr-task-input,
        .znr-task-textarea{
            width:100%;
            border-radius:12px;
            border:1px solid #dbe3f0;
            background:#ffffff;
            color:#0f172a;
            padding:10px 12px;
            font-size:.9rem;
            outline:none;
        }

        .dark .znr-task-input,
        .dark .znr-task-textarea{
            border:1px solid rgba(148,163,184,.18);
            background:rgba(2, 6, 23, .72);
            color:#ffffff;
        }

        .znr-task-textarea{
            min-height:110px;
            resize:vertical;
        }

        .znr-task-error{
            margin-top:6px;
            color:#dc2626;
            font-size:.78rem;
            font-weight:600;
        }

        .dark .znr-task-error{
            color:#fda4af;
        }

        .znr-task-modal-footer{
            padding:14px 18px 18px 18px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
        }

        .znr-task-btns-right{
            display:flex;
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
            background:#8b5cf6;
            color:#ffffff;
        }

        .znr-btn-danger{
            background:#ef4444;
            color:#ffffff;
        }

        @media (max-width: 1024px){
            .znr-cal-cell{
                min-height:84px;
                padding:3px;
            }

            .znr-cal-day{
                font-size:14px;
            }

            .znr-cal-weekday{
                font-size:13px;
            }

            .znr-event,
            .znr-task-main,
            .znr-task-row{
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
                min-height:70px;
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
                <button type="button" class="znr-cal-add-btn" wire:click="openTaskCreateModal">
                    +
                </button>

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

            <div class="znr-legend-item">
                <span class="znr-legend-dot task"></span>
                @svg('heroicon-m-clipboard-document-check', 'znr-legend-icon')
                <span>Radni zadaci</span>
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
                        <div class="znr-cal-cell-top">
                            <div class="znr-cal-day">{{ $day['date']->day }}</div>

                            <button
                                type="button"
                                class="znr-day-plus"
                                wire:click="openTaskCreateModal('{{ $day['date']->format('Y-m-d') }}')"
                                title="Dodaj radni zadatak"
                            >
                                +
                            </button>
                        </div>

                        @foreach($day['items'] as $item)
                            @if(($item['type'] ?? 'default') === 'task')
                                <div
                                    class="znr-task-row {{ $item['class'] }}"
                                    wire:key="task-{{ $item['id'] }}-{{ !empty($item['is_done']) ? 'done' : 'open' }}"
                                >
                                    @if (!empty($item['is_done']))
                                        <button
                                            type="button"
                                            class="znr-task-toggle"
                                            wire:click.stop="reopenTask({{ $item['id'] }})"
                                            title="Vrati u otvorene"
                                            aria-label="Vrati u otvorene"
                                        ></button>
                                    @else
                                        <button
                                            type="button"
                                            class="znr-task-toggle"
                                            wire:click.stop="completeTask({{ $item['id'] }})"
                                            title="Zatvori zadatak"
                                            aria-label="Zatvori zadatak"
                                        ></button>
                                    @endif

                                    <button
                                        type="button"
                                        class="znr-task-main"
                                        wire:click.stop="openTaskEditModal({{ $item['id'] }})"
                                        title="Uredi zadatak: {{ $item['title'] }}"
                                    >
                                        {{ $item['title'] }}
                                    </button>

                                    <button
                                        type="button"
                                        class="znr-task-delete"
                                        wire:click.stop="deleteTask({{ $item['id'] }})"
                                        wire:confirm="Obrisati radni zadatak?"
                                        title="Obriši zadatak"
                                        aria-label="Obriši zadatak"
                                    >
                                        ✕
                                    </button>
                                </div>
                            @else
                                <a
                                    href="{{ $item['url'] }}"
                                    class="znr-event {{ $item['class'] }}"
                                    title="{{ $item['title'] }}"
                                >
                                    {{ $item['title'] }}
                                </a>
                            @endif
                        @endforeach

                        @if($day['extra_count'] > 0)
                            <div class="znr-more">+{{ $day['extra_count'] }} više</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    @if ($showTaskModal)
        <div class="znr-task-modal">
            <div class="znr-task-modal-box">
                <div class="znr-task-modal-head">
                    <h3 class="znr-task-modal-title">
                        {{ $editingTaskId ? 'Uredi radni zadatak' : 'Novi radni zadatak' }}
                    </h3>

                    <div class="znr-task-modal-subtitle">
                        Zadatak će biti prikazan u glavnom kalendaru.
                    </div>
                </div>

                <div class="znr-task-modal-body">
                    <div class="znr-task-field">
                        <label>Naziv zadatka</label>
                        <input
                            type="text"
                            wire:model.defer="taskTitle"
                            maxlength="120"
                            class="znr-task-input"
                            placeholder="Npr. Pošalji uputnicu dr. medicine rada"
                        >
                        @error('taskTitle')
                            <div class="znr-task-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="znr-task-field">
                        <label>Opis</label>
                        <textarea
                            wire:model.defer="taskDescription"
                            maxlength="1000"
                            class="znr-task-textarea"
                            placeholder="Dodatna napomena..."
                        ></textarea>
                        @error('taskDescription')
                            <div class="znr-task-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="znr-task-field">
                        <label>Datum</label>
                        <input
                            type="date"
                            wire:model.defer="taskDate"
                            class="znr-task-input"
                        >
                        @error('taskDate')
                            <div class="znr-task-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="znr-task-modal-footer">
                    <div>
                        @if ($editingTaskId)
                            <button
                                type="button"
                                class="znr-btn znr-btn-danger"
                                wire:click="deleteTask({{ $editingTaskId }})"
                                wire:confirm="Obrisati radni zadatak?"
                            >
                                Obriši
                            </button>
                        @endif
                    </div>

                    <div class="znr-task-btns-right">
                        <button type="button" class="znr-btn znr-btn-secondary" wire:click="closeTaskModal">
                            Odustani
                        </button>

                        <button type="button" class="znr-btn znr-btn-primary" wire:click="saveTask">
                            Spremi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>