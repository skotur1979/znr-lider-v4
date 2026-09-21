<!DOCTYPE html>
<html lang="hr">

<head>
    <meta charset="UTF-8">

    <title>
        ZNR dosje zaposlenika
    </title>

    <style>
        @page {
            margin:
                18mm
                14mm
                16mm
                14mm;
        }

        body {
            font-family:
                "DejaVu Sans",
                sans-serif;

            font-size: 10px;
            color: #111827;
            line-height: 1.35;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | ZAGLAVLJE
        |--------------------------------------------------------------------------
        */

        .header {
            border-bottom:
                2px solid #1f4e78;

            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .brand {
            font-size: 11px;
            font-weight: bold;
            color: #1f4e78;
            margin-bottom: 5px;
        }

        .employee-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 10px;
            color: #4b5563;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLICE
        |--------------------------------------------------------------------------
        */

        table {
            border-collapse: collapse;
        }

        .pdf-section {
            width: 100%;
            margin-top: 13px;
            border-collapse: collapse;
        }

        .pdf-section th,
        .pdf-section td {
            border:
                1px solid #d1d5db;

            padding: 5px 6px;
            vertical-align: top;
        }

        /*
         * Pojedinačni red tablice
         * DomPDF ne smije prelomiti.
         */
        .pdf-section tr {
            page-break-inside: avoid;
        }

        /*
        |--------------------------------------------------------------------------
        | NEDJELJIVI POČETAK SEKCIJE
        |--------------------------------------------------------------------------
        |
        | Naslov + nazivi stupaca + prvi zapis
        | nalaze se unutar jednog vanjskog TR-a.
        |
        | Tako DomPDF ne može ostaviti naslov
        | sekcije sam na prethodnoj stranici.
        |
        */

        .section-start-row {
            page-break-inside: avoid !important;
        }

        .section-start-cell {
            padding: 0 !important;
            border: 0 !important;

            page-break-inside: avoid !important;
        }

        .section-start-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;

            page-break-inside: avoid !important;
        }

        .section-start-table th,
        .section-start-table td {
            border:
                1px solid #d1d5db;

            padding: 5px 6px;
            vertical-align: top;
        }

        .section-heading th {
            background: #eaf2f8;
            color: #1f4e78;

            font-size: 11px;
            font-weight: bold;

            padding: 6px 8px;
            text-align: left;
        }

        .column-heading th {
            background: #f3f4f6;
            color: #111827;

            font-weight: bold;
            text-align: left;
        }

        /*
        |--------------------------------------------------------------------------
        | MALE SEKCIJE
        |--------------------------------------------------------------------------
        */

        .keep-together {
            page-break-inside: avoid !important;
        }

        .info-label {
            width: 22%;
            color: #4b5563;
            font-weight: bold;
        }

        /*
        |--------------------------------------------------------------------------
        | STATUSI
        |--------------------------------------------------------------------------
        */

        .status-ok {
            color: #166534;
            font-weight: bold;
        }

        .status-warning {
            color: #92400e;
            font-weight: bold;
        }

        .status-danger {
            color: #991b1b;
            font-weight: bold;
        }

        .status-info {
            color: #1e40af;
            font-weight: bold;
        }

        .status-gray {
            color: #6b7280;
            font-weight: bold;
        }

        /*
        |--------------------------------------------------------------------------
        | OSTALO
        |--------------------------------------------------------------------------
        */

        .no-data {
            color: #6b7280;
        }

        .footer-note {
            margin-top: 14px;
            padding-top: 6px;

            border-top:
                1px solid #d1d5db;

            font-size: 8px;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>

<body>

    @php
        $stateClass = function (
            string $state
        ): string {
            return match ($state) {
                'success' =>
                    'status-ok',

                'warning' =>
                    'status-warning',

                'danger' =>
                    'status-danger',

                'info' =>
                    'status-info',

                default =>
                    'status-gray',
            };
        };

        $firstCertificate =
            $certificates->first();

        $remainingCertificates =
            $certificates->skip(1);

        $firstAlcoholTest =
            $alcoholTests->first();

        $remainingAlcoholTests =
            $alcoholTests->skip(1);

        $firstPpeItem =
            $ppeItems->first();

        $remainingPpeItems =
            $ppeItems->skip(1);

        $firstAttachment =
            $attachments->first();

        $remainingAttachments =
            $attachments->skip(1);
    @endphp

    {{-- =========================================================
         ZAGLAVLJE
    ========================================================== --}}

    <div class="header">

        <div class="brand">
            ZNR LIDER
        </div>

        <div class="employee-name">
            {{ $employee->name }}
        </div>

        <div class="subtitle">

            {{
                $employee->workplace
                ?: 'Radno mjesto nije evidentirano'
            }}

            @if (
                filled(
                    $employee->organization_unit
                )
            )
                |
                {{ $employee->organization_unit }}
            @endif

        </div>

    </div>

    {{-- =========================================================
         OSNOVNI PODACI
    ========================================================== --}}

    <table class="pdf-section keep-together">

        <thead>
            <tr class="section-heading">
                <th colspan="4">
                    OSNOVNI PODACI
                </th>
            </tr>
        </thead>

        <tbody>

            <tr>
                <td class="info-label">
                    OIB
                </td>

                <td>
                    {{ $employee->OIB ?: '—' }}
                </td>

                <td class="info-label">
                    Zanimanje
                </td>

                <td>
                    {{
                        $employee->job_title
                        ?: '—'
                    }}
                </td>
            </tr>

            <tr>
                <td class="info-label">
                    Radno mjesto
                </td>

                <td>
                    {{
                        $employee->workplace
                        ?: '—'
                    }}
                </td>

                <td class="info-label">
                    Organizacijska jedinica
                </td>

                <td>
                    {{
                        $employee
                            ->organization_unit
                        ?: '—'
                    }}
                </td>
            </tr>

            <tr>
                <td class="info-label">
                    Datum zaposlenja
                </td>

                <td>
                    {{
                        $employee
                            ->employeed_at
                            ?->format(
                                'd.m.Y.'
                            )
                        ?: '—'
                    }}
                </td>

                <td class="info-label">
                    Vrsta ugovora
                </td>

                <td>
                    {{
                        $employee
                            ->contract_type
                        ?: '—'
                    }}
                </td>
            </tr>

            <tr>
                <td class="info-label">
                    Telefon
                </td>

                <td>
                    {{
                        $employee->phone
                        ?: '—'
                    }}
                </td>

                <td class="info-label">
                    E-mail
                </td>

                <td>
                    {{
                        $employee->email
                        ?: '—'
                    }}
                </td>
            </tr>

        </tbody>

    </table>

    {{-- =========================================================
         STATUS ZNR
    ========================================================== --}}

    <table class="pdf-section">

        <thead>

            <tr class="section-heading">
                <th colspan="3">
                    STATUS ZNR
                </th>
            </tr>

            <tr class="column-heading">

                <th style="width:34%;">
                    Područje
                </th>

                <th style="width:42%;">
                    Podatak
                </th>

                <th style="width:24%;">
                    Status
                </th>

            </tr>

        </thead>

        <tbody>

            @foreach (
                $statusItems
                as $item
            )
                <tr>

                    <td>
                        <strong>
                            {{
                                $item[
                                    'label'
                                ]
                            }}
                        </strong>
                    </td>

                    <td>
                        {{
                            $item[
                                'value'
                            ]
                        }}
                    </td>

                    <td
                        class="{{
                            $stateClass(
                                $item[
                                    'state'
                                ]
                            )
                        }}"
                    >
                        {{
                            $item[
                                'state_label'
                            ]
                        }}
                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>

    {{-- =========================================================
         LIJEČNIČKI PODACI
    ========================================================== --}}

    <table class="pdf-section keep-together">

        <thead>

            <tr class="section-heading">
                <th colspan="2">
                    LIJEČNIČKI PODACI
                </th>
            </tr>

        </thead>

        <tbody>

            <tr>

                <td
                    class="info-label"
                    style="width:25%;"
                >
                    Članak 3. točke
                </td>

                <td>
                    {{
                        $employee->article
                        ?: '—'
                    }}
                </td>

            </tr>

            <tr>

                <td class="info-label">
                    Napomena liječnika
                </td>

                <td>
                    {{
                        $employee->remark
                        ?: '—'
                    }}
                </td>

            </tr>

        </tbody>

    </table>

    {{-- =========================================================
         EDUKACIJE
    ========================================================== --}}

    <table class="pdf-section">

        <colgroup>
            <col style="width:46%;">
            <col style="width:18%;">
            <col style="width:18%;">
            <col style="width:18%;">
        </colgroup>

        <tbody>

            <tr class="section-start-row">

                <td
                    colspan="4"
                    class="section-start-cell"
                >

                    <table class="section-start-table">

                        <colgroup>
                            <col style="width:46%;">
                            <col style="width:18%;">
                            <col style="width:18%;">
                            <col style="width:18%;">
                        </colgroup>

                        <tr class="section-heading">

                            <th colspan="4">
                                OSTALE EDUKACIJE I OVLAŠTENJA
                            </th>

                        </tr>

                        @if ($firstCertificate)

                            <tr class="column-heading">

                                <th>
                                    Naziv
                                </th>

                                <th>
                                    Vrijedi od
                                </th>

                                <th>
                                    Vrijedi do
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                            <tr>

                                <td>
                                    {{
                                        $firstCertificate[
                                            'title'
                                        ]
                                    }}
                                </td>

                                <td>
                                    {{
                                        $firstCertificate[
                                            'valid_from'
                                        ]
                                    }}
                                </td>

                                <td>
                                    {{
                                        $firstCertificate[
                                            'valid_until'
                                        ]
                                    }}
                                </td>

                                <td
                                    class="{{
                                        $stateClass(
                                            $firstCertificate[
                                                'state'
                                            ]
                                        )
                                    }}"
                                >
                                    {{
                                        $firstCertificate[
                                            'state_label'
                                        ]
                                    }}
                                </td>

                            </tr>

                        @else

                            <tr>

                                <td
                                    colspan="4"
                                    class="no-data"
                                >
                                    Nema evidentiranih dodatnih
                                    edukacija ili ovlaštenja.
                                </td>

                            </tr>

                        @endif

                    </table>

                </td>

            </tr>

            @foreach (
                $remainingCertificates
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

                    <td
                        class="{{
                            $stateClass(
                                $certificate[
                                    'state'
                                ]
                            )
                        }}"
                    >
                        {{
                            $certificate[
                                'state_label'
                            ]
                        }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    {{-- =========================================================
         ALKOTESTIRANJA
    ========================================================== --}}

    <table class="pdf-section">

        <colgroup>
            <col style="width:18%;">
            <col style="width:16%;">
            <col style="width:26%;">
            <col style="width:40%;">
        </colgroup>

        <tbody>

            <tr class="section-start-row">

                <td
                    colspan="4"
                    class="section-start-cell"
                >

                    <table class="section-start-table">

                        <colgroup>
                            <col style="width:18%;">
                            <col style="width:16%;">
                            <col style="width:26%;">
                            <col style="width:40%;">
                        </colgroup>

                        <tr class="section-heading">

                            <th colspan="4">
                                ALKOTESTIRANJA
                            </th>

                        </tr>

                        @if ($firstAlcoholTest)

                            <tr class="column-heading">

                                <th>
                                    Datum
                                </th>

                                <th>
                                    Rezultat
                                </th>

                                <th>
                                    Kontrolu proveo
                                </th>

                                <th>
                                    Napomena
                                </th>

                            </tr>

                            <tr>

                                <td>
                                    {{
                                        $firstAlcoholTest[
                                            'test_date'
                                        ]
                                    }}
                                </td>

                                <td
                                    class="{{
                                        $stateClass(
                                            $firstAlcoholTest[
                                                'state'
                                            ]
                                        )
                                    }}"
                                >
                                    {{
                                        $firstAlcoholTest[
                                            'result'
                                        ]
                                    }}
                                </td>

                                <td>
                                    {{
                                        $firstAlcoholTest[
                                            'tested_by'
                                        ]
                                    }}
                                </td>

                                <td>
                                    {{
                                        $firstAlcoholTest[
                                            'note'
                                        ]
                                    }}
                                </td>

                            </tr>

                        @else

                            <tr>

                                <td
                                    colspan="4"
                                    class="no-data"
                                >
                                    Nema evidentiranih
                                    alkotestiranja.
                                </td>

                            </tr>

                        @endif

                    </table>

                </td>

            </tr>

            @foreach (
                $remainingAlcoholTests
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

                    <td
                        class="{{
                            $stateClass(
                                $test[
                                    'state'
                                ]
                            )
                        }}"
                    >
                        {{
                            $test[
                                'result'
                            ]
                        }}
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

    {{-- =========================================================
         OZO
    ========================================================== --}}

    <table class="pdf-section">

        <colgroup>
            <col style="width:28%;">
            <col style="width:16%;">
            <col style="width:11%;">
            <col style="width:15%;">
            <col style="width:15%;">
            <col style="width:15%;">
        </colgroup>

        <tbody>

            {{-- 
                VAŽNO:
                naslov + stupci + prvi OZO
                nalaze se u jednom vanjskom TR-u.
            --}}
            <tr class="section-start-row">

                <td
                    colspan="6"
                    class="section-start-cell"
                >

                    <table class="section-start-table">

                        <colgroup>
                            <col style="width:28%;">
                            <col style="width:16%;">
                            <col style="width:11%;">
                            <col style="width:15%;">
                            <col style="width:15%;">
                            <col style="width:15%;">
                        </colgroup>

                        <tr class="section-heading">

                            <th colspan="6">
                                OZO – OSOBNA ZAŠTITNA OPREMA
                            </th>

                        </tr>

                        @if ($firstPpeItem)

                            <tr class="column-heading">

                                <th>
                                    Naziv OZO
                                </th>

                                <th>
                                    HRN EN
                                </th>

                                <th>
                                    Veličina
                                </th>

                                <th>
                                    Izdano
                                </th>

                                <th>
                                    Istek
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                            <tr>

                                <td>
                                    {{
                                        $firstPpeItem[
                                            'equipment_name'
                                        ]
                                    }}
                                </td>

                                <td>
                                    {{
                                        $firstPpeItem[
                                            'standard'
                                        ]
                                    }}
                                </td>

                                <td>
                                    {{
                                        $firstPpeItem[
                                            'size'
                                        ]
                                    }}
                                </td>

                                <td>
                                    {{
                                        $firstPpeItem[
                                            'issue_date'
                                        ]
                                    }}
                                </td>

                                <td>
                                    {{
                                        $firstPpeItem[
                                            'end_date'
                                        ]
                                    }}
                                </td>

                                <td
                                    class="{{
                                        $stateClass(
                                            $firstPpeItem[
                                                'state'
                                            ]
                                        )
                                    }}"
                                >
                                    {{
                                        $firstPpeItem[
                                            'state_label'
                                        ]
                                    }}
                                </td>

                            </tr>

                        @else

                            <tr>

                                <td
                                    colspan="6"
                                    class="no-data"
                                >
                                    Nema trenutno zadužene
                                    osobne zaštitne opreme.
                                </td>

                            </tr>

                        @endif

                    </table>

                </td>

            </tr>

            @foreach (
                $remainingPpeItems
                as $item
            )

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

                    <td
                        class="{{
                            $stateClass(
                                $item[
                                    'state'
                                ]
                            )
                        }}"
                    >
                        {{
                            $item[
                                'state_label'
                            ]
                        }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    {{-- =========================================================
         DOKUMENTI
    ========================================================== --}}

    <table class="pdf-section">

        <colgroup>
            <col style="width:8%;">
            <col style="width:92%;">
        </colgroup>

        <tbody>

            <tr class="section-start-row">

                <td
                    colspan="2"
                    class="section-start-cell"
                >

                    <table class="section-start-table">

                        <colgroup>
                            <col style="width:8%;">
                            <col style="width:92%;">
                        </colgroup>

                        <tr class="section-heading">

                            <th colspan="2">
                                DOKUMENTI / PRILOZI
                            </th>

                        </tr>

                        @if ($firstAttachment)

                            <tr class="column-heading">

                                <th>
                                    #
                                </th>

                                <th>
                                    Naziv datoteke
                                </th>

                            </tr>

                            <tr>

                                <td>
                                    1
                                </td>

                                <td>
                                    {{
                                        $firstAttachment[
                                            'name'
                                        ]
                                    }}
                                </td>

                            </tr>

                        @else

                            <tr>

                                <td
                                    colspan="2"
                                    class="no-data"
                                >
                                    Nema dostupnih priloga.
                                </td>

                            </tr>

                        @endif

                    </table>

                </td>

            </tr>

            @foreach (
                $remainingAttachments
                as $index => $attachment
            )

                <tr>

                    <td>
                        {{ $index + 2 }}
                    </td>

                    <td>
                        {{
                            $attachment[
                                'name'
                            ]
                        }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    {{-- =========================================================
         PODNOŽJE
    ========================================================== --}}

    <div class="footer-note">

        ZNR LIDER •
        ZNR dosje zaposlenika •
        Generirano

        {{
            $generatedAt->format(
                'd.m.Y. H:i'
            )
        }}

    </div>

</body>
</html>