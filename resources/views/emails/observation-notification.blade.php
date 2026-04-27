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

    $title = $mode === 'updated' ? 'Izmjena zapažanja' : 'Novo zapažanje';
@endphp

<div style="font-family: Arial, sans-serif; background:#f4f6f8; padding:24px; color:#111827;">
    <div style="max-width:820px; margin:0 auto; background:#ffffff; border-radius:18px; overflow:hidden; border:1px solid #e5e7eb;">
        <div style="background:#111827; color:#ffffff; padding:24px 28px;">
            <div style="font-size:13px; letter-spacing:1px; color:#f59e0b; font-weight:700;">ZNR LIDER</div>
            <h1 style="margin:8px 0 0; font-size:26px;">{{ $title }}</h1>
            <p style="margin:8px 0 0; color:#cbd5e1;">
                {{ $mode === 'updated' ? 'Automatska obavijest o promjeni zapažanja' : 'Automatska operativna obavijest' }}
            </p>
        </div>

        <div style="padding:26px 28px;">
            <p style="font-size:15px; line-height:1.6; margin-top:0;">
                Poštovani,<br>
                @if ($mode === 'updated')
                    zapažanje u sustavu ZNR LIDER je izmijenjeno. U nastavku su ažurirani podaci.
                @else
                    u sustavu ZNR LIDER kreirano je novo zapažanje koje zahtijeva pregled i eventualno poduzimanje mjera.
                @endif
            </p>

            <div style="background:#fef3c7; border:1px solid #f59e0b; border-radius:14px; padding:18px; margin:22px 0;">
                <div style="font-size:13px; font-weight:800; color:#92400e; text-transform:uppercase;">Pametni sažetak</div>
                <p style="margin:8px 0 0; font-size:15px; line-height:1.6;">
                    Zapažanje prioriteta <strong>{{ $priorityLabel }}</strong> na lokaciji
                    <strong>{{ $observation->location }}</strong>.
                    Odgovorna osoba: <strong>{{ $observation->responsible ?: 'nije dodijeljena' }}</strong>.
                    Rok provedbe:
                    <strong>{{ $observation->target_date ? $observation->target_date->format('d.m.Y.') : 'nije definiran' }}</strong>.
                </p>
            </div>

            @if (! empty($imagePath) && file_exists($imagePath))
                <h2 style="font-size:19px; margin:26px 0 12px;">Slika zapažanja</h2>

                <div style="margin-bottom:22px; border:1px solid #e5e7eb; border-radius:14px; padding:12px; background:#f9fafb;">
                    <img
                        src="{{ $message->embed($imagePath) }}"
                        alt="Slika zapažanja"
                        style="max-width:100%; height:auto; border-radius:10px; display:block;"
                    >
                </div>
            @endif

            <h2 style="font-size:19px; margin:26px 0 12px;">Podaci zapažanja</h2>

            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700; width:210px;">Datum</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $observation->incident_date?->format('d.m.Y.') }}</td></tr>
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700;">Vrsta zapažanja</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $typeLabel }}</td></tr>
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700;">Prioritet</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $priorityLabel }}</td></tr>
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700;">Lokacija</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $observation->location }}</td></tr>
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700;">Opis zapažanja</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $observation->item }}</td></tr>
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700;">Vrsta opasnosti</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $observation->potential_incident_type }}</td></tr>
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700;">Potrebna radnja</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $observation->action }}</td></tr>
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700;">Odgovorna osoba</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $observation->responsible }}</td></tr>
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700;">Rok za provedbu</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $observation->target_date?->format('d.m.Y.') }}</td></tr>
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700;">Status</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $statusLabel }}</td></tr>
                <tr><td style="padding:10px; border-bottom:1px solid #e5e7eb; font-weight:700;">Komentar</td><td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $observation->comments }}</td></tr>
            </table>

            <div style="margin-top:28px; padding:16px; background:#f9fafb; border-radius:14px; border:1px solid #e5e7eb;">
                Ovaj email je automatski generiran iz sustava ZNR LIDER.
                Ako podaci odstupaju od očekivanih, provjerite zapažanje unutar aplikacije.
            </div>
        </div>
    </div>
</div>