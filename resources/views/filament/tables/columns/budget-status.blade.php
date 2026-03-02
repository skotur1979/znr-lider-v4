@php
    /** @var \App\Models\Budget $record */
    $record = $getRecord(); // ✅ OBAVEZNO zagrade

    $spent = (float) $record->expenses()
        ->where('realizirano', true)
        ->sum('iznos');

    $budget    = (float) $record->ukupni_budget;
    $remaining = $budget - $spent;

    $fmt = fn ($n) => number_format($n, 2, ',', '.') . ' €';

    $pct = $budget > 0 ? min(100, ($spent / $budget) * 100) : 0;
@endphp

<div class="flex items-center gap-3">
    <span class="{{ $remaining < 0 ? 'text-red-600' : 'text-green-600' }}">
        {{ $fmt($remaining) }}
    </span>

    <div class="w-28 h-1.5 rounded bg-gray-200 dark:bg-gray-700">
        <div class="h-1.5 rounded {{ $remaining < 0 ? 'bg-red-500' : 'bg-primary-500' }}"
             style="width: {{ $pct }}%"></div>
    </div>
</div>