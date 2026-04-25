@php
    use App\Support\ExpiryBadge;

    $record = $getRecord();
    $items = $getState();

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

<div style="
    max-width: 260px;
    width: 260px;
    overflow: hidden;
">
    <div style="
        display: flex;
        flex-direction: column;
        gap: 4px;
        max-width: 100%;
        overflow: hidden;
    ">
        @forelse ($items as $c)
            @php
                $title = data_get($c, 'title', '—');
                $until = data_get($c, 'valid_until');

                $color = ExpiryBadge::color($until);
                $icon = ExpiryBadge::icon($until);
                $tooltip = ExpiryBadge::tooltip($until);

                $untilLabel = blank($until)
                    ? '—'
                    : \Illuminate\Support\Carbon::parse($until)->format('d.m.Y.');
            @endphp

            <div style="
                display: flex;
                align-items: center;
                gap: 6px;
                max-width: 100%;
                overflow: hidden;
                white-space: nowrap;
            ">
                <span
                    title="{{ $title }}"
                    style="
                        display: inline-block;
                        max-width: 145px;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                        font-size: 13px;
                        font-weight: 600;
                    "
                >
                    {{ $title }}
                </span>

                <span style="flex-shrink: 0;">
                    <x-filament::badge :color="$color" :icon="$icon" :tooltip="$tooltip">
                        {{ $untilLabel }}
                    </x-filament::badge>
                </span>
            </div>
        @empty
            <span style="font-size:12px; color:#6b7280;">—</span>
        @endforelse
    </div>
</div>