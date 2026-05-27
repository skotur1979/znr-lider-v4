@php
    $record = $getRecord();

    $activeStyles = [
        0 => 'background:#991b1b;color:#fff;border-color:#ef4444;',
        1 => 'background:#dc2626;color:#fff;border-color:#f87171;',
        2 => 'background:#f59e0b;color:#111827;border-color:#fbbf24;',
        3 => 'background:#fde047;color:#111827;border-color:#facc15;',
        4 => 'background:#84cc16;color:#111827;border-color:#a3e635;',
        5 => 'background:#16a34a;color:#fff;border-color:#4ade80;',
    ];
@endphp

<div style="display:flex; align-items:center; justify-content:center; gap:10px; min-width:420px; padding:6px 0;">
    @for ($i = 0; $i <= 5; $i++)
        @php
            $isActive = (int) $record->score === $i;

            $style = $isActive
                ? $activeStyles[$i] . 'transform:scale(1.08); box-shadow:0 6px 16px rgba(0,0,0,.30);'
                : 'background:#1f2937;color:#ffffff;border-color:#4b5563;';
        @endphp

        <button
            type="button"
            wire:click="setScore({ id: {{ $record->id }}, score: {{ $i }} })"
            title="Ocjena {{ $i }}"
            style="
                width:46px;
                height:42px;
                border-radius:12px;
                border:2px solid;
                font-size:17px;
                font-weight:900;
                line-height:1;
                cursor:pointer;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                transition:all .15s ease;
                {{ $style }}
            "
        >
            {{ $i }}
        </button>
    @endfor
</div>