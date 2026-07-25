<x-filament-panels::page>
    <div class="space-y-6">

        @php
            $overall = $summary['overall'] ?? 'warning';

            $statusColors = [
                'ok' => '#16a34a',
                'warning' => '#d97706',
                'critical' => '#dc2626',
                'info' => '#71717a',
            ];

            $overallColor = $statusColors[$overall] ?? '#d97706';
        @endphp

        {{-- Ukupni status --}}
        <div style="
            background: #18181b;
            border: 2px solid {{ $overallColor }};
            border-radius: 14px;
            padding: 20px;
        ">
            <div style="
                font-size: 13px;
                color: #a1a1aa;
            ">
                Ukupni status sustava
            </div>

            <div style="
                font-size: 26px;
                font-weight: 800;
                color: white;
                margin-top: 5px;
            ">
                @if ($overall === 'ok')
                    ✅ SUSTAV JE U REDU
                @elseif ($overall === 'critical')
                    ❌ SUSTAV JE KRITIČAN
                @else
                    ⚠️ SUSTAV IMA UPOZORENJA
                @endif
            </div>

            <div style="
                display: flex;
                gap: 18px;
                flex-wrap: wrap;
                margin-top: 14px;
                color: #d4d4d8;
            ">
                <span>
                    U redu:
                    <strong>{{ $summary['ok'] ?? 0 }}</strong>
                </span>

                <span>
                    Upozorenja:
                    <strong>{{ $summary['warning'] ?? 0 }}</strong>
                </span>

                <span>
                    Kritično:
                    <strong>{{ $summary['critical'] ?? 0 }}</strong>
                </span>

                <span>
                    Informativno:
                    <strong>{{ $summary['info'] ?? 0 }}</strong>
                </span>

                <span>
                    Osvježeno:
                    <strong>
                        {{ $summary['updated_at'] ?? '-' }}
                    </strong>
                </span>
            </div>
        </div>

        {{-- Konfiguracija --}}
        <div>
            <h2 style="
                font-size: 20px;
                font-weight: 700;
                margin-bottom: 12px;
            ">
                Konfiguracija sustava
            </h2>

            <div class="
                grid
                grid-cols-1
                md:grid-cols-2
                xl:grid-cols-3
                gap-4
            ">
                @foreach ($checks as $check)
                    @php
                        $status = $check['status'] ?? 'warning';

                        $color = $statusColors[$status]
                            ?? '#71717a';
                    @endphp

                    <div style="
                        background: #18181b;
                        border: 1px solid {{ $color }};
                        border-radius: 14px;
                        padding: 18px;
                    ">
                        <div style="
                            font-size: 13px;
                            color: #a1a1aa;
                        ">
                            {{ $check['label'] }}
                        </div>

                        <div style="
                            font-size: 22px;
                            font-weight: 700;
                            color: white;
                            margin-top: 6px;
                        ">
                            @if ($status === 'ok')
                                ✅
                            @elseif ($status === 'critical')
                                ❌
                            @elseif ($status === 'warning')
                                ⚠️
                            @else
                                ℹ️
                            @endif

                            {{ $check['value'] }}
                        </div>

                        <div style="
                            font-size: 13px;
                            color: #d4d4d8;
                            margin-top: 8px;
                        ">
                            {{ $check['note'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Automatski zadaci --}}
        <div>
            <h2 style="
                font-size: 20px;
                font-weight: 700;
                margin-bottom: 12px;
            ">
                Automatski zadaci
            </h2>

            <div class="
                grid
                grid-cols-1
                md:grid-cols-2
                xl:grid-cols-3
                gap-4
            ">
                @foreach ($taskChecks as $task)
                    @php
                        $status = $task['status'] ?? 'info';

                        $color = $statusColors[$status]
                            ?? '#71717a';
                    @endphp

                    <div style="
                        background: #18181b;
                        border: 1px solid {{ $color }};
                        border-radius: 14px;
                        padding: 18px;
                    ">
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: flex-start;
                            gap: 12px;
                        ">
                            <div>
                                <div style="
                                    font-size: 13px;
                                    color: #a1a1aa;
                                ">
                                    {{ $task['label'] }}
                                </div>

                                <div style="
                                    font-size: 19px;
                                    font-weight: 700;
                                    color: white;
                                    margin-top: 5px;
                                ">
                                    @if ($status === 'ok')
                                        ✅
                                    @elseif ($status === 'critical')
                                        ❌
                                    @elseif ($status === 'warning')
                                        ⚠️
                                    @else
                                        ℹ️
                                    @endif

                                    {{ $task['status_label'] }}
                                </div>
                            </div>
                        </div>

                        <div style="
                            border-top: 1px solid #3f3f46;
                            margin-top: 12px;
                            padding-top: 10px;
                            display: grid;
                            gap: 6px;
                            font-size: 13px;
                            color: #d4d4d8;
                        ">
                            <div>
                                Raspored:
                                <strong>
                                    {{ $task['schedule'] ?? '-' }}
                                </strong>
                            </div>

                            <div>
                                Sljedeće izvršenje:
                                <strong>
                                    {{ $task['next_run'] ?? '-' }}
                                </strong>
                            </div>

                            <div>
                                Zadnje izvršenje:
                                <strong>
                                    {{ $task['last_run'] ?? '-' }}
                                </strong>
                            </div>

                            @if (! is_null($task['processed_count']))
                                <div>
                                    Obrađeno:
                                    <strong>
                                        {{ $task['processed_count'] }}
                                    </strong>
                                </div>
                            @endif

                            @if (! is_null($task['duration_ms']))
                                <div>
                                    Trajanje:
                                    <strong>
                                        {{
                                            number_format(
                                                $task['duration_ms'] / 1000,
                                                2,
                                                ',',
                                                '.'
                                            )
                                        }} s
                                    </strong>
                                </div>
                            @endif
                        </div>

                        <div style="
                            font-size: 13px;
                            color: #a1a1aa;
                            margin-top: 10px;
                        ">
                            {{ $task['message'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Server --}}
        <div>
            <h2 style="
                font-size: 20px;
                font-weight: 700;
                margin-bottom: 12px;
            ">
                Server i infrastruktura
            </h2>

            <div class="
                grid
                grid-cols-1
                md:grid-cols-2
                xl:grid-cols-4
                gap-4
            ">
                @foreach ($serverChecks as $check)
                    @php
                        $status = $check['status'] ?? 'warning';

                        $color = $statusColors[$status]
                            ?? '#71717a';
                    @endphp

                    <div style="
                        background: #18181b;
                        border: 1px solid {{ $color }};
                        border-radius: 14px;
                        padding: 18px;
                    ">
                        <div style="
                            font-size: 13px;
                            color: #a1a1aa;
                        ">
                            {{ $check['label'] }}
                        </div>

                        <div style="
                            font-size: 22px;
                            font-weight: 700;
                            color: white;
                            margin-top: 6px;
                        ">
                            @if ($status === 'ok')
                                ✅
                            @elseif ($status === 'critical')
                                ❌
                            @elseif ($status === 'warning')
                                ⚠️
                            @else
                                ℹ️
                            @endif

                            {{ $check['value'] }}
                        </div>

                        <div style="
                            font-size: 13px;
                            color: #d4d4d8;
                            margin-top: 8px;
                            line-height: 1.5;
                        ">
                            {{ $check['note'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Backup --}}
        <div style="
            background: #18181b;
            border: 1px solid #3f3f46;
            border-radius: 14px;
            padding: 18px;
        ">
            <h2 style="
                font-size: 20px;
                font-weight: 700;
                color: white;
                margin-bottom: 12px;
            ">

            {{-- cPanel hosting račun --}}
        <div>
            <h2 style="
                font-size: 20px;
                font-weight: 700;
                margin-bottom: 12px;
            ">
                cPanel hosting račun
            </h2>

            <div class="
                grid
                grid-cols-1
                md:grid-cols-2
                gap-4
            ">
                @foreach ($hostingChecks as $check)
                    @php
                        $status = $check['status'] ?? 'info';

                        $color = $statusColors[$status]
                            ?? '#71717a';
                    @endphp

                    <div style="
                        background: #18181b;
                        border: 1px solid {{ $color }};
                        border-radius: 14px;
                        padding: 18px;
                    ">
                        <div style="
                            font-size: 13px;
                            color: #a1a1aa;
                        ">
                            {{ $check['label'] }}
                        </div>

                        <div style="
                            font-size: 22px;
                            font-weight: 700;
                            color: white;
                            margin-top: 6px;
                        ">
                            @if ($status === 'ok')
                                ✅
                            @elseif ($status === 'critical')
                                ❌
                            @elseif ($status === 'warning')
                                ⚠️
                            @else
                                ℹ️
                            @endif

                            {{ $check['value'] }}
                        </div>

                        <div style="
                            font-size: 13px;
                            color: #d4d4d8;
                            margin-top: 8px;
                            line-height: 1.5;
                        ">
                            {{ $check['note'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
                Backup status
            </h2>

            <pre style="
                background: #09090b;
                color: #d4d4d8;
                padding: 14px;
                border-radius: 10px;
                overflow-x: auto;
                font-size: 13px;
                line-height: 1.45;
            ">{{ $backupOutput }}</pre>
        </div>

        {{-- Akcije --}}
        <div style="
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        ">
            <x-filament::button
                wire:click="loadChecks"
                color="gray"
                icon="heroicon-o-arrow-path"
            >
                Osvježi status
            </x-filament::button>

            <x-filament::button
                wire:click="sendTestMail"
                color="warning"
                icon="heroicon-o-envelope"
            >
                Pošalji testni mail meni
            </x-filament::button>
        </div>

    </div>
</x-filament-panels::page>