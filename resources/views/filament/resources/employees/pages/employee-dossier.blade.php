<x-filament-panels::page>
    @php
        $employee = $dossier['employee'];
        $statusItems = $dossier['statusItems'];
        $certificates = $dossier['certificates'];
        $alcoholTests = $dossier['alcoholTests'];
        $ppeItems = $dossier['ppeItems'];
        $attachments = $dossier['attachments'];

        $badgeStyles = [
            'success' =>
                'background:#dcfce7;color:#166534;border:1px solid #bbf7d0;',

            'warning' =>
                'background:#fef3c7;color:#92400e;border:1px solid #fde68a;',

            'danger' =>
                'background:#fee2e2;color:#991b1b;border:1px solid #fecaca;',

            'info' =>
                'background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;',

            'gray' =>
                'background:#f3f4f6;color:#4b5563;border:1px solid #e5e7eb;',
        ];
    @endphp

    <style>
        .znr-dossier {
            display: grid;
            gap: 18px;
        }

        .znr-dossier-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        }

        .znr-dossier-header {
            padding: 22px 24px;
            background: linear-gradient(
                135deg,
                #f8fafc 0%,
                #ffffff 100%
            );
            border-bottom: 1px solid #e5e7eb;
        }

        .znr-dossier-name {
            margin: 0;
            font-size: 25px;
            line-height: 1.2;
            font-weight: 800;
            color: #111827;
        }

        .znr-dossier-subtitle {
            margin-top: 7px;
            color: #6b7280;
            font-size: 14px;
        }

        .znr-dossier-section-title {
            margin: 0;
            padding: 15px 20px;
            font-size: 15px;
            font-weight: 800;
            color: #111827;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .znr-dossier-body {
            padding: 18px 20px;
        }

        .znr-dossier-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 12px 22px;
        }

        .znr-dossier-field {
            min-width: 0;
        }

        .znr-dossier-label {
            display: block;
            margin-bottom: 4px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .znr-dossier-value {
            color: #111827;
            font-size: 14px;
            font-weight: 600;
            word-break: break-word;
        }

        .znr-status-list {
            display: grid;
            gap: 10px;
        }

        .znr-status-row {
            display: grid;
            grid-template-columns:
                minmax(220px, 1fr)
                minmax(180px, 1fr)
                auto;
            gap: 12px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .znr-status-row:last-child {
            border-bottom: 0;
        }

        .znr-status-title {
            font-weight: 750;
            color: #111827;
        }

        .znr-status-value {
            color: #4b5563;
            font-size: 13px;
        }

        .znr-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 800;
        }

        .znr-table-wrap {
            overflow-x: auto;
        }

        .znr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .znr-table th {
            text-align: left;
            padding: 10px 12px;
            background: #f9fafb;
            color: #374151;
            font-weight: 800;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .znr-table td {
            padding: 10px 12px;
            color: #111827;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .znr-table tr:last-child td {
            border-bottom: 0;
        }

        .znr-empty {
            padding: 18px 20px;
            color: #6b7280;
            font-size: 13px;
        }

        .znr-attachments {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .znr-attachment {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 11px;
            border-radius: 9px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .znr-generated {
            color: #6b7280;
            font-size: 12px;
            text-align: right;
        }

        @media (max-width: 900px) {
            .znr-dossier-grid {
                grid-template-columns: 1fr;
            }

            .znr-status-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .znr-status-row .znr-badge {
                justify-self: start;
            }
        }

        .dark .znr-dossier-card {
            background: #111827;
            border-color: #374151;
        }

        .dark .znr-dossier-header,
        .dark .znr-dossier-section-title,
        .dark .znr-table th {
            background: #1f2937;
            border-color: #374151;
        }

        .dark .znr-dossier-name,
        .dark .znr-dossier-section-title,
        .dark .znr-dossier-value,
        .dark .znr-status-title,
        .dark .znr-table td {
            color: #f9fafb;
        }

        .dark .znr-dossier-subtitle,
        .dark .znr-dossier-label,
        .dark .znr-status-value,
        .dark .znr-empty,
        .dark .znr-generated {
            color: #9ca3af;
        }

        .dark .znr-status-row,
        .dark .znr-table th,
        .dark .znr-table td {
            border-color: #374151;
        }
    </style>

    <div class="znr-dossier">

        {{-- =========================================================
             OSNOVNI PODACI
        ========================================================== --}}
        <div class="znr-dossier-card">

            <div class="znr-dossier-header">
                <h2 class="znr-dossier-name">
                    {{ $employee->name }}
                </h2>

                <div class="znr-dossier-subtitle">
                    {{
                        $employee->workplace
                        ?: 'Radno mjesto nije evidentirano'
                    }}

                    @if (
                        filled(
                            $employee->organization_unit
                        )
                    )
                        &nbsp;|&nbsp;
                        {{ $employee->organization_unit }}
                    @endif
                </div>
            </div>

            <h3 class="znr-dossier-section-title">
                Osnovni podaci
            </h3>

            <div class="znr-dossier-body">
                <div class="znr-dossier-grid">

                    <div class="znr-dossier-field">
                        <span class="znr-dossier-label">
                            OIB
                        </span>

                        <span class="znr-dossier-value">
                            {{ $employee->OIB ?: '—' }}
                        </span>
                    </div>

                    <div class="znr-dossier-field">
                        <span class="znr-dossier-label">
                            Zanimanje
                        </span>

                        <span class="znr-dossier-value">
                            {{
                                $employee->job_title
                                ?: '—'
                            }}
                        </span>
                    </div>

                    <div class="znr-dossier-field">
                        <span class="znr-dossier-label">
                            Radno mjesto
                        </span>

                        <span class="znr-dossier-value">
                            {{
                                $employee->workplace
                                ?: '—'
                            }}
                        </span>
                    </div>

                    <div class="znr-dossier-field">
                        <span class="znr-dossier-label">
                            Organizacijska jedinica
                        </span>

                        <span class="znr-dossier-value">
                            {{
                                $employee
                                    ->organization_unit
                                ?: '—'
                            }}
                        </span>
                    </div>

                    <div class="znr-dossier-field">
                        <span class="znr-dossier-label">
                            Datum zaposlenja
                        </span>

                        <span class="znr-dossier-value">
                            {{
                                $employee
                                    ->employeed_at
                                    ?->format(
                                        'd.m.Y.'
                                    )
                                ?: '—'
                            }}
                        </span>
                    </div>

                    <div class="znr-dossier-field">
                        <span class="znr-dossier-label">
                            Vrsta ugovora
                        </span>

                        <span class="znr-dossier-value">
                            {{
                                $employee
                                    ->contract_type
                                ?: '—'
                            }}
                        </span>
                    </div>

                    <div class="znr-dossier-field">
                        <span class="znr-dossier-label">
                            Telefon / mobitel
                        </span>

                        <span class="znr-dossier-value">
                            {{
                                $employee->phone
                                ?: '—'
                            }}
                        </span>
                    </div>

                    <div class="znr-dossier-field">
                        <span class="znr-dossier-label">
                            E-mail
                        </span>

                        <span class="znr-dossier-value">
                            {{
                                $employee->email
                                ?: '—'
                            }}
                        </span>
                    </div>

                </div>
            </div>
        </div>

        {{-- =========================================================
             STATUS ZNR
        ========================================================== --}}
        <div class="znr-dossier-card">

            <h3 class="znr-dossier-section-title">
                Status ZNR
            </h3>

            <div class="znr-dossier-body">
                <div class="znr-status-list">

                    @foreach ($statusItems as $item)
                        <div class="znr-status-row">

                            <div class="znr-status-title">
                                <span aria-hidden="true">
                                    {{ $item['icon'] }}
                                </span>

                                {{ $item['label'] }}
                            </div>

                            <div class="znr-status-value">
                                {{ $item['value'] }}
                            </div>

                            <span
                                class="znr-badge"
                                style="{{
                                    $badgeStyles[
                                        $item['state']
                                    ]
                                    ?? $badgeStyles[
                                        'gray'
                                    ]
                                }}"
                            >
                                {{ $item['state_label'] }}
                            </span>

                        </div>
                    @endforeach

                </div>
            </div>
        </div>

        {{-- =========================================================
             LIJEČNIČKI PODACI
        ========================================================== --}}
        <div class="znr-dossier-card">

            <h3 class="znr-dossier-section-title">
                Liječnički podaci
            </h3>

            <div class="znr-dossier-body">
                <div class="znr-dossier-grid">

                    <div class="znr-dossier-field">
                        <span class="znr-dossier-label">
                            Članak 3. točke
                        </span>

                        <span class="znr-dossier-value">
                            {{
                                $employee->article
                                ?: '—'
                            }}
                        </span>
                    </div>

                    <div class="znr-dossier-field">
                        <span class="znr-dossier-label">
                            Napomena liječnika
                        </span>

                        <span class="znr-dossier-value">
                            {{
                                $employee->remark
                                ?: '—'
                            }}
                        </span>
                    </div>

                </div>
            </div>
        </div>

        {{-- =========================================================
             EDUKACIJE
        ========================================================== --}}
        <div class="znr-dossier-card">

            <h3 class="znr-dossier-section-title">
                Ostale edukacije i ovlaštenja
            </h3>

            @if ($certificates->isEmpty())

                <div class="znr-empty">
                    Nema evidentiranih dodatnih
                    edukacija ili ovlaštenja.
                </div>

            @else

                <div class="znr-table-wrap">
                    <table class="znr-table">

                        <thead>
                            <tr>
                                <th>Naziv</th>
                                <th>Vrijedi od</th>
                                <th>Vrijedi do</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $certificates
                                as $certificate
                            )
                                <tr>
                                    <td>
                                        {{
                                            $certificate[
                                                'title'
                                            ]
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $certificate[
                                                'valid_from'
                                            ]
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $certificate[
                                                'valid_until'
                                            ]
                                        }}
                                    </td>

                                    <td>
                                        <span
                                            class="znr-badge"
                                            style="{{
                                                $badgeStyles[
                                                    $certificate[
                                                        'state'
                                                    ]
                                                ]
                                                ?? $badgeStyles[
                                                    'gray'
                                                ]
                                            }}"
                                        >
                                            {{
                                                $certificate[
                                                    'state_label'
                                                ]
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            @endif
        </div>

        {{-- =========================================================
             ALKOTESTIRANJA
        ========================================================== --}}
        <div class="znr-dossier-card">

            <h3 class="znr-dossier-section-title">
                Alkotestiranja
            </h3>

            @if ($alcoholTests->isEmpty())

                <div class="znr-empty">
                    Nema evidentiranih alkotestiranja.
                </div>

            @else

                <div class="znr-table-wrap">
                    <table class="znr-table">

                        <thead>
                            <tr>
                                <th>Datum kontrole</th>
                                <th>Rezultat</th>
                                <th>Kontrolu proveo</th>
                                <th>Napomena</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach (
                                $alcoholTests
                                as $test
                            )
                                <tr>
                                    <td>
                                        {{
                                            $test[
                                                'test_date'
                                            ]
                                        }}
                                    </td>

                                    <td>
                                        <span
                                            class="znr-badge"
                                            style="{{
                                                $badgeStyles[
                                                    $test[
                                                        'state'
                                                    ]
                                                ]
                                                ?? $badgeStyles[
                                                    'gray'
                                                ]
                                            }}"
                                        >
                                            {{
                                                $test[
                                                    'result'
                                                ]
                                            }}
                                        </span>
                                    </td>

                                    <td>
                                        {{
                                            $test[
                                                'tested_by'
                                            ]
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $test[
                                                'note'
                                            ]
                                        }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            @endif
        </div>

        {{-- =========================================================
             OZO
        ========================================================== --}}
        <div class="znr-dossier-card">

            <h3 class="znr-dossier-section-title">
                OZO – osobna zaštitna oprema
            </h3>

            @if ($ppeItems->isEmpty())

                <div class="znr-empty">
                    Nema trenutno zadužene osobne
                    zaštitne opreme.
                </div>

            @else

                <div class="znr-table-wrap">
                    <table class="znr-table">

                        <thead>
                            <tr>
                                <th>Naziv OZO</th>
                                <th>HRN EN</th>
                                <th>Veličina</th>
                                <th>Izdano</th>
                                <th>Istek</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($ppeItems as $item)
                                <tr>
                                    <td>
                                        {{
                                            $item[
                                                'equipment_name'
                                            ]
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $item[
                                                'standard'
                                            ]
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $item[
                                                'size'
                                            ]
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $item[
                                                'issue_date'
                                            ]
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $item[
                                                'end_date'
                                            ]
                                        }}
                                    </td>

                                    <td>
                                        <span
                                            class="znr-badge"
                                            style="{{
                                                $badgeStyles[
                                                    $item[
                                                        'state'
                                                    ]
                                                ]
                                                ?? $badgeStyles[
                                                    'gray'
                                                ]
                                            }}"
                                        >
                                            {{
                                                $item[
                                                    'state_label'
                                                ]
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            @endif
        </div>

        {{-- =========================================================
             DOKUMENTI / PRILOZI
        ========================================================== --}}
        <div class="znr-dossier-card">

            <h3 class="znr-dossier-section-title">
                Dokumenti / prilozi
            </h3>

            <div class="znr-dossier-body">

                @if ($attachments->isEmpty())

                    <div
                        class="znr-empty"
                        style="padding:0;"
                    >
                        Nema dostupnih priloga.
                    </div>

                @else

                    <div class="znr-attachments">

                        @foreach (
                            $attachments
                            as $attachment
                        )
                            @php
                                $attachmentUrl =
                                    \App\Support\SecureFilePreview::url(
                                        $attachment[
                                            'path'
                                        ]
                                    );
                            @endphp

                            <a
                                class="znr-attachment"
                                href="{{ $attachmentUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                📎
                                {{
                                    $attachment[
                                        'name'
                                    ]
                                }}
                            </a>

                        @endforeach

                    </div>

                @endif

            </div>
        </div>

        <div class="znr-generated">
            Generirano:
            {{
                $dossier[
                    'generatedAt'
                ]->format(
                    'd.m.Y. H:i'
                )
            }}
        </div>

    </div>
</x-filament-panels::page>