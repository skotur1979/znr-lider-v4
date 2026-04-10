<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                Trend po mjesecima ({{ now()->year }})
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pregled unesenih i automatski generiranih vrijednosti po mjesecima
            </p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-6">
        @foreach ($trend as $item)
            @php
                $hasValue = $item['has_value'];
                $value = $item['value'];
                $target = $record->target_value;

                $statusClass = 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60';

                if ($hasValue && $target !== null) {
                    $status = $record->evaluateStatus($value);

                    $statusClass = match ($status) {
                        'success' => 'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950/30',
                        'warning' => 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30',
                        'danger' => 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30',
                        default => 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60',
                    };
                }
            @endphp

            <div class="rounded-xl border p-4 {{ $statusClass }}">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $item['label'] }}
                </div>

                <div class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                    {{ $item['formatted'] }}
                </div>

                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $record->unit ?: 'bez jedinice' }}
                </div>
            </div>
        @endforeach
    </div>
</div>