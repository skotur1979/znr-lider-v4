<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['title'] ?? 'Dnevni status' }}</title>
</head>
<body style="margin:0; padding:0; background:#eef2f7; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">

@php
    $summary = $data['summary'];

    $criticalExpired =
        ($summary['deadlines']['employees_expired'] ?? 0) +
        ($summary['deadlines']['employee_certificates_expired'] ?? 0) +
        ($summary['deadlines']['machines_expired'] ?? 0) +
        ($summary['deadlines']['fires_expired'] ?? 0) +
        ($summary['deadlines']['miscellaneous_expired'] ?? 0) +
        ($summary['deadlines']['work_permits_expired'] ?? 0) +
        ($summary['deadlines']['first_aid_expired'] ?? 0);

    $warningSoon =
        ($summary['deadlines']['employees_expiring_30'] ?? 0) +
        ($summary['deadlines']['employee_certificates_expiring_30'] ?? 0) +
        ($summary['deadlines']['machines_expiring_30'] ?? 0) +
        ($summary['deadlines']['fires_expiring_30'] ?? 0) +
        ($summary['deadlines']['miscellaneous_expiring_30'] ?? 0) +
        ($summary['deadlines']['work_permits_expiring_30'] ?? 0) +
        ($summary['deadlines']['first_aid_expiring_30'] ?? 0);

    $actionRequired =
        ($summary['actions']['observations_open_total'] ?? 0) +
        ($summary['actions']['incidents_open'] ?? 0) +
        ($summary['actions']['inspection_findings_open'] ?? 0) +
        ($summary['actions']['waste_forms_open'] ?? 0) +
        ($summary['actions']['work_tasks_open'] ?? 0) +
        ($summary['actions']['work_permits_open'] ?? 0);

    $resolvedObservations =
        max(0, ($summary['totals']['observations'] ?? 0) - ($summary['actions']['observations_open_total'] ?? 0));

    $appUrl = config('app.url') ?: '#';
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef2f7; margin:0; padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="760" cellpadding="0" cellspacing="0" border="0" style="width:760px; max-width:760px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 8px 28px rgba(15,23,42,0.10);">

                <tr>
                    <td style="background:linear-gradient(135deg, #020617 0%, #0f172a 55%, #111827 100%); padding:26px 32px 22px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td align="left">
                                    <div style="font-size:12px; line-height:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#f59e0b; margin-bottom:10px;">
                                        ZNR LIDER
                                    </div>
                                    <div style="font-size:28px; line-height:34px; font-weight:700; color:#ffffff; margin:0 0 6px 0;">
                                        {{ $data['title'] ?? 'Dnevni status' }}
                                    </div>
                                    <div style="font-size:14px; line-height:20px; color:#cbd5e1;">
                                        Datum izvještaja: {{ $data['period_label'] ?? now()->format('d.m.Y.') }}
                                    </div>
                                </td>
                                <td align="right" valign="top" style="width:160px;">
                                    <div style="display:inline-block; background:#f59e0b; color:#111827; font-size:12px; font-weight:700; border-radius:999px; padding:8px 12px;">
                                        Automatski izvještaj
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px 32px 10px 32px;">
                        <div style="font-size:15px; line-height:24px; color:#334155;">
                            Poštovani <strong style="color:#0f172a;">{{ $data['user']->name ?? 'korisniče' }}</strong>,
                            <br>
                            u nastavku je profesionalni pregled trenutnog stanja u sustavu <strong>ZNR LIDER</strong>.
                        </div>

                        @if(!empty($data['insight_text']))
                            <div style="margin-top:18px; background:#eff6ff; border:1px solid #bfdbfe; border-left:5px solid #2563eb; border-radius:12px; padding:16px 18px;">
                                <div style="font-size:12px; font-weight:700; letter-spacing:.7px; text-transform:uppercase; color:#1d4ed8; margin-bottom:8px;">
                                    Pametni sažetak
                                </div>
                                <div style="font-size:14px; line-height:22px; color:#1e3a8a;">
                                    {{ $data['insight_text'] }}
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 32px 8px 32px;">
                        @if($criticalExpired > 0)
                            <div style="background:#fff1f2; border:1px solid #fecdd3; border-left:5px solid #e11d48; border-radius:12px; padding:16px 18px;">
                                <div style="font-size:15px; font-weight:700; color:#9f1239; margin-bottom:6px;">
                                    Kritično stanje
                                </div>
                                <div style="font-size:14px; line-height:22px; color:#881337;">
                                    Imate <strong>{{ $criticalExpired }}</strong> isteklih stavki koje zahtijevaju hitnu reakciju.
                                </div>
                            </div>
                        @elseif($warningSoon > 0 || $actionRequired > 0)
                            <div style="background:#fffbeb; border:1px solid #fde68a; border-left:5px solid #f59e0b; border-radius:12px; padding:16px 18px;">
                                <div style="font-size:15px; font-weight:700; color:#92400e; margin-bottom:6px;">
                                    Potrebna pažnja
                                </div>
                                <div style="font-size:14px; line-height:22px; color:#78350f;">
                                    Trenutno imate <strong>{{ $warningSoon }}</strong> stavki koje uskoro istječu i
                                    <strong>{{ $actionRequired }}</strong> otvorenih stavki za obradu.
                                </div>
                            </div>
                        @else
                            <div style="background:#ecfdf5; border:1px solid #bbf7d0; border-left:5px solid #16a34a; border-radius:12px; padding:16px 18px;">
                                <div style="font-size:15px; font-weight:700; color:#166534; margin-bottom:6px;">
                                    Stanje uredno
                                </div>
                                <div style="font-size:14px; line-height:22px; color:#166534;">
                                    Trenutno nema kritičnih isteklih stavki niti otvorenih obveza koje zahtijevaju hitnu akciju.
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:14px 32px 6px 32px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:14px;">Sažetak stanja</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="25%" style="padding:0 6px 12px 0;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['employees'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Zaposlenici</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 6px 12px 6px;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['machines'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Radna oprema</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 6px 12px 6px;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['observations'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Zapažanja</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 0 12px 6px;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['inspection_findings'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Nalazi nadzora</div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td width="25%" style="padding:0 6px 12px 0;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['fires'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Vatrogasni aparati</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 6px 12px 6px;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['miscellaneous'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Ostala ispitivanja</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 6px 12px 6px;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['work_tasks'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Radni zadaci</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 0 12px 6px;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['work_permits'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Dozvole za rad</div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td width="25%" style="padding:0 6px 0 0;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['incidents'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Incidenti</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 6px 0 6px;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['waste_forms'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Prateći listovi</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 6px 0 6px;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['totals']['first_aid_items'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Prva pomoć</div>
                                    </div>
                                </td>
                                <td width="25%" style="padding:0 0 0 6px;">
                                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px 14px; text-align:center;">
                                        <div style="font-size:26px; font-weight:800; color:#0f172a;">{{ $summary['deadlines']['employees_expiring_30'] ?? 0 }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Liječnički uskoro</div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:22px 32px 6px 32px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:14px;">Prioriteti za danas</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="33.33%" style="padding:0 8px 0 0;">
                                    <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:14px; padding:16px;">
                                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#c2410c; margin-bottom:8px;">
                                            Uskoro istječe
                                        </div>
                                        <div style="font-size:28px; font-weight:800; color:#9a3412; line-height:30px;">
                                            {{ $warningSoon }}
                                        </div>
                                        <div style="font-size:12px; color:#9a3412; margin-top:6px;">
                                            Stavke unutar 30 dana
                                        </div>
                                    </div>
                                </td>
                                <td width="33.33%" style="padding:0 4px;">
                                    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:14px; padding:16px;">
                                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#1d4ed8; margin-bottom:8px;">
                                            Za obradu
                                        </div>
                                        <div style="font-size:28px; font-weight:800; color:#1e3a8a; line-height:30px;">
                                            {{ $actionRequired }}
                                        </div>
                                        <div style="font-size:12px; color:#1e3a8a; margin-top:6px;">
                                            Otvorene aktivnosti
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

                <tr>
                    <td style="padding:24px 32px 6px 32px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:14px;">Rokovi i valjanosti</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate; border-spacing:0; overflow:hidden; border:1px solid #e2e8f0; border-radius:14px;">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th align="left" style="padding:12px 14px; font-size:13px; color:#334155; border-bottom:1px solid #e2e8f0;">Stavka</th>
                                    <th align="center" style="padding:12px 14px; font-size:13px; color:#334155; border-bottom:1px solid #e2e8f0;">Isteklo</th>
                                    <th align="center" style="padding:12px 14px; font-size:13px; color:#334155; border-bottom:1px solid #e2e8f0;">U 30 dana</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $deadlineRows = [
                                        ['Zaposlenici - liječnički', 'employees_expired', 'employees_expiring_30'],
                                        ['Zaposlenici - certifikati', 'employee_certificates_expired', 'employee_certificates_expiring_30'],
                                        ['Radna oprema', 'machines_expired', 'machines_expiring_30'],
                                        ['Vatrogasni aparati', 'fires_expired', 'fires_expiring_30'],
                                        ['Ostala ispitivanja', 'miscellaneous_expired', 'miscellaneous_expiring_30'],
                                        ['Dozvole za rad', 'work_permits_expired', 'work_permits_expiring_30'],
                                        ['Prva pomoć', 'first_aid_expired', 'first_aid_expiring_30'],
                                    ];
                                @endphp

                                @foreach($deadlineRows as $index => $row)
                                    @php
                                        $expired = $summary['deadlines'][$row[1]] ?? 0;
                                        $soon = $summary['deadlines'][$row[2]] ?? 0;
                                    @endphp
                                    <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#fcfdff' }};">
                                        <td style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                            {{ $row[0] }}
                                        </td>
                                        <td align="center" style="padding:12px 14px; font-size:14px; font-weight:700; color:{{ $expired > 0 ? '#dc2626' : '#16a34a' }}; border-bottom:1px solid #e2e8f0;">
                                            {{ $expired }}
                                        </td>
                                        <td align="center" style="padding:12px 14px; font-size:14px; font-weight:700; color:{{ $soon > 0 ? '#d97706' : '#16a34a' }}; border-bottom:1px solid #e2e8f0;">
                                            {{ $soon }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px 32px 6px 32px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:14px;">Aktivnosti i statusi</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate; border-spacing:0; overflow:hidden; border:1px solid #e2e8f0; border-radius:14px;">
                            <tbody>
                                @php
                                    $actionRows = [
                                        ['Zapažanja - nije započeto', $summary['actions']['observations_not_started'] ?? 0],
                                        ['Zapažanja - u tijeku', $summary['actions']['observations_in_progress'] ?? 0],
                                        ['Zapažanja - otvorena ukupno', $summary['actions']['observations_open_total'] ?? 0],
                                        ['Zapažanja - završena', $resolvedObservations],
                                        ['Radni zadaci - otvoreni', $summary['actions']['work_tasks_open'] ?? 0],
                                        ['Dozvole za rad - otvorene', $summary['actions']['work_permits_open'] ?? 0],
                                        ['Incidenti - otvoreni', $summary['actions']['incidents_open'] ?? 0],
                                        ['Nalazi nadzora - otvoreni', $summary['actions']['inspection_findings_open'] ?? 0],
                                        ['Prateći listovi - otvoreni', $summary['actions']['waste_forms_open'] ?? 0],
                                    ];
                                @endphp

                                @foreach($actionRows as $index => $row)
                                    <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#fcfdff' }};">
                                        <td style="padding:12px 14px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">
                                            {{ $row[0] }}
                                        </td>
                                        <td align="right" style="padding:12px 14px; font-size:15px; font-weight:700; color:{{ $row[1] > 0 ? '#0f172a' : '#64748b' }}; border-bottom:1px solid #e2e8f0;">
                                            {{ $row[1] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px 32px 6px 32px;">
                        <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:14px;">Ukupna evidencija</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate; border-spacing:0; overflow:hidden; border:1px solid #e2e8f0; border-radius:14px;">
                            <tbody>
                                @php
                                    $totalRows = [
                                        ['Zaposlenici', $summary['totals']['employees'] ?? 0],
                                        ['Radna oprema', $summary['totals']['machines'] ?? 0],
                                        ['Vatrogasni aparati', $summary['totals']['fires'] ?? 0],
                                        ['Ostala ispitivanja', $summary['totals']['miscellaneous'] ?? 0],
                                        ['Zapažanja', $summary['totals']['observations'] ?? 0],
                                        ['Radni zadaci', $summary['totals']['work_tasks'] ?? 0],
                                        ['Dozvole za rad', $summary['totals']['work_permits'] ?? 0],
                                        ['Incidenti', $summary['totals']['incidents'] ?? 0],
                                        ['Nalazi nadzora', $summary['totals']['inspection_findings'] ?? 0],
                                        ['Prateći listovi', $summary['totals']['waste_forms'] ?? 0],
                                        ['Prva pomoć - stavke', $summary['totals']['first_aid_items'] ?? 0],
                                    ];
                                @endphp

                                @foreach($totalRows as $index => $row)
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

                <tr>
                    <td align="center" style="padding:28px 32px 10px 32px;">
                        <a href="{{ $appUrl }}"
                           style="display:inline-block; background:#f59e0b; color:#111827; text-decoration:none; font-size:14px; font-weight:700; padding:14px 22px; border-radius:10px;">
                            Otvori ZNR LIDER
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 32px 30px 32px;">
                        <div style="border-top:1px solid #e2e8f0; padding-top:16px; font-size:12px; line-height:20px; color:#64748b; text-align:center;">
                            Ovaj email je automatski generiran iz sustava <strong>ZNR LIDER</strong>.<br>
                            Ako podaci odstupaju od očekivanih, provjerite evidencije i statuse unutar aplikacije.
                        </div>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>