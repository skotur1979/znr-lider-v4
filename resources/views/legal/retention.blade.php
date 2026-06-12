<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Politika zadržavanja podataka - ZNR Lider</title>
</head>
<body style="font-family: Arial, sans-serif; max-width:900px; margin:40px auto; line-height:1.6; color:#111827;">

<h1>Politika zadržavanja i brisanja podataka</h1>

<p>
    <strong>{{ config('legal.provider_name') }}</strong><br>
    Verzija: {{ config('legal.retention_version') }}
</p>

<h2>1. Aktivni korisnici</h2>

<p>
    Podaci se čuvaju za vrijeme trajanja pretplate i korištenja sustava.
</p>

<h2>2. Prestanak korištenja</h2>

<p>
    Nakon prestanka korištenja sustava podaci se mogu čuvati još
    {{ config('legal.data_retention_after_cancellation_days') }}
    dana.
</p>

<p>
    Razlog čuvanja je:
</p>

<ul>
    <li>omogućavanje izvoza podataka,</li>
    <li>povrat pristupa u slučaju pogreške,</li>
    <li>rješavanje otvorenih obveza.</li>
</ul>

<h2>3. Brisanje podataka</h2>

<p>
    Nakon isteka razdoblja čuvanja podaci se mogu:
</p>

<ul>
    <li>trajno obrisati,</li>
    <li>anonimizirati,</li>
    <li>arhivirati ako postoji zakonita osnova.</li>
</ul>

<h2>4. Zakonske obveze</h2>

<p>
    Podaci koje zakon nalaže čuvati dulje mogu se zadržati
    u propisanim rokovima.
</p>

<h2>5. Kontakt</h2>

<p>
    {{ config('legal.provider_email') }}
</p>

<hr>

<p style="font-size:13px; color:#6b7280;">
    {{ config('legal.provider_name') }}
</p>

</body>
</html>