<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Često postavljana pitanja - ZNR Lider</title>
</head>
<body style="font-family: Arial, sans-serif; max-width:900px; margin:40px auto; line-height:1.6; color:#111827;">

<h1>Često postavljana pitanja</h1>

<p>
    <strong>{{ config('legal.provider_name') }}</strong><br>
    Verzija: {{ config('legal.faq_version') }}<br>
    Stupa na snagu: {{ config('legal.effective_date') }}
</p>

<h2>1. Što je ZNR Lider?</h2>
<p>
    ZNR Lider je poslovna aplikacija za vođenje evidencija, rokova, dokumenata,
    radnih zadataka i aktivnosti povezanih sa zaštitom na radu, zaštitom od požara,
    zaštitom okoliša i povezanim poslovnim procesima.
</p>

<h2>2. Kome je ZNR Lider namijenjen?</h2>
<p>
    Aplikacija je namijenjena malim i srednjim poslovnim subjektima, obrtima,
    stručnjacima zaštite na radu, ovlaštenicima poslodavca, organizacijama i svima
    koji žele jednostavnije voditi evidencije i rokove.
</p>

<h2>3. Koliko košta ZNR Lider?</h2>
<p>
    Redovna cijena korištenja iznosi {{ config('legal.monthly_price') }} mjesečno
    ili {{ config('legal.yearly_price') }} godišnje, osim ako je drugačije definirano
    ponudom, ugovorom ili posebnim dogovorom.
</p>

<h2>4. Postoji li probni period?</h2>
<p>
    Da. Probni period traje {{ config('legal.trial_days') }} dana, bez kartice,
    bez automatske naplate i bez obveze nastavka korištenja.
</p>

<h2>5. Kako se plaća pretplata?</h2>
<p>
    Plaćanje se obavlja prema izdanoj ponudi ili računu, u pravilu uplatom na
    transakcijski račun. ZNR Lider trenutno ne koristi automatsku kartičnu naplatu.
</p>

<h2>6. Mogu li otkazati korištenje?</h2>
<p>
    Da. Korisnik može otkazati korištenje sukladno Općim uvjetima korištenja,
    ponudi ili posebnom dogovoru. Otkaz u pravilu vrijedi od kraja tekućeg
    plaćenog razdoblja.
</p>

<h2>7. Što se događa s podacima nakon otkazivanja?</h2>
<p>
    Nakon prestanka korištenja podaci se mogu čuvati još
    {{ config('legal.data_retention_after_cancellation_days') }} dana radi izvoza,
    mogućeg povrata pristupa ili rješavanja otvorenih obveza.
</p>
<p>
    Nakon toga se podaci mogu obrisati, anonimizirati ili arhivirati ako postoji
    zakonita osnova za daljnje čuvanje.
</p>

<h2>8. Mogu li izvesti svoje podatke?</h2>
<p>
    Da. Korisnik može koristiti dostupne izvoze iz aplikacije, primjerice PDF,
    Excel ili druge dostupne formate, ovisno o modulu.
</p>

<h2>9. Mogu li uvesti podatke iz Excela?</h2>
<p>
    Da. ZNR Lider podržava uvoz podataka iz Excel datoteka za određene module,
    primjerice zaposlenike, radnu opremu, vatrogasne aparate, kemikalije i druge
    evidencije, kada je takva funkcionalnost dostupna.
</p>

<h2>10. Je li ZNR Lider zamjena za stručnjaka zaštite na radu?</h2>
<p>
    Ne. ZNR Lider je softverski alat koji pomaže u vođenju evidencija, rokova,
    dokumenata i izvještaja. Korisnik je i dalje odgovoran za zakonitost, točnost
    i stručnu ispravnost podataka i postupanja.
</p>

<h2>11. Tko je odgovoran za podatke unesene u aplikaciju?</h2>
<p>
    Korisnik je odgovoran za zakonitost, točnost i ažurnost podataka koje unosi.
    ZNR Lider osigurava tehnički sustav za pohranu, prikaz i obradu podataka u
    svrhu pružanja usluge.
</p>

<h2>12. Koristi li ZNR Lider kolačiće?</h2>
<p>
    Da, ali trenutno samo nužne kolačiće potrebne za prijavu, sigurnost sesije,
    zaštitu obrazaca i ispravan rad aplikacije.
</p>

<h2>13. Koristi li ZNR Lider analitiku ili marketing kolačiće?</h2>
<p>
    Trenutno ne. Ako se u budućnosti uključe analitički ili marketinški alati koji
    zahtijevaju privolu, korisnik će biti jasno obaviješten.
</p>

<h2>14. Kako kontaktirati podršku?</h2>
<p>
    Podršku možeš kontaktirati putem e-maila:
    {{ config('legal.provider_email') }}.
</p>

<h2>15. Što ako pronađem grešku u aplikaciji?</h2>
<p>
    Grešku možeš prijaviti putem e-maila ili drugog dostupnog kanala podrške.
    Manji problemi se rješavaju u razumnom roku, a složeniji problemi prema
    tehničkim mogućnostima i prioritetu.
</p>

<h2>16. Gdje mogu pronaći pravne dokumente?</h2>
<p>
    Pravni dokumenti dostupni su kroz aplikaciju i javne poveznice:
</p>

<ul>
    <li>Uvjeti korištenja</li>
    <li>Pravila privatnosti</li>
    <li>Politika kolačića</li>
    <li>Ugovor o obradi podataka</li>
    <li>Politika sigurnosti</li>
    <li>Politika zadržavanja i brisanja podataka</li>
</ul>

<h2>17. Kontakt</h2>
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