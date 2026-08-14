<x-filament-panels::page.simple>
    <style>
        .fi-simple-main {
            max-width: none !important;
            width: 100% !important;
            padding: 0 !important;
        }

        .fi-simple-page {
            min-height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 40px 24px !important;
            background:
                radial-gradient(circle at 12% 15%, rgba(245, 158, 11, 0.20), transparent 28%),
                radial-gradient(circle at 85% 75%, rgba(234, 88, 12, 0.16), transparent 32%),
                linear-gradient(135deg, #09090b 0%, #0f0f12 55%, #1a1005 100%) !important;
        }

        .fi-simple-header {
            display: none !important;
        }

        .znr-login-wrapper {
            width: 100%;
            max-width: 1240px;
            margin: 0 auto;
        }

        .znr-login-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(390px, 0.65fr);
            gap: 26px;
            align-items: stretch;
        }
        
        /* Sakrij drugi red Filament notifikacije ako prijevod nije pronađen */
        .fi-notification-body {
            display: none !important;
        }

        /* Malo profesionalniji izgled notifikacije */
        .fi-notification {
            border-radius: 14px !important;
        }

        .fi-notification-title {
            font-weight: 800 !important;
            line-height: 1.4 !important;
        }
        .znr-panel {
            position: relative;
            overflow: hidden;
            background: rgba(24, 24, 27, 0.96);
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 26px;
            box-shadow: 0 30px 100px rgba(0, 0, 0, 0.52);
        }

        .znr-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(120deg, rgba(255, 255, 255, 0.08), transparent 22%),
                radial-gradient(circle at top right, rgba(245, 158, 11, 0.08), transparent 38%);
        }

        .znr-info,
        .znr-form {
            position: relative;
            z-index: 1;
        }

        .znr-info {
            padding: 38px;
        }

        .znr-form {
            padding: 38px 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background:
                linear-gradient(180deg, rgba(245, 158, 11, 0.09), rgba(24, 24, 27, 0.98));
        }

        .znr-form::before {
            content: '';
            display: block;
            width: 76px;
            height: 4px;
            border-radius: 999px;
            background: linear-gradient(90deg, #f59e0b, #ea580c);
            margin: 0 auto 26px auto;
        }

        .znr-brand {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 24px;
        }

        .znr-logo {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            background: linear-gradient(135deg, #fbbf24, #ea580c);
            display: grid;
            place-items: center;
            font-weight: 950;
            color: #111827;
            box-shadow: 0 12px 35px rgba(245, 158, 11, 0.28);
        }

        .znr-eyebrow {
            color: #fbbf24;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .znr-title {
            color: #ffffff;
            font-size: 34px;
            font-weight: 950;
            line-height: 1;
        }

        .znr-main-heading {
            color: #ffffff;
            font-size: 34px;
            font-weight: 950;
            line-height: 1.15;
            max-width: 720px;
            margin-bottom: 10px;
        }

        .znr-highlight {
            color: #fbbf24;
        }

        .znr-subline {
            color: #d4d4d8;
            font-size: 15px;
            line-height: 1.6;
            max-width: 800px;
            margin-bottom: 18px;
        }

        .znr-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .znr-badge {
            color: #fde68a;
            background: rgba(245, 158, 11, 0.10);
            border: 1px solid rgba(245, 158, 11, 0.22);
            border-radius: 999px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 800;
        }

        .znr-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin: 20px 0;
        }

        .znr-stat {
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 16px 12px;
            text-align: center;
        }

        .znr-stat-number {
            font-size: 24px;
            font-weight: 950;
            color: #f59e0b;
            line-height: 1;
        }

        .znr-stat-label {
            margin-top: 7px;
            font-size: 12px;
            color: #a1a1aa;
            line-height: 1.35;
        }
        .znr-login-button {
            margin-top: 20px;
        }

        .znr-login-button .fi-btn {
            width: 100% !important;
            justify-content: center !important;
            min-height: 48px !important;
            font-size: 15px !important;
            font-weight: 800 !important;
            border-radius: 12px !important;
        }
        .znr-cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 14px;
        }

        .znr-card,
        .znr-about,
        .znr-contact,
        .znr-trust-item {
            background: rgba(39, 39, 42, 0.82);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
        }

        .znr-card {
            padding: 18px;
        }

        .znr-card h3 {
            color: #ffffff;
            font-size: 15px;
            font-weight: 950;
            margin-bottom: 8px;
        }

        .znr-card p,
        .znr-card li {
            color: #d4d4d8;
            font-size: 13px;
            line-height: 1.58;
        }

        .znr-card ul {
            margin-left: 18px;
        }

        .znr-about {
            padding: 18px;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.14), rgba(234, 88, 12, 0.06));
            border-color: rgba(245, 158, 11, 0.25);
        }

        .znr-about h3 {
            color: #fbbf24;
            font-size: 16px;
            font-weight: 950;
            margin-bottom: 8px;
        }

        .znr-about p {
            color: #e5e7eb;
            font-size: 13px;
            line-height: 1.62;
        }

        .znr-about p + p {
            margin-top: 9px;
        }
        /* =====================================================
           FILAMENT LOGIN FORMA – BOJE TEKSTA
           ===================================================== */
        
        /* Adresa e-pošte i Lozinka */
        .znr-form form .fi-fo-field-wrp-label,
        .znr-form form .fi-fo-field-wrp-label span,
        .znr-form form label,
        .znr-form form label span {
            color: #e5e7eb !important;
        }
        
        /* Obavezna zvjezdica uz naziv polja */
        .znr-form form .fi-fo-field-wrp-label .text-danger-600,
        .znr-form form .fi-fo-field-wrp-label sup {
            color: #f59e0b !important;
        }
        
        /* Zapamti me */
        .znr-form form .fi-checkbox-label,
        .znr-form form .fi-checkbox-label span,
        .znr-form form .fi-fo-checkbox-list-option-label,
        .znr-form form .fi-fo-checkbox-list-option-label span {
            color: #d4d4d8 !important;
        }
        
        /* Pomoćni tekst i poruke ispod polja */
        .znr-form form .fi-fo-field-wrp-helper-text,
        .znr-form form .fi-fo-field-wrp-helper-text span {
            color: #a1a1aa !important;
        }
        
        /* Tekst koji korisnik upisuje u bijela polja */
        .znr-form form input {
            color: #111827 !important;
            background-color: #ffffff !important;
        }
        
        /* Placeholder unutar polja */
        .znr-form form input::placeholder {
            color: #6b7280 !important;
            opacity: 1 !important;
        }
        
        /* Poveznica Zaboravljena lozinka */
        .znr-form form a {
            color: #f59e0b !important;
        }
        
        .znr-form form a:hover {
            color: #fbbf24 !important;
        }

        .znr-trust {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .znr-trust-item {
            padding: 10px 12px;
            background: rgba(16, 185, 129, 0.075);
            border-color: rgba(16, 185, 129, 0.20);
            color: #d1fae5;
            font-size: 12px;
            font-weight: 800;
        }

        .znr-contact {
            margin-top: 14px;
            padding: 14px 16px;
            color: #e5e7eb;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .znr-contact a {
            color: #fbbf24;
            font-weight: 900;
            text-decoration: none;
        }

        .znr-form-heading {
            text-align: center;
            margin-bottom: 24px;
        }

        .znr-form-heading .znr-form-logo {
            width: 54px;
            height: 54px;
            margin: 0 auto 14px;
            border-radius: 17px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #fbbf24, #ea580c);
            color: #111827;
            font-weight: 950;
            box-shadow: 0 12px 35px rgba(245, 158, 11, 0.26);
        }

        .znr-form-heading h1 {
            color: #ffffff;
            font-size: 27px;
            font-weight: 950;
            line-height: 1.2;
        }

        .znr-form-heading p {
            color: #a1a1aa;
            font-size: 13px;
            margin-top: 7px;
            line-height: 1.5;
        }
        .znr-login-features {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .znr-login-feature {
            padding: 14px;
            border-radius: 14px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
        }

        .znr-login-feature-title {
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .znr-login-feature-text {
            color: #a1a1aa;
            font-size: 12px;
            line-height: 1.5;
        }

        .znr-version {
            text-align: center;
            margin-top: 18px;
            color: #f59e0b;
            font-size: 12px;
            font-weight: 700;
        }

        .znr-note {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: #a1a1aa;
            font-size: 12px;
            line-height: 1.55;
            text-align: center;
        }

        .znr-security-row {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .znr-security-row span {
            color: #d4d4d8;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 800;
        }

        .znr-form .fi-btn {
            min-height: 44px;
            border-radius: 12px !important;
            font-weight: 900 !important;
        }
        .znr-test-notice {
            margin-top: 14px;
            padding: 16px 18px;
            border-radius: 18px;
            background: linear-gradient(
            135deg,
            rgba(245, 158, 11, .12),
            rgba(234, 88, 12, .05)
            );
            border: 1px solid rgba(245, 158, 11, .28);
        }

        .znr-test-title {
            color: #fbbf24;
            font-size: 14px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .znr-test-text {
            color: #e5e7eb;
            font-size: 13px;
            line-height: 1.6;
        }

        .znr-test-text + .znr-test-text {
            margin-top: 8px;
        }

        @media (max-width: 1120px) {
            .znr-login-grid {
                grid-template-columns: 1fr;
            }

            .znr-form {
                max-width: 540px;
                width: 100%;
                margin: 0 auto;
            }
        }

        @media (max-width: 760px) {
            .fi-simple-page {
                padding: 20px 12px !important;
                align-items: flex-start !important;
            }

            .znr-info,
            .znr-form {
                padding: 24px;
            }

            .znr-main-heading,
            .znr-title {
                font-size: 26px;
            }

            .znr-cards,
            .znr-stats,
            .znr-trust {
                grid-template-columns: 1fr;
            }

            .znr-contact {
                align-items: flex-start;
                flex-direction: column;
            }
            
        }
    </style>

    <div class="znr-login-wrapper">
        <div class="znr-login-grid">
            <section class="znr-panel znr-info">
                <div class="znr-brand">
                    <div class="znr-logo">ZL</div>
                    <div>
                        <div class="znr-eyebrow">Profesionalni ZNR sustav</div>
                        <div class="znr-title">ZNR LIDER</div>
                    </div>
                </div>

                <h1 class="znr-main-heading">
                    Sve evidencije, rokovi i dokumentacija
                    <span class="znr-highlight">na jednom mjestu.</span>
                </h1>

                <p class="znr-subline">
                    Digitalni sustav za vođenje poslova zaštite na radu, zaštite od požara,
                    zaštite okoliša, KPI pokazatelja i ISO obveza u organizaciji.
                </p>

                <div class="znr-badges">
                    <span class="znr-badge">Zaštita na radu</span>
                    <span class="znr-badge">Zaštita od požara</span>
                    <span class="znr-badge">Zaštita okoliša</span>
                    <span class="znr-badge">KPI</span>
                    <span class="znr-badge">ISO sustavi</span>
                </div>

                <div class="znr-stats">
                    <div class="znr-stat">
                        <div class="znr-stat-number">20+</div>
                        <div class="znr-stat-label">godina iskustva</div>
                    </div>

                    <div class="znr-stat">
                        <div class="znr-stat-number">15+</div>
                        <div class="znr-stat-label">modula sustava</div>
                    </div>

                    <div class="znr-stat">
                        <div class="znr-stat-number">100%</div>
                        <div class="znr-stat-label">web aplikacija</div>
                    </div>

                    <div class="znr-stat">
                        <div class="znr-stat-number">24/7</div>
                        <div class="znr-stat-label">dostupnost podataka</div>
                    </div>
                </div>

                <div class="znr-cards">
                    <div class="znr-card">
                        <h3>Za koga je aplikacija?</h3>
                        <p>
                            Za stručnjake zaštite na radu, ovlaštenike poslodavaca, odgovorne osobe,
                            voditelje sustava i organizacije koje žele bolju kontrolu nad rokovima,
                            evidencijama i dokumentacijom.
                        </p>
                    </div>

                    <div class="znr-card">
                        <h3>Što ZNR LIDER omogućuje?</h3>
                        <ul>
                            <li>zaposlenici, edukacije i liječnički pregledi</li>
                            <li>radna oprema, OZO i vatrogasni aparati</li>
                            <li>kemikalije, zapažanja i incidenti</li>
                            <li>KPI pokazatelji, zadaci i dokumentacija</li>
                        </ul>
                    </div>
                </div>

                <div class="znr-about">
                    <h3>O autoru aplikacije</h3>

                    <p>
                        ZNR LIDER nastao je iz entuzijazma, operativnog nacionalnog i međunarodnog iskustva i želje da tržište
                        dobije praktičnu i operativnu aplikaciju koja pomaže svima koji svakodnevno obavljaju
                        poslove zaštite na radu, zaštite od požara i zaštite okoliša. Potrebno je napomenuti da glavni cilj ovog sustava nije zarada već pružanje praktičnog alata koji olakšava svakodnevni rad i doprinosi sigurnosti i učinkovitosti organizacija. Na tržištu postoji mnogo aplikacija ali ovo je prva aplikacija razvijena od strane stručnjaka zaštite na radu iz pogona i terena, koji razumije stvarne izazove i potrebe korisnika.
                    </p>

                    <p>
                        Aplikacija je razvijena na temelju više od 20 godina rada u području zaštite
                        na radu, zaštite od požara, zaštite okoliša, vođenja ISO sustava i rada s
                        dokumentacijom, rokovima, ljudima i stvarnim izazovima na terenu.
                    </p>

                    <p>
                        Strast prema programiranju započela je još u osnovnoj školi na računalima
                        Orao, Amiga i Commodore, a ZNR LIDER je spoj praktičnog iskustva, razumijevanja
                        potreba korisnika i želje da svakodnevni posao bude jednostavniji, pregledniji
                        i sigurniji. AI tehnologije su omogućile da se aplikacija razvije brže i učinkovitije, a korisničko iskustvo je u fokusu svakog novog modula.
                    </p>
                </div>

                <div class="znr-trust">
                    <div class="znr-trust-item">✓ GDPR usklađeno</div>
                    <div class="znr-trust-item">✓ Sigurna pohrana podataka</div>
                    <div class="znr-trust-item">✓ Evidencija aktivnosti korisnika</div>
                    <div class="znr-trust-item">✓ Višekorisnički sustav organizacije</div>
                </div>

                <div class="znr-test-notice">
                    <div class="znr-test-title">
                        ⚠ TESTNA VERZIJA APLIKACIJE
                    </div>

                    <div class="znr-test-text">
                        ZNR LIDER trenutno se nalazi u fazi razvoja i internog testiranja.
                        Aplikacija se koristi isključivo za vlastite potrebe radi razvoja,
                        provjere funkcionalnosti i ispitivanja rada sustava.
                    </div>

                    <div class="znr-test-text">
                        Sustav nije dostupan za komercijalno korištenje niti se za njegovo
                        korištenje naplaćuje bilo kakva naknada.
                    </div>
                </div>
            </section>

            <section class="znr-panel znr-form">
                <div class="znr-form-heading">
                    <div class="znr-form-logo">ZL</div>
                    <h1>Prijava u sustav</h1>
                    <p>Unesite korisničke podatke za siguran pristup aplikaciji.</p>
                </div>

               <form wire:submit="authenticate">
                    {{ $this->form }}

                    <div class="znr-login-button">
                        <x-filament::button
                            type="submit"
                            icon="heroicon-m-arrow-right-on-rectangle"
                            size="lg"
                            class="w-full"
                        >
                            Prijavi se
                        </x-filament::button>
                    </div>
                </form>

                <div class="znr-login-features">
                <div class="znr-login-feature">
                    <div class="znr-login-feature-title">
                        🔒 Siguran pristup
                    </div>

                    <div class="znr-login-feature-text">
                        Korisnički računi, organizacijska prava pristupa i evidencija aktivnosti.
                    </div>
                </div>

                <div class="znr-login-feature">
                    <div class="znr-login-feature-title">
                        📋 Upravljanje obvezama
                    </div>

                    <div class="znr-login-feature-text">
                        Praćenje rokova, liječničkih pregleda, edukacija, radne opreme i ostalih obveza.
                    </div>
                </div>

                <div class="znr-login-feature">
                    <div class="znr-login-feature-title">
                        📊 KPI i izvještavanje
                    </div>

                    <div class="znr-login-feature-text">
                        KPI pokazatelji, statistike, izvještaji i analiza sigurnosnih pokazatelja.
                    </div>
                </div>
            </div>

            <div class="znr-version">
                ZNR LIDER v4 • © {{ date('Y') }}
            </div>

            <div class="znr-note">
                Pristup aplikaciji dopušten je samo ovlaštenim korisnicima.
                Aktivnosti se mogu evidentirati radi sigurnosti, kontrole pristupa i sljedivosti sustava.
            </div>
            </section>
        </div>
    </div>
</x-filament-panels::page.simple>