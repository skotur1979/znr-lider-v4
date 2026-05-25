<x-filament-panels::page>
    <style>
        .privacy-wrap { display: grid; gap: 18px; max-width: 1100px; }
        .privacy-card { background: #111827; border: 1px solid #374151; border-radius: 18px; padding: 22px; box-shadow: 0 10px 25px rgba(0,0,0,.18); }
        .privacy-title { font-size: 20px; font-weight: 800; margin-bottom: 14px; color: #fff; }
        .privacy-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 14px; }
        .privacy-item { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: 14px; padding: 14px; }
        .privacy-label { font-size: 12px; color: #9ca3af; margin-bottom: 5px; }
        .privacy-value { font-weight: 700; color: #fff; }
        .privacy-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; padding: 10px 15px; font-weight: 700; font-size: 14px; text-decoration: none; border: 0; cursor: pointer; }
        .btn-amber { background: #f59e0b; color: #111827; }
        .btn-red { background: #dc2626; color: white; }
        .btn-dark { background: #374151; color: white; }
        .btn-outline { border: 1px solid #4b5563; color: #fff; background: transparent; }
        .privacy-text { color: #d1d5db; line-height: 1.6; }
        .privacy-danger { background: rgba(127,29,29,.35); border-color: rgba(239,68,68,.35); }
        .privacy-textarea { width: 100%; min-height: 86px; border-radius: 12px; border: 1px solid #4b5563; background: #030712; color: #fff; padding: 12px; margin: 10px 0; }
        @media (max-width: 768px) { .privacy-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="privacy-wrap">

        @if (session('status'))
            <div class="privacy-card" style="border-color:#16a34a;">
                <div class="privacy-value">{{ session('status') }}</div>
            </div>
        @endif

        <div class="privacy-card">
            <div class="privacy-title">Status privatnosti i pravnih dokumenata</div>

            <div class="privacy-grid">
                <div class="privacy-item">
                    <div class="privacy-label">Korisnik</div>
                    <div class="privacy-value">{{ auth()->user()->name }}</div>
                </div>

                <div class="privacy-item">
                    <div class="privacy-label">E-mail</div>
                    <div class="privacy-value">{{ auth()->user()->email }}</div>
                </div>

                <div class="privacy-item">
                    <div class="privacy-label">Uvjeti korištenja</div>
                    <div class="privacy-value">
                        @if(auth()->user()->accepted_terms_at)
                            {{ auth()->user()->accepted_terms_at->format('d.m.Y. H:i') }} / verzija {{ auth()->user()->terms_version ?? '-' }}
                        @else
                            Nije prihvaćeno
                        @endif
                    </div>
                </div>

                <div class="privacy-item">
                    <div class="privacy-label">Pravila privatnosti</div>
                    <div class="privacy-value">
                        @if(auth()->user()->accepted_privacy_at)
                            {{ auth()->user()->accepted_privacy_at->format('d.m.Y. H:i') }} / verzija {{ auth()->user()->privacy_version ?? '-' }}
                        @else
                            Nije prihvaćeno
                        @endif
                    </div>
                </div>

                <div class="privacy-item">
                    <div class="privacy-label">Newsletter</div>
                    <div class="privacy-value">{{ auth()->user()->newsletter_opt_in ? 'Da' : 'Ne' }}</div>
                </div>

                <div class="privacy-item">
                    <div class="privacy-label">Zahtjev za brisanje računa</div>
                    <div class="privacy-value">
                        @if(auth()->user()->account_deletion_requested_at)
                            Podnesen {{ auth()->user()->account_deletion_requested_at->format('d.m.Y. H:i') }}
                        @else
                            Nije podnesen
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="privacy-card">
            <div class="privacy-title">Moji osobni podaci</div>
            <p class="privacy-text">
                Preuzmite export osnovnih osobnih podataka koji se vode uz vaš korisnički račun.
            </p>

            <div class="privacy-actions">
                <a href="{{ route('user.privacy.export') }}" class="btn btn-amber">
                    Export mojih podataka
                </a>
            </div>
        </div>

        <div class="privacy-card">
            <div class="privacy-title">Pravni dokumenti</div>

            <div class="privacy-actions">
                <a href="{{ route('legal.terms') }}" target="_blank" class="btn btn-outline">Uvjeti korištenja</a>
                <a href="{{ route('legal.privacy') }}" target="_blank" class="btn btn-outline">Pravila privatnosti</a>
                <a href="{{ route('legal.cookies') }}" target="_blank" class="btn btn-outline">Politika kolačića</a>
            </div>
        </div>

        <div class="privacy-card privacy-danger">
            <div class="privacy-title">GDPR zahtjevi</div>

            <div class="privacy-grid">
                <form method="POST" action="{{ route('legal.withdraw') }}">
                    @csrf

                    <div class="privacy-value">Povlačenje privole / prihvaćanja</div>

                    <textarea name="reason" class="privacy-textarea" placeholder="Razlog nije obavezan"></textarea>

                    <button type="submit"
                            onclick="return confirm('Jeste li sigurni? Nakon povlačenja bit će potrebno ponovno prihvatiti važeće dokumente.')"
                            class="btn btn-red">
                        Povuci privolu
                    </button>
                </form>

                <form method="POST" action="{{ route('account.deletion.request') }}">
                    @csrf

                    <div class="privacy-value">Zahtjev za brisanje korisničkog računa</div>

                    <textarea name="reason" class="privacy-textarea" placeholder="Razlog nije obavezan"></textarea>

                    <button type="submit"
                            onclick="return confirm('Jeste li sigurni da želite podnijeti zahtjev za brisanje korisničkog računa?')"
                            class="btn btn-dark">
                        Zatraži brisanje računa
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-filament-panels::page>
