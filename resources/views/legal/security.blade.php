<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Politika sigurnosti - ZNR Lider</title>
</head>
<body style="font-family: Arial, sans-serif; max-width:900px; margin:40px auto; line-height:1.6; color:#111827;">

<h1>Politika sigurnosti informacijskog sustava</h1>

<p>
    <strong>{{ config('legal.provider_name') }}</strong><br>
    Verzija: {{ config('legal.security_version') }}<br>
    Stupa na snagu: {{ config('legal.effective_date') }}
</p>

<h2>1. Svrha</h2>

<p>
    Svrha ove Politike sigurnosti je definirati osnovna načela zaštite
    podataka, korisničkih računa, infrastrukture i funkcionalnosti
    aplikacije ZNR Lider.
</p>

<h2>2. Opseg</h2>

<p>
    Ova politika primjenjuje se na:
</p>

<ul>
    <li>korisničke račune,</li>
    <li>administratore sustava,</li>
    <li>pohranjene podatke,</li>
    <li>sigurnosne kopije,</li>
    <li>dokumente i priloge,</li>
    <li>infrastrukturu i hosting,</li>
    <li>integracije s vanjskim servisima.</li>
</ul>

<h2>3. Kontrola pristupa</h2>

<p>
    ZNR Lider koristi sustav korisničkih uloga i ovlasti.
</p>

<p>
    Korisnicima se dodjeljuju samo prava potrebna za obavljanje njihovih
    poslovnih zadataka (načelo najmanjih ovlasti).
</p>

<p>
    Administratori organizacija odgovorni su za upravljanje korisnicima
    unutar svoje organizacije.
</p>

<h2>4. Korisnički računi i lozinke</h2>

<ul>
    <li>lozinke se pohranjuju u kriptiranom obliku,</li>
    <li>korisnici su odgovorni za čuvanje svojih pristupnih podataka,</li>
    <li>zabranjeno je dijeljenje korisničkih računa,</li>
    <li>preporučuje se korištenje snažnih lozinki,</li>
    <li>korisnici trebaju odmah prijaviti sumnju na kompromitaciju računa.</li>
</ul>

<h2>5. Dvofaktorska autentifikacija</h2>

<p>
    Za određene korisnike i administratorske račune može biti omogućena
    dvofaktorska autentifikacija (2FA) putem e-maila ili drugih metoda.
</p>

<h2>6. Sigurnosne kopije</h2>

<p>
    ZNR Lider može izrađivati sigurnosne kopije podataka radi zaštite od
    gubitka podataka, osiguravanja kontinuiteta poslovanja i oporavka sustava.
</p>

<p>
    Sigurnosne kopije čuvaju se u skladu s primjenjivim tehničkim i
    organizacijskim mjerama zaštite te mogu sadržavati osobne podatke
    unesene u sustav.
</p>

<p>
    Iako sigurnosne kopije predstavljaju važnu mjeru zaštite, one ne mogu
    predstavljati apsolutno jamstvo potpunog oporavka svih podataka u svim
    okolnostima.
</p>

<h2>7. Audit evidencija</h2>

<p>
    ZNR Lider može voditi evidenciju određenih aktivnosti korisnika,
    uključujući prijave, prihvaćanje pravnih dokumenata i druge radnje
    važne za sigurnost sustava.
</p>

<h2>8. Zaštita sesije</h2>

<p>
    Sustav koristi mehanizme zaštite korisničkih sesija,
    uključujući zaštitu od CSRF napada i automatski istek sesije
    nakon razdoblja neaktivnosti.
</p>

<h2>9. Sigurnosni incidenti</h2>

<p>
    U slučaju sigurnosnog incidenta ZNR Lider će poduzeti razumne tehničke
    i organizacijske mjere radi ograničavanja posljedica, utvrđivanja
    uzroka i sanacije nastale situacije.
</p>

<p>
    Ako ZNR Lider kao izvršitelj obrade sazna za povredu osobnih podataka
    koja može predstavljati rizik za prava i slobode ispitanika, bez
    nepotrebnog odgađanja obavijestit će korisnika koji djeluje kao
    voditelj obrade kako bi isti mogao ispuniti svoje zakonske obveze
    prema nadležnom nadzornom tijelu.
</p>

<p>
    Kada je to primjenjivo, korisnik kao voditelj obrade može biti obvezan
    prijaviti povredu osobnih podataka Agenciji za zaštitu osobnih podataka
    (AZOP) u roku od 72 sata od saznanja za povredu, sukladno GDPR-u.
</p>

<h2>10. Obveze korisnika</h2>

<ul>
    <li>koristiti sustav odgovorno,</li>
    <li>ne dijeliti pristupne podatke,</li>
    <li>koristiti ažurirane uređaje i preglednike,</li>
    <li>prijaviti sumnjive aktivnosti,</li>
    <li>poštivati dodijeljene ovlasti.</li>
</ul>

<h2>11. Odgovornosti korisnika kao voditelja obrade</h2>

<p>
    Korisnik koji koristi ZNR Lider odgovoran je za zakonitost osobnih
    podataka koje unosi u sustav te za osiguravanje odgovarajuće pravne
    osnove za njihovu obradu.
</p>

<p>
    Korisnik je odgovoran za upravljanje korisničkim računima unutar svoje
    organizacije, određivanje razina pristupa i postupanje u skladu s
    važećim propisima o zaštiti osobnih podataka.
</p>

<h2>12. Izmjene politike</h2>

<p>
    ZNR Lider može ažurirati ovu Politiku sigurnosti radi tehničkih,
    organizacijskih ili zakonskih razloga.
</p>

<h2>13. Kontakt</h2>

<p>
    {{ config('legal.provider_email') }}<br>
    {{ config('legal.provider_phone') }}
</p>

<hr>

<p style="font-size:13px; color:#6b7280;">
    {{ config('legal.provider_name') }}
</p>

</body>
</html>