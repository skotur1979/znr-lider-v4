<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <title>ONTO obrazac</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 100%;
        }

        .top-small {
            text-align: center;
            font-weight: 700;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .title-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        .title-table td {
            vertical-align: middle;
        }

        .title-left {
            width: 20%;
        }

        .title-center {
            width: 60%;
            text-align: center;
            font-weight: 700;
            font-size: 12px;
        }

        .title-right {
            width: 20%;
            text-align: right;
            font-style: italic;
            font-weight: 700;
            font-size: 11px;
        }

        table.meta,
        table.entries {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.meta {
            border: 1px solid #000;
            margin-bottom: 0;
        }

        table.entries {
            border: 1px solid #000;
            border-top: none;
        }

        table.meta td,
        table.entries td,
        table.entries th {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: top;
        }

        .meta-row td {
            height: 42px;
        }

        .meta-label {
            font-size: 9px;
            line-height: 1.1;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 10px;
            font-weight: 700;
            line-height: 1.15;
            word-break: break-word;
        }

        .section-title {
            text-align: center;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 0;
        }

        .head th {
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            padding: 3px 2px;
            vertical-align: middle;
            line-height: 1.1;
        }

        .entry td {
            font-size: 9.4px;
            height: 17px;
            padding: 1px 3px;
            line-height: 1.05;
            vertical-align: middle;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .footer {
            margin-top: 4px;
            text-align: right;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
@php
    $organization = $record->organization;
    $location = $record->organizationLocation;
    $wasteType = $record->wasteType;
    $entries = collect($record->entries ?? [])->values();

    $companyName = $organization->company_name ?? $organization->name ?? '';
    $companyAddress = $organization->registered_office ?? $organization->address ?? '';

    $locationText = $location->display_name
        ?? $location->name
        ?? $location->location_name
        ?? $location->address
        ?? '';

    $wasteCode = $wasteType?->waste_code
        ? \App\Support\WasteCodeFormatter::plain($wasteType->waste_code)
        : '';

    $formatKg = fn ($value) => number_format((float) $value, 2, ',', '.');

    $fixedRows = 22;
@endphp

<div class="page">
    <div class="top-small">DODATAK XII.</div>

    <table class="title-table">
        <tr>
            <td class="title-left"></td>
            <td class="title-center">OČEVIDNIK O NASTANKU I TIJEKU OTPADA</td>
            <td class="title-right">Obrazac ONTO</td>
        </tr>
    </table>

    <table class="meta">
        <colgroup>
            <col style="width:29%">
            <col style="width:40%">
            <col style="width:31%">
        </colgroup>

        <tr class="meta-row">
            <td>
                <div class="meta-label">Tvrtka:</div>
                <div class="meta-value">{{ $companyName }}</div>
            </td>
            <td>
                <div class="meta-label">Lokacija:</div>
                <div class="meta-value">{{ $locationText }}</div>
            </td>
            <td>
                <div class="meta-label">Godina:</div>
                <div class="meta-value">{{ $record->year ?: '' }}</div>
            </td>
        </tr>

        <tr class="meta-row">
            <td>
                <div class="meta-label">Sjedište:</div>
                <div class="meta-value">{{ $companyAddress }}</div>
            </td>
            <td>
                <div class="meta-label">&nbsp;</div>
                <div class="meta-value">&nbsp;</div>
            </td>
            <td>
                <div class="meta-label">Datum otvaranja:</div>
                <div class="meta-value">
                    {{ $record->opening_date ? \Illuminate\Support\Carbon::parse($record->opening_date)->format('d.m.Y.') : '' }}
                </div>
            </td>
        </tr>

        <tr class="meta-row">
            <td>
                <div class="meta-label">Odgovorna osoba:</div>
                <div class="meta-value">{{ $record->responsible_person ?: '' }}</div>
            </td>
            <td>
                <div class="meta-label">Ključni broj otpada:</div>
                <div class="meta-value">{{ $wasteCode }}</div>
            </td>
            <td>
                <div class="meta-label">Datum zatvaranja:</div>
                <div class="meta-value">
                    {{ $record->closing_date ? \Illuminate\Support\Carbon::parse($record->closing_date)->format('d.m.Y.') : '' }}
                </div>
            </td>
        </tr>

        <tr>
            <td colspan="3" class="section-title">PODACI O TIJEKU OTPADA</td>
        </tr>
    </table>

    <table class="entries">
        <tr class="head">
    <th style="width:3%">BR.</th>
    <th style="width:8%">DATUM</th>
    <th style="width:9%">ULAZ (kg)</th>
    <th style="width:9%">IZLAZ (kg)</th>
    <th style="width:31%">NAČIN</th>
    <th style="width:10%">STANJE (kg)</th>
    <th style="width:30%">Napomena</th>
</tr>

        @for ($i = 0; $i < $fixedRows; $i++)
            @php
                $entry = $entries[$i] ?? null;
            @endphp
            <tr class="entry">
                <td class="center">{{ $entry?->entry_no ?? '' }}</td>
                <td class="center">
                    {{ $entry?->entry_date ? \Illuminate\Support\Carbon::parse($entry->entry_date)->format('d.m.Y.') : '' }}
                </td>
                <td class="right">
                    {{ $entry && (float) $entry->input_kg > 0 ? $formatKg($entry->input_kg) : '' }}
                </td>
                <td class="right">
                    {{ $entry && (float) $entry->output_kg > 0 ? $formatKg($entry->output_kg) : '' }}
                </td>
                <td>{{ $entry?->method ?? '' }}</td>
                <td class="right">
                    {{ $entry ? $formatKg($entry->balance_after_kg) : '' }}
                </td>
                <td>{{ $entry?->note ?? '' }}</td>
            </tr>
        @endfor
    </table>

    <div class="footer">
        Datum izvoza: {{ now()->format('d.m.Y. H:i') }}
    </div>
</div>
</body>
</html>