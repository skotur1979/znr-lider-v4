<x-filament-panels::page>
    @if ($this->showOcrDiffs && count($this->ocrDiffs))
        <div style="
            margin-bottom: 24px;
            border: 1px solid #1d4ed8;
            border-radius: 18px;
            padding: 22px;
            background: linear-gradient(180deg, #08204a 0%, #0b2a55 100%);
            box-shadow: 0 12px 30px rgba(0,0,0,0.28);
        ">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
                <div style="
                    width:44px;
                    height:44px;
                    border-radius:14px;
                    background: rgba(255,255,255,0.08);
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    color:#ffffff;
                    font-size:20px;
                    border:1px solid rgba(255,255,255,0.08);
                ">
                    🔍
                </div>

                <div>
                    <div style="font-size:22px; font-weight:700; color:#ffffff; line-height:1.1;">
                        OCR razlike prije zamjene
                    </div>
                    <div style="font-size:13px; color:#c7d2fe; margin-top:4px;">
                        Prikazana su samo polja koja su stvarno različita
                    </div>
                </div>
            </div>

            <div style="
                display:grid;
                grid-template-columns:repeat(2, minmax(0, 1fr));
                gap:14px;
            ">
                @foreach ($this->ocrDiffs as $field => $diff)
                    @php
                        $type = $diff['type'] ?? 'changed';

                        if ($type === 'changed') {
                            $cardBorder = '#f59e0b';
                            $badgeBg = 'rgba(245, 158, 11, 0.16)';
                            $badgeColor = '#fbbf24';
                            $noteText = 'Različita vrijednost';
                        } elseif ($type === 'new') {
                            $cardBorder = '#22c55e';
                            $badgeBg = 'rgba(34, 197, 94, 0.16)';
                            $badgeColor = '#4ade80';
                            $noteText = 'Novo polje iz OCR-a';
                        } else {
                            $cardBorder = '#334155';
                            $badgeBg = 'rgba(148, 163, 184, 0.12)';
                            $badgeColor = '#cbd5e1';
                            $noteText = 'Bez nove vrijednosti';
                        }
                    @endphp

                    <div style="
                        border:1px solid {{ $cardBorder }};
                        border-radius:14px;
                        padding:14px;
                        background: rgba(255,255,255,0.04);
                    ">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
                            <div style="font-size:17px; font-weight:700; color:#ffffff;">
                                {{ $diff['label'] }}
                            </div>

                            <span style="
                                padding:4px 8px;
                                border-radius:999px;
                                font-size:11px;
                                font-weight:700;
                                background: {{ $badgeBg }};
                                color: {{ $badgeColor }};
                                border:1px solid rgba(255,255,255,0.06);
                                white-space:nowrap;
                            ">
                                {{ $noteText }}
                            </span>
                        </div>

                        <div style="
                            display:grid;
                            grid-template-columns:1fr 1fr;
                            gap:10px;
                            margin-bottom:12px;
                        ">
                            <div>
                                <div style="
                                    font-size:11px;
                                    font-weight:700;
                                    text-transform:uppercase;
                                    letter-spacing:.04em;
                                    color:#93a4c3;
                                    margin-bottom:5px;
                                ">
                                    Postojeće
                                </div>

                                <div style="
                                    padding:9px 10px;
                                    border-radius:10px;
                                    background:#0f172a;
                                    border:1px solid rgba(255,255,255,0.06);
                                    color:#e5e7eb;
                                    font-size:14px;
                                    min-height:18px;
                                ">
                                    {{ $diff['old'] ?: '—' }}
                                </div>
                            </div>

                            <div>
                                <div style="
                                    font-size:11px;
                                    font-weight:700;
                                    text-transform:uppercase;
                                    letter-spacing:.04em;
                                    color:#93a4c3;
                                    margin-bottom:5px;
                                ">
                                    Novo iz OCR-a
                                </div>

                                <div style="
                                    padding:9px 10px;
                                    border-radius:10px;
                                    background:#1e293b;
                                    border:1px solid rgba(255,255,255,0.06);
                                    color:#ffffff;
                                    font-size:14px;
                                    font-weight:600;
                                    min-height:18px;
                                ">
                                    {{ $diff['new'] ?: '—' }}
                                </div>
                            </div>
                        </div>

                        <label style="
                            display:flex;
                            align-items:center;
                            gap:8px;
                            font-size:14px;
                            font-weight:600;
                            color:#e2e8f0;
                        ">
                            <input
                                type="checkbox"
                                wire:model.live="ocrDiffs.{{ $field }}.replace"
                                style="width:15px; height:15px; accent-color:#f59e0b;"
                            >
                            Zamijeni ovo polje
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <style>
            @media (max-width: 1100px) {
                [style*="grid-template-columns:repeat(2, minmax(0, 1fr))"] {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>
    @endif

    {{ $this->form }}
</x-filament-panels::page>