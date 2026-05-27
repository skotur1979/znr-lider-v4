<x-filament-panels::page>
    <style>
    .privacy-wrap{
        display:grid;
        gap:18px;
        max-width:1100px;
    }

    .privacy-card{
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:18px;
        padding:22px;
        box-shadow:
            0 1px 2px rgba(15,23,42,.04),
            0 10px 24px rgba(15,23,42,.06);
        transition:all .2s ease;
    }

    .privacy-card:hover{
        transform:translateY(-1px);
        box-shadow:
            0 4px 12px rgba(15,23,42,.08),
            0 18px 32px rgba(15,23,42,.08);
    }

    .dark .privacy-card{
        background:#0f172a;
        border-color:#1e293b;
        box-shadow:
            0 1px 2px rgba(0,0,0,.25),
            0 14px 30px rgba(0,0,0,.28);
    }

    .privacy-title{
        font-size:20px;
        font-weight:800;
        margin-bottom:16px;
        color:#0f172a;
        letter-spacing:-0.02em;
    }

    .dark .privacy-title{
        color:#f8fafc;
    }

    .privacy-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:14px;
    }

    .privacy-item{
        background:#f8fafc;
        border:1px solid #e2e8f0;
        border-radius:14px;
        padding:14px;
    }

    .dark .privacy-item{
        background:rgba(255,255,255,.03);
        border-color:rgba(255,255,255,.06);
    }

    .privacy-label{
        font-size:12px;
        font-weight:600;
        color:#64748b;
        margin-bottom:6px;
        text-transform:uppercase;
        letter-spacing:.04em;
    }

    .dark .privacy-label{
        color:#94a3b8;
    }

    .privacy-value{
        font-weight:700;
        color:#0f172a;
    }

    .dark .privacy-value{
        color:#f8fafc;
    }

    .privacy-actions{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        margin-top:16px;
    }

    .btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border-radius:12px;
        padding:10px 15px;
        font-weight:700;
        font-size:14px;
        text-decoration:none;
        border:1px solid transparent;
        cursor:pointer;
        transition:all .18s ease;
    }

    .btn:hover{
        transform:translateY(-1px);
    }

    .btn-amber{
        background:#f59e0b;
        color:#111827;
    }

    .btn-amber:hover{
        background:#fbbf24;
    }

    .btn-red{
        background:#dc2626;
        color:white;
    }

    .btn-red:hover{
        background:#ef4444;
    }

    .btn-dark{
        background:#334155;
        color:white;
    }

    .btn-dark:hover{
        background:#475569;
    }

    .btn-outline{
        border:1px solid #d1d5db;
        color:#0f172a;
        background:#ffffff;
    }

    .btn-outline:hover{
        background:#f8fafc;
    }

    .dark .btn-outline{
        border-color:#475569;
        color:#f8fafc;
        background:transparent;
    }

    .dark .btn-outline:hover{
        background:rgba(255,255,255,.05);
    }

    .privacy-text{
        color:#475569;
        line-height:1.7;
    }

    .dark .privacy-text{
        color:#cbd5e1;
    }

    .privacy-danger{
        background:#fff7f7;
        border-color:#fecaca;
    }

    .dark .privacy-danger{
        background:rgba(127,29,29,.20);
        border-color:rgba(239,68,68,.25);
    }

    .privacy-textarea{
        width:100%;
        min-height:86px;
        border-radius:12px;
        border:1px solid #d1d5db;
        background:#ffffff;
        color:#0f172a;
        padding:12px;
        margin:10px 0;
        transition:border-color .15s ease, box-shadow .15s ease;
    }

    .privacy-textarea:focus{
        outline:none;
        border-color:#f59e0b;
        box-shadow:0 0 0 3px rgba(245,158,11,.15);
    }

    .dark .privacy-textarea{
        border-color:#475569;
        background:#020617;
        color:#f8fafc;
    }

    @media (max-width:768px){
        .privacy-grid{
            grid-template-columns:1fr;
        }
    }
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
