<x-filament-widgets::widget>
    <div id="today-mini-block" class="today-line-wrap">

        {{-- DATUM --}}
        <div class="today-line-left">
            <div class="today-line-icon">
                📅
            </div>

            <div class="today-line-title-wrap">
                <span class="today-line-title">
                    {{ $dayLabel }}
                </span>

                <span class="today-line-date">
                    ({{ $dayDateLabel }})
                </span>
            </div>
        </div>

        {{-- ZADACI / ROKOVI --}}
        <div class="today-line-center">
            @if ($hasAnything)

                <a
                    href="{{ $tasksUrl }}"
                    class="today-line-summary today-line-summary-task"
                    title="Otvori radne zadatke za {{ $dayDateLabel }}"
                >
                    <span class="today-line-summary-icon">
                        ✓
                    </span>

                    <span class="today-line-label">
                        Zadaci
                    </span>

                    <span class="today-line-number">
                        {{ $taskCount }}
                    </span>
                </a>

                <a
                    href="{{ $calendarUrl }}"
                    class="today-line-summary today-line-summary-deadline"
                    title="Prikaži rokove za {{ $dayDateLabel }}"
                >
                    <span class="today-line-summary-icon">
                        ⏱
                    </span>

                    <span class="today-line-label">
                        Rokovi danas
                    </span>

                    <span class="today-line-number">
                        {{ $deadlineCount }}
                    </span>
                </a>

            @else

                <span class="today-line-empty">
                    Nema zadataka ni rokova za {{ $dayDateLabel }}
                </span>

            @endif
        </div>

        {{-- VRSTE ROKOVA --}}
        @if (! empty($deadlines))
            <div class="today-line-right">
                @foreach ($deadlines as $label => $count)
                    <span
                        class="today-line-pill"
                        title="{{ $label }}"
                    >
                        {{ $label }}:
                        <strong>{{ $count }}</strong>
                    </span>
                @endforeach
            </div>
        @endif

    </div>

    <style>
        /*
        |--------------------------------------------------------------------------
        | GLAVNA TRAKA
        |--------------------------------------------------------------------------
        */

        .today-line-wrap {
            display: grid;

            grid-template-columns:
                auto
                minmax(240px, 1fr)
                auto;

            grid-template-areas:
                "left center right";

            align-items: center;

            column-gap: 18px;
            row-gap: 10px;

            width: 100%;

            padding: 11px 14px;

            border-radius: 14px;

            border: 1px solid #dbe3f0;

            background:
                linear-gradient(
                    180deg,
                    #ffffff 0%,
                    #f8fbff 100%
                );

            box-shadow:
                0 6px 16px
                rgba(15, 23, 42, .05);

            scroll-margin-top: 90px;
        }

        .dark .today-line-wrap {
            border-color:
                rgba(148, 163, 184, .16);

            background:
                linear-gradient(
                    180deg,
                    rgba(8, 18, 40, .96),
                    rgba(5, 12, 28, .96)
                );

            box-shadow:
                0 6px 16px
                rgba(0, 0, 0, .14);
        }


        /*
        |--------------------------------------------------------------------------
        | LIJEVO - DATUM
        |--------------------------------------------------------------------------
        */

        .today-line-left {
            grid-area: left;

            display: flex;
            align-items: center;

            gap: 10px;

            min-width: 0;
        }

        .today-line-icon {
            width: 34px;
            height: 34px;

            flex: 0 0 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background:
                rgba(59, 130, 246, .08);

            border:
                1px solid
                rgba(59, 130, 246, .16);

            font-size: 15px;
        }

        .dark .today-line-icon {
            background:
                rgba(59, 130, 246, .11);

            border-color:
                rgba(59, 130, 246, .22);
        }

        .today-line-title-wrap {
            display: flex;
            align-items: center;

            gap: 5px;

            flex-wrap: wrap;

            min-width: 0;
        }

        .today-line-title {
            font-size: .92rem;
            font-weight: 900;

            color: #0f172a;

            line-height: 1.2;
        }

        .dark .today-line-title {
            color: #ffffff;
        }

        .today-line-date {
            font-size: .88rem;
            font-weight: 900;

            color: #2563eb;

            line-height: 1.2;

            white-space: nowrap;
        }

        .dark .today-line-date {
            color: #60a5fa;
        }


        /*
        |--------------------------------------------------------------------------
        | SREDINA - BROJ ZADATAKA I ROKOVA
        |--------------------------------------------------------------------------
        */

        .today-line-center {
            grid-area: center;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 8px;

            min-width: 0;
        }

        .today-line-summary {
            display: inline-flex;
            align-items: center;

            gap: 6px;

            min-height: 30px;

            padding:
                5px 9px;

            border-radius:
                9px;

            text-decoration: none;

            white-space: nowrap;

            transition:
                transform .15s ease,
                opacity .15s ease,
                border-color .15s ease;
        }

        .today-line-summary:hover {
            transform:
                translateY(-1px);

            opacity: .92;

            text-decoration: none;
        }

        .today-line-summary-task {
            border:
                1px solid
                rgba(59, 130, 246, .18);

            background:
                rgba(59, 130, 246, .07);
        }

        .today-line-summary-deadline {
            border:
                1px solid
                rgba(245, 158, 11, .20);

            background:
                rgba(245, 158, 11, .08);
        }

        .dark .today-line-summary-task {
            border-color:
                rgba(96, 165, 250, .22);

            background:
                rgba(59, 130, 246, .11);
        }

        .dark .today-line-summary-deadline {
            border-color:
                rgba(245, 158, 11, .22);

            background:
                rgba(245, 158, 11, .10);
        }

        .today-line-summary-icon {
            font-size: .82rem;

            font-weight: 900;

            color: #64748b;
        }

        .dark .today-line-summary-icon {
            color: #cbd5e1;
        }

        .today-line-label {
            color: #475569;

            font-size: .76rem;

            font-weight: 800;

            line-height: 1;
        }

        .dark .today-line-label {
            color: #cbd5e1;
        }

        .today-line-number {
            min-width: 20px;
            height: 20px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding:
                0 5px;

            border-radius:
                999px;

            background:
                rgba(15, 23, 42, .08);

            color:
                #0f172a;

            font-size:
                .78rem;

            font-weight:
                900;

            line-height:
                1;
        }

        .dark .today-line-number {
            background:
                rgba(255, 255, 255, .10);

            color:
                #ffffff;
        }


        /*
        |--------------------------------------------------------------------------
        | NEMA ZADATAKA
        |--------------------------------------------------------------------------
        */

        .today-line-empty {
            color: #64748b;

            font-size: .82rem;

            font-weight: 700;

            line-height: 1.35;
        }

        .dark .today-line-empty {
            color: #94a3b8;
        }


        /*
        |--------------------------------------------------------------------------
        | DESNO - VRSTE ROKOVA
        |--------------------------------------------------------------------------
        */

        .today-line-right {
            grid-area: right;

            display: flex;
            align-items: center;
            justify-content: flex-end;

            gap: 5px;

            flex-wrap: wrap;

            min-width: 0;
        }

        .today-line-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 3px;

            padding:
                5px 8px;

            border-radius:
                999px;

            background:
                rgba(245, 158, 11, .10);

            border:
                1px solid
                rgba(245, 158, 11, .20);

            color:
                #b45309;

            font-size:
                .68rem;

            font-weight:
                800;

            line-height:
                1;

            white-space:
                nowrap;
        }

        .dark .today-line-pill {
            background:
                rgba(245, 158, 11, .11);

            border-color:
                rgba(245, 158, 11, .20);

            color:
                #fbbf24;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLET
        |--------------------------------------------------------------------------
        |
        | Na tabletima više ništa ne pokušavamo
        | ugurati u jednu dugu horizontalnu liniju.
        |
        */

        @media (max-width: 1023px) {

            .today-line-wrap {
                grid-template-columns:
                    minmax(0, 1fr)
                    auto;

                grid-template-areas:
                    "left center"
                    "right right";

                align-items:
                    center;

                padding:
                    11px 13px;

                column-gap:
                    12px;

                row-gap:
                    8px;
            }

            .today-line-center {
                justify-content:
                    flex-end;
            }

            .today-line-right {
                justify-content:
                    flex-start;

                padding-left:
                    44px;
            }

            .today-line-empty {
                text-align:
                    right;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | MOBITEL
        |--------------------------------------------------------------------------
        */

        @media (max-width: 640px) {

            .today-line-wrap {
                grid-template-columns:
                    1fr;

                grid-template-areas:
                    "left"
                    "center"
                    "right";

                gap:
                    8px;

                padding:
                    10px 11px;

                border-radius:
                    13px;
            }

            .today-line-icon {
                width:
                    32px;

                height:
                    32px;

                flex-basis:
                    32px;

                font-size:
                    14px;
            }

            .today-line-title {
                font-size:
                    .88rem;
            }

            .today-line-date {
                font-size:
                    .82rem;
            }

            .today-line-center {
                display:
                    grid;

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );

                width:
                    100%;

                gap:
                    7px;

                justify-content:
                    stretch;

                padding-left:
                    42px;
            }

            .today-line-summary {
                width:
                    100%;

                justify-content:
                    center;

                min-width:
                    0;

                padding:
                    6px 7px;
            }

            .today-line-label {
                overflow:
                    hidden;

                text-overflow:
                    ellipsis;

                font-size:
                    .72rem;
            }

            .today-line-number {
                flex-shrink:
                    0;
            }

            .today-line-empty {
                grid-column:
                    1 / -1;

                text-align:
                    left;

                padding:
                    1px 0;

                font-size:
                    .77rem;
            }

            .today-line-right {
                justify-content:
                    flex-start;

                padding-left:
                    42px;

                gap:
                    5px;
            }

            .today-line-pill {
                font-size:
                    .65rem;

                padding:
                    5px 7px;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | VRLO USKI MOBITEL
        |--------------------------------------------------------------------------
        */

        @media (max-width: 390px) {

            .today-line-center {
                grid-template-columns:
                    1fr;
            }

            .today-line-summary {
                justify-content:
                    flex-start;
            }

            .today-line-right {
                padding-left:
                    0;
            }

            .today-line-center {
                padding-left:
                    0;
            }
        }

    </style>
</x-filament-widgets::widget>