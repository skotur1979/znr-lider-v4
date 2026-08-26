<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="robots"
        content="noindex,nofollow,noarchive"
    >

    <title>
        Radna oprema - {{ $machine->name }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
            color: #111827;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 16px;
            padding: 22px;
            background: #111827;
            border-radius: 16px;
            color: #ffffff;
        }

        .header-small {
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            opacity: .75;
        }

        .header h1 {
            margin: 0;
            font-size: 25px;
            line-height: 1.25;
        }

        .card {
            margin-bottom: 16px;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow:
                0 1px 3px rgba(0, 0, 0, .06);
        }

        .card h2 {
            margin:
                0
                0
                15px;
            font-size: 18px;
        }

        .row {
            display: grid;
            grid-template-columns:
                minmax(130px, 42%)
                1fr;

            padding: 11px 0;
            border-bottom: 1px solid #f3f4f6;
            gap: 12px;
        }

        .row:last-child {
            border-bottom: 0;
        }

        .label {
            color: #6b7280;
            font-size: 14px;
        }

        .value {
            font-size: 14px;
            font-weight: 600;
            word-break: break-word;
        }

        .expiry {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .valid {
            background: #dcfce7;
            color: #166534;
        }

        .soon {
            background: #fef3c7;
            color: #92400e;
        }

        .expired {
            background: #fee2e2;
            color: #991b1b;
        }

        .attachment {
            display: block;
            margin-top: 9px;
            padding: 13px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            color: #1d4ed8;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .attachment:hover {
            background: #f9fafb;
        }

        .notice {
            color: #6b7280;
            font-size: 12px;
            line-height: 1.5;
            text-align: center;
        }

        @media (max-width: 520px) {
            body {
                padding: 12px;
            }

            .header,
            .card {
                border-radius: 12px;
            }

            .row {
                grid-template-columns: 1fr;
                gap: 4px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <div class="header-small">
            ZNR LIDER · Radna oprema
        </div>

        <h1>
            {{ $machine->name }}
        </h1>
    </div>

    <div class="card">

        <h2>Podaci o radnoj opremi</h2>

        <div class="row">
            <div class="label">
                Naziv
            </div>

            <div class="value">
                {{ $machine->name }}
            </div>
        </div>

        @if(filled($machine->manufacturer))
            <div class="row">
                <div class="label">
                    Proizvođač
                </div>

                <div class="value">
                    {{ $machine->manufacturer }}
                </div>
            </div>
        @endif

        @if(filled($machine->factory_number))
            <div class="row">
                <div class="label">
                    Tvornički broj
                </div>

                <div class="value">
                    {{ $machine->factory_number }}
                </div>
            </div>
        @endif

        @if(filled($machine->inventory_number))
            <div class="row">
                <div class="label">
                    Inventarni broj
                </div>

                <div class="value">
                    {{ $machine->inventory_number }}
                </div>
            </div>
        @endif

        @if(filled($machine->location))
            <div class="row">
                <div class="label">
                    Lokacija
                </div>

                <div class="value">
                    {{ $machine->location }}
                </div>
            </div>
        @endif

        @if($machine->examination_valid_from)
            <div class="row">
                <div class="label">
                    Datum ispitivanja
                </div>

                <div class="value">
                    {{ $machine->examination_valid_from->format('d.m.Y.') }}
                </div>
            </div>
        @endif

        @if($machine->examination_valid_until)

            @php
                $today = now()->startOfDay();

                $until =
                    $machine
                        ->examination_valid_until
                        ->copy()
                        ->startOfDay();

                if ($until->lt($today)) {
                    $expiryClass = 'expired';
                    $expiryText = 'Isteklo';
                } elseif (
                    $until->lte(
                        $today->copy()->addDays(30)
                    )
                ) {
                    $expiryClass = 'soon';
                    $expiryText = 'Uskoro ističe';
                } else {
                    $expiryClass = 'valid';
                    $expiryText = 'Važeće';
                }
            @endphp

            <div class="row">
                <div class="label">
                    Ispitivanje vrijedi do
                </div>

                <div class="value">
                    {{ $until->format('d.m.Y.') }}

                    <br><br>

                    <span
                        class="expiry {{ $expiryClass }}"
                    >
                        {{ $expiryText }}
                    </span>
                </div>
            </div>

        @endif

        @if(filled($machine->examined_by))
            <div class="row">
                <div class="label">
                    Ispitao
                </div>

                <div class="value">
                    {{ $machine->examined_by }}
                </div>
            </div>
        @endif

        @if(filled($machine->report_number))
            <div class="row">
                <div class="label">
                    Broj izvještaja
                </div>

                <div class="value">
                    {{ $machine->report_number }}
                </div>
            </div>
        @endif

    </div>

    @if(
        is_array($machine->pdf)
        && count($machine->pdf) > 0
    )

        <div class="card">

            <h2>Prilozi</h2>

            @foreach(
                array_values($machine->pdf)
                as $index => $file
            )

                <a
                    class="attachment"
                    href="{{ route(
                        'public.machine.attachment',
                        [
                            'token' => $qrCode->token,
                            'index' => $index,
                        ]
                    ) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    📎
                    {{ $index + 1 }}.
                    {{ basename($file) }}
                </a>

            @endforeach

        </div>

    @endif

    <div class="notice">
        Podaci su dostupni samo za ovu radnu opremu.
        <br>
        Ovaj prikaz ne omogućuje uređivanje podataka.
    </div>

</div>

</body>
</html>