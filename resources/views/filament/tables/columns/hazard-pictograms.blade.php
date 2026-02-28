@php
    $items = $getState();

    if (is_string($items)) {
        $items = preg_split('/[,\s]+/', $items);
    }

    $items = is_array($items) ? $items : [];

    $items = collect($items)
        ->map(fn ($v) => strtoupper(trim((string) $v)))
        ->filter()
        ->unique()
        ->values();

    $candidates = function (string $code): array {
        return [
            "images/ghs/{$code}.gif",
            "images/ghs/{$code}.png",
            "images/ghs/{$code}.svg",
            "piktogrami/{$code}.gif",
            "piktogrami/{$code}.png",
            "piktogrami/{$code}.svg",
        ];
    };
@endphp

{{-- FIX: grid 3 kolone + fiksna širina ćelija + fiksna veličina img --}}
<div
    style="
        display: grid;
        grid-template-columns: repeat(3, 28px);
        gap: 6px;
        justify-content: center;
        align-content: center;
        width: 102px;      /* 3*28 + 2*6 = 96 */
        max-width: 102px;
        margin: 0 auto;
    "
>
    @foreach ($items as $code)
        @php
            $src = null;
            foreach ($candidates($code) as $path) {
                if (file_exists(public_path($path))) {
                    $src = asset($path);
                    break;
                }
            }
        @endphp

        @if ($src)
            <img
                src="{{ $src }}"
                alt="{{ $code }}"
                title="{{ $code }}"
                loading="lazy"
                style="
                    width: 30px;
                    height: 30px;
                    display: block;
                    object-fit: contain;
                "
            />
        @endif
    @endforeach
</div>