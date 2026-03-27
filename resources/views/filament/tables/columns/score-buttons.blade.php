@php
    $record = $getRecord();
@endphp

<div class="flex gap-1 justify-center">
    @for ($i = 0; $i <= 5; $i++)
        <button
            type="button"
            wire:click="setScore({ id: {{ $record->id }}, score: {{ $i }} })"
            class="px-2 py-1 text-xs rounded border transition
                {{ (int) $record->score === $i
                    ? 'bg-primary-600 text-white border-primary-600'
                    : 'bg-white/5 text-white border-white/10 hover:bg-white/10' }}"
        >
            {{ $i }}
        </button>
    @endfor
</div>