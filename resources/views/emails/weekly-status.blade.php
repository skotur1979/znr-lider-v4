<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['title'] ?? 'Tjedni status' }}</title>
</head>
<body style="margin:0; padding:0; background:#eef2f7; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">

@php
    $summary = $data['summary'];
    $range = $summary['range'] ?? ['from' => now(), 'to' => now()];
    $created = $summary['created_last_week'] ?? [];
    $closed = $summary['closed_last_week'] ?? [];
    $current = $summary['current_state'] ?? ['deadlines' => [], 'actions' => [], 'totals' => []];

    $totalCreated = array_sum($created);
    $totalClosed = array_sum($closed);

    $criticalExpired =
        ($current['deadlines']['employees_expired'] ?? 0) +
        ($current['deadlines']['employee_certificates_expired'] ?? 0) +
        ($current['deadlines']['machines_expired'] ?? 0) +
        ($current['deadlines']['fires_expired'] ?? 0) +
        ($current['deadlines']['miscellaneous_expired'] ?? 0) +
        ($current['deadlines']['work_permits_expired'] ?? 0) +
        ($current['deadlines']['first_aid_expired'] ?? 0);

    $warningSoon =
        ($current['deadlines']['employees_expiring_30'] ?? 0) +
        ($current['deadlines']['employee_certificates_expiring_30'] ?? 0) +
        ($current['deadlines']['machines_expiring_30'] ?? 0) +
        ($current['deadlines']['fires_expiring_30'] ?? 0) +
        ($current['deadlines']['miscellaneous_expiring_30'] ?? 0) +
        ($current['deadlines']['work_permits_expiring_30'] ?? 0) +
        ($current['deadlines']['first_aid_expiring_30'] ?? 0);

    $actionRequired =
        ($current['actions']['observations_open_total'] ?? 0) +
        ($current['actions']['incidents_open'] ?? 0) +
        ($current['actions']['inspection_findings_open'] ?? 0) +
        ($current['actions']['waste_forms_open'] ?? 0) +
        ($current['actions']['work_tasks_open'] ?? 0) +
        ($current['actions']['work_permits_open'] ?? 0);

    $resolvedObservations =
        max(0, ($current['totals']['observations'] ?? 0) - ($current['actions']['observations_open_total'] ?? 0));

    $appUrl = config('app.url') ?: '#';

    $createdRows = [
        ['Zaposlenici', $created['employees'] ?? 0],
        ['Radna oprema', $created['machines'] ?? 0],
        ['Vatrogasni aparati', $created['fires'] ?? 0],
        ['Ostala ispitivanja', $created['miscellaneous'] ?? 0],
        ['Zapažanja', $created['observations'] ?? 0],
        ['Radni zadaci', $created['work_tasks'] ?? 0],
        ['Dozvole za rad', $created['work_permits'] ?? 0],
        ['Incidenti', $created['incidents'] ?? 0],
        ['Nalazi nadzora', $created['inspection_findings'] ?? 0],
        ['Prateći listovi', $created['waste_forms'] ?? 0],
    ];

    $closedRows = [
        ['Zapažanja - završena', $closed['observations_complete'] ?? 0],
        ['Radni zadaci - završeni', $closed['work_tasks_closed'] ?? 0],
        ['Dozvole za rad - završene', $closed['work_permits_finished'] ?? 0],
        ['Nalazi nadzora - zatvoreni', $closed['inspection_findings_closed'] ?? 0],
    ];

    $currentRows = [
        ['Zaposlenici', $current['totals']['employees'] ?? 0],
        ['Radna oprema', $current['totals']['machines'] ?? 0],
        ['Vatrogasni aparati', $current['totals']['fires'] ?? 0],
        ['Ostala ispitivanja', $current['totals']['miscellaneous'] ?? 0],
        ['Zapažanja', $current['totals']['observations'] ?? 0],
        ['Radni zadaci', $current['totals']['work_tasks'] ?? 0],
        ['Dozvole za rad', $current['totals']['work_permits'] ?? 0],
        ['Incidenti', $current['totals']['incidents'] ?? 0],
        ['Nalazi nadzora', $current['totals']['inspection_findings'] ?? 0],
        ['Prateći listovi', $current['totals']['waste_forms'] ?? 0],
        ['Prva pomoć - stavke', $current['totals']['first_aid_items'] ?? 0],
    ];
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef2f7; margin:0; padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="760" cellpadding="0" cellspacing="0" border="0" style="width:760px; max-width:760px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 8px 28px rgba(15,23,42,0.10);">

                {{-- HEADER --}}
                <tr>
                    <td style="background:linear-gradient(135deg, #020617 0%, #0f172a 55%, #111827 100%); padding:26px 32px 22px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td align="left">
                                    <div style="font-size:12px; line-height:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#f59e0b; margin-bottom:10px;">
                                        ZNR LIDER
                                    </div>
                                    <div style="font-size:28px; line-height:34px; font-weight:700; color:#ffffff; margin:0 0 6px 0;">
                                        {{ $data['title'] ?? 'Tjedni status' }}
                                    </div>
                                    <div style="font-size:14px; line-height:20px; color:#cbd5e1;">
                                        Razdoblje: {{ $range['from']->format('d.m.Y.') }} - {{ $range['to']->format('d.m.Y.') }}
                                    </div>
                                </td>
                                <td align="right" valign="top" style="width:160px;">
                                    <div style="display:inline-block; background:#f59e0b; color:#111827; font-size:12px; font-weight:700; border-radius:999px; padding:8px 12px;">
                                        Tjedni izvještaj
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- BODY --}}
                <tr>
                    <td style="padding:28px 32px 10px 32px;">
                        <div style="font-size:15px; line-height:24px; color:#334155;">
                            Poštovani <strong style="color:#0f172a;">{{ $data['user']->name ?? 'korisniče' }}</strong>,
                            <br>
                            u nastavku je tjedni pregled aktivnosti, promjena i trenutnog stanja u sustavu <strong>ZNR LIDER</strong>.
                        </div>

                        @if(!empty($data['insight_text']))
                            <div style="margin-top:18px; background:#eff6ff; border:1px solid #bfdbfe; border-left:5px solid #2563eb; border-radius:12px; padding:16px 18px;">
                                <div style="font-size:12px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:#1d4ed8; margin-bottom:8px;">
                                    Pametni sažetak tjedna
                                </div>
                                <div style="font-size:14px; line-height:22px; color:#1e3a8a;">
                                    {{ $data['insight_text'] }}
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>

                {{-- ALERT BLOCK --}}
                <tr>
                    <td style="padding:0 32px 8px 32px;">
                        @if($criticalExpired > 0)
                            <div style="background:#fff1f2; border:1px solid #fecdd3; border-left:5px solid #e11d48; border-radius:12px; padding:16px 18px;">
                                <div style="font-size:15px; font-weight:700; color:#9f1239; margin-bottom:6px;">
                                    Kritično stanje
                                </div>
                                <div style="font-size:14px; line-height:22px; color:#881337;">
                                    Trenutno postoji <strong>{{ $criticalExpired }}</strong> isteklih stavki koje zahtijevaju hitnu reakciju.
                                </div>
                            </div>
                        @elseif($warningSoon > 0 || $actionRequired > 0)
                            <div style="background:#fffbeb; border:1px solid #fde68a; border-left:5px solid #f59e0b; border-radius:12px; padding:16px 18px;">
                                <div style="font-size:15px; font-weight:700; color:#92400e; margin-bottom:6px;">
                                    Potrebna pažnja
                                </div>
                                <div style="font-size:14px; line-height:22px; color:#78350f;">
                                    Trenutno imate <strong>{{ $warningSoon }}</strong> stavki koje uskoro istječu i
                                    <strong>{{ $actionRequired }}</strong> otvorenih aktivnosti u sustavu.
                                </div>
                            </div>
                        @else
                            <div style="background:#ecfdf5; border:1px solid #bbf7d0; border-left:5px solid #16a34a; border-radius:12px; padding:16px 18px;">
                                <div style="font-size:15px; font-weight:700; color:#166534; margin-bottom:6px;">
                                    Stanje uredno
                                </div>
                                <div style="font-size:14px; line-height:22px; color:#166534;">
                                    Sustav je trenutno stabilan, bez kritičnih isteklih stavki i bez većih zaostataka.
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>

                {{-- KPI CARDS --}}
                <tr>
                    <td style="padding:14px 32px 6px 32px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:14px;">Tjedni sažetak</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="25%" style="padding:0 6px 12px 0;">
                                    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#1e3a8a;">{{ $totalCreated }}</div>
                                        <div style="font-size:12px; color:#1d4ed8; margin-top:4px;">Novo kreirano</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 6px 12px 6px;">
                                    <div style="background:#ecfdf5; border:1px solid #bbf7d0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#166534;">{{ $totalClosed }}</div>
                                        <div style="font-size:12px; color:#15803d; margin-top:4px;">Zatvoreno</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 6px 12px 6px;">
                                    <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#9a3412;">{{ $warningSoon }}</div>
                                        <div style="font-size:12px; color:#c2410c; margin-top:4px;">Uskoro istječe</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 0 12px 6px;">
                                    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#b91c1c;">{{ $criticalExpired }}</div>
                                        <div style="font-size:12px; color:#dc2626; margin-top:4px;">Isteklo</div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- PRIORITETI --}}
                <tr>
                    <td style="padding:22px 32px 6px 32px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:14px;">Ključni prioriteti</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="33.33%" style="padding:0 8px 0 0;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:16px;">
                                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#475569; margin-bottom:8px;">
                                            Otvorene aktivnosti
                                        </div>
                                        <div style="font-size:28px; font-weight:800; color:#0f172a; line-height:30px;">
                                            {{ $actionRequired }}
                                        </div>
                                        <div style="font-size:12px; color:#64748b; margin-top:6px;">
                                            Trenutno aktivno
                                        </div>
                                    </div>
                                </td>
                                <td width="33.33%" style="padding:0 4px;">
                                    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:14px; padding:16px;">
                                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#1d4ed8; margin-bottom:8px;">
                                            Tjedni unos
                                        </div>
                                        <div style="font-size:28px; font-weight:800; color:#1e3a8a; line-height:30px;">
                                            {{ $totalCreated }}
                                        </div>
                                        <div style="font-size:12px; color:#1e3a8a; margin-top:6px;">
                                            Novi zapisi
                                        </div>
                                    </div>
                                </td>
                                <td width="33.33%" style="padding:0 0 0 8px;">
                                    <div style="background:#ecfdf5; border:1px solid #bbf7d0; border-radius:14px; padding:16px;">
                                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#15803d; margin-bottom:8px;">
                                            Završeno
                                        </div>
                                        <div style="font-size:28px; font-weight:800; color:#166534; line-height:30px;">
                                            {{ $resolvedObservations }}
                                        </div>
                                        <div style="font-size:12px; color:#166534; margin-top:6px;">
                                            Zatvorena zapažanja
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- NOVO U TJEDNU --}}
                <tr>
                    <td style="padding:24px 32px 6px 32px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:14px;">Novo u ovom tjednu</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate; border-spacing:0; overflow:hidden; border:1px solid #e2e8f0; border-radius:14px;">
                            <tbody>
                                @foreach($createdRows as $index => $row)
                                    <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#fcfdff' }};">
                                        <td style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                            {{ $row[0] }}
                                        </td>
                                        <td align="right" style="padding:12px 14px; font-size:15px; font-weight:700; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                            {{ $row[1] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>

                {{-- ZATVORENO U TJEDNU --}}
                <tr>
                    <td style="padding:24px 32px 6px 32px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:14px;">Zatvoreno u ovom tjednu</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate; border-spacing:0; overflow:hidden; border:1px solid #e2e8f0; border-radius:14px;">
                            <tbody>
                                @foreach($closedRows as $index => $row)
                                    <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#fcfdff' }};">
                                        <td style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                            {{ $row[0] }}
                                        </td>
                                        <td align="right" style="padding:12px 14px; font-size:15px; font-weight:700; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                            {{ $row[1] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>

                {{-- TRENUTNO STANJE --}}
                <tr>
                    <td style="padding:24px 32px 6px 32px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:14px;">Trenutno stanje sustava</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate; border-spacing:0; overflow:hidden; border:1px solid #e2e8f0; border-radius:14px;">
                            <tbody>
                                @foreach($currentRows as $index => $row)
                                    <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#fcfdff' }};">
                                        <td style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                            {{ $row[0] }}
                                        </td>
                                        <td align="right" style="padding:12px 14px; font-size:15px; font-weight:700; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                            {{ $row[1] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>

                {{-- CTA --}}
                <tr>
                    <td align="center" style="padding:28px 32px 10px 32px;">
                        <a href="{{ $appUrl }}"
                           style="display:inline-block; background:#f59e0b; color:#111827; text-decoration:none; font-size:14px; font-weight:700; padding:14px 22px; border-radius:10px;">
                            Otvori ZNR LIDER
                        </a>
                    </td>
                </tr>

                {{-- FOOTER --}}
                <tr>
                    <td style="padding:16px 32px 30px 32px;">
                        <div style="border-top:1px solid #e2e8f0; padding-top:16px; font-size:12px; line-height:20px; color:#64748b; text-align:center;">
                            Ovaj email je automatski generiran iz sustava <strong>ZNR LIDER</strong>.<br>
                            Tjedni pregled služi kao sažetak aktivnosti i trenutnog operativnog stanja.
                        </div>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>