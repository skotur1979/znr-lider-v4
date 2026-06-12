<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            line-height: 1.45;
            color: #111827;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 4px;
        }

        h2 {
            font-size: 13px;
            margin-top: 14px;
            margin-bottom: 4px;
            color: #111827;
        }

        p {
            margin: 0 0 7px 0;
        }

        ul {
            margin-top: 4px;
            margin-bottom: 8px;
        }

        li {
            margin-bottom: 2px;
        }

        .meta {
            color: #4b5563;
            margin-bottom: 14px;
            font-size: 10px;
        }

        .footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #4b5563;
        }
    </style>
</head>
<body>

<h1>Pravila privatnosti</h1>

<div class="meta">
    {{ config('legal.provider_name') }} |
    Verzija {{ config('legal.privacy_version') }} |
    Stupa na snagu {{ config('legal.effective_date') }} |
    Ispisano {{ now()->format('d.m.Y. H:i') }}
</div>

<h2>1. Uvod</h2>
<p>
    Ova Pravila privatnosti objašnjavaju kako ZNR Lider prikuplja, koristi,
    pohranjuje i štiti osobne podatke korisnika aplikacije, poslovnih subjekata,
    zaposlenika, ispitanika i drugih osoba čiji se podaci obrađuju kroz sustav.
</p>
<p>
    Korištenjem aplikacije ZNR Lider korisnik potvrđuje da je upoznat s ovim
    Pravilima privatnosti.
</p>

<h2>2. Podaci o pružatelju usluge</h2>
<p>
    Naziv: {{ config('legal.provider_name') }}<br>
    Vlasnik: {{ config('legal.provider_owner') }}<br>
    Adresa: {{ config('legal.provider_address') }}<br>
    OIB: {{ config('legal.provider_oib') }}<br>
    E-mail: {{ config('legal.provider_email') }}<br>
    Telefon: {{ config('legal.provider_phone') }}<br>
    Web: {{ config('legal.provider_web') }}
</p>

<h2>3. Uloge u obradi podataka</h2>
<p>
    Korisnik aplikacije, odnosno poslovni subjekt koji unosi podatke u ZNR Lider,
    u pravilu je voditelj obrade osobnih podataka.
</p>
<p>
    ZNR Lider djeluje kao izvršitelj obrade u dijelu tehničke pohrane, obrade,
    prikaza, zaštite i održavanja podataka unesenih u aplikaciju.
</p>
<p>
    Za osobne podatke koje korisnik samostalno unosi u sustav, uključujući podatke
    zaposlenika, vanjskih suradnika, ispitanika i drugih osoba, korisnik je odgovoran
    osigurati odgovarajuću pravnu osnovu i zakonitost obrade.
</p>

<h2>4. Koje podatke prikupljamo</h2>
<p>Ovisno o načinu korištenja sustava, mogu se obrađivati sljedeći podaci:</p>

<ul>
    <li>ime i prezime korisnika,</li>
    <li>naziv organizacije, obrta, tvrtke ili drugog poslovnog subjekta,</li>
    <li>OIB, adresa, e-mail adresa i broj telefona,</li>
    <li>korisničko ime i podaci vezani uz korisnički račun,</li>
    <li>IP adresa, preglednik, uređaj i vrijeme pristupa,</li>
    <li>podaci o zaposlenicima uneseni u module aplikacije,</li>
    <li>podaci o liječničkim pregledima, osposobljavanjima i ovlaštenjima,</li>
    <li>podaci o radnoj opremi, vatrogasnim aparatima i ostalim ispitivanjima,</li>
    <li>podaci o osobnoj zaštitnoj opremi i prvoj pomoći,</li>
    <li>podaci o incidentima, zapažanjima, internim nadzorima i radnim zadacima,</li>
    <li>podaci o kemikalijama, dokumentima, rokovima i prilozima,</li>
    <li>KPI podaci, izvještaji i poslovne evidencije,</li>
    <li>komunikacija putem e-maila, telefona ili drugih kanala podrške,</li>
    <li>podaci o prihvaćanju pravnih dokumenata, privolama i GDPR zahtjevima.</li>
</ul>

<p>
    ZNR Lider ne traži unos podataka koji nisu potrebni za pružanje usluge,
    tehničku podršku, sigurnost sustava, naplatu ili ispunjavanje zakonskih obveza.
</p>
<p>
    Korisnik potvrđuje da za unos i obradu posebnih kategorija osobnih podataka,
    uključujući podatke koji mogu predstavljati zdravstvene podatke ili podatke
    o zdravstvenoj sposobnosti radnika kada su isti potrebni radi ispunjavanja
    zakonskih obveza iz područja zaštite na radu, zaštite od požara i povezanih
    propisa, posjeduje odgovarajuću pravnu osnovu sukladno članku 9. Opće uredbe
    o zaštiti podataka (GDPR).
</p>

<h2>5. Svrha obrade podataka</h2>
<p>Osobni podaci obrađuju se u sljedeće svrhe:</p>

<ul>
    <li>otvaranje i održavanje korisničkih računa,</li>
    <li>pružanje ZNR Lider aplikacije i povezanih digitalnih usluga,</li>
    <li>vođenje evidencija zaštite na radu, zaštite od požara i zaštite okoliša,</li>
    <li>vođenje evidencija zaposlenika, rokova, ispitivanja i osposobljavanja,</li>
    <li>upravljanje dokumentacijom, radnim zadacima, incidentima i zapažanjima,</li>
    <li>izrada PDF, Excel i drugih izvještaja,</li>
    <li>tehnička podrška i otklanjanje poteškoća,</li>
    <li>sigurnost sustava i sprječavanje zlouporabe,</li>
    <li>evidencija prihvaćanja pravnih dokumenata,</li>
    <li>postupanje po zahtjevima za izvoz, ispravak ili brisanje podataka,</li>
    <li>slanje obavijesti vezanih uz uslugu, sigurnost, nadogradnje ili promjene uvjeta,</li>
    <li>izdavanje računa, poslovna komunikacija i ispunjavanje zakonskih obveza.</li>
</ul>

<h2>6. Pravna osnova obrade</h2>
<p>
    Osobni podaci obrađuju se samo kada postoji odgovarajuća pravna osnova.
</p>

<p>Pravne osnove mogu uključivati:</p>

<ul>
    <li>izvršenje ugovora ili poduzimanje radnji prije sklapanja ugovora,</li>
    <li>ispunjavanje zakonskih obveza,</li>
    <li>legitimni interes za sigurnost, podršku, komunikaciju i razvoj usluge,</li>
    <li>privolu korisnika kada je ona potrebna, primjerice za određene marketinške obavijesti,</li>
    <li>obradu nužnu za zaštitu prava i pravnih interesa pružatelja usluge ili korisnika.</li>
</ul>

<p>
    Korisnik može u svakom trenutku povući privolu, ali povlačenje privole ne utječe
    na zakonitost obrade provedene prije povlačenja.
</p>

<h2>7. Podaci koje korisnik unosi u ZNR Lider</h2>
<p>
    Korisnik je odgovoran za zakonitost, točnost, ažurnost i sadržaj podataka koje
    unosi u ZNR Lider.
</p>
<p>
    ZNR Lider kao pružatelj softverske usluge ne preuzima odgovornost za sadržaj
    poslovnih podataka koje korisnik unosi, uključujući podatke o zaposlenicima,
    pregledima, evidencijama, dokumentima, incidentima ili drugim poslovnim zapisima.
</p>
<p>
    Korisnik je dužan osigurati da za osobne podatke koje unosi u sustav ima
    odgovarajuću pravnu osnovu i da ih obrađuje u skladu s važećim propisima.
</p>

<h2>8. Dijeljenje podataka s trećim stranama</h2>
<p>
    Osobni podaci se ne prodaju, ne iznajmljuju i ne dijele s trećim osobama
    u marketinške svrhe.
</p>

<p>Podaci se mogu dijeliti samo kada je to potrebno za pružanje ugovorene usluge,
hosting, servere, e-mail sustave i tehničku infrastrukturu, sigurnosne kopije,
održavanje sustava, knjigovodstvene, porezne ili pravne obveze te postupanje po
zahtjevu nadležnih tijela kada za to postoji zakonska obveza.</p>

<p>
    S vanjskim pružateljima usluga, kada je primjenjivo, koriste se odgovarajuće
    ugovorne, tehničke i organizacijske mjere zaštite.
</p>

<h2>9. Sigurnost podataka</h2>
<p>
    ZNR Lider primjenjuje razumne tehničke i organizacijske mjere zaštite osobnih
    podataka, uključujući kontrolu pristupa, korisničke uloge, zaštitu korisničkih
    računa, sigurnosne kopije, ograničenja pristupa i evidenciju određenih aktivnosti.
</p>
<p>
    Lozinke se pohranjuju u kriptiranom obliku. Sustav može koristiti dodatne sigurnosne
    mjere, uključujući dvofaktorsku autentifikaciju, zaštitu sesije i audit evidenciju.
</p>
<p>
    Nijedan informacijski sustav, prijenos podataka putem interneta ili digitalna usluga
    ne može biti apsolutno sigurna. ZNR Lider ne može jamčiti potpunu zaštitu od svih
    mogućih rizika, osobito u slučaju korisničke nepažnje, slabih lozinki, dijeljenja
    pristupnih podataka, zlonamjernog softvera na korisničkom uređaju ili neovlaštenih
    radnji trećih osoba.
</p>
<p>
    U slučaju sigurnosnog incidenta ili povrede osobnih podataka ZNR Lider će
    poduzeti razumne tehničke i organizacijske mjere radi ograničavanja posljedica,
    utvrđivanja uzroka i sanacije nastale situacije.
</p>

<p>
    Ako ZNR Lider kao izvršitelj obrade sazna za povredu osobnih podataka koja može
    predstavljati rizik za prava i slobode ispitanika, bez nepotrebnog odgađanja
    obavijestit će korisnika koji djeluje kao voditelj obrade kako bi isti mogao
    ispuniti svoje zakonske obveze prijave nadležnom nadzornom tijelu, uključujući
    obvezu prijave povrede osobnih podataka u roku od 72 sata kada je to propisano
    GDPR-om.
</p>
<h2>10. Korisničke lozinke i pristupni podaci</h2>
<p>
    Korisnik je odgovoran za čuvanje svojih pristupnih podataka.
</p>

<p>
    Korisnik se obvezuje koristiti sigurnu lozinku, ne dijeliti pristupne podatke
    s neovlaštenim osobama, odmah obavijestiti ZNR Lider ako sumnja na neovlašteni
    pristup i osigurati da njegovi zaposlenici ili suradnici koriste sustav isključivo
    u skladu s dodijeljenim ovlastima.
</p>

<p>
    ZNR Lider ne odgovara za štetu nastalu zbog korisnikovog neodgovornog čuvanja
    pristupnih podataka.
</p>

<h2>11. Rok čuvanja podataka</h2>
<p>
    Osobni podaci čuvaju se onoliko dugo koliko je potrebno za svrhu za koju su
    prikupljeni, odnosno koliko zahtijevaju zakonske, porezne, računovodstvene,
    ugovorne ili druge legitimne obveze.
</p>
<p>
    Podaci korisničkog računa mogu se čuvati za vrijeme trajanja korištenja usluge.
</p>
<p>
    Nakon prestanka korištenja usluge podaci se mogu čuvati još
    {{ config('legal.data_retention_after_cancellation_days') }} dana radi mogućeg izvoza,
    povrata pristupa ili rješavanja otvorenih obveza, nakon čega se mogu obrisati,
    anonimizirati ili arhivirati ako postoji zakonita osnova za daljnje čuvanje.
</p>
<p>
    Podaci vezani uz račune, ugovorne odnose, poslovnu dokumentaciju ili zakonske obveze
    mogu se čuvati dulje, u rokovima propisanim važećim zakonima.
</p>

<h2>12. Prava ispitanika</h2>
<p>
    Ispitanici imaju pravo zatražiti pristup osobnim podacima, ispravak netočnih ili
    nepotpunih podataka, brisanje podataka kada za to postoje uvjeti, ograničenje obrade,
    prigovor na obradu, prijenos podataka kada je primjenjivo i povlačenje ranije dane
    privole.
</p>
<p>
    Za ostvarivanje prava korisnik se može obratiti putem e-maila:
    {{ config('legal.provider_email') }}.
</p>
<p>
    Ako korisnik smatra da su njegova prava povrijeđena, ima pravo obratiti se
    nadležnom nadzornom tijelu za zaštitu osobnih podataka. U Republici Hrvatskoj
    nadležno tijelo je Agencija za zaštitu osobnih podataka (AZOP).
</p>

<h2>13. Kolačići i analitika</h2>
<p>
    ZNR Lider koristi nužne kolačiće potrebne za prijavu, sigurnost sesije,
    zaštitu od CSRF napada i ispravan rad aplikacije.
</p>
<p>
    Ako se u budućnosti uključe analitički ili marketinški alati koji zahtijevaju
    privolu, korisnik će biti jasno obaviješten i, gdje je potrebno, moći će prihvatiti
    ili odbiti takve kolačiće.
</p>
<p>
    Više informacija dostupno je u Politici kolačića.
</p>

<h2>14. Vanjske poveznice i integracije</h2>
<p>
    ZNR Lider može sadržavati poveznice na vanjske stranice ili integracije s vanjskim
    servisima. ZNR Lider nije odgovoran za sadržaj, sigurnost, dostupnost ili pravila
    privatnosti trećih strana.
</p>
<p>
    Korisnik je dužan upoznati se s pravilima korištenja i privatnosti svake vanjske
    usluge koju koristi ili povezuje sa ZNR Lider sustavom.
</p>

<h2>15. Izmjene Pravila privatnosti</h2>
<p>
    ZNR Lider zadržava pravo izmjene ovih Pravila privatnosti u bilo kojem trenutku,
    osobito radi usklađivanja sa zakonskim promjenama, tehničkim promjenama ili
    promjenama u načinu pružanja usluge.
</p>
<p>
    Nova verzija Pravila privatnosti bit će objavljena u sustavu ili na web stranici.
    U slučaju bitnih izmjena korisnik može biti zatražen da ponovno prihvati novu verziju.
</p>

<h2>16. Kontakt</h2>
<p>
    Za sva pitanja vezana uz privatnost i zaštitu osobnih podataka korisnik se može
    obratiti na:
</p>
<p>
    {{ config('legal.provider_email') }}<br>
    {{ config('legal.provider_phone') }}
</p>

<div class="footer">
    {{ config('legal.provider_name') }} · OIB {{ config('legal.provider_oib') }} ·
    {{ config('legal.provider_address') }} · {{ config('legal.provider_email') }}
</div>

</body>
</html>