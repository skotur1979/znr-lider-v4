@php
    $points = collect($trend)->values();
    $numericValues = $points->pluck('value')->filter(fn ($v) => $v !== null);

    $max = $numericValues->max();
    $min = $numericValues->min();

    if ($max === null || $min === null) {
        $max = 1;
        $min = 0;
    }

    if ((float) $max === (float) $min) {
        $max += 1;
    }

    $width = 900;
    $height = 240;
    $paddingX = 40;
    $paddingY = 30;
    $count = max($points->count(), 1);
    $stepX = ($width - ($paddingX * 2)) / max($count - 1, 1);

    $svgPoints = [];
    foreach ($points as $index => $item) {
        $x = $paddingX + ($index * $stepX);
        $value = $item['value'];

        if ($value === null) {
            $svgPoints[] = null;
            continue;
        }

        $normalized = ($value - $min) / (($max - $min) ?: 1);
        $y = $height - $paddingY - ($normalized * ($height - ($paddingY * 2)));

        $svgPoints[] = [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'label' => $item['label'],
            'value' => $item['formatted'],
        ];
    }

    $polyline = collect($svgPoints)
        ->filter()
        ->map(fn ($p) => $p['x'] . ',' . $p['y'])
        ->implode(' ');
@endphp

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="mb-4">
        <h3 class="text-base font-bold text-gray-900 dark:text-white">
            Trend po mjesecima ({{ now()->year }})
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Linijski pregled unesenih i automatski generiranih vrijednosti
        </p>
    </div>

    <div class="overflow-x-auto">
        <svg viewBox="0 0 900 240" class="min-w-[900px] w-full">
            @for ($i = 0; $i < 5; $i++)
                @php
                    $y = 30 + ($i * 45);
                @endphp
                <line x1="40" y1="{{ $y }}" x2="860" y2="{{ $y }}" stroke="rgba(148,163,184,0.25)" stroke-width="1" />
            @endfor

            @if($polyline !== '')
                <polyline
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3"
                    class="text-primary-500"
                    points="{{ $polyline }}"
                />
            @endif

            @foreach($svgPoints as $point)
                @if($point)
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4.5" class="fill-primary-500" />
                @endif
            @endforeach

            @foreach($points as $index => $item)
                @php
                    $x = $paddingX + ($index * $stepX);
                @endphp
                <text x="{{ $x }}" y="228" text-anchor="middle" font-size="11" fill="currentColor" class="text-gray-500">
                    {{ $item['label'] }}
                </text>
            @endforeach
        </svg>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-6">
        @foreach ($trend as $item)
            @php
                $statusClass = 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60';

                if ($item['has_value'] && $record->target_value !== null) {
                    $status = $record->evaluateStatus($item['value']);

                    $statusClass = match ($status) {
                        'success' => 'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950/30',
                        'warning' => 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30',
                        'danger' => 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30',
                        default => 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60',
                    };
                }
            @endphp

            <div class="rounded-xl border p-3 {{ $statusClass }}">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $item['label'] }}
                </div>
                <div class="mt-1 text-base font-bold text-gray-900 dark:text-white">
                    {{ $item['formatted'] }}
                </div>
            </div>
        @endforeach
    </div>
</div>