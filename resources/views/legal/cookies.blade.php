<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Politika kolačića - ZNR Lider</title>
</head>
<body style="font-family: Arial, sans-serif; max-width:900px; margin:40px auto; line-height:1.6; color:#111827;">

<h1>Politika kolačića</h1>

<p>
    <strong>{{ config('legal.provider_name') }}</strong><br>
    Verzija: {{ config('legal.cookies_version') }}<br>
    Stupa na snagu: {{ config('legal.effective_date') }}
</p>

<h2>1. Što su kolačići</h2>
<p>
    Kolačići su male tekstualne datoteke koje se pohranjuju na uređaj korisnika
    prilikom korištenja web stranice ili aplikacije. Koriste se radi ispravnog rada
    sustava, sigurnosti, prijave korisnika i poboljšanja korisničkog iskustva.
</p>

<h2>2. Koje kolačiće koristi ZNR Lider</h2>
<p>
    ZNR Lider trenutno koristi samo nužne kolačiće potrebne za prijavu, sigurnost
    sesije, zaštitu korisničkog računa i ispravan rad aplikacije.
</p>

<h2>3. Nužni kolačići</h2>
<p>
    Nužni kolačići omogućuju osnovne funkcionalnosti aplikacije, uključujući:
</p>

<ul>
    <li>prijavu korisnika u sustav,</li>
    <li>održavanje korisničke sesije,</li>
    <li>zaštitu od CSRF napada,</li>
    <li>sigurnost korisničkog računa,</li>
    <li>ispravan rad obrazaca i administracijskog sučelja.</li>
</ul>

<p>
    Bez nužnih kolačića aplikacija ne može pravilno raditi.
</p>

<h2>4. Primjeri nužnih kolačića</h2>

<table style="width:100%; border-collapse:collapse; margin-top:10px;">
    <thead>
        <tr>
            <th style="border:1px solid #d1d5db; padding:8px; text-align:left; background:#f3f4f6;">Naziv kolačića</th>
            <th style="border:1px solid #d1d5db; padding:8px; text-align:left; background:#f3f4f6;">Svrha</th>
            <th style="border:1px solid #d1d5db; padding:8px; text-align:left; background:#f3f4f6;">Trajanje</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border:1px solid #d1d5db; padding:8px;">XSRF-TOKEN</td>
            <td style="border:1px solid #d1d5db; padding:8px;">Zaštita obrazaca i zahtjeva od CSRF napada.</td>
            <td style="border:1px solid #d1d5db; padding:8px;">Trajanje sesije / prema postavkama aplikacije.</td>
        </tr>
        <tr>
            <td style="border:1px solid #d1d5db; padding:8px;">znr_lider_session ili laravel_session</td>
            <td style="border:1px solid #d1d5db; padding:8px;">Održavanje prijave korisnika i sigurnosti sesije.</td>
            <td style="border:1px solid #d1d5db; padding:8px;">Trajanje sesije / prema postavkama aplikacije.</td>
        </tr>
    </tbody>
</table>

<h2>5. Analitički kolačići</h2>
<p>
    ZNR Lider trenutno ne koristi analitičke kolačiće.
</p>
<p>
    Ako se u budućnosti uključe analitički alati, primjerice Google Analytics,
    Matomo, Microsoft Clarity ili slični alati, korisnik će biti jasno obaviješten.
    Kada je potrebna privola, korisniku će biti omogućeno prihvaćanje ili odbijanje
    takvih kolačića.
</p>

<h2>6. Marketinški kolačići</h2>
<p>
    ZNR Lider trenutno ne koristi marketinške kolačiće niti kolačiće za ciljano
    oglašavanje.
</p>

<h2>7. Kolačići trećih strana</h2>
<p>
    Ako aplikacija u budućnosti bude koristila vanjske servise, video sadržaje,
    karte, analitiku, sustave podrške ili druge integracije, ti servisi mogu koristiti
    vlastite kolačiće.
</p>
<p>
    ZNR Lider nije odgovoran za kolačiće i pravila privatnosti trećih strana.
    Korisnik se treba upoznati s pravilima tih vanjskih servisa.
</p>

<h2>8. Upravljanje kolačićima</h2>
<p>
    Korisnik može upravljati kolačićima putem postavki svojeg internetskog preglednika.
    Preglednik omogućuje brisanje, blokiranje ili ograničavanje kolačića.
</p>
<p>
    Ako korisnik onemogući nužne kolačiće, aplikacija se možda neće moći pravilno
    koristiti, uključujući prijavu, rad obrazaca i sigurnosne funkcije.
</p>

<h2>9. Promjene Politike kolačića</h2>
<p>
    ZNR Lider može povremeno ažurirati ovu Politiku kolačića radi tehničkih promjena,
    zakonskih obveza ili promjena u načinu korištenja kolačića.
</p>
<p>
    Nova verzija bit će objavljena u sustavu ili na web stranici.
</p>

<h2>10. Kontakt</h2>
<p>
    Za sva pitanja vezana uz kolačiće korisnik se može obratiti na:
</p>

<p>
    {{ config('legal.provider_email') }}<br>
    {{ config('legal.provider_phone') }}
</p>

<hr>

<p style="font-size:13px; color:#6b7280;">
    {{ config('legal.provider_name') }} · OIB {{ config('legal.provider_oib') }} ·
    {{ config('legal.provider_address') }} · {{ config('legal.provider_email') }}
</p>

</body>
</html>