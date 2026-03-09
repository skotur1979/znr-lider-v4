<x-filament-panels::page>
    @php
        $formatKg = fn ($value) => number_format((float) $value, 2, ',', '.');

        $wasteCode = $record->wasteType?->formatted_waste_code ?? '-';
        $wasteName = $record->wasteType?->name ?? '-';
    @endphp

    <style>
        .onto-wrap {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .onto-sheet {
            background: #ffffff;
            color: #111827;
            border: 1px solid #d1d5db;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
        }

        .dark .onto-sheet {
            background: #111827;
            color: #f9fafb;
            border-color: rgba(255,255,255,.10);
            box-shadow: 0 10px 28px rgba(0, 0, 0, .35);
        }

        .onto-title-top {
            text-align: center;
            padding: 18px 20px 10px;
            border-bottom: 1px solid #d1d5db;
        }

        .dark .onto-title-top {
            border-bottom-color: rgba(255,255,255,.10);
        }

        .onto-suptitle {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .onto-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 8px;
        }

        .onto-title-main {
            flex: 1;
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.15;
        }

        .onto-title-right {
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
        }

        .onto-meta-grid {
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 1fr;
            border-bottom: 1px solid #d1d5db;
        }

        .dark .onto-meta-grid {
            border-bottom-color: rgba(255,255,255,.10);
        }

        .onto-meta-cell {
            padding: 14px 16px;
            border-right: 1px solid #d1d5db;
            min-height: 150px;
        }

        .onto-meta-cell:last-child {
            border-right: none;
        }

        .dark .onto-meta-cell {
            border-right-color: rgba(255,255,255,.10);
        }

        .onto-field {
            margin-bottom: 12px;
        }

        .onto-field:last-child {
            margin-bottom: 0;
        }

        .onto-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
            opacity: .75;
            margin-bottom: 4px;
        }

        .onto-value {
            font-size: 15px;
            font-weight: 600;
            line-height: 1.35;
            word-break: break-word;
        }

        .onto-section-title {
            text-align: center;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 10px 16px;
            border-bottom: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .dark .onto-section-title {
            background: rgba(255,255,255,.03);
            border-bottom-color: rgba(255,255,255,.10);
        }

        .onto-table-wrap {
            overflow-x: auto;
        }

        .onto-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .onto-table th,
        .onto-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            font-size: 13px;
            vertical-align: top;
        }

        .dark .onto-table th,
        .dark .onto-table td {
            border-color: rgba(255,255,255,.10);
        }

        .onto-table thead th {
            background: #f3f4f6;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .02em;
            text-align: center;
        }

        .dark .onto-table thead th {
            background: rgba(255,255,255,.04);
        }

        .onto-col-rb {
            width: 60px;
            text-align: center;
        }

        .onto-col-date {
            width: 120px;
            text-align: center;
        }

        .onto-col-in,
        .onto-col-out,
        .onto-col-balance {
            width: 130px;
            text-align: right;
        }

        .onto-col-method {
            width: 240px;
        }

        .onto-col-note {
            width: auto;
        }

        .onto-num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .onto-center {
            text-align: center;
        }

        .onto-empty {
            text-align: center;
            padding: 24px !important;
            color: #6b7280;
        }

        .dark .onto-empty {
            color: #9ca3af;
        }

        .onto-badge-open,
        .onto-badge-closed {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .onto-badge-open {
            background: rgba(34, 197, 94, .12);
            color: #15803d;
        }

        .onto-badge-closed {
            background: rgba(239, 68, 68, .12);
            color: #b91c1c;
        }

        .dark .onto-badge-open {
            color: #86efac;
        }

        .dark .onto-badge-closed {
            color: #fca5a5;
        }

        .onto-method {
            font-weight: 700;
            white-space: nowrap;
        }

        @media (max-width: 1100px) {
            .onto-meta-grid {
                grid-template-columns: 1fr;
            }

            .onto-meta-cell {
                border-right: none;
                border-bottom: 1px solid #d1d5db;
                min-height: auto;
            }

            .onto-meta-cell:last-child {
                border-bottom: none;
            }

            .dark .onto-meta-cell {
                border-bottom-color: rgba(255,255,255,.10);
            }

            .onto-title-row {
                flex-direction: column;
                align-items: center;
            }

            .onto-title-main {
                font-size: 24px;
            }
        }
    </style>

    <div class="onto-wrap">
        <div class="onto-sheet">
            <div class="onto-title-top">
                <div class="onto-suptitle">Dodatak XII.</div>

                <div class="onto-title-row">
                    <div style="width: 120px;"></div>
                    <div class="onto-title-main">Očevidnik o nastanku i tijeku otpada</div>
                    <div class="onto-title-right">Obrazac ONTO</div>
                </div>
            </div>

            <div class="onto-meta-grid">
                <div class="onto-meta-cell">
                    <div class="onto-field">
                        <span class="onto-label">Tvrtka / obrt</span>
                        <div class="onto-value">{{ $record->organization?->company_name ?? '-' }}</div>
                    </div>

                    <div class="onto-field">
                        <span class="onto-label">Sjedište</span>
                        <div class="onto-value">{{ $record->organization?->registered_office ?? '-' }}</div>
                    </div>

                    <div class="onto-field">
                        <span class="onto-label">Odgovorna osoba</span>
                        <div class="onto-value">{{ $record->responsible_person ?: '-' }}</div>
                    </div>
                </div>

                <div class="onto-meta-cell">
                    <div class="onto-field">
                        <span class="onto-label">Godina</span>
                        <div class="onto-value">{{ $record->year }}</div>
                    </div>

                    <div class="onto-field">
                        <span class="onto-label">Lokacija</span>
                        <div class="onto-value">
                            {{ $record->location?->display_name ?? ($record->location?->name ?? '-') }}
                        </div>
                    </div>

                    <div class="onto-field">
                        <span class="onto-label">Ključni broj otpada</span>
                        @php
$raw = $record->wasteType?->waste_code ?? null;

$danger = str_contains($raw ?? '', '*');

$code = str_replace('*','',$raw);

$formatted = $code
    ? substr($code,0,2).' '.substr($code,2,2).' '.substr($code,4,2)
    : '-';
@endphp

<div class="onto-value">
    {{ $formatted }}
    @if($danger)
        <sup style="font-size:0.7em">*</sup>
    @endif
</div>
                    </div>

                    <div class="onto-field">
                        <span class="onto-label">Naziv otpada</span>
                        <div class="onto-value">{{ $wasteName }}</div>
                    </div>
                </div>

                <div class="onto-meta-cell">
                    <div class="onto-field">
                        <span class="onto-label">Datum otvaranja</span>
                        <div class="onto-value">
                            {{ $record->opening_date ? \Illuminate\Support\Carbon::parse($record->opening_date)->format('d.m.Y.') : '-' }}
                        </div>
                    </div>

                    <div class="onto-field">
                        <span class="onto-label">Datum zatvaranja</span>
                        <div class="onto-value">
                            {{ $record->closing_date ? \Illuminate\Support\Carbon::parse($record->closing_date)->format('d.m.Y.') : '-' }}
                        </div>
                    </div>

                    <div class="onto-field">
                        <span class="onto-label">Trenutno stanje</span>
                        <div class="onto-value">{{ $formatKg($record->current_balance_kg) }} kg</div>
                    </div>

                    <div class="onto-field">
                        <span class="onto-label">Status</span>
                        <div class="onto-value">
                            @if ($record->is_closed)
                                <span class="onto-badge-closed">Zatvoren</span>
                            @else
                                <span class="onto-badge-open">Otvoren</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="onto-section-title">Podaci o tijeku otpada</div>

            <div class="onto-table-wrap">
                <table class="onto-table">
                    <thead>
                        <tr>
                            <th class="onto-col-rb">Br.</th>
                            <th class="onto-col-date">Datum</th>
                            <th class="onto-col-in">Ulaz (kg)</th>
                            <th class="onto-col-out">Izlaz (kg)</th>
                            <th class="onto-col-method">Način</th>
                            <th class="onto-col-balance">Stanje (kg)</th>
                            <th class="onto-col-note">Napomena</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="onto-center">
                                    {{ $entry->entry_no }}
                                </td>

                                <td class="onto-center">
                                    {{ $entry->entry_date ? \Illuminate\Support\Carbon::parse($entry->entry_date)->format('d.m.Y.') : '-' }}
                                </td>

                                <td class="onto-num">
                                    {{ (float) $entry->input_kg > 0 ? $formatKg($entry->input_kg) : '-' }}
                                </td>

                                <td class="onto-num">
                                    {{ (float) $entry->output_kg > 0 ? $formatKg($entry->output_kg) : '-' }}
                                </td>

                                <td>
                                    <span class="onto-method">{{ $entry->method ?: '-' }}</span>
                                </td>

                                <td class="onto-num">
                                    {{ $formatKg($entry->balance_after_kg) }}
                                </td>

                                <td>
                                    {{ $entry->note ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="onto-empty">
                                    Još nema evidentiranih ulaza ili izlaza otpada za ovaj ONTO obrazac.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>