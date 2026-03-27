@php
    $record = $getRecord();

    $buttonClasses = function (int $value) use ($record) {
        $isActive = (int) $record->score === $value;

        if ($isActive) {
            return match ($value) {
                0 => 'bg-red-700 text-white border-red-500 shadow-[0_0_0_1px_rgba(239,68,68,.45)]',
                1 => 'bg-red-600 text-white border-red-400 shadow-[0_0_0_1px_rgba(248,113,113,.45)]',
                2 => 'bg-amber-600 text-black border-amber-400 shadow-[0_0_0_1px_rgba(251,191,36,.45)]',
                3 => 'bg-yellow-400 text-black border-yellow-300 shadow-[0_0_0_1px_rgba(253,224,71,.45)]',
                4 => 'bg-lime-500 text-black border-lime-300 shadow-[0_0_0_1px_rgba(132,204,22,.45)]',
                5 => 'bg-green-600 text-white border-green-400 shadow-[0_0_0_1px_rgba(74,222,128,.45)]',
                default => 'bg-primary-600 text-white border-primary-400',
            };
        }

        return 'bg-gray-100 text-gray-900 border-gray-300 hover:bg-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700';
    };
@endphp

<div class="flex flex-wrap items-center justify-center gap-1 min-w-[170px]">
    @for ($i = 0; $i <= 5; $i++)
        <button
            type="button"
            wire:click="setScore({ id: {{ $record->id }}, score: {{ $i }} })"
            class="min-w-[30px] h-[30px] px-2 text-sm font-bold rounded-md border transition {{ $buttonClasses($i) }}"
            title="Ocjena {{ $i }}"
        >
            {{ $i }}
        </button>
    @endfor
</div>