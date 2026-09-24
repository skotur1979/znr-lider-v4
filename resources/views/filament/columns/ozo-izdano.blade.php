@php
    use Illuminate\Support\Carbon;

    $pregled =
        data_get(
            $this->tableFilters ?? [],
            'pregled.value'
        );

    if (blank($pregled)) {
        $pregled =
            match (
                request()->query(
                    'pregled'
                )
            ) {
                'isteklo' =>
                    'isteklo',

                'uskoro' =>
                    'istek',

                default =>
                    null,
            };
    }

    $today =
        Carbon::today()
            ->startOfDay();

    $soonUntil =
        Carbon::today()
            ->addDays(30)
            ->endOfDay();

    $items =
        collect(
            $getRecord()->items
            ?? []
        )
            ->filter(
                function (
                    $item
                ) use (
                    $pregled,
                    $today,
                    $soonUntil
                ): bool {
                    if (
                        ! blank(
                            $item->return_date
                        )
                    ) {
                        return false;
                    }

                    if (
                        blank($pregled)
                        || $pregled === 'svi'
                        || $pregled === 'deaktivirani'
                    ) {
                        return true;
                    }

                    if (
                        blank(
                            $item->end_date
                        )
                    ) {
                        return false;
                    }

                    $endDate =
                        Carbon::parse(
                            $item->end_date
                        )->startOfDay();

                    if (
                        $pregled ===
                        'isteklo'
                    ) {
                        return $endDate
                            ->lt(
                                $today
                            );
                    }

                    if (
                        in_array(
                            $pregled,
                            [
                                'istek',
                                'uskoro',
                            ],
                            true
                        )
                    ) {
                        return $endDate
                            ->gte(
                                $today
                            )
                            && $endDate
                                ->lte(
                                    $soonUntil
                                );
                    }

                    return true;
                }
            )
            ->sort(
                function (
                    $a,
                    $b
                ) {
                    $aHasEnd =
                        ! blank(
                            $a->end_date
                        );

                    $bHasEnd =
                        ! blank(
                            $b->end_date
                        );

                    if (
                        $aHasEnd
                        && ! $bHasEnd
                    ) {
                        return -1;
                    }

                    if (
                        ! $aHasEnd
                        && $bHasEnd
                    ) {
                        return 1;
                    }

                    if (
                        ! $aHasEnd
                        && ! $bHasEnd
                    ) {
                        $aIssue =
                            $a->issue_date
                                ? Carbon::parse(
                                    $a->issue_date
                                )->timestamp
                                : 0;

                        $bIssue =
                            $b->issue_date
                                ? Carbon::parse(
                                    $b->issue_date
                                )->timestamp
                                : 0;

                        return
                            $bIssue
                            <=>
                            $aIssue;
                    }

                    $aEnd =
                        Carbon::parse(
                            $a->end_date
                        )->timestamp;

                    $bEnd =
                        Carbon::parse(
                            $b->end_date
                        )->timestamp;

                    if (
                        $aEnd !==
                        $bEnd
                    ) {
                        return
                            $bEnd
                            <=>
                            $aEnd;
                    }

                    $aIssue =
                        $a->issue_date
                            ? Carbon::parse(
                                $a->issue_date
                            )->timestamp
                            : 0;

                    $bIssue =
                        $b->issue_date
                            ? Carbon::parse(
                                $b->issue_date
                            )->timestamp
                            : 0;

                    if (
                        $aIssue !==
                        $bIssue
                    ) {
                        return
                            $bIssue
                            <=>
                            $aIssue;
                    }

                    return
                        (int) (
                            $b->duration_months
                            ?? 0
                        )
                        <=>
                        (int) (
                            $a->duration_months
                            ?? 0
                        );
                }
            )
            ->values();
@endphp

<div
    style="
        display:flex;
        flex-direction:column;
        gap:6px;
    "
>
    @forelse($items as $item)
        <div
            style="
                min-height:30px;
                display:flex;
                align-items:center;
                white-space:nowrap;
            "
        >
            {{
                $item->issue_date
                    ? Carbon::parse(
                        $item->issue_date
                    )->format(
                        'd.m.Y.'
                    )
                    : '—'
            }}
        </div>
    @empty
        <div
            style="
                min-height:30px;
                display:flex;
                align-items:center;
                color:#9ca3af;
            "
        >
            —
        </div>
    @endforelse
</div>