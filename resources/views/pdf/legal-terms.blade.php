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

<h1>Opći uvjeti korištenja</h1>

<div class="meta">
    {{ config('legal.provider_name') }} |
    Verzija {{ config('legal.terms_version') }} |
    Stupa na snagu {{ config('legal.effective_date') }} |
    Ispisano {{ now()->format('d.m.Y. H:i') }}
</div>

<h2>1. Uvodne odredbe</h2>
<p>
    Ovi Opći uvjeti korištenja uređuju korištenje aplikacije ZNR Lider, demo sustava,
    digitalnih aplikacija, povezanih usluga, tehničke podrške i drugih softverskih rješenja
    koje pruža {{ config('legal.provider_name') }}.
</p>
<p>
    Korištenjem aplikacije, korisničkog računa, demo verzije ili bilo koje ZNR Lider usluge,
    korisnik potvrđuje da je pročitao, razumio i prihvatio ove uvjete.
</p>
<p>
    Ako se korisnik ne slaže s ovim uvjetima, ne smije koristiti ZNR Lider usluge.
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

<h2>3. Opis usluge</h2>
<p>
    ZNR Lider je poslovna aplikacija za vođenje evidencija, rokova, dokumenata,
    radnih zadataka i aktivnosti povezanih sa zaštitom na radu, zaštitom od požara,
    zaštitom okoliša, kemikalijama, radnom opremom, zapažanjima, incidentima,
    KPI pokazateljima i povezanim poslovnim procesima.
</p>
<p>
    ZNR Lider može uključivati ERP module, dokumentacijski sustav, izvještaje,
    evidencije, izvoz podataka, uvoz podataka, korisničke uloge, obavijesti,
    nadzornu ploču, PDF i Excel izvještaje te druge funkcionalnosti.
</p>
<p>
    Pružatelj usluge može mijenjati, nadograđivati, uklanjati ili prilagođavati
    pojedine funkcionalnosti radi poboljšanja usluge, sigurnosti, tehničkih razloga,
    zakonskih promjena ili poslovnih potreba.
</p>

<h2>4. Korisnici usluge</h2>
<p>
    Korisnik može biti fizička osoba, obrt, trgovačko društvo, ustanova, udruga,
    OPG ili druga pravna osoba koja koristi ZNR Lider usluge.
</p>
<p>
    Osoba koja u ime poslovnog subjekta ugovara ili koristi ZNR Lider uslugu jamči
    da je ovlaštena prihvatiti ove uvjete u ime tog poslovnog subjekta.
</p>
<p>
    Korisnik je odgovoran za sve radnje izvršene putem njegovog korisničkog računa.
</p>

<h2>5. Demo verzija i probno korištenje</h2>
<p>
    ZNR Lider može omogućiti demo verziju ili probno korištenje sustava u trajanju od
    {{ config('legal.trial_days') }} dana, bez obveze nastavka korištenja.
</p>
<p>
    Demo verzija služi za upoznavanje s funkcionalnostima sustava i ne mora sadržavati
    sve mogućnosti produkcijske verzije.
</p>
<p>
    Podaci uneseni u demo sustav mogu biti testni, privremeni i mogu biti obrisani
    nakon isteka probnog razdoblja, osim ako je drugačije dogovoreno.
</p>

<h2>6. Korisnički račun</h2>
<p>
    Za korištenje aplikacije potrebno je otvoriti korisnički račun. Korisnik se obvezuje
    dati točne, potpune i ažurne podatke.
</p>
<p>
    Pružatelj usluge zadržava pravo odbiti, suspendirati ili ukinuti korisnički račun
    ako postoji sumnja na netočne podatke, zlouporabu, sigurnosni rizik, kršenje ovih
    uvjeta ili nezakonito postupanje.
</p>
<p>
    Korisnik je odgovoran za čuvanje korisničkog imena, lozinke, 2FA kodova i svih
    drugih pristupnih podataka.
</p>

<h2>7. Obveze korisnika</h2>
<p>Korisnik se obvezuje da neće:</p>
<ul>
    <li>koristiti sustav protivno zakonu,</li>
    <li>unositi netočne, nezakonite, uvredljive ili štetne podatke,</li>
    <li>pokušavati neovlašteno pristupiti sustavu ili podacima drugih korisnika,</li>
    <li>dijeliti pristupne podatke s neovlaštenim osobama,</li>
    <li>kopirati, prodavati, iznajmljivati ili distribuirati softver bez pisanog dopuštenja,</li>
    <li>pokušavati zaobići sigurnosne mehanizme,</li>
    <li>ometati rad sustava ili nerazumno opterećivati infrastrukturu,</li>
    <li>koristiti sustav za prijevare, spam, zlonamjerne aktivnosti ili nezakonite svrhe.</li>
</ul>
<p>
    Korisnik je u potpunosti odgovoran za zakonitost svog poslovanja, sadržaj koji unosi,
    dokumente koje izrađuje i podatke koje obrađuje putem sustava.
</p>

<h2>8. Točnost podataka i poslovna odgovornost korisnika</h2>
<p>
    ZNR Lider pruža softverski alat, ali ne preuzima odgovornost za poslovne odluke
    korisnika, točnost unesenih podataka, sadržaj evidencija, rokove, dokumente,
    izvještaje ili postupanje korisnika prema zakonskim obvezama.
</p>
<p>
    Korisnik je dužan provjeriti sve podatke prije donošenja odluka, slanja dokumentacije,
    predaje izvještaja ili korištenja podataka iz sustava.
</p>
<p>
    ZNR Lider ne pruža pravne, porezne, knjigovodstvene, medicinske ili stručne savjete,
    osim ako je to izričito posebno ugovoreno.
</p>

<h2>9. Vanjske integracije</h2>
<p>
    Ako sustav omogućuje povezivanje s vanjskim servisima, korisnik prihvaća da takve
    usluge mogu ovisiti o trećim stranama.
</p>
<p>
    ZNR Lider ne odgovara za nedostupnost, greške, promjene uvjeta, promjene API-ja,
    prekide rada, kašnjenja ili odbijene zahtjeve od strane vanjskih sustava.
</p>

<h2>10. Dostupnost usluge</h2>
<p>
    ZNR Lider nastoji osigurati stabilan i pouzdan rad sustava, ali ne jamči da će usluga
    biti dostupna neprekidno, bez grešaka ili bez zastoja.
</p>
<p>Mogući su privremeni prekidi zbog održavanja, nadogradnji, sigurnosnih zahvata,
kvara servera, problema s internet vezom, problema kod hosting pružatelja, više sile,
napada trećih osoba ili problema kod vanjskih integracija.</p>

<h2>11. Sigurnosne kopije i gubitak podataka</h2>
<p>
    ZNR Lider može provoditi sigurnosne kopije sustava i podataka, ali korisnik prihvaća
    da sigurnosne kopije nisu apsolutno jamstvo oporavka svih podataka u svakoj situaciji.
</p>
<p>
    Korisnik je dužan samostalno čuvati vlastite važne poslovne dokumente, izvještaje
    i zakonski relevantnu dokumentaciju.
</p>
<p>
    ZNR Lider ne odgovara za gubitak podataka uzrokovan radnjama korisnika,
    neovlaštenim pristupom zbog korisnikove nepažnje, pogrešnim unosom,
    brisanjem podataka, vanjskim napadom, kvarom treće strane ili višom silom.
</p>

<h2>12. Cijene, plaćanje i aktivacija usluge</h2>
<p>
    Redovna cijena korištenja sustava iznosi {{ config('legal.monthly_price') }} mjesečno
    ili {{ config('legal.yearly_price') }} godišnje, osim ako je drugačije definirano ponudom,
    ugovorom, promotivnom akcijom ili posebnim dogovorom.
</p>
<p>
    Plaćanje se obavlja prema izdanoj ponudi ili računu. ZNR Lider ne provodi automatsku
    naplatu putem kartica ako to nije posebno uvedeno i jasno navedeno.
</p>
<p>
    Usluga se može aktivirati nakon zaprimljene narudžbe, potvrde ponude, izvršene uplate
    ili drugog dogovorenog uvjeta.
</p>
<p>
    U slučaju zakašnjenja s plaćanjem, ZNR Lider može privremeno ograničiti, suspendirati
    ili ukinuti pristup usluzi nakon prethodne obavijesti korisniku, osim ako okolnosti
    zahtijevaju hitno postupanje.
</p>

<h2>13. Otkazivanje usluge</h2>
<p>
    Korisnik može otkazati uslugu u skladu s ugovorenim uvjetima, ponudom ili važećim
    pravilima pretplate. Otkaz vrijedi od kraja tekućeg plaćenog razdoblja, osim ako je
    drugačije dogovoreno.
</p>
<p>
    ZNR Lider može otkazati ili suspendirati uslugu ako korisnik ne plaća uslugu, krši ove
    uvjete, koristi sustav nezakonito, ugrožava sigurnost sustava, zlorabi podršku,
    pokušava kopirati ili neovlašteno koristiti softver ili nanosi štetu ZNR Lideru,
    drugim korisnicima ili trećim osobama.
</p>
<p>
    Nakon prestanka usluge korisniku se može omogućiti razuman rok za izvoz podataka,
    ako je to tehnički moguće i ako su sve dospjele obveze podmirene.
</p>

<h2>14. Pravo na jednostrani raskid za potrošače</h2>
<p>
    Ako korisnik nastupa kao potrošač, može imati pravo na jednostrani raskid ugovora
    sklopljenog na daljinu u roku od 14 dana, sukladno važećim propisima o zaštiti
    potrošača.
</p>
<p>
    Kod digitalnog sadržaja i digitalnih usluga mogu vrijediti posebna pravila, osobito
    ako je korisnik dao izričit pristanak da isporuka započne prije isteka roka za raskid.
</p>
<p>
    Ako korisnik koristi uslugu kao poslovni subjekt, obrt, OPG, trgovačko društvo,
    ustanova ili druga pravna osoba, pravila o potrošačkom jednostranom raskidu u pravilu
    se ne primjenjuju, osim ako je zakonom drugačije propisano.
</p>

<h2>15. Intelektualno vlasništvo</h2>
<p>
    Sav softver, dizajn, struktura sustava, programski kod, baze podataka, moduli,
    tekstovi, grafički elementi, logotip, naziv ZNR Lider, dokumentacija, video upute
    i drugi materijali vlasništvo su pružatelja usluge ili se koriste na temelju
    odgovarajućeg prava.
</p>
<p>
    Korisnik ne stječe vlasništvo nad softverom, već samo ograničeno, neprenosivo
    i opozivo pravo korištenja usluge u skladu s ovim uvjetima.
</p>
<p>
    Zabranjeno je kopiranje, modificiranje, distribuiranje, prodaja, iznajmljivanje,
    reverzni inženjering, pokušaj izdvajanja izvornog koda ili izrada izvedenih proizvoda
    na temelju ZNR Lider softvera bez izričitog pisanog dopuštenja.
</p>

<h2>16. Sadržaj korisnika</h2>
<p>
    Korisnik zadržava prava na podatke i sadržaj koji sam unosi u sustav.
</p>
<p>
    Korisnik ZNR Lideru daje ograničeno pravo obrade, pohrane, prikaza i tehničke obrade
    tog sadržaja isključivo u svrhu pružanja usluge, održavanja sustava, podrške i
    ispunjenja ugovornih obveza.
</p>
<p>
    Korisnik jamči da ima pravo unositi, obrađivati i koristiti sve podatke koje unosi
    u ZNR Lider sustav.
</p>

<h2>17. Tehnička podrška</h2>
<p>
    ZNR Lider može pružati tehničku podršku putem e-maila, telefona, poruka,
    udaljenog pristupa ili drugih kanala, ovisno o dogovoru i dostupnosti.
</p>
<p>
    Podrška ne uključuje nužno pravno, stručno, porezno, knjigovodstveno ili poslovno
    savjetovanje.
</p>
<p>
    ZNR Lider zadržava pravo odbiti podršku u slučaju zlouporabe, neprimjerene
    komunikacije, neplaćenih obveza, neovlaštenih izmjena sustava ili korištenja usluge
    protivno ovim uvjetima.
</p>

<h2>18. Ograničenje odgovornosti</h2>
<p>
    ZNR Lider neće biti odgovoran za gubitak dobiti, gubitak prihoda, prekid poslovanja,
    gubitak poslovnih prilika, gubitak podataka uzrokovan radnjom korisnika, netočne
    podatke koje je korisnik unio, neispravno korištenje sustava, odluke donesene na
    temelju podataka iz sustava, probleme uzrokovane vanjskim servisima, nedostupnost
    interneta, servera, API-ja ili trećih sustava, štetu nastalu zbog više sile ili štetu
    nastalu zbog neovlaštenog pristupa uzrokovanog korisnikovom nepažnjom.
</p>
<p>
    Ukupna odgovornost ZNR Lidera, ako bi ona postojala, ograničava se na iznos koji je
    korisnik platio za korištenje konkretne usluge u posljednja tri mjeseca prije nastanka
    događaja iz kojeg proizlazi zahtjev, osim ako je drugačije propisano prisilnim
    zakonskim odredbama.
</p>

<h2>19. Viša sila</h2>
<p>
    ZNR Lider ne odgovara za neispunjenje ili zakašnjenje u ispunjenju obveza ako je ono
    posljedica okolnosti izvan razumne kontrole, uključujući kvarove infrastrukture,
    nestanak električne energije, prekide interneta, hakerske napade, rat, požar,
    poplavu, epidemiju, odluke nadležnih tijela, štrajkove, probleme kod pružatelja
    hostinga ili druge izvanredne događaje.
</p>

<h2>20. Izmjene usluge i uvjeta</h2>
<p>
    ZNR Lider zadržava pravo izmjene ovih Općih uvjeta u bilo kojem trenutku.
</p>
<p>
    Izmjene stupaju na snagu objavom u sustavu ili na web stranici, osim ako nije
    drugačije navedeno. Nastavak korištenja usluge nakon objave izmjena smatra se
    prihvaćanjem novih uvjeta.
</p>
<p>
    U slučaju bitnih izmjena korisnik može biti zatražen da ponovno prihvati novu verziju
    uvjeta.
</p>

<h2>21. Povjerljivost</h2>
<p>
    ZNR Lider i korisnik obvezuju se čuvati povjerljive poslovne informacije do kojih dođu
    tijekom suradnje.
</p>
<p>
    Povjerljivim informacijama smatraju se poslovni podaci, tehnička dokumentacija,
    pristupni podaci, poslovni planovi, cjenici, korisnički podaci, interni procesi i drugi
    podaci koji nisu javno dostupni.
</p>
<p>
    Obveza povjerljivosti ne odnosi se na podatke koji su javno dostupni, zakonito
    pribavljeni od treće strane ili se moraju otkriti na temelju zakona ili odluke
    nadležnog tijela.
</p>

<h2>22. Mjerodavno pravo i rješavanje sporova</h2>
<p>
    Na ove Opće uvjete primjenjuje se pravo Republike Hrvatske.
</p>
<p>
    ZNR Lider i korisnik nastojat će sve sporove riješiti mirnim putem. Ako mirno rješenje
    nije moguće, nadležan je stvarno nadležni sud prema sjedištu pružatelja usluge,
    osim ako prisilni propisi ne određuju drugačije.
</p>

<h2>23. Završne odredbe</h2>
<p>
    Ako se neka odredba ovih Općih uvjeta pokaže ništetnom, nevaljanom ili neprovedivom,
    to neće utjecati na valjanost ostalih odredbi.
</p>
<p>
    Ovi Opći uvjeti primjenjuju se od dana objave u sustavu ili na web stranici.
</p>

<h2>24. Kontakt</h2>
<p>
    Za sva pitanja vezana uz korištenje ZNR Lider usluga korisnik se može obratiti na:
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