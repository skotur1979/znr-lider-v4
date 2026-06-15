<x-filament-panels::page>
    <div class="space-y-6">

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
                        {{ $check['ok'] ? '✅' : '⚠️' }} {{ $check['value'] }}
                    </div>

                    <div style="font-size: 13px; color: #d4d4d8; margin-top: 8px;">
                        {{ $check['note'] }}
                    </div>
                </div>
            @endforeach
        </div>

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
            ">{{ $this->runBackupList() }}</pre>
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