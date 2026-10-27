@php
    use Carbon\Carbon;
    use App\Support\ExpiryBadge;
    use App\Filament\Resources\FirstAidKits\FirstAidKitResource;

    /** @var \App\Models\FirstAidKit $record */
    $record = $getRecord();

    $items =
        $record?->items
        ?? collect();

    $today =
        now(
            config('app.timezone')
        )->startOfDay();

    $soonDays = 30;

    $soonItems = [];
    $expiredItems = [];

    foreach ($items as $item) {
        $raw =
            $item->valid_until;

        if (blank($raw)) {
            continue;
        }

        $date =
            $raw instanceof \DateTimeInterface
                ? Carbon::instance(
                    $raw
                )->startOfDay()
                : Carbon::parse(
                    $raw
                )->startOfDay();

        if (
            $date->lt(
                $today
            )
        ) {
            $expiredItems[] = [
                $item,
                $date,
            ];

            continue;
        }

        if (
            $date->lte(
                $today
                    ->copy()
                    ->addDays(
                        $soonDays
                    )
            )
        ) {
            $soonItems[] = [
                $item,
                $date,
            ];
        }
    }

    $soon =
        count(
            $soonItems
        );

    $expired =
        count(
            $expiredItems
        );

    /*
    |--------------------------------------------------------------------------
    | TOOLTIP
    |--------------------------------------------------------------------------
    */

    $tooltipLines = [];

    foreach (
        $expiredItems
        as [$item, $date]
    ) {
        $tooltipLines[] =
            "Isteklo: {$item->material_type} — "
            . $date->format(
                'd.m.Y.'
            );
    }

    foreach (
        $soonItems
        as [$item, $date]
    ) {
        $tooltipLines[] =
            "Uskoro: {$item->material_type} — "
            . $date->format(
                'd.m.Y.'
            );
    }

    $tooltip =
        $tooltipLines
            ? implode(
                "\n",
                $tooltipLines
            )
            : 'Sve stavke važeće';

    /*
    |--------------------------------------------------------------------------
    | URL ZA PREGLED
    |--------------------------------------------------------------------------
    */

    $parameters = [
        'record' =>
            $record,
    ];

    $pregled =
        request()->query(
            'pregled'
        );

    if (
        in_array(
            $pregled,
            [
                'isteklo',
                'uskoro',
            ],
            true
        )
    ) {
        $parameters[
            'pregled'
        ] =
            $pregled;
    }

    $viewUrl =
        FirstAidKitResource::getUrl(
            'view',
            $parameters
        );
@endphp

<div
    style="
        width:100%;
        min-width:100%;
        display:flex;
        align-items:center;
        justify-content:center;
        text-align:center;
    "
>
    <a
        href="{{ $viewUrl }}"
        title="{{ $tooltip }}"
        x-on:click.stop
        style="
            width:100%;
            min-width:100%;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap:4px;
            text-align:center;
            text-decoration:none;
        "
    >
        @if (
            $soon === 0
            && $expired === 0
        )
            <div
                style="
                    width:100%;
                    display:flex;
                    justify-content:center;
                    align-items:center;
                "
            >
                <x-filament::badge
                    color="success"
                >
                    Sve stavke važeće
                </x-filament::badge>
            </div>
        @else
            @if ($soon > 0)
                <div
                    style="
                        width:100%;
                        display:flex;
                        justify-content:center;
                        align-items:center;
                    "
                >
                    <x-filament::badge
                        color="warning"
                    >
                        {{ $soon }} uskoro
                    </x-filament::badge>
                </div>
            @endif

            @if ($expired > 0)
                <div
                    style="
                        width:100%;
                        display:flex;
                        justify-content:center;
                        align-items:center;
                    "
                >
                    <x-filament::badge
                        color="danger"
                    >
                        {{ $expired }} isteklo
                    </x-filament::badge>
                </div>
            @endif
        @endif
    </a>
</div>