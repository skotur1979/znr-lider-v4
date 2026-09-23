@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    /*
    |--------------------------------------------------------------------------
    | ISTI REDOSLIJED KAO IZDANO / ISTEK
    |--------------------------------------------------------------------------
    |
    | Ovo mora ostati jednako u sve tri Blade datoteke
    | kako bi naziv, datum izdavanja i datum isteka
    | uvijek bili u istom retku.
    |
    */

    $items = collect(
        $getRecord()->items ?? []
    )->sort(function ($a, $b) {
        $aHasEnd =
            ! blank($a->end_date);

        $bHasEnd =
            ! blank($b->end_date);

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

            return $bIssue <=> $aIssue;
        }

        $aEnd =
            Carbon::parse(
                $a->end_date
            )->timestamp;

        $bEnd =
            Carbon::parse(
                $b->end_date
            )->timestamp;

        if ($aEnd !== $bEnd) {
            /*
             * Najduži / najkasniji rok gore.
             */
            return $bEnd <=> $aEnd;
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

        if ($aIssue !== $bIssue) {
            /*
             * Novije izdano gore.
             */
            return $bIssue <=> $aIssue;
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
            <=> $aDuration;
    })->values();

    /*
    |--------------------------------------------------------------------------
    | IKONA PREMA NAZIVU OZO
    |--------------------------------------------------------------------------
    |
    | Ne spremamo ikonu u bazu.
    |
    | Naziv se samo analizira prilikom prikaza.
    | Ako naziv ne odgovara niti jednoj kategoriji,
    | prikazuje se univerzalni simbol zaštite.
    |
    */

    $getOzoIcon = function (
        ?string $name
    ): array {
        /*
        * Naziv pretvaramo u mala slova
        * i uklanjamo hrvatske znakove.
        *
        * npr.
        * "Zaštitne naočale"
        * ->
        * "zastitne naocale"
        */
        $name =
            Str::lower(
                Str::ascii(
                    trim(
                        (string) $name
                    )
                )
            );

        /*
        |--------------------------------------------------------------------------
        | ZAŠTITA SLUHA
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'cepici za usi',
                    'cepici',
                    'stitnici za usi',
                    'antifon',
                    'antifoni',
                    'zastita sluha',
                ]
            )
        ) {
            return [
                'icon' => '🎧',
                'label' => 'Zaštita sluha',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ZAŠTITA GLAVE
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'zastitna kaciga',
                    'kaciga',
                    'slem',
                    'sljem',
                ]
            )
        ) {
            return [
                'icon' => '⛑️',
                'label' => 'Zaštita glave',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ZAŠTITA OČIJU
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'zastitne naocale',
                    'naocale prozirne',
                    'naocale tamne',
                    'naocale',
                    'zastita ociju',
                ]
            )
        ) {
            return [
                'icon' => '🥽',
                'label' => 'Zaštita očiju',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | VIZIR / ZAŠTITA LICA
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'zastitni vizir',
                    'vizir',
                    'stitnik lica',
                    'zastita lica',
                ]
            )
        ) {
            return [
                'icon' => '🛡️',
                'label' => 'Zaštita lica',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | MASKA ZA ZAVARIVANJE
        |--------------------------------------------------------------------------
        |
        | Mora biti prije općeg pravila za masku.
        |
        */

        if (
            Str::contains(
                $name,
                [
                    'maska za zavarivanje',
                    'zavarivacka maska',
                ]
            )
        ) {
            return [
                'icon' => '🥽',
                'label' => 'Zaštita pri zavarivanju',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ZAŠTITA DIŠNIH ORGANA
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'polumaska',
                    'respirator',
                    'ffp1',
                    'ffp2',
                    'ffp3',
                    'maska respirator',
                    'maska s filterom',
                    'maska sa filterom',
                    'zastitna maska',
                ]
            )
        ) {
            return [
                'icon' => '😷',
                'label' => 'Zaštita dišnih organa',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ZAŠTITNE CIPELE / ČIZME
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'zastitne cipele',
                    'radne cipele',
                    'cipele s kapicom',
                    'cipele niske s kapicom',
                    'cipele',
                    'gumene cizme',
                    'zastitne cizme',
                    'cizme s kapicom',
                    'cizme',
                    'obuca',
                ]
            )
        ) {
            return [
                'icon' => '🥾',
                'label' => 'Zaštitna obuća',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RUKAVICE
        |--------------------------------------------------------------------------
        |
        | Obuhvaća:
        | - montažerske
        | - proturezne
        | - neopren
        | - kemijske
        | - za zavarivače
        | - visoke temperature
        |
        */

        if (
            Str::contains(
                $name,
                [
                    'zastitne rukavice',
                    'rukavice',
                    'rukavica',
                    'maxiflex',
                    'maxicut',
                    'neopren',
                ]
            )
        ) {
            return [
                'icon' => '🧤',
                'label' => 'Zaštita ruku',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PROTUREZNA MANDŽETA
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'mandzeta',
                    'proturezna mandzeta',
                    'mandzeta proturezna',
                    'zastita podlaktice',
                ]
            )
        ) {
            return [
                'icon' => '💪',
                'label' => 'Zaštita ruke i podlaktice',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ŠTITNIK ZA KOLJENA
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'stitnik za koljena',
                    'stitnici za koljena',
                    'zastita koljena',
                ]
            )
        ) {
            return [
                'icon' => '🦵',
                'label' => 'Zaštita koljena',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | REFLEKTIRAJUĆI PRSLUK
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'reflektirajuci prsluk',
                    'zastitni reflektirajuci prsluk',
                    'reflektirajuci',
                    'prsluk',
                    'high visibility',
                ]
            )
        ) {
            return [
                'icon' => '🦺',
                'label' => 'Uočljiva zaštitna odjeća',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RAD NA VISINI
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'zastitni pojas',
                    'sigurnosni pojas',
                    'pojas sa uzetom',
                    'pojas s uzetom',
                    'rad na visini',
                    'zaustavljanje pada',
                    'uprta',
                    'uprtac',
                ]
            )
        ) {
            return [
                'icon' => '🪢',
                'label' => 'Zaštita od pada s visine',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ZAŠTITNA PREGAČA
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'zastitna pregaca',
                    'pregaca',
                ]
            )
        ) {
            return [
                'icon' => '🥼',
                'label' => 'Zaštitna odjeća',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | KIŠNA KABANICA / GUMENO ODIJELO
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'kisna kabanica',
                    'kabanica',
                    'gumeno odijelo',
                    'kisno odijelo',
                ]
            )
        ) {
            return [
                'icon' => '🌧️',
                'label' => 'Zaštita od vremenskih uvjeta',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RADNA / ZIMSKA JAKNA
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'zimska jakna',
                    'radna jakna',
                    'jakna',
                ]
            )
        ) {
            return [
                'icon' => '🧥',
                'label' => 'Zaštitna odjeća',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RADNE HLAČE
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'radne hlace',
                    'zastitne hlace',
                    'hlace',
                ]
            )
        ) {
            return [
                'icon' => '👖',
                'label' => 'Zaštitna odjeća',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RADNO ODIJELO
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'radno odijelo',
                    'zastitno odijelo',
                    'bluza hlace',
                    'bluza/hlace',
                ]
            )
        ) {
            return [
                'icon' => '🧥',
                'label' => 'Zaštitno radno odijelo',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | MAJICE
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains(
                $name,
                [
                    'majica kratkih rukava',
                    'majica dugih rukava',
                    'majica s kratkim rukavima',
                    'majica sa kratkim rukavima',
                    'majica s dugim rukavima',
                    'majica sa dugim rukavima',
                    'majica',
                ]
            )
        ) {
            return [
                'icon' => '👕',
                'label' => 'Radna odjeća',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | OSTALO OZO
        |--------------------------------------------------------------------------
        |
        | Ako kasnije dodaš neku novu vrstu OZO
        | koju nismo predvidjeli, prikazuje se
        | univerzalni simbol zaštite.
        |
        */

        return [
            'icon' => '🛡️',
            'label' => 'Osobna zaštitna oprema',
        ];
    };
@endphp

<style>
    .ozo-name-row {
        min-height: 30px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ozo-name-icon {
        width: 22px;
        min-width: 22px;
        height: 22px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        font-size: 17px;
        line-height: 1;
    }

    .ozo-name-text {
        min-width: 0;
        line-height: 1.25;
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
            $ozo =
                $getOzoIcon(
                    $item->equipment_name
                );
        @endphp

        <div class="ozo-name-row">
            <span
                class="ozo-name-icon"
                title="{{ $ozo['label'] }}"
                aria-hidden="true"
            >
                {{ $ozo['icon'] }}
            </span>

            <span
                class="ozo-name-text"
                title="{{ $item->equipment_name }}"
            >
                {{ $item->equipment_name }}
            </span>
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