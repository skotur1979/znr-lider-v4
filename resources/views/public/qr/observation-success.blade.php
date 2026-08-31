<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="robots"
        content="noindex,nofollow,noarchive"
    >

    <title>
        Zapažanje poslano
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

        .card {
            width: 100%;
            max-width: 560px;

            margin:
                60px
                auto;

            padding: 36px;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 18px;

            background: #ffffff;

            text-align: center;

            box-shadow:
                0
                2px
                8px
                rgba(0, 0, 0, .06);
        }

        .icon {
            display: flex;

            width: 64px;
            height: 64px;

            margin:
                0
                auto
                20px;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #dcfce7;
            color: #166534;

            font-size: 34px;
            font-weight: 900;
        }

        h1 {
            margin:
                0
                0
                12px;

            font-size: 25px;
        }

        p {
            margin:
                0
                0
                24px;

            color: #6b7280;

            line-height: 1.6;
        }

        a {
            display: inline-block;

            padding:
                12px
                18px;

            border-radius: 9px;

            background: #111827;
            color: #ffffff;

            text-decoration: none;

            font-weight: 700;
        }

    </style>

</head>

<body>

<div class="card">

    <div class="icon">
        ✓
    </div>

    <h1>
        Zapažanje je uspješno poslano
    </h1>

    <p>
        Hvala što doprinosite sigurnijem
        radnom mjestu.
    </p>

    <a
        href="{{ route(
            'public.observation.show',
            [
                'token' =>
                    $qrCode->token,
            ]
        ) }}"
    >
        Prijavi novo zapažanje
    </a>

</div>

</body>

</html>