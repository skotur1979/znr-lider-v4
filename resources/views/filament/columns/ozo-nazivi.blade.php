@php
    $items = collect($getRecord()->items ?? [])->sortBy('equipment_name')->values();
@endphp

<div style="display:flex; flex-direction:column; gap:4px;">
    @forelse($items as $item)
        <div style="
            min-height: 28px;
            display:flex;
            align-items:center;
            white-space:nowrap;
        ">
            {{ $item->equipment_name }}
        </div>
    @empty
        <div style="
            min-height: 28px;
            display:flex;
            align-items:center;
            color:#9ca3af;
        ">
            —
        </div>
    @endforelse
</div>