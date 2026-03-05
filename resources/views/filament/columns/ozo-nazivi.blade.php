@php
    $record = $getRecord(); // ✅ Filament v4
    $items = $record?->items ?? collect();
@endphp

<div class="space-y-1 text-sm">
    @forelse($items as $item)
        <div class="font-semibold">
            {{ $item->equipment_name }}
        </div>
    @empty
        <div class="text-gray-500">—</div>
    @endforelse
</div>