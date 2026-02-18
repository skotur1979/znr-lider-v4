@php
    use App\Support\ExpiryBadge;

    /** @var \App\Models\Employee|null $record */
    $record = $getRecord();

    // U v4 uzimamo state preko $getState()
    $items = $getState();

    // Fallback ako state nije zadan (da radi i ako slučajno promijeniš column)
    if ($items === null && $record) {
        $items = $record->certificates;
    }

    if ($items instanceof \Illuminate\Database\Eloquent\Collection) {
        $items = $items->values();
    } elseif (is_array($items)) {
        $items = collect($items);
    } elseif ($items instanceof \Illuminate\Support\Collection) {
        $items = $items->values();
    } else {
        $items = collect();
    }
@endphp

<div class="flex flex-col gap-1">
    @forelse ($items as $c)
        @php
            $title = data_get($c, 'title', '—');
            $until = data_get($c, 'valid_until');

            // ista logika kao Machines preko ExpiryBadge
            $color   = ExpiryBadge::color($until);
            $icon    = ExpiryBadge::icon($until);
            $tooltip = ExpiryBadge::tooltip($until);

            $untilLabel = blank($until)
                ? '—'
                : \Illuminate\Support\Carbon::parse($until)->format('d.m.Y.');
        @endphp

        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium">
                {{ $title }}
            </span>

            <x-filament::badge :color="$color" :icon="$icon" :tooltip="$tooltip">
                {{ $untilLabel }}
            </x-filament::badge>
        </div>
    @empty
        <span class="text-xs text-gray-500">—</span>
    @endforelse
</div>
