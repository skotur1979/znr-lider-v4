<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Ugovor o obradi podataka - ZNR Lider</title>
</head>
<body style="font-family: Arial, sans-serif; max-width:900px; margin:40px auto; line-height:1.6; color:#111827;">

<h1>Ugovor o obradi podataka (DPA)</h1>

<p>
    <strong>{{ config('legal.provider_name') }}</strong><br>
    Verzija: {{ config('legal.dpa_version') }}<br>
    Stupa na snagu: {{ config('legal.effective_date') }}
</p>

<h2>1. Svrha dokumenta</h2>
<p>
    Ovaj Ugovor o obradi podataka uređuje odnos između korisnika aplikacije ZNR Lider
    kao voditelja obrade i ZNR Lidera kao izvršitelja obrade za osobne podatke koje
    korisnik unosi u sustav.
</p>

<h2>2. Uloge strana</h2>
<p>
    Korisnik određuje svrhe i sredstva obrade osobnih podataka koje unosi u aplikaciju
    te se smatra voditeljem obrade.
</p>
<p>
    ZNR Lider obrađuje podatke isključivo u svrhu pružanja, održavanja, zaštite i podrške
    aplikaciji te se u tom dijelu smatra izvršiteljem obrade.
</p>

<h2>3. Predmet obrade</h2>
<p>
    Predmet obrade su osobni i poslovni podaci koje korisnik unosi u module aplikacije,
    uključujući evidencije zaposlenika, osposobljavanja, liječničke preglede, radnu opremu,
    vatrogasne aparate, osobnu zaštitnu opremu, prvu pomoć, incidente, zapažanja,
    dokumentaciju, KPI podatke, radne zadatke i druge povezane zapise.
</p>

<h2>4. Trajanje obrade</h2>
<p>
    Obrada traje za vrijeme korištenja aplikacije ZNR Lider, odnosno do prestanka
    pretplate, brisanja korisničkog računa ili drugog prestanka ugovornog odnosa.
</p>
<p>
    Nakon prestanka korištenja podaci se mogu čuvati još
    {{ config('legal.data_retention_after_cancellation_days') }} dana radi izvoza,
    tehničkog oporavka ili rješavanja otvorenih obveza, osim ako postoji zakonita osnova
    za dulje čuvanje.
</p>

<h2>5. Vrste osobnih podataka</h2>
<p>Podaci koji se mogu obrađivati uključuju:</p>
<ul>
    <li>ime i prezime,</li>
    <li>OIB, datum rođenja i radno mjesto kada ih korisnik unese,</li>
    <li>e-mail adresu, telefon i podatke o organizaciji,</li>
    <li>podatke o osposobljavanjima, pregledima i ovlaštenjima,</li>
    <li>podatke o zaduženjima, incidentima, zapažanjima i radnim zadacima,</li>
    <li>tehničke podatke o pristupu aplikaciji,</li>
    <li>dokumente i priloge koje korisnik samostalno učita u sustav.</li>
</ul>

<h2>6. Kategorije ispitanika</h2>
<p>
    Ispitanici mogu biti korisnici aplikacije, zaposlenici korisnika, vanjski suradnici,
    osobe navedene u evidencijama, kandidati za testiranja, osobe uključene u incidente,
    zapažanja ili druge evidencije koje korisnik vodi kroz sustav.
</p>

<h2>7. Obveze korisnika kao voditelja obrade</h2>
<p>Korisnik je dužan:</p>
<ul>
    <li>osigurati zakonitu osnovu za unos i obradu osobnih podataka,</li>
    <li>ispitanike informirati o obradi podataka kada je to potrebno,</li>
    <li>unositi samo podatke koji su nužni i relevantni,</li>
    <li>održavati podatke točnima i ažurnima,</li>
    <li>upravljati korisničkim pravima pristupa unutar svoje organizacije,</li>
    <li>osigurati da njegovi korisnici čuvaju pristupne podatke,</li>
    <li>postupati po zahtjevima ispitanika kada je to primjenjivo.</li>
</ul>

<h2>8. Obveze ZNR Lidera kao izvršitelja obrade</h2>
<p>ZNR Lider se obvezuje:</p>
<ul>
    <li>obrađivati podatke samo prema uputama korisnika i u svrhu pružanja usluge,</li>
    <li>primjenjivati razumne tehničke i organizacijske mjere zaštite,</li>
    <li>ograničiti pristup podacima osobama kojima je pristup potreban za pružanje usluge,</li>
    <li>čuvati povjerljivost podataka,</li>
    <li>pomoći korisniku u ostvarivanju prava ispitanika kada je to tehnički moguće,</li>
    <li>obavijestiti korisnika o sigurnosnom incidentu kada je to primjenjivo i razumno moguće,</li>
    <li>brisati, anonimizirati ili vratiti podatke nakon prestanka usluge u skladu s pravilima čuvanja podataka.</li>
</ul>

<h2>9. Sigurnosne mjere</h2>
<p>
    Sigurnosne mjere mogu uključivati korisničke uloge i ovlasti, lozinke u kriptiranom
    obliku, 2FA za osjetljive račune, ograničenje pristupa, sigurnosne kopije, audit logove,
    zaštitu sesije, kontrolu pristupa datotekama i druge tehničke mjere.
</p>

<h2>10. Podizvršitelji obrade</h2>
<p>
    ZNR Lider može koristiti podizvršitelje obrade kada je to potrebno za hosting,
    servere, e-mail usluge, sigurnosne kopije, tehničko održavanje, podršku ili druge
    elemente pružanja usluge.
</p>
<p>
    ZNR Lider će nastojati koristiti pružatelje koji primjenjuju odgovarajuće mjere
    zaštite podataka.
</p>

<h2>11. Međunarodni prijenosi podataka</h2>
<p>
    Ako se podaci prenose izvan Europskog gospodarskog prostora, ZNR Lider će nastojati
    osigurati da se takav prijenos temelji na odgovarajućoj pravnoj osnovi i primjerenim
    zaštitnim mjerama, kada je to primjenjivo.
</p>

<h2>12. Povjerljivost</h2>
<p>
    ZNR Lider se obvezuje čuvati povjerljivost osobnih i poslovnih podataka korisnika.
    Pristup podacima dopušten je samo osobama kojima je pristup nužan za pružanje,
    održavanje, sigurnost ili podršku usluge.
</p>

<h2>13. Sigurnosni incidenti</h2>
<p>
    U slučaju sigurnosnog incidenta koji može utjecati na osobne podatke korisnika,
    ZNR Lider će, kada je to primjenjivo i razumno moguće, obavijestiti korisnika bez
    nepotrebnog odgađanja te dostaviti dostupne informacije potrebne za procjenu rizika.
</p>

<h2>14. Povrat i brisanje podataka</h2>
<p>
    Nakon prestanka korištenja usluge korisniku se može omogućiti izvoz podataka, ako je
    to tehnički moguće i ako su podmirene dospjele obveze.
</p>
<p>
    Nakon isteka razdoblja čuvanja podataka, podaci se mogu obrisati, anonimizirati ili
    arhivirati ako postoji zakonita osnova za daljnje čuvanje.
</p>

<h2>15. Revizija i dokazivanje usklađenosti</h2>
<p>
    ZNR Lider može korisniku pružiti razumne informacije o tehničkim i organizacijskim
    mjerama zaštite, u mjeri u kojoj to ne ugrožava sigurnost sustava, povjerljivost drugih
    korisnika ili poslovne tajne pružatelja usluge.
</p>

<h2>16. Završne odredbe</h2>
<p>
    Ovaj DPA čini sastavni dio Općih uvjeta korištenja i primjenjuje se na sve korisnike
    koji u aplikaciju unose osobne podatke za koje se smatraju voditeljima obrade.
</p>
<p>
    U slučaju sukoba između ovog dokumenta i Općih uvjeta korištenja, u pitanjima obrade
    osobnih podataka prednost imaju odredbe ovog dokumenta.
</p>

<h2>17. Kontakt</h2>
<p>
    Za pitanja vezana uz obradu podataka korisnik se može obratiti na:
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