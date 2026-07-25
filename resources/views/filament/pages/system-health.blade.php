<x-filament-panels::page>
    <div class="space-y-6">

        @php
            $overall = $summary['overall'] ?? 'warning';

            $overallColor = match ($overall) {
                'ok' => '#16a34a',
                'critical' => '#dc2626',
                default => '#d97706',
            };

            $statusColors = [
                'ok' => '#16a34a',
                'warning' => '#d97706',
                'critical' => '#dc2626',
                'never_run' => '#71717a',
            ];
        @endphp

        {{-- Sažetak --}}
        <div style="
            background: #18181b;
            border: 2px solid {{ $overallColor }};
            border-radius: 14px;
            padding: 20px;
        ">
            <div style="font-size: 13px; color: #a1a1aa;">
                Ukupni status sustava
            </div>

            <div style="font-size: 26px; font-weight: 800; color: white; margin-top: 5px;">
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
                <span>U redu: <strong>{{ $summary['ok'] ?? 0 }}</strong></span>
                <span>Upozorenja: <strong>{{ $summary['warning'] ?? 0 }}</strong></span>
                <span>Kritično: <strong>{{ $summary['critical'] ?? 0 }}</strong></span>
                <span>
                    Osvježeno:
                    <strong>{{ $summary['updated_at'] ?? '-' }}</strong>
                </span>
            </div>
        </div>

        {{-- Konfiguracija --}}
        <div>
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 12px;">
                Konfiguracija sustava
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($checks as $check)
                    <div style="
                        background: #18181b;
                        border: 1px solid {{ $check['ok'] ? '#16a34a' : '#dc2626' }};
                        border-radius: 14px;
                        padding: 18px;
                    ">
                        <div style="font-size: 13px; color: #a1a1aa;">
                            {{ $check['label'] }}
                        </div>

                        <div style="font-size: 22px; font-weight: 700; color: white; margin-top: 6px;">
                            {{ $check['ok'] ? '✅' : '⚠️' }}
                            {{ $check['value'] }}
                        </div>

                        <div style="font-size: 13px; color: #d4d4d8; margin-top: 8px;">
                            {{ $check['note'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Automatski zadaci --}}
        <div>
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 12px;">
                Automatski zadaci
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($taskChecks as $task)
                    @php
                        $taskColor = $statusColors[$task['status']] ?? '#71717a';
                    @endphp

                    <div style="
                        background: #18181b;
                        border: 1px solid {{ $taskColor }};
                        border-radius: 14px;
                        padding: 18px;
                    ">
                        <div style="font-size: 13px; color: #a1a1aa;">
                            {{ $task['label'] }}
                        </div>

                        <div style="
                            font-size: 20px;
                            font-weight: 700;
                            color: white;
                            margin-top: 6px;
                        ">
                            @if ($task['status'] === 'ok')
                                ✅
                            @elseif ($task['status'] === 'critical')
                                ❌
                            @elseif ($task['status'] === 'warning')
                                ⚠️
                            @else
                                ℹ️
                            @endif

                            {{ $task['status_label'] }}
                        </div>

                        <div style="font-size: 13px; color: #d4d4d8; margin-top: 8px;">
                            Zadnje uspješno izvršenje:
                            <strong>{{ $task['last_run'] ?? '-' }}</strong>
                        </div>

                        @if (! is_null($task['processed_count']))
                            <div style="font-size: 13px; color: #d4d4d8; margin-top: 5px;">
                                Obrađeno:
                                <strong>{{ $task['processed_count'] }}</strong>
                            </div>
                        @endif

                        @if (! is_null($task['duration_ms']))
                            <div style="font-size: 13px; color: #d4d4d8; margin-top: 5px;">
                                Trajanje:
                                <strong>{{ number_format($task['duration_ms'] / 1000, 2, ',', '.') }} s</strong>
                            </div>
                        @endif

                        <div style="font-size: 13px; color: #a1a1aa; margin-top: 8px;">
                            {{ $task['message'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Server i infrastruktura --}}
        <div>
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 12px;">
                Server i infrastruktura
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach ($serverChecks as $check)
                    @php
                        $checkColor = $statusColors[$check['status']] ?? '#71717a';
                    @endphp

                    <div style="
                        background: #18181b;
                        border: 1px solid {{ $checkColor }};
                        border-radius: 14px;
                        padding: 18px;
                    ">
                        <div style="font-size: 13px; color: #a1a1aa;">
                            {{ $check['label'] }}
                        </div>

                        <div style="font-size: 22px; font-weight: 700; color: white; margin-top: 6px;">
                            {{ $check['value'] }}
                        </div>

                        <div style="font-size: 13px; color: #d4d4d8; margin-top: 8px;">
                            {{ $check['note'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Backup status --}}
        <div style="
            background: #18181b;
            border: 1px solid #3f3f46;
            border-radius: 14px;
            padding: 18px;
        ">
            <h2 style="font-size: 20px; font-weight: 700; color: white; margin-bottom: 12px;">
                Backup status
            </h2>

            <pre style="
                background: #09090b;
                color: #d4d4d8;
                padding: 14px;
                border-radius: 10px;
                overflow-x: auto;
                font-size: 13px;
            ">{{ $backupOutput }}</pre>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <x-filament::button wire:click="loadChecks" color="gray">
                Osvježi status
            </x-filament::button>

            <x-filament::button wire:click="sendTestMail" color="warning">
                Pošalji testni mail meni
            </x-filament::button>
        </div>

    </div>
</x-filament-panels::page>