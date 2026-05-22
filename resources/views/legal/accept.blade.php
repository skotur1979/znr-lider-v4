<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Prihvaćanje uvjeta - ZNR Lider</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 40px;
            color: #111827;
        }

        .container {
            max-width: 760px;
            margin: 0 auto;
            background: white;
            border-radius: 18px;
            padding: 34px;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 14px;
            font-size: 34px;
            line-height: 1.15;
            color: #111827;
        }

        p {
            line-height: 1.6;
        }

        .warning-box {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            padding: 16px;
            border-radius: 12px;
            margin: 24px 0;
            color: #9a3412;
            line-height: 1.6;
        }

        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 16px;
            border-radius: 12px;
            margin: 24px 0;
            color: #1e3a8a;
            line-height: 1.6;
        }

        .error-box {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .checkbox-group {
            margin-bottom: 18px;
        }

        label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
            font-size: 15px;
        }

        input[type="checkbox"] {
            margin-top: 3px;
            width: 16px;
            height: 16px;
        }

        a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        a:hover {
            text-decoration: underline;
        }

        .pdf-links {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .button {
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            transition: .2s ease;
            box-shadow: 0 4px 14px rgba(245,158,11,.25);
        }

        .button:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .footer-note {
            margin-top: 28px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
        }

        .logo {
            font-size: 15px;
            font-weight: bold;
            color: #f59e0b;
            margin-bottom: 20px;
            letter-spacing: .4px;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="logo">
        ZNR LIDER
    </div>

    <h1>
        Prije nastavka korištenja sustava
    </h1>

    <p>
        Za korištenje aplikacije ZNR Lider potrebno je prihvatiti
        Uvjete korištenja i Pravila privatnosti.
    </p>

    <div class="warning-box">
        <strong>Važno:</strong><br><br>

        Korisnik aplikacije odgovoran je za zakonitost unosa podataka
        radnika i drugih osobnih podataka te potvrđuje da ima pravnu osnovu
        za obradu podataka sukladno važećim GDPR i drugim zakonskim propisima.

        <br><br>

        ZNR Lider djeluje kao tehnički sustav za obradu, pohranu i vođenje evidencija.
    </div>

    <div class="info-box">
        Prihvaćanje uvjeta evidentira se radi sigurnosti sustava,
        audit evidencije i dokazivanja prihvaćanja pravnih dokumenata.
    </div>

    @if ($errors->any())
        <div class="error-box">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('legal.accept.store') }}">
        @csrf

        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="accepted_terms" value="1" required>

                <span>
                    Prihvaćam
                    <a href="{{ route('legal.terms') }}" target="_blank">
                        Uvjete korištenja
                    </a>
                    verzija {{ $termsVersion }}.
                </span>
            </label>
        </div>

        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="accepted_privacy" value="1" required>

                <span>
                    Prihvaćam
                    <a href="{{ route('legal.privacy') }}" target="_blank">
                        Pravila privatnosti
                    </a>
                    verzija {{ $privacyVersion }}.
                </span>
            </label>
        </div>

        <div class="checkbox-group" style="margin-bottom: 28px;">
            <label>
                <input type="checkbox" name="newsletter_opt_in" value="1">

                <span>
                    Želim primati novosti, sigurnosne obavijesti i informacije
                    vezane uz sustav ZNR Lider putem e-maila.
                </span>
            </label>
        </div>

        <button type="submit" class="button">
            Prihvati i nastavi
        </button>

        <div class="pdf-links">
            <strong>PDF dokumenti:</strong><br><br>

            <a href="{{ route('legal.terms.pdf') }}" target="_blank">
                📄 Uvjeti korištenja PDF
            </a>

            <br><br>

            <a href="{{ route('legal.privacy.pdf') }}" target="_blank">
                📄 Pravila privatnosti PDF
            </a>
        </div>

        <div class="footer-note">
            Datum i vrijeme prihvaćanja, IP adresa i tehnički podaci preglednika
            mogu biti evidentirani radi sigurnosti sustava, zaštite podataka
            i audit evidencije.
        </div>
    </form>

</div>

</body>
</html>