<x-filament-panels::page>
    <style>
        .oplog-wrap{
            border-radius:18px;
            border:1px solid rgba(148,163,184,.18);
            background:rgba(15,23,42,.04);
            overflow:hidden;
        }

        .dark .oplog-wrap{
            background:linear-gradient(180deg, rgba(10,24,52,.96), rgba(4,10,24,.96));
            border-color:rgba(148,163,184,.16);
        }

        .oplog-head{
            padding:18px 20px;
            border-bottom:1px solid rgba(148,163,184,.18);
            display:flex;
            justify-content:space-between;
            gap:16px;
            align-items:flex-start;
        }

        .oplog-title{
            font-size:1.1rem;
            font-weight:900;
            color:#0f172a;
        }

        .dark .oplog-title{
            color:white;
        }

        .oplog-sub{
            margin-top:4px;
            color:#64748b;
            font-size:.86rem;
        }

        .dark .oplog-sub{
            color:#93c5fd;
        }

        .oplog-stats{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            justify-content:flex-end;
        }

        .oplog-stat{
            border-radius:12px;
            padding:8px 10px;
            background:white;
            border:1px solid rgba(148,163,184,.20);
            min-width:96px;
        }

        .dark .oplog-stat{
            background:rgba(15,23,42,.70);
            border-color:rgba(148,163,184,.16);
        }

        .oplog-stat-label{
            font-size:.68rem;
            color:#64748b;
            font-weight:700;
            text-transform:uppercase;
        }

        .dark .oplog-stat-label{
            color:#93c5fd;
        }

        .oplog-stat-value{
            margin-top:2px;
            font-size:1.1rem;
            font-weight:900;
            color:#0f172a;
        }

        .dark .oplog-stat-value{
            color:white;
        }

        .oplog-list{
            padding:16px 20px 20px;
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .oplog-item{
            display:grid;
            grid-template-columns:40px 1fr auto;
            gap:12px;
            align-items:flex-start;
            border-radius:16px;
            background:white;
            border:1px solid rgba(148,163,184,.20);
            padding:14px;
        }

        .dark .oplog-item{
            background:rgba(7,18,40,.82);
            border-color:rgba(148,163,184,.14);
        }

        .oplog-check{
            width:32px;
            height:32px;
            border-radius:999px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:900;
            border:1px solid rgba(148,163,184,.25);
            background:#f8fafc;
            color:#64748b;
        }

        .oplog-check.task{
            background:rgba(245,158,11,.12);
            color:#f59e0b;
            border-color:rgba(245,158,11,.28);
        }

        .oplog-check.done{
            background:rgba(34,197,94,.12);
            color:#22c55e;
            border-color:rgba(34,197,94,.28);
        }

        .oplog-note{
            color:#0f172a;
            font-size:.95rem;
            line-height:1.45;
            font-weight:650;
            white-space:pre-line;
        }

        .dark .oplog-note{
            color:white;
        }

        .oplog-meta{
            margin-top:8px;
            display:flex;
            flex-wrap:wrap;
            gap:6px;
        }

        .oplog-badge{
            border-radius:999px;
            padding:4px 8px;
            font-size:.72rem;
            font-weight:800;
            border:1px solid rgba(148,163,184,.20);
            color:#475569;
            background:#f8fafc;
        }

        .dark .oplog-badge{
            color:#cbd5e1;
            background:rgba(15,23,42,.75);
            border-color:rgba(148,163,184,.16);
        }

        .oplog-badge.task{
            color:#92400e;
            background:#fffbeb;
            border-color:#fcd34d;
        }

        .dark .oplog-badge.task{
            color:#fbbf24;
            background:rgba(245,158,11,.12);
            border-color:rgba(245,158,11,.25);
        }

        .oplog-badge.done{
            color:#166534;
            background:#f0fdf4;
            border-color:#86efac;
        }

        .dark .oplog-badge.done{
            color:#22c55e;
            background:rgba(34,197,94,.12);
            border-color:rgba(34,197,94,.25);
        }

        .oplog-actions{
            display:flex;
            align-items:center;
            gap:8px;
        }

        .oplog-link{
            border-radius:10px;
            padding:7px 10px;
            font-size:.76rem;
            font-weight:800;
            text-decoration:none;
            border:1px solid rgba(96,165,250,.30);
            background:rgba(59,130,246,.10);
            color:#2563eb;
            white-space:nowrap;
        }

        .dark .oplog-link{
            color:#93c5fd;
            background:rgba(59,130,246,.12);
            border-color:rgba(96,165,250,.26);
        }

        @media (max-width: 760px){
            .oplog-head{
                flex-direction:column;
            }

            .oplog-stats{
                justify-content:flex-start;
            }

            .oplog-item{
                grid-template-columns:34px 1fr;
            }

            .oplog-actions{
                grid-column:2;
            }
        }
    </style>

    <div class="oplog-wrap">
        <div class="oplog-head">
            <div>
                <div class="oplog-title">
                    Operativni dnevnik za {{ $record->log_date?->format('d.m.Y.') }}
                </div>

                <div class="oplog-sub">
                    Pregled dnevnih natuknica, zapisa i radnih zadataka kreiranih iz dnevnika.
                </div>
            </div>

            <div class="oplog-stats">
                <div class="oplog-stat">
                    <div class="oplog-stat-label">Bilješki</div>
                    <div class="oplog-stat-value">{{ $totalItems }}</div>
                </div>

                <div class="oplog-stat">
                    <div class="oplog-stat-label">Označeno</div>
                    <div class="oplog-stat-value">{{ $taskItems }}</div>
                </div>

                <div class="oplog-stat">
                    <div class="oplog-stat-label">Zadataka</div>
                    <div class="oplog-stat-value">{{ $createdTasks }}</div>
                </div>
            </div>
        </div>

        <div class="oplog-list">
            @forelse ($items as $index => $item)
                @php
                    $taskId = $item['task_id'] ?? null;
                    $task = $taskId ? ($tasks[$taskId] ?? null) : null;
                    $isTask = ! empty($item['create_task']);
                    $isCreated = ! empty($taskId);
                    $isDone = $task?->is_done ?? false;
                @endphp

                <div class="oplog-item">
                    <div class="oplog-check {{ $isCreated ? 'done' : ($isTask ? 'task' : '') }}">
                        {{ $index + 1 }}
                    </div>

                    <div>
                        <div class="oplog-note">{{ $item['note'] ?? '' }}</div>

                        <div class="oplog-meta">
                            @if ($isTask)
                                <span class="oplog-badge task">Radni zadatak</span>
                            @else
                                <span class="oplog-badge">Samo zapis</span>
                            @endif

                            @if ($isCreated)
                                <span class="oplog-badge done">Zadatak kreiran</span>
                            @endif

                            @if ($isDone)
                                <span class="oplog-badge done">Završeno</span>
                            @endif
                        </div>
                    </div>

                    <div class="oplog-actions">
                        @if ($task)
                            <a class="oplog-link" href="{{ \App\Filament\Resources\WorkTasks\WorkTaskResource::getUrl('edit', ['record' => $task]) }}">
                                Otvori zadatak
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="oplog-item">
                    <div class="oplog-check">0</div>
                    <div>
                        <div class="oplog-note">Nema upisanih bilješki.</div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>