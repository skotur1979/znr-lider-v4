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

    $title = $mode === 'updated' ? 'Izmjena zapažanja' : 'Novo zapažanje';
@endphp

<div style="font-family: Arial, sans-serif; background:#f4f6f8; padding:24px; color:#111827;">
    <div style="max-width:820px; margin:0 auto; background:#ffffff; border-radius:18px; overflow:hidden; border:1px solid #e5e7eb;">
        
        <!-- HEADER -->
        <div style="background:#111827; color:#ffffff; padding:24px 28px;">
            <div style="font-size:13px; letter-spacing:1px; color:#f59e0b; font-weight:700;">ZNR LIDER</div>
            <h1 style="margin:8px 0 0; font-size:26px;">{{ $title }}</h1>
            <p style="margin:8px 0 0; color:#cbd5e1;">
                {{ $mode === 'updated' ? 'Automatska obavijest o promjeni zapažanja' : 'Automatska operativna obavijest' }}
            </p>
        </div>

        <div style="padding:26px 28px;">

            <!-- UVOD -->
            <p style="font-size:15px; line-height:1.6; margin-top:0;">
                Poštovani,<br>
                @if ($mode === 'updated')
                    zapažanje u sustavu ZNR LIDER je izmijenjeno.
                @else
                    kreirano je novo zapažanje koje zahtijeva pregled i poduzimanje mjera.
                @endif
            </p>

            <!-- SAŽETAK -->
            <div style="background:#fef3c7; border:1px solid #f59e0b; border-radius:14px; padding:18px; margin:22px 0;">
                <div style="font-size:13px; font-weight:800; color:#92400e; text-transform:uppercase;">Pametni sažetak</div>
                <p style="margin:8px 0 0; font-size:15px;">
                    Prioritet <strong>{{ $priorityLabel }}</strong> na lokaciji <strong>{{ $observation->location }}</strong>.
                </p>
            </div>

            <!-- PRIORITET BLOK -->
            <div style="{{ $priorityStyle }} border-radius:16px; padding:20px; margin:22px 0; text-align:center;">
                <div style="font-size:13px; font-weight:900; text-transform:uppercase;">
                    PRIORITET ZAPAŽANJA
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

            <!-- SLIKA -->
            @if (! empty($imagePath) && file_exists($imagePath))
                <h2 style="font-size:19px; margin:26px 0 12px;">Slika zapažanja</h2>

                <div style="margin-bottom:22px; border:1px solid #e5e7eb; border-radius:14px; padding:12px;">
                    <img
                        src="{{ $message->embed($imagePath) }}"
                        style="max-width:100%; border-radius:10px;"
                    >
                </div>
            @endif

            <!-- TABLICA -->
            <h2 style="font-size:19px; margin:26px 0 12px;">Podaci zapažanja</h2>

            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr>
                    <td style="padding:10px; font-weight:700;">Datum</td>
                    <td style="padding:10px;">{{ $observation->incident_date?->format('d.m.Y.') }}</td>
                </tr>

                <tr>
                    <td style="padding:10px; font-weight:700;">Vrsta</td>
                    <td style="padding:10px;">{{ $typeLabel }}</td>
                </tr>

                <!-- OVDJE JE FIX -->
                <tr>
                    <td style="padding:10px; font-weight:700;">Prioritet</td>
                    <td style="padding:10px; text-align:center;">
                        <span style="{{ $priorityStyle }} 
                            display:inline-flex; 
                            align-items:center; 
                            justify-content:center; 
                            gap:6px;
                            padding:10px 18px; 
                            border-radius:999px; 
                            font-weight:900;">
                            
                            <span>{{ $priorityIcon }}</span>
                            <span>{{ $priorityLabel }}</span>
                        </span>
                    </td>
                </tr>

                <tr>
                    <td style="padding:10px; font-weight:700;">Lokacija</td>
                    <td style="padding:10px;">{{ $observation->location }}</td>
                </tr>

                <tr>
                    <td style="padding:10px; font-weight:700;">Opis</td>
                    <td style="padding:10px;">{{ $observation->item }}</td>
                </tr>

                <tr>
                    <td style="padding:10px; font-weight:700;">Opasnost</td>
                    <td style="padding:10px;">{{ $observation->potential_incident_type }}</td>
                </tr>

                <tr>
                    <td style="padding:10px; font-weight:700;">Radnja</td>
                    <td style="padding:10px;">{{ $observation->action }}</td>
                </tr>

                <tr>
                    <td style="padding:10px; font-weight:700;">Odgovorna osoba</td>
                    <td style="padding:10px;">{{ $observation->responsible }}</td>
                </tr>

                <tr>
                    <td style="padding:10px; font-weight:700;">Rok</td>
                    <td style="padding:10px;">{{ $observation->target_date?->format('d.m.Y.') }}</td>
                </tr>

                <tr>
                    <td style="padding:10px; font-weight:700;">Status</td>
                    <td style="padding:10px;">{{ $statusLabel }}</td>
                </tr>

                <tr>
                    <td style="padding:10px; font-weight:700;">Komentar</td>
                    <td style="padding:10px;">{{ $observation->comments }}</td>
                </tr>
            </table>

            <!-- FOOTER -->
            <div style="margin-top:28px; padding:16px; background:#f9fafb; border-radius:14px;">
                Automatski generirano iz ZNR LIDER sustava.
            </div>

        </div>
    </div>
</div>