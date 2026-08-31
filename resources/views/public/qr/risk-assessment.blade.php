<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Procjena rizika -
        {{ $riskAssessment->broj_procjene }}
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            padding:
                24px
                15px
                50px;

            background: #f3f4f6;

            color: #111827;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .container {
            width: 100%;
            max-width: 700px;

            margin: 0 auto;
        }

        .header {
            margin-bottom: 16px;

            padding: 20px;

            border-radius: 14px;

            background: #111827;
            color: #ffffff;
        }

        .header-small {
            margin-bottom: 7px;

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .6px;
        }

        .header h1 {
            margin: 0;

            font-size: 24px;

            line-height: 1.15;
        }

        .header-company {
            margin-top: 8px;

            color: #d1d5db;

            font-size: 14px;
        }

        .card {
            margin-bottom: 14px;

            padding: 18px;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0
                1px
                3px
                rgba(0, 0, 0, .05);
        }

        .card h2 {
            margin:
                0
                0
                14px;

            font-size: 17px;
        }

        .row {
            display: grid;

            grid-template-columns:
                42%
                58%;

            padding:
                10px
                0;

            border-bottom:
                1px
                solid
                #f3f4f6;
        }

        .row:last-child {
            border-bottom: 0;
        }

        .label {
            color: #6b7280;

            font-size: 13px;
        }

        .value {
            font-size: 13px;
            font-weight: 700;

            word-break: break-word;
        }

        .attachment {
            display: block;

            margin-bottom: 10px;

            padding:
                13px
                14px;

            border:
                1px
                solid
                #d1d5db;

            border-radius: 9px;

            background: #f9fafb;

            color: #1d4ed8;

            font-size: 14px;
            font-weight: 700;

            text-decoration: none;

            word-break: break-word;
        }

        .attachment:last-child {
            margin-bottom: 0;
        }

        .revision {
            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 12px;

            padding:
                9px
                0;

            border-bottom:
                1px
                solid
                #f3f4f6;

            font-size: 13px;
        }

        .revision:last-child {
            border-bottom: 0;
        }

        .empty {
            color: #6b7280;

            font-size: 13px;
        }

        .footer {
            margin-top: 18px;

            color: #6b7280;

            font-size: 11px;

            text-align: center;
        }

        @media (max-width: 520px) {

            .row {
                grid-template-columns: 1fr;

                gap: 5px;
            }

            .revision {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 21px;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="header">

        <div class="header-small">
            ZNR LIDER · PROCJENA RIZIKA
        </div>

        <h1>
            Procjena rizika
            {{ $riskAssessment->broj_procjene }}
        </h1>

        <div class="header-company">
            {{ $riskAssessment->tvrtka }}
        </div>

    </div>


    <div class="card">

        <h2>
            Podaci o procjeni rizika
        </h2>

        <div class="row">

            <div class="label">
                Tvrtka
            </div>

            <div class="value">
                {{ $riskAssessment->tvrtka }}
            </div>

        </div>


        @if(filled($riskAssessment->adresa_tvrtke))

            <div class="row">

                <div class="label">
                    Adresa
                </div>

                <div class="value">
                    {{ $riskAssessment->adresa_tvrtke }}
                </div>

            </div>

        @endif


        <div class="row">

            <div class="label">
                Broj procjene
            </div>

            <div class="value">
                {{ $riskAssessment->broj_procjene }}
            </div>

        </div>


        <div class="row">

            <div class="label">
                Datum izrade
            </div>

            <div class="value">

                {{
                    $riskAssessment
                        ->datum_izrade
                        ?->format('d.m.Y.')
                        ?? '—'
                }}

            </div>

        </div>


        <div class="row">

            <div class="label">
                Vrsta procjene
            </div>

            <div class="value">
                {{ $riskAssessment->vrsta_procjene }}
            </div>

        </div>

    </div>


    @if(
        $riskAssessment->revisions
        && $riskAssessment->revisions->isNotEmpty()
    )

        <div class="card">

            <h2>
                Revizije
            </h2>

            @foreach(
                $riskAssessment->revisions
                as $revision
            )

                <div class="revision">

                    <div>

                        <strong>
                            Revizija
                        </strong>

                        <br>

                        {{
                            $revision->revizija_broj
                            ?: '—'
                        }}

                    </div>

                    <div>

                        <strong>
                            Datum
                        </strong>

                        <br>

                        {{
                            $revision->datum_izrade
                                ? \Illuminate\Support\Carbon::parse(
                                    $revision->datum_izrade
                                )->format('d.m.Y.')
                                : '—'
                        }}

                    </div>

                </div>

            @endforeach

        </div>

    @endif


    <div class="card">

        <h2>
            Dokumentacija procjene rizika
        </h2>

        @forelse(
            $riskAssessment->attachments
            as $index => $attachment
        )

            <a
                href="{{ route(
                    'public.risk-assessment.attachment',
                    [
                        'token' =>
                            $qrCode->token,

                        'index' =>
                            $index,
                    ]
                ) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="attachment"
            >

                📎

                {{
                    filled($attachment->naziv)
                        ? $attachment->naziv
                        : basename(
                            $attachment->file_path
                        )
                }}

            </a>

        @empty

            <div class="empty">
                Za ovu procjenu rizika trenutačno
                nema dostupnih dokumenata.
            </div>

        @endforelse

    </div>


    <div class="footer">
        Podaci su dostupni samo za ovu procjenu rizika.
        <br>
        ZNR LIDER
    </div>

</div>

</body>

</html>