@php
    use Illuminate\Support\Carbon;
    use App\Support\ExpiryBadge;

    /*
    |--------------------------------------------------------------------------
    | REDOSLIJED OZO STAVKI
    |--------------------------------------------------------------------------
    |
    | Mora ostati jednak kao u Bladeovima:
    |
    | - Naziv OZO
    | - Izdano
    | - Istek
    |
    | kako bi svi podaci ostali u istom retku.
    |
    */

    $items = collect(
        $getRecord()->items ?? []
    )->sort(function ($a, $b) {
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
            $aEnd !== $bEnd
        ) {
            /*
             * Najkasniji istek gore.
             */
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
            $aIssue !== $bIssue
        ) {
            return
                $bIssue
                <=>
                $aIssue;
        }

        $aDuration =
            (int) (
                $a->duration_months
                ?? 0
            );

        $bDuration =
            (int) (
                $b->duration_months
                ?? 0
            );

        return
            $bDuration
            <=>
            $aDuration;
    })->values();

    /*
     * Isto pravilo kao i u ostalim
     * modulima aplikacije.
     */
    $soonDays = 30;

    $classMap = [
        'danger' =>
            'ozo-badge ozo-badge-danger',

        'warning' =>
            'ozo-badge ozo-badge-warning',

        'success' =>
            'ozo-badge ozo-badge-success',

        'gray' =>
            'ozo-badge ozo-badge-gray',
    ];

    /*
    |--------------------------------------------------------------------------
    | IKONE
    |--------------------------------------------------------------------------
    */

    $svg = [
        'heroicon-o-check-circle' =>
            '<svg
                xmlns="http://www.w3.org/2000/svg"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                style="display:block"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                />
            </svg>',

        'heroicon-o-exclamation-triangle' =>
            '<svg
                xmlns="http://www.w3.org/2000/svg"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                style="display:block"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 9v4"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 17h.01"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
                />
            </svg>',

        'heroicon-o-x-circle' =>
            '<svg
                xmlns="http://www.w3.org/2000/svg"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                style="display:block"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 9l-6 6"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 9l6 6"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                />
            </svg>',

        null => '',
    ];
@endphp

<style>
    /*
    |--------------------------------------------------------------------------
    | OSNOVNI BADGE
    |--------------------------------------------------------------------------
    */

    .ozo-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        gap: 6px;

        min-height: 24px;
        min-width: 105px;

        padding: 3px 10px;

        border-radius: 9999px;
        border: 1px solid;

        font-weight: 700;
        line-height: 1.2;

        white-space: nowrap;
    }

    /*
    |--------------------------------------------------------------------------
    | LIGHT MODE
    |--------------------------------------------------------------------------
    |
    | Boje su namjerno nešto izraženije
    | nego prije, ali prate isti princip:
    |
    | success = zeleno
    | warning = žuto
    | danger  = crveno
    |
    */

    .ozo-badge-success {
        border-color: #86efac;
        background-color: #dcfce7;
        color: #166534;
    }

    .ozo-badge-warning {
        border-color: #fcd34d;
        background-color: #fef3c7;
        color: #92400e;
    }

    .ozo-badge-danger {
        border-color: #fca5a5;
        background-color: #fee2e2;
        color: #991b1b;
    }

    .ozo-badge-gray {
        border-color: #d1d5db;
        background-color: #f3f4f6;
        color: #374151;
    }

    /*
    |--------------------------------------------------------------------------
    | DARK MODE
    |--------------------------------------------------------------------------
    */

    .dark .ozo-badge-success {
        border-color: rgba(
            34,
            197,
            94,
            .90
        );

        background-color: rgba(
            34,
            197,
            94,
            .22
        );

        color: #bbf7d0;
    }

    .dark .ozo-badge-warning {
        border-color: rgba(
            245,
            158,
            11,
            .95
        );

        background-color: rgba(
            245,
            158,
            11,
            .24
        );

        color: #fde68a;
    }

    .dark .ozo-badge-danger {
        border-color: rgba(
            239,
            68,
            68,
            .90
        );

        background-color: rgba(
            239,
            68,
            68,
            .24
        );

        color: #fecaca;
    }

    .dark .ozo-badge-gray {
        border-color: rgba(
            156,
            163,
            175,
            .70
        );

        background-color: rgba(
            156,
            163,
            175,
            .16
        );

        color: #e5e7eb;
    }
</style>

<div
    style="
        display:flex;
        flex-direction:column;
        gap:6px;
    "
>
    @forelse($items as $item)
        @php
            $date =
                $item->end_date
                    ? Carbon::parse(
                        $item->end_date
                    )->startOfDay()
                    : null;

            /*
             * Sva poslovna logika ostaje
             * centralizirana u ExpiryBadge.
             */
            $status =
                ExpiryBadge::color(
                    $date,
                    $soonDays
                );

            $iconKey =
                ExpiryBadge::icon(
                    $date,
                    $soonDays
                );

            $tooltip =
                ExpiryBadge::tooltip(
                    $date,
                    $soonDays
                );

            $classes =
                $classMap[$status]
                ?? $classMap['gray'];

            $text =
                $date
                    ? $date->format(
                        'd.m.Y.'
                    )
                    : '—';
        @endphp

        <div
            style="
                min-height:30px;
                display:flex;
                align-items:center;
                white-space:nowrap;
            "
        >
            <span
                class="{{ $classes }}"
                title="{{ $tooltip }}"
            >
                {!! $svg[$iconKey] ?? '' !!}

                <span>
                    {{ $text }}
                </span>
            </span>
        </div>
    @empty
        <div
            style="
                min-height:30px;
                display:flex;
                align-items:center;
                white-space:nowrap;
            "
        >
            <span
                class="{{ $classMap['gray'] }}"
                title="Rok nije definiran"
            >
                —
            </span>
        </div>
    @endforelse
</div>