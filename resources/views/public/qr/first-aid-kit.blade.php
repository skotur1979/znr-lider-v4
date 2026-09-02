<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Prva pomoć - {{ $firstAidKit->location }}
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            padding:
                24px
                15px
                50px;

            background:
                #f3f4f6;

            color:
                #111827;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .page {
            width: 100%;

            max-width: 760px;

            margin: 0 auto;
        }

        .header {
            margin-bottom: 14px;

            padding: 20px;

            border-radius: 14px;

            background:
                #111827;

            color:
                #ffffff;
        }

        .header-small {
            margin-bottom: 7px;

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .5px;
        }

        .header h1 {
            margin: 0;

            font-size: 24px;

            line-height: 1.2;
        }

        .header-location {
            margin-top: 8px;

            color:
                #d1d5db;

            font-size: 14px;
        }

        /*
        |--------------------------------------------------------------------------
        | UREĐIVANJE
        |--------------------------------------------------------------------------
        */

        .edit-box {
            margin-bottom: 14px;

            padding: 14px;

            background:
                #ecfdf5;

            border:
                1px
                solid
                #86efac;

            border-radius: 12px;
        }

        .edit-box-title {
            margin-bottom: 9px;

            color:
                #166534;

            font-size: 13px;
            font-weight: 700;
        }

        .edit-button {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-height: 40px;

            padding:
                10px
                16px;

            border-radius: 8px;

            background:
                #16a34a;

            color:
                #ffffff;

            font-size: 14px;
            font-weight: 800;

            text-decoration: none;
        }

        .edit-button:hover {
            background:
                #15803d;
        }

        .card {
            margin-bottom: 14px;

            padding: 18px;

            background:
                #ffffff;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 14px;

            box-shadow:
                0
                1px
                3px
                rgba(
                    0,
                    0,
                    0,
                    .05
                );
        }

        .card h2 {
            margin:
                0
                0
                14px;

            font-size: 17px;
        }

        .info-row {
            display: grid;

            grid-template-columns:
                42%
                58%;

            gap: 10px;

            padding:
                10px
                0;

            border-bottom:
                1px
                solid
                #f3f4f6;
        }

        .info-row:last-child {
            border-bottom: 0;
        }

        .info-label {
            color:
                #6b7280;

            font-size: 13px;
        }

        .info-value {
            font-size: 13px;

            font-weight: 700;

            word-break:
                break-word;
        }

        .items {
            display: grid;

            gap: 10px;
        }

        .item {
            padding: 14px;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 10px;

            background:
                #f9fafb;
        }

        .item-title {
            margin-bottom: 6px;

            font-size: 15px;
            font-weight: 800;
        }

        .item-purpose {
            margin-bottom: 9px;

            color:
                #4b5563;

            font-size: 13px;

            line-height: 1.4;
        }

        .expiry {
            display: inline-flex;

            padding:
                5px
                9px;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 800;
        }

        .expiry-valid {
            background:
                #dcfce7;

            color:
                #166534;
        }

        .expiry-soon {
            background:
                #fef08a;

            color:
                #854d0e;
        }

        .expiry-expired {
            background:
                #fee2e2;

            color:
                #991b1b;
        }

        .expiry-none {
            background:
                #e5e7eb;

            color:
                #4b5563;
        }

        .empty {
            color:
                #6b7280;

            font-size: 13px;
        }

        .legend {
            display: flex;

            flex-wrap: wrap;

            gap: 7px;

            margin-bottom: 14px;

            font-size: 11px;
        }

        .legend span {
            padding:
                4px
                7px;

            border-radius: 999px;

            font-weight: 700;
        }

        .footer {
            margin-top: 16px;

            color:
                #6b7280;

            font-size: 11px;

            text-align: center;
        }

        @media (
            max-width: 500px
        ) {

            body {
                padding:
                    15px
                    10px
                    35px;
            }

            .info-row {
                grid-template-columns:
                    1fr;

                gap: 4px;
            }

            .edit-button {
                width: 100%;
            }
        }

    </style>

</head>

<body>

@php

    use Illuminate\Support\Carbon;

    $today =
        Carbon::today();

@endphp


<div class="page">

    <div class="header">

        <div class="header-small">
            ZNR LIDER · PRVA POMOĆ
        </div>

        <h1>
            Ormarić prve pomoći
        </h1>

        <div class="header-location">
            {{ $firstAidKit->location }}
        </div>

    </div>


    {{-- =========================================================
         UREĐIVANJE - VIDI SAMO PRIJAVLJENI KORISNIK
         ISTE ORGANIZACIJE ILI SUPERADMIN
       ========================================================= --}}

    @if(
        $canEdit
        && filled(
            $editUrl
        )
    )

        <div class="edit-box">

            <div class="edit-box-title">
                Prijavljeni ste i imate pravo uređivati ovaj ormarić.
            </div>

            <a
                href="{{ $editUrl }}"
                class="edit-button"
            >
                Uredi sadržaj ormarića
            </a>

        </div>

    @endif


    <div class="card">

        <h2>
            Podaci o ormariću
        </h2>

        <div class="info-row">

            <div class="info-label">
                Lokacija
            </div>

            <div class="info-value">
                {{ $firstAidKit->location }}
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                Pregled obavljen
            </div>

            <div class="info-value">

                {{
                    $firstAidKit
                        ->inspected_at
                        ?->format(
                            'd.m.Y.'
                        )
                    ?? '—'
                }}

            </div>

        </div>


        @if(
            filled(
                $firstAidKit->note
            )
        )

            <div class="info-row">

                <div class="info-label">
                    Napomena
                </div>

                <div class="info-value">
                    {{ $firstAidKit->note }}
                </div>

            </div>

        @endif

    </div>


    <div class="card">

        <h2>
            Sanitetski materijal
        </h2>

        <div class="legend">

            <span class="expiry expiry-expired">
                Isteklo
            </span>

            <span class="expiry expiry-soon">
                Istječe unutar 30 dana
            </span>

            <span class="expiry expiry-valid">
                Važeće
            </span>

        </div>


        <div class="items">

            @forelse(
                $firstAidKit->items
                as $item
            )

                @php

                    $validUntil =
                        $item->valid_until
                            ? Carbon::parse(
                                $item->valid_until
                            )->startOfDay()
                            : null;

                    if (! $validUntil) {

                        $expiryClass =
                            'expiry-none';

                        $expiryText =
                            'Rok nije definiran';

                    } elseif (
                        $validUntil->lt(
                            $today
                        )
                    ) {

                        $expiryClass =
                            'expiry-expired';

                        $expiryText =
                            'Isteklo '
                            . $validUntil
                                ->format(
                                    'd.m.Y.'
                                );

                    } elseif (
                        $validUntil->lte(
                            $today
                                ->copy()
                                ->addDays(30)
                        )
                    ) {

                        $expiryClass =
                            'expiry-soon';

                        $expiryText =
                            'Vrijedi do '
                            . $validUntil
                                ->format(
                                    'd.m.Y.'
                                );

                    } else {

                        $expiryClass =
                            'expiry-valid';

                        $expiryText =
                            'Vrijedi do '
                            . $validUntil
                                ->format(
                                    'd.m.Y.'
                                );
                    }

                @endphp


                <div class="item">

                    <div class="item-title">
                        {{ $item->material_type }}
                    </div>

                    @if(
                        filled(
                            $item->purpose
                        )
                    )

                        <div class="item-purpose">
                            {{ $item->purpose }}
                        </div>

                    @endif

                    <span class="expiry {{ $expiryClass }}">
                        {{ $expiryText }}
                    </span>

                </div>

            @empty

                <div class="empty">
                    Za ovaj ormarić trenutačno nema evidentiranog sanitetskog materijala.
                </div>

            @endforelse

        </div>

    </div>


    <div class="footer">

        @if($canEdit)

            Sadržaj ormarića možete uređivati nakon prijave u ZNR LIDER.

        @else

            Podaci su dostupni samo za pregled putem QR koda.
            <br>
            Za izmjene sadržaja obratite se odgovornoj osobi.

        @endif

        <br>
        ZNR LIDER

    </div>

</div>

</body>

</html>