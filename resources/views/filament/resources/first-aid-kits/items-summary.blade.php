@php
    use Carbon\Carbon;
    use App\Support\ExpiryBadge;

    /** @var \App\Models\FirstAidKit $record */
    $record = $getRecord(); // ✅ Filament v4
    $items = $record?->items ?? collect();

    $today = now(config('app.timezone'))->startOfDay();

    $soonDays = 30;
    $soonItems = [];
    $expiredItems = [];
    $validItems = [];

    foreach ($items as $item) {
        $raw = $item->valid_until;

        if (blank($raw)) {
            $validItems[] = $item;
            continue;
        }

        $d = $raw instanceof \DateTimeInterface
            ? Carbon::instance($raw)->startOfDay()
            : Carbon::parse($raw)->startOfDay();

        if ($d->lt($today)) {
            $expiredItems[] = [$item, $d];
        } elseif ($d->lte($today->copy()->addDays($soonDays))) {
            $soonItems[] = [$item, $d];
        } else {
            $validItems[] = [$item, $d];
        }
    }

    $soon = count($soonItems);
    $expired = count($expiredItems);

    // tooltip popis (uskoro + isteklo)
    $tooltipLines = [];

    foreach ($expiredItems as [$item, $d]) {
        $tooltipLines[] = "🔴 {$item->material_type} — {$d->format('d.m.Y')}";
    }
    foreach ($soonItems as [$item, $d]) {
        $tooltipLines[] = "🟡 {$item->material_type} — {$d->format('d.m.Y')}";
    }

    $tooltip = $tooltipLines
        ? implode("\n", $tooltipLines)
        : 'Sve stavke važeće';

    $modalId = 'fa-kit-items-' . $record->getKey();

    // klase preko ExpiryBadge (hack: šaljemo "datum" koji daje željenu boju)
    $classExpired = ExpiryBadge::classes($today->copy()->subDay(), $soonDays);
    $classSoon    = ExpiryBadge::classes($today->copy()->addDays(1), $soonDays);
    $classOk      = ExpiryBadge::classes($today->copy()->addYears(10), $soonDays);
@endphp

<div class="flex flex-col items-center gap-1">
    {{-- klik na badge => modal --}}
    <button
        type="button"
        class="focus:outline-none"
        title="{{ $tooltip }}"
        x-on:click="$dispatch('open-modal', { id: '{{ $modalId }}' })"
    >
        @if ($soon === 0 && $expired === 0)
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs {{ $classOk }}">
                Sve stavke važeće
            </span>
        @else
            <div class="flex flex-col items-center gap-1">
                @if ($soon > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs {{ $classSoon }}">
                        🟡 {{ $soon }} uskoro
                    </span>
                @endif

                @if ($expired > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs {{ $classExpired }}">
                        🔴 {{ $expired }} isteklo
                    </span>
                @endif
            </div>
        @endif
    </button>

    {{-- Modal: sadržaj ormarića --}}
    <x-filament::modal :id="$modalId" width="3xl" alignment="center">
        <x-slot name="heading">
            Sadržaj ormarića: {{ $record->location }}
        </x-slot>

        <div class="space-y-3">
            @if ($items->isEmpty())
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Nema stavki.
                </div>
            @else
                <div class="divide-y divide-white/10">
                    @foreach ($items as $item)
                        @php
                            $d = blank($item->valid_until)
                                ? null
                                : ($item->valid_until instanceof \DateTimeInterface
                                    ? Carbon::instance($item->valid_until)->startOfDay()
                                    : Carbon::parse($item->valid_until)->startOfDay());

                            $badgeClass = ExpiryBadge::classes($d, $soonDays);
                            $badgeTip   = ExpiryBadge::tooltip($d, $soonDays);

                            $dateText = $d ? $d->format('d.m.Y') : '—';
                        @endphp

                        <div class="py-2 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-medium text-sm text-gray-100">
                                    {{ $item->material_type }}
                                </div>
                                @if (!blank($item->purpose))
                                    <div class="text-xs text-gray-400">
                                        {{ $item->purpose }}
                                    </div>
                                @endif
                            </div>

                            <div class="shrink-0 text-right">
                                <div class="text-xs text-gray-400 mb-1">Vrijedi do</div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs {{ $badgeClass }}" title="{{ $badgeTip }}">
                                    {{ $dateText }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: '{{ $modalId }}' })">
                Zatvori
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>