<x-filament-widgets::widget>
    @php
        $stats = $this->getCachedStats();
    @endphp

    <style>
        /* “pro” glow pulsiranje (radi s currentColor) */
        @keyframes znrGlow {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(0,0,0,0)); opacity: 1; }
            50%      { transform: scale(1.05); filter: drop-shadow(0 0 10px currentColor); opacity: .85; }
        }
        .znr-glow {
            animation: znrGlow 2.2s ease-in-out infinite;
        }
    </style>

    {{-- ✅ UVIJEK 6 u jednom redu:
         - grid-cols-6
         - ako ekran nema širinu -> horizontal scroll (ne lomi u 2 reda)
    --}}
    <div class="overflow-x-auto">
        <div class="grid grid-cols-6 gap-4 min-w-[960px]">
            @foreach ($stats as $stat)
                @php
                    $key  = data_get($stat->getExtraAttributes(), 'data-key');
                    $tone = data_get($stat->getExtraAttributes(), 'data-tone', 'emerald');

                    $isDays = $key === 'days';

                    $ring = match($tone) {
                        'red' => 'ring-1 ring-red-500/30',
                        'yellow' => 'ring-1 ring-yellow-500/30',
                        default => 'ring-1 ring-emerald-500/30',
                    };

                    $number = match($tone) {
                        'red' => 'text-red-400',
                        'yellow' => 'text-yellow-400',
                        default => 'text-emerald-400',
                    };
                @endphp

                <div class="
                    rounded-xl
                    bg-white dark:bg-gray-800
                    shadow
                    h-28
                    px-4
                    flex flex-col items-center justify-center text-center
                    ring-1 ring-white/0
                    {{ $isDays ? $ring : '' }}
                ">
                    <div class="text-[11px] text-gray-500 dark:text-gray-300 font-bold uppercase tracking-wider">
                        {{ $stat->getLabel() }}
                    </div>

                    <div class="
                        mt-2
                        font-extrabold leading-none
                        {{ $isDays ? ($number.' znr-glow') : 'text-gray-900 dark:text-white' }}
                    " style="font-size: 34px; line-height: 1;">
                        {{ $stat->getValue() }}
                    </div>

                    @if($stat->getDescription())
                        <div class="text-[10px] mt-2 text-gray-500 dark:text-gray-400">
                            {{ $stat->getDescription() }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>