<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        {{ $learningMaterial->title }}
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

        .page {
            width: 100%;
            max-width: 680px;

            margin: 0 auto;
        }

        .header {
            margin-bottom: 14px;

            padding: 20px;

            border-radius: 14px;

            background: #111827;
            color: #ffffff;
        }

        .header-small {
            margin-bottom: 7px;

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .5px;
        }

        .header h1 {
            margin: 0;

            font-size: 23px;
            line-height: 1.2;
        }

        .header-category {
            margin-top: 8px;

            color: #d1d5db;

            font-size: 14px;
        }

        .scope {
            display: inline-flex;

            margin-top: 10px;

            padding:
                5px
                9px;

            border-radius: 999px;

            background: #374151;
            color: #ffffff;

            font-size: 11px;
            font-weight: 800;
        }

        .card {
            margin-bottom: 14px;

            padding: 18px;

            background: #ffffff;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 14px;

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

        .description {
            color: #374151;

            font-size: 14px;
            line-height: 1.65;

            white-space: pre-line;
        }

        .link,
        .attachment {
            display: block;

            margin-top: 8px;

            padding:
                12px
                14px;

            border:
                1px
                solid
                #d1d5db;

            border-radius: 9px;

            background: #f9fafb;

            color: #1d4ed8;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;

            word-break: break-word;
        }

        .link:first-of-type,
        .attachment:first-of-type {
            margin-top: 0;
        }

        .empty {
            color: #6b7280;

            font-size: 13px;
        }

        .footer {
            margin-top: 16px;

            color: #6b7280;

            font-size: 11px;

            text-align: center;
        }

    </style>

</head>

<body>

@php

    $links =
        $learningMaterial
            ->getAllLinks();

    $files =
        array_values(
            $learningMaterial
                ->getAllFiles()
        );

@endphp


<div class="page">

    <div class="header">

        <div class="header-small">
            ZNR LIDER · EDUKACIJSKI CENTAR
        </div>

        <h1>
            {{ $learningMaterial->title }}
        </h1>

        @if(
            filled(
                $learningMaterial
                    ->category
                    ?->name
            )
        )

            <div class="header-category">
                {{
                    $learningMaterial
                        ->category
                        ->name
                }}
            </div>

        @endif


        <div class="scope">

            {{
                $learningMaterial->is_global
                    ? 'Globalni materijal'
                    : 'Materijal organizacije'
            }}

        </div>

    </div>


    @if(
        filled(
            $learningMaterial->description
        )
    )

        <div class="card">

            <h2>
                Opis
            </h2>

            <div class="description">
                {{ $learningMaterial->description }}
            </div>

        </div>

    @endif


    <div class="card">

        <h2>
            Linkovi
        </h2>

        @forelse(
            $links
            as $link
        )

            <a
                href="{{ $link['url'] }}"
                target="_blank"
                rel="noopener noreferrer"
                class="link"
            >
                🔗
                {{
                    $link['label']
                    ?? 'Otvori link'
                }}
            </a>

        @empty

            <div class="empty">
                Ovaj materijal nema dostupnih linkova.
            </div>

        @endforelse

    </div>


    <div class="card">

        <h2>
            Dokumenti i materijali
        </h2>

        @forelse(
            $files
            as $index => $file
        )

            <a
                href="{{
                    route(
                        'public.learning-material.attachment',
                        [
                            'token' =>
                                $qrCode->token,

                            'index' =>
                                $index,
                        ]
                    )
                }}"
                target="_blank"
                rel="noopener noreferrer"
                class="attachment"
            >
                📎
                {{ $index + 1 }}.
                {{ basename($file) }}
            </a>

        @empty

            <div class="empty">
                Ovaj materijal nema dostupnih dokumenata.
            </div>

        @endforelse

    </div>


    <div class="footer">
        Edukacijski materijal dostupan putem ZNR LIDER QR koda.
        <br>
        ZNR LIDER
    </div>

</div>

</body>

</html>