@php
    $typeLabel = match ($observation->observation_type) {
        'Near Miss' => 'NM - Skoro nezgoda',
        'Negative Observation' => 'Negativno zapažanje',
        'Positive Observation' => 'Pozitivno zapažanje',
        default => $observation->observation_type,
    };

    $statusLabel = match ($observation->status) {
        'Not started' => 'Nije započeto',
        'In progress' => 'U tijeku',
        'Complete' => 'Završeno',
        default => $observation->status,
    };

    $priorityLabel = match ($observation->priority) {
        'low' => 'Nisko',
        'medium' => 'Srednje',
        'high' => 'Visoko',
        'critical' => 'Kritično',
        default => 'Srednje',
    };

    $priorityStyle = match ($observation->priority) {
        'critical' => 'background:#dc2626; color:#ffffff; border:2px solid #991b1b;',
        'high' => 'background:#f97316; color:#ffffff; border:2px solid #c2410c;',
        'medium' => 'background:#f59e0b; color:#111827; border:2px solid #d97706;',
        'low' => 'background:#e5e7eb; color:#111827; border:2px solid #9ca3af;',
        default => 'background:#e5e7eb; color:#111827; border:2px solid #9ca3af;',
    };

    $priorityIcon = match ($observation->priority) {
        'critical' => '🚨',
        'high' => '⚠️',
        'medium' => '🔶',
        'low' => 'ℹ️',
        default => 'ℹ️',
    };

    $title = $mode === 'updated'
        ? 'Izmjena zapažanja'
        : 'Novo zapažanje';

    /*
     * Status roka za provedbu radnje.
     */
    $today = \Illuminate\Support\Carbon::today();

    $targetDate = filled($observation->target_date)
        ? \Illuminate\Support\Carbon::parse($observation->target_date)->startOfDay()
        : null;

    $deadlineLabel = 'Rok za provedbu nije upisan';
    $deadlineDescription = 'Za ovu radnju nije određen krajnji rok.';
    $deadlineIcon = 'ℹ️';
    $deadlineBackground = '#f3f4f6';
    $deadlineBorder = '#9ca3af';
    $deadlineColor = '#374151';

    if ($observation->status === 'Complete') {
        $deadlineLabel = 'RADNJA JE ZAVRŠENA';
        $deadlineDescription = $targetDate
            ? 'Radnja je označena kao završena. Rok za provedbu bio je '
                . $targetDate->format('d.m.Y.') . '.'
            : 'Radnja je označena kao završena.';

        $deadlineIcon = '✅';
        $deadlineBackground = '#dcfce7';
        $deadlineBorder = '#22c55e';
        $deadlineColor = '#166534';
    } elseif ($targetDate) {
        if ($targetDate->lt($today)) {
            $daysExpired = (int) $targetDate->diffInDays($today);

            $deadlineLabel = $daysExpired === 1
                ? 'ROK JE ISTEKAO PRIJE 1 DAN'
                : 'ROK JE ISTEKAO PRIJE ' . $daysExpired . ' DANA';

            $deadlineDescription =
                'Rok za provedbu istekao je '
                . $targetDate->format('d.m.Y.')
                . '. Potrebno je provjeriti status i poduzeti odgovarajuće mjere.';

            $deadlineIcon = '🚨';
            $deadlineBackground = '#fee2e2';
            $deadlineBorder = '#ef4444';
            $deadlineColor = '#991b1b';
        } elseif ($targetDate->isSameDay($today)) {
            $deadlineLabel = 'ROK ISTJEČE DANAS';

            $deadlineDescription =
                'Rok za provedbu je danas, '
                . $targetDate->format('d.m.Y.')
                . '. Potrebno je provjeriti je li radnja provedena.';

            $deadlineIcon = '⚠️';
            $deadlineBackground = '#ffedd5';
            $deadlineBorder = '#f97316';
            $deadlineColor = '#9a3412';
        } else {
            $daysRemaining = (int) $today->diffInDays($targetDate);

            if ($daysRemaining <= 30) {
                $deadlineLabel = $daysRemaining === 1
                    ? 'ROK ISTJEČE ZA 1 DAN'
                    : 'ROK ISTJEČE ZA ' . $daysRemaining . ' DANA';

                $deadlineDescription =
                    'Rok za provedbu je '
                    . $targetDate->format('d.m.Y.')
                    . '. Molimo pravodobno provedite potrebnu radnju.';

                $deadlineIcon = '⏳';
                $deadlineBackground = '#fef3c7';
                $deadlineBorder = '#f59e0b';
                $deadlineColor = '#92400e';
            } else {
                $deadlineLabel =
                    'ROK JE VAŽEĆI – PREOSTALO '
                    . $daysRemaining
                    . ' DANA';

                $deadlineDescription =
                    'Rok za provedbu je '
                    . $targetDate->format('d.m.Y.')
                    . '.';

                $deadlineIcon = '✅';
                $deadlineBackground = '#dcfce7';
                $deadlineBorder = '#22c55e';
                $deadlineColor = '#166534';
            }
        }
    }
@endphp

<div style="font-family:Arial, sans-serif; background:#f4f6f8; padding:24px; color:#111827;">
    <div style="max-width:820px; margin:0 auto; background:#ffffff; border-radius:18px; overflow:hidden; border:1px solid #e5e7eb;">

        <!-- HEADER -->
        <div style="background:#111827; color:#ffffff; padding:24px 28px;">
            <div style="font-size:13px; letter-spacing:1px; color:#f59e0b; font-weight:700;">
                ZNR LIDER
            </div>

            <h1 style="margin:8px 0 0; font-size:26px;">
                {{ $title }}
            </h1>

            <p style="margin:8px 0 0; color:#cbd5e1;">
                {{ $mode === 'updated'
                    ? 'Obavijest o promjeni zapažanja'
                    : 'Operativna obavijest o zapažanju'
                }}
            </p>
        </div>

        <div style="padding:26px 28px;">

            <!-- UVOD -->
            <p style="font-size:15px; line-height:1.6; margin-top:0;">
                Poštovani,<br>

                @if ($mode === 'updated')
                    zapažanje u sustavu ZNR LIDER je izmijenjeno.
                @else
                    dostavljamo vam zapažanje koje zahtijeva pregled i, prema potrebi, poduzimanje odgovarajućih mjera.
                @endif
            </p>

            <!-- SAŽETAK -->
            <div style="background:#fef3c7; border:1px solid #f59e0b; border-radius:14px; padding:18px; margin:22px 0;">
                <div style="font-size:13px; font-weight:800; color:#92400e; text-transform:uppercase;">
                    Sažetak
                </div>

                <p style="margin:8px 0 0; font-size:15px; line-height:1.5;">
                    Prioritet
                    <strong>{{ $priorityLabel }}</strong>
                    na lokaciji
                    <strong>{{ $observation->location }}</strong>.
                </p>
            </div>

            <!-- PRIORITET BLOK -->
            <div style="{{ $priorityStyle }} border-radius:16px; padding:20px; margin:22px 0; text-align:center;">
                <div style="font-size:13px; font-weight:900; text-transform:uppercase;">
                    Prioritet zapažanja
                </div>

                <div style="font-size:30px; font-weight:900; margin-top:6px;">
                    {{ $priorityIcon }} {{ mb_strtoupper($priorityLabel) }}
                </div>

                @if ($observation->priority === 'critical')
                    <div style="margin-top:10px; font-weight:800;">
                        🚨 HITNO DJELOVANJE POTREBNO
                    </div>
                @endif
            </div>

            <!-- STATUS ROKA -->
            <div
                style="
                    margin:22px 0;
                    padding:18px;
                    border:1px solid {{ $deadlineBorder }};
                    border-left:7px solid {{ $deadlineBorder }};
                    border-radius:14px;
                    background:{{ $deadlineBackground }};
                    color:{{ $deadlineColor }};
                "
            >
                <div
                    style="
                        text-align:center;
                        font-size:19px;
                        font-weight:900;
                        line-height:1.4;
                    "
                >
                    {{ $deadlineIcon }} {{ $deadlineLabel }}
                </div>

                <div
                    style="
                        margin-top:8px;
                        text-align:center;
                        font-size:14px;
                        font-weight:600;
                        line-height:1.5;
                    "
                >
                    {{ $deadlineDescription }}
                </div>
            </div>

            <!-- SLIKA -->
            @if (! empty($imagePath) && file_exists($imagePath))
                <h2 style="font-size:19px; margin:26px 0 12px;">
                    Slika zapažanja
                </h2>

                <div style="margin-bottom:22px; border:1px solid #e5e7eb; border-radius:14px; padding:12px;">
                    <img
                        src="{{ $message->embed($imagePath) }}"
                        alt="Slika zapažanja"
                        style="display:block; max-width:100%; height:auto; border-radius:10px;"
                    >
                </div>
            @endif

            <!-- TABLICA -->
            <h2 style="font-size:19px; margin:26px 0 12px;">
                Podaci zapažanja
            </h2>

            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; width:30%; font-weight:700;">
                        Datum
                    </td>

                    <td style="padding:10px;">
                        {{ filled($observation->incident_date)
                            ? \Illuminate\Support\Carbon::parse($observation->incident_date)->format('d.m.Y.')
                            : 'Nije upisan'
                        }}
                    </td>
                </tr>

                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; font-weight:700;">
                        Vrsta
                    </td>

                    <td style="padding:10px;">
                        {{ $typeLabel }}
                    </td>
                </tr>

                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; font-weight:700;">
                        Prioritet
                    </td>

                    <td style="padding:10px; text-align:center;">
                        <span
                            style="
                                {{ $priorityStyle }}
                                display:inline-block;
                                padding:10px 18px;
                                border-radius:999px;
                                font-weight:900;
                            "
                        >
                            {{ $priorityIcon }} {{ $priorityLabel }}
                        </span>
                    </td>
                </tr>

                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; font-weight:700;">
                        Lokacija
                    </td>

                    <td style="padding:10px;">
                        {{ $observation->location ?: 'Nije upisana' }}
                    </td>
                </tr>

                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; font-weight:700;">
                        Opis
                    </td>

                    <td style="padding:10px; line-height:1.5;">
                        {!! nl2br(e($observation->item ?: 'Nije upisan')) !!}
                    </td>
                </tr>

                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; font-weight:700;">
                        Opasnost
                    </td>

                    <td style="padding:10px;">
                        {{ $observation->potential_incident_type ?: 'Nije upisana' }}
                    </td>
                </tr>

                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; font-weight:700;">
                        Potrebna radnja
                    </td>

                    <td style="padding:10px; line-height:1.5;">
                        {!! nl2br(e($observation->action ?: 'Nije upisana')) !!}
                    </td>
                </tr>

                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; font-weight:700;">
                        Odgovorna osoba
                    </td>

                    <td style="padding:10px;">
                        {{ $observation->responsible ?: 'Nije određena' }}
                    </td>
                </tr>

                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; font-weight:700;">
                        Rok za provedbu
                    </td>

                    <td style="padding:10px; font-weight:700; color:{{ $deadlineColor }};">
                        {{ $targetDate
                            ? $targetDate->format('d.m.Y.')
                            : 'Nije upisan'
                        }}
                    </td>
                </tr>

                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; font-weight:700;">
                        Status roka
                    </td>

                    <td style="padding:10px;">
                        <span
                            style="
                                display:inline-block;
                                padding:7px 12px;
                                border:1px solid {{ $deadlineBorder }};
                                border-radius:999px;
                                background:{{ $deadlineBackground }};
                                color:{{ $deadlineColor }};
                                font-weight:800;
                            "
                        >
                            {{ $deadlineIcon }} {{ $deadlineLabel }}
                        </span>
                    </td>
                </tr>

                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; font-weight:700;">
                        Status radnje
                    </td>

                    <td style="padding:10px;">
                        {{ $statusLabel }}
                    </td>
                </tr>

                <tr>
                    <td style="padding:10px; font-weight:700;">
                        Komentar
                    </td>

                    <td style="padding:10px; line-height:1.5;">
                        {!! nl2br(e($observation->comments ?: 'Nema komentara')) !!}
                    </td>
                </tr>
            </table>

            <!-- FOOTER -->
            <div style="margin-top:28px; padding:16px; background:#f9fafb; border-radius:14px; color:#4b5563; font-size:13px; line-height:1.5;">
                Ova je obavijest generirana iz sustava ZNR LIDER.
                Molimo provjerite navedeno zapažanje, potrebnu radnju i rok za provedbu.
            </div>

        </div>
    </div>
</div>