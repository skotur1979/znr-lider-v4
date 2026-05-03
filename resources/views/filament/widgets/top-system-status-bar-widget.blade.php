@php
    use App\Models\Incident;
    use App\Models\User;
    use Illuminate\Support\Carbon;

    $barClass = match ($state) {
        'critical' => 'tsb-critical',
        'warning' => 'tsb-warning',
        default => 'tsb-ok',
    };

    $daysWithoutLta = null;
    $recordDaysWithoutLta = null;

    try {
        $user = auth()->user();

        $baseQuery = Incident::query()->withoutTrashed();

        if ($user && method_exists($user, 'isSuperAdmin') && ! $user->isSuperAdmin()) {
            $ownerId = method_exists($user, 'ownerId') ? $user->ownerId() : $user->id;

            $organizationUserIds = User::query()
                ->where('id', $ownerId)
                ->orWhere('parent_user_id', $ownerId)
                ->pluck('id');

            $baseQuery->whereIn('user_id', $organizationUserIds);
        }

        $ltaDates = (clone $baseQuery)
            ->where('type_of_incident', 'like', '%LTA%')
            ->whereNotNull('date_occurred')
            ->orderBy('date_occurred')
            ->pluck('date_occurred')
            ->map(fn ($date) => Carbon::parse($date)->startOfDay())
            ->values();

        $lastLtaDate = $ltaDates->last();

        if ($lastLtaDate) {
            $daysWithoutLta = $lastLtaDate->diffInDays(Carbon::today());
        }

        if ($ltaDates->count() > 0) {
            $record = 0;

            for ($i = 0; $i < $ltaDates->count() - 1; $i++) {
                $daysBetween = $ltaDates[$i]->diffInDays($ltaDates[$i + 1]);

                if ($daysBetween > $record) {
                    $record = $daysBetween;
                }
            }

            if ($daysWithoutLta !== null && $daysWithoutLta > $record) {
                $record = $daysWithoutLta;
            }

            $recordDaysWithoutLta = $record;
        }
    } catch (\Throwable $e) {
        $daysWithoutLta = null;
        $recordDaysWithoutLta = null;
    }

    $recordIsActive = $daysWithoutLta !== null
        && $recordDaysWithoutLta !== null
        && $daysWithoutLta >= $recordDaysWithoutLta;
@endphp

<x-filament-widgets::widget>
    <div class="tsb-wrap {{ $barClass }}">
        <div class="tsb-accent"></div>

        <div class="tsb-main">
            <div class="tsb-left">
                <div class="tsb-header">
                    <span class="tsb-dot"></span>
                    <span class="tsb-overline">Status sustava</span>

                    <span class="tsb-state-pill tsb-state-pill-{{ $state }}">
                        {{ $title }}
                    </span>
                </div>

                <div class="tsb-summary-row">
                    <div class="tsb-alert-icon">!</div>

                    <div class="tsb-summary-copy">
                        @if ($totalExpired > 0)
                            <div class="tsb-summary-title">
                                Sustav je kritičan zbog <strong>{{ $totalExpired }}</strong> isteklih stavki.
                            </div>
                            <div class="tsb-summary-desc">
                                Riješite istekle obaveze i smanjite rizik.
                            </div>
                        @elseif ($totalSoon > 0)
                            <div class="tsb-summary-title">
                                Sustav zahtijeva pažnju — <strong>{{ $totalSoon }}</strong> stavki uskoro istječe.
                            </div>
                            <div class="tsb-summary-desc">
                                Planirajte aktivnosti prije isteka rokova.
                            </div>
                        @else
                            <div class="tsb-summary-title">Sustav je uredan.</div>
                            <div class="tsb-summary-desc">
                                Trenutno nema isteklih ni skorih rokova.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="tsb-right">
                <div class="tsb-score-card tsb-score-danger">
                    <div class="tsb-score-icon">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="tsb-score-svg" />
                    </div>
                    <div>
                        <span class="tsb-score-label">Isteklo</span>
                        <span class="tsb-score-value">{{ $totalExpired }}</span>
                    </div>
                </div>

                <div class="tsb-score-card tsb-score-warning">
                    <div class="tsb-score-icon">
                        <x-filament::icon icon="heroicon-o-clock" class="tsb-score-svg" />
                    </div>
                    <div>
                        <span class="tsb-score-label">Uskoro</span>
                        <span class="tsb-score-value">{{ $totalSoon }}</span>
                    </div>
                </div>

                <a href="{{ route('znr.general-report.pdf') }}" target="_blank" class="tsb-report-card">
                    <x-filament::icon icon="heroicon-o-document-chart-bar" class="tsb-report-icon" />

                    <div class="tsb-report-text">
                        <span class="tsb-report-label">PDF</span>
                        <span class="tsb-report-title">Izvještaj</span>
                    </div>
                </a>

                <div class="tsb-score-card tsb-score-lta">
                    <div class="tsb-score-icon">
                        <x-filament::icon icon="heroicon-o-shield-check" class="tsb-score-svg" />
                    </div>
                    <div>
                        <span class="tsb-score-label">Bez LTA</span>
                        <span class="tsb-score-value">{{ $daysWithoutLta ?? '—' }}</span>
                        <span class="tsb-score-small">{{ $daysWithoutLta !== null ? 'dana' : '' }}</span>
                    </div>
                </div>

                <div class="tsb-score-card tsb-score-record {{ $recordIsActive ? 'tsb-record-active' : '' }}">
                    <div class="tsb-score-icon">
                        <x-filament::icon icon="heroicon-o-trophy" class="tsb-score-svg" />
                    </div>
                    <div>
                        <span class="tsb-score-label">Rekord LTA</span>
                        <span class="tsb-score-value">{{ $recordDaysWithoutLta ?? '—' }}</span>
                        <span class="tsb-score-small">{{ $recordDaysWithoutLta !== null ? 'dana' : '' }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if (count($rows))
            <div class="tsb-modules">
                @foreach ($rows as $row)
                    <div class="tsb-module {{ ($row['expired_count'] ?? 0) > 0 ? 'tsb-module-expired' : '' }}">
                        <div class="tsb-module-inner">
                            <span class="tsb-module-icon">{{ $row['icon'] }}</span>

                            <div class="tsb-module-text">
                                <span class="tsb-module-label">{{ $row['label'] }}</span>

                                <div class="tsb-module-stats">
                                    @if (($row['expired_count'] ?? 0) > 0 && ! empty($row['expired_url']))
                                        <a href="{{ $row['expired_url'] }}" class="tsb-expired-link tsb-expired-blink">
                                            Isteklo {{ $row['expired_count'] }}
                                        </a>
                                    @else
                                        <span class="tsb-expired-text">
                                            Isteklo {{ $row['expired_count'] }}
                                        </span>
                                    @endif

                                    <span class="tsb-sep">/</span>

                                    <span class="tsb-soon-text">
                                        Uskoro {{ $row['soon_count'] }}
                                    </span>
                                </div>
                            </div>

                            <span class="tsb-chevron">›</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        .tsb-wrap{
            position:relative;
            overflow:hidden;
            border-radius:20px;
            padding:22px;
            margin-bottom:16px;
            border:1px solid rgba(148,163,184,.16);
            background:#ffffff;
            box-shadow:0 10px 26px rgba(15,23,42,.06);
        }

        .dark .tsb-wrap{
            background:
                radial-gradient(circle at 0% 0%, rgba(37,99,235,.22), transparent 34%),
                radial-gradient(circle at 100% 0%, rgba(239,68,68,.10), transparent 30%),
                linear-gradient(180deg, rgba(8,18,40,.98), rgba(4,10,24,.98));
            border:1px solid rgba(96,165,250,.18);
            box-shadow:0 18px 42px rgba(0,0,0,.24);
        }

        .tsb-accent{position:absolute;left:0;top:0;bottom:0;width:5px;background:#22c55e;box-shadow:0 0 22px rgba(34,197,94,.35);}
        .tsb-critical .tsb-accent{background:#ef4444;box-shadow:0 0 24px rgba(239,68,68,.45);}
        .tsb-warning .tsb-accent{background:#f59e0b;box-shadow:0 0 24px rgba(245,158,11,.40);}

        .tsb-main{display:flex;justify-content:space-between;gap:18px;align-items:center;flex-wrap:wrap;}
        .tsb-left{min-width:260px;flex:1;}
        .tsb-header{display:flex;align-items:center;gap:9px;margin-bottom:14px;flex-wrap:wrap;}

        .tsb-dot{width:11px;height:11px;border-radius:999px;background:#22c55e;box-shadow:0 0 0 5px rgba(34,197,94,.12);flex-shrink:0;}
        .tsb-warning .tsb-dot{background:#f59e0b;box-shadow:0 0 0 5px rgba(245,158,11,.12);}
        .tsb-critical .tsb-dot{background:#ef4444;box-shadow:0 0 0 5px rgba(239,68,68,.15);}

        .tsb-overline{font-size:.72rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#64748b;}
        .dark .tsb-overline{color:#bfdbfe;}

        .tsb-state-pill{display:inline-flex;align-items:center;border-radius:999px;padding:4px 10px;font-size:.72rem;font-weight:900;line-height:1;border:1px solid transparent;}
        .tsb-state-pill-ok{color:#15803d;background:rgba(34,197,94,.10);border-color:rgba(34,197,94,.22);}
        .tsb-state-pill-warning{color:#d97706;background:rgba(245,158,11,.10);border-color:rgba(245,158,11,.22);}
        .tsb-state-pill-critical{color:#dc2626;background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.24);}
        .dark .tsb-state-pill-ok{color:#86efac;background:rgba(34,197,94,.12);border-color:rgba(34,197,94,.24);}
        .dark .tsb-state-pill-warning{color:#fde68a;background:rgba(245,158,11,.12);border-color:rgba(245,158,11,.26);}
        .dark .tsb-state-pill-critical{color:#fecaca;background:rgba(239,68,68,.18);border-color:rgba(239,68,68,.32);}

        .tsb-summary-row{display:flex;align-items:center;gap:14px;}
        .tsb-alert-icon{width:52px;height:52px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border-radius:18px;font-size:1.8rem;line-height:1;font-weight:1000;color:#ef4444;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.28);box-shadow:0 0 24px rgba(239,68,68,.16);}
        .dark .tsb-alert-icon{color:#fca5a5;background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.30);box-shadow:0 0 28px rgba(239,68,68,.18);}

        .tsb-summary-copy{min-width:0;}
        .tsb-summary-title{font-size:1.24rem;line-height:1.12;font-weight:950;color:#0f172a;letter-spacing:-.02em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:760px;}
        .dark .tsb-summary-title{color:#ffffff;}
        .tsb-summary-title strong{color:#ef4444;font-weight:1000;}
        .tsb-summary-desc{margin-top:5px;max-width:520px;font-size:.82rem;line-height:1.35;color:#64748b;}
        .dark .tsb-summary-desc{color:#dbeafe;}

        .tsb-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}

        .tsb-report-card{
            min-width:118px;
            height:62px;
            display:flex;
            align-items:center;
            justify-content:flex-start;
            gap:10px;
            padding:0 14px;
            border-radius:16px;
            border:1px solid rgba(37,99,235,.40);
            background:rgba(37,99,235,.16);
            color:#1d4ed8;
            text-decoration:none;
            transition:all .15s ease;
        }

        .tsb-report-card:hover{transform:translateY(-2px);border-color:rgba(37,99,235,.70);background:rgba(37,99,235,.22);box-shadow:0 12px 22px rgba(37,99,235,.18);}
        .dark .tsb-report-card{background:rgba(37,99,235,.18);border-color:rgba(96,165,250,.45);color:#bfdbfe;}
        .dark .tsb-report-card:hover{background:rgba(37,99,235,.28);border-color:rgba(96,165,250,.70);}
        .tsb-report-icon{width:20px;height:20px;color:#2563eb;}
        .dark .tsb-report-icon{color:#60a5fa;}
        .tsb-report-text{display:flex;flex-direction:column;line-height:1.1;}
        .tsb-report-label{display:block;font-size:.72rem;line-height:1;font-weight:850;color:#2563eb;}
        .dark .tsb-report-label{color:#bfdbfe;}
        .tsb-report-title{display:block;margin-top:5px;font-size:.76rem;line-height:1.05;font-weight:950;color:#1e3a8a;white-space:nowrap;}
        .dark .tsb-report-title{color:#ffffff;}

        .tsb-score-card{
            min-width:118px;
            height:62px;
            display:flex;
            align-items:center;
            gap:10px;
            padding:10px 13px;
            border-radius:15px;
            border:1px solid rgba(148,163,184,.16);
            background:rgba(255,255,255,.70);
            box-shadow:inset 0 1px 0 rgba(255,255,255,.05);
        }

        .dark .tsb-score-card{background:rgba(255,255,255,.045);border-color:rgba(148,163,184,.18);}
        .tsb-score-danger{border-color:rgba(239,68,68,.30);background:rgba(239,68,68,.075);}
        .tsb-score-warning{border-color:rgba(245,158,11,.30);background:rgba(245,158,11,.075);}
        .tsb-score-lta{border-color:rgba(34,197,94,.32);background:rgba(34,197,94,.08);}
        .tsb-score-record{border-color:rgba(59,130,246,.34);background:rgba(59,130,246,.08);}
        .dark .tsb-score-lta{border-color:rgba(74,222,128,.30);background:rgba(34,197,94,.08);}
        .dark .tsb-score-record{border-color:rgba(96,165,250,.32);background:rgba(59,130,246,.09);}

        .tsb-record-active{
            border-color:rgba(34,197,94,.55) !important;
            background:rgba(34,197,94,.12) !important;
            box-shadow:0 0 18px rgba(34,197,94,.22), inset 0 1px 0 rgba(255,255,255,.06);
        }

        .tsb-record-active .tsb-score-icon{
            color:#22c55e !important;
            background:rgba(34,197,94,.16) !important;
            border-color:rgba(34,197,94,.35) !important;
        }

        .tsb-record-active .tsb-score-value,
        .tsb-record-active .tsb-score-small{
            color:#22c55e !important;
        }

        .dark .tsb-record-active{
            border-color:rgba(74,222,128,.55) !important;
            background:rgba(34,197,94,.13) !important;
            box-shadow:0 0 22px rgba(34,197,94,.28), inset 0 1px 0 rgba(255,255,255,.06);
        }

        .tsb-score-icon{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:12px;flex-shrink:0;}
        .tsb-score-danger .tsb-score-icon{color:#ef4444;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);}
        .tsb-score-warning .tsb-score-icon{color:#f59e0b;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.25);}
        .tsb-score-lta .tsb-score-icon{color:#22c55e;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);}
        .tsb-score-record .tsb-score-icon{color:#3b82f6;background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.25);}
        .tsb-score-svg{width:18px;height:18px;}

        .tsb-score-label{display:block;font-size:.70rem;line-height:1;font-weight:850;color:#64748b;white-space:nowrap;}
        .dark .tsb-score-label{color:#cbd5e1;}
        .tsb-score-value{display:block;margin-top:4px;font-size:1.34rem;line-height:1;font-weight:1000;color:#0f172a;letter-spacing:-.02em;}
        .dark .tsb-score-value{color:#ffffff;}
        .tsb-score-small{display:block;margin-top:2px;font-size:.66rem;line-height:1;font-weight:800;color:#16a34a;white-space:nowrap;}
        .dark .tsb-score-small{color:#86efac;}
        .tsb-score-record .tsb-score-small{color:#60a5fa;}

        .tsb-modules{margin-top:18px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;}
        .tsb-module{min-height:72px;padding:13px 14px;border-radius:15px;border:1px solid rgba(148,163,184,.16);background:rgba(255,255,255,.64);transition:transform .15s ease,border-color .15s ease,background .15s ease,box-shadow .15s ease;}
        .dark .tsb-module{background:rgba(255,255,255,.035);border-color:rgba(148,163,184,.14);}
        .tsb-module:hover{transform:translateY(-2px);border-color:rgba(96,165,250,.36);box-shadow:0 10px 18px rgba(0,0,0,.10);}
        .tsb-module-expired{border-color:rgba(239,68,68,.34);background:rgba(239,68,68,.045);}
        .dark .tsb-module-expired{border-color:rgba(239,68,68,.30);background:rgba(239,68,68,.065);}
        .tsb-module-inner{display:flex;align-items:center;gap:11px;min-width:0;width:100%;}
        .tsb-module-icon{width:28px;height:28px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.15rem;line-height:1;}
        .tsb-module-text{min-width:0;flex:1;}
        .tsb-module-label{display:block;font-size:.88rem;line-height:1.15;font-weight:950;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .dark .tsb-module-label{color:#ffffff;}
        .tsb-module-stats{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:7px;font-size:.79rem;line-height:1;font-weight:900;}
        .tsb-expired-link,.tsb-expired-text{color:#ef4444;font-weight:1000;}
        .tsb-expired-link{text-decoration:none;cursor:pointer;transition:opacity .15s ease;}
        .tsb-expired-link:hover{opacity:.82;text-decoration:none;}
        .tsb-expired-blink{animation:tsbTextPulse 2.2s ease-in-out infinite;}
        .tsb-soon-text{color:#f59e0b;font-weight:1000;}
        .tsb-sep{color:#94a3b8;font-weight:900;}
        .tsb-chevron{margin-left:auto;flex-shrink:0;color:#93c5fd;font-size:1.45rem;line-height:1;opacity:.9;}

        @keyframes tsbTextPulse{
            0%{ text-shadow:0 0 0 rgba(239,68,68,0); }
            50%{ text-shadow:0 0 10px rgba(239,68,68,.75); }
            100%{ text-shadow:0 0 0 rgba(239,68,68,0); }
        }

        @media (max-width:1280px){
            .tsb-modules{grid-template-columns:repeat(2,minmax(0,1fr));}
        }

        @media (max-width:700px){
            .tsb-wrap{padding:18px 16px;}
            .tsb-right{width:100%;}
            .tsb-score-card{min-width:0;flex:1;}
            .tsb-report-card{min-width:100%;width:100%;}
            .tsb-summary-row{align-items:flex-start;}
            .tsb-alert-icon{width:48px;height:48px;font-size:1.6rem;}
            .tsb-summary-title{white-space:normal;}
            .tsb-modules{grid-template-columns:1fr;}
        }

        @media (prefers-reduced-motion:reduce){
            .tsb-expired-blink,
            .tsb-module{
                animation:none;
                transition:none;
            }
        }
    </style>
</x-filament-widgets::widget>@php
    $barClass = match ($state) {
        'critical' => 'tsb-critical',
        'warning' => 'tsb-warning',
        default => 'tsb-ok',
    };

    $daysWithoutLta = $daysWithoutLta ?? null;
    $recordDaysWithoutLta = $recordDaysWithoutLta ?? null;

    $recordIsActive = $recordIsActive ?? (
        $daysWithoutLta !== null
        && $recordDaysWithoutLta !== null
        && $daysWithoutLta >= $recordDaysWithoutLta
    );
@endphp

<x-filament-widgets::widget>
    <div class="tsb-wrap {{ $barClass }}">
        <div class="tsb-accent"></div>

        <div class="tsb-main">
            <div class="tsb-left">
                <div class="tsb-header">
                    <span class="tsb-dot"></span>
                    <span class="tsb-overline">Status sustava</span>

                    <span class="tsb-state-pill tsb-state-pill-{{ $state }}">
                        {{ $title }}
                    </span>
                </div>

                <div class="tsb-summary-row">
                    <div class="tsb-alert-icon">!</div>

                    <div class="tsb-summary-copy">
                        @if ($totalExpired >= \App\Filament\Widgets\TopSystemStatusBarWidget::CRITICAL_EXPIRED_THRESHOLD)
                            <div class="tsb-summary-title">
                                Sustav je kritičan zbog <strong>{{ $totalExpired }}</strong> isteklih stavki.
                            </div>
                            <div class="tsb-summary-desc">
                                Riješite istekle obaveze i smanjite rizik.
                            </div>
                        @elseif ($totalExpired > 0)
                            <div class="tsb-summary-title">
                                Sustav zahtijeva pažnju — <strong>{{ $totalExpired }}</strong> isteklih stavki.
                            </div>
                            <div class="tsb-summary-desc">
                                Potrebno je planirati rješavanje isteklih obveza.
                            </div>
                        @elseif ($totalSoon > 0)
                            <div class="tsb-summary-title">
                                Sustav zahtijeva pažnju — <strong>{{ $totalSoon }}</strong> stavki uskoro istječe.
                            </div>
                            <div class="tsb-summary-desc">
                                Planirajte aktivnosti prije isteka rokova.
                            </div>
                        @else
                            <div class="tsb-summary-title">Sustav je uredan.</div>
                            <div class="tsb-summary-desc">
                                Trenutno nema isteklih ni skorih rokova.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="tsb-right">
                <div class="tsb-score-card tsb-score-danger">
                    <div class="tsb-score-icon">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="tsb-score-svg" />
                    </div>
                    <div>
                        <span class="tsb-score-label">Isteklo</span>
                        <span class="tsb-score-value">{{ $totalExpired }}</span>
                    </div>
                </div>

                <div class="tsb-score-card tsb-score-warning">
                    <div class="tsb-score-icon">
                        <x-filament::icon icon="heroicon-o-clock" class="tsb-score-svg" />
                    </div>
                    <div>
                        <span class="tsb-score-label">Uskoro</span>
                        <span class="tsb-score-value">{{ $totalSoon }}</span>
                    </div>
                </div>

                <a href="{{ route('znr.general-report.pdf') }}" target="_blank" class="tsb-report-card">
                    <x-filament::icon icon="heroicon-o-document-chart-bar" class="tsb-report-icon" />

                    <div class="tsb-report-text">
                        <span class="tsb-report-label">PDF</span>
                        <span class="tsb-report-title">Izvještaj</span>
                    </div>
                </a>

                <div class="tsb-score-card tsb-score-lta">
                    <div class="tsb-score-icon">
                        <x-filament::icon icon="heroicon-o-shield-check" class="tsb-score-svg" />
                    </div>
                    <div>
                        <span class="tsb-score-label">Bez LTA</span>
                        <span class="tsb-score-value">{{ $daysWithoutLta ?? '—' }}</span>
                        <span class="tsb-score-small">{{ $daysWithoutLta !== null ? 'dana' : '' }}</span>
                    </div>
                </div>

                <div class="tsb-score-card tsb-score-record {{ $recordIsActive ? 'tsb-record-active' : '' }}">
                    <div class="tsb-score-icon">
                        <x-filament::icon icon="heroicon-o-trophy" class="tsb-score-svg" />
                    </div>
                    <div>
                        <span class="tsb-score-label">Rekord LTA</span>
                        <span class="tsb-score-value">{{ $recordDaysWithoutLta ?? '—' }}</span>
                        <span class="tsb-score-small">{{ $recordDaysWithoutLta !== null ? 'dana' : '' }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if (count($rows))
            <div class="tsb-modules">
                @foreach ($rows as $row)
                    <div class="tsb-module {{ ($row['expired_count'] ?? 0) > 0 ? 'tsb-module-expired' : '' }}">
                        <div class="tsb-module-inner">
                            <span class="tsb-module-icon">{{ $row['icon'] }}</span>

                            <div class="tsb-module-text">
                                <span class="tsb-module-label">{{ $row['label'] }}</span>

                                <div class="tsb-module-stats">
                                    @if (($row['expired_count'] ?? 0) > 0 && ! empty($row['expired_url']))
                                        <a href="{{ $row['expired_url'] }}" class="tsb-expired-link tsb-expired-blink">
                                            Isteklo {{ $row['expired_count'] }}
                                        </a>
                                    @else
                                        <span class="tsb-expired-text">
                                            Isteklo {{ $row['expired_count'] }}
                                        </span>
                                    @endif

                                    <span class="tsb-sep">/</span>

                                    <span class="tsb-soon-text">
                                        Uskoro {{ $row['soon_count'] }}
                                    </span>
                                </div>
                            </div>

                            <span class="tsb-chevron">›</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        .tsb-wrap{
            position:relative;
            overflow:hidden;
            border-radius:20px;
            padding:22px;
            margin-bottom:16px;
            border:1px solid rgba(148,163,184,.16);
            background:#ffffff;
            box-shadow:0 10px 26px rgba(15,23,42,.06);
        }

        .dark .tsb-wrap{
            background:
                radial-gradient(circle at 0% 0%, rgba(37,99,235,.22), transparent 34%),
                radial-gradient(circle at 100% 0%, rgba(239,68,68,.10), transparent 30%),
                linear-gradient(180deg, rgba(8,18,40,.98), rgba(4,10,24,.98));
            border:1px solid rgba(96,165,250,.18);
            box-shadow:0 18px 42px rgba(0,0,0,.24);
        }

        .tsb-accent{
            position:absolute;
            left:0;
            top:0;
            bottom:0;
            width:5px;
            background:#22c55e;
            box-shadow:0 0 22px rgba(34,197,94,.35);
        }

        .tsb-critical .tsb-accent{
            background:#ef4444;
            box-shadow:0 0 24px rgba(239,68,68,.45);
        }

        .tsb-warning .tsb-accent{
            background:#f59e0b;
            box-shadow:0 0 24px rgba(245,158,11,.40);
        }

        .tsb-main{
            display:flex;
            justify-content:space-between;
            gap:18px;
            align-items:center;
            flex-wrap:wrap;
        }

        .tsb-left{
            min-width:260px;
            flex:1;
        }

        .tsb-header{
            display:flex;
            align-items:center;
            gap:9px;
            margin-bottom:14px;
            flex-wrap:wrap;
        }

        .tsb-dot{
            width:11px;
            height:11px;
            border-radius:999px;
            background:#22c55e;
            box-shadow:0 0 0 5px rgba(34,197,94,.12);
            flex-shrink:0;
        }

        .tsb-warning .tsb-dot{
            background:#f59e0b;
            box-shadow:0 0 0 5px rgba(245,158,11,.12);
        }

        .tsb-critical .tsb-dot{
            background:#ef4444;
            box-shadow:0 0 0 5px rgba(239,68,68,.15);
        }

        .tsb-overline{
            font-size:.72rem;
            font-weight:900;
            letter-spacing:.08em;
            text-transform:uppercase;
            color:#64748b;
        }

        .dark .tsb-overline{
            color:#bfdbfe;
        }

        .tsb-state-pill{
            display:inline-flex;
            align-items:center;
            border-radius:999px;
            padding:4px 10px;
            font-size:.72rem;
            font-weight:900;
            line-height:1;
            border:1px solid transparent;
        }

        .tsb-state-pill-ok{
            color:#15803d;
            background:rgba(34,197,94,.10);
            border-color:rgba(34,197,94,.22);
        }

        .tsb-state-pill-warning{
            color:#d97706;
            background:rgba(245,158,11,.10);
            border-color:rgba(245,158,11,.22);
        }

        .tsb-state-pill-critical{
            color:#dc2626;
            background:rgba(239,68,68,.12);
            border-color:rgba(239,68,68,.24);
        }

        .dark .tsb-state-pill-ok{
            color:#86efac;
            background:rgba(34,197,94,.12);
            border-color:rgba(34,197,94,.24);
        }

        .dark .tsb-state-pill-warning{
            color:#fde68a;
            background:rgba(245,158,11,.12);
            border-color:rgba(245,158,11,.26);
        }

        .dark .tsb-state-pill-critical{
            color:#fecaca;
            background:rgba(239,68,68,.18);
            border-color:rgba(239,68,68,.32);
        }

        .tsb-summary-row{
            display:flex;
            align-items:center;
            gap:14px;
        }

        .tsb-alert-icon{
            width:52px;
            height:52px;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-shrink:0;
            border-radius:18px;
            font-size:1.8rem;
            line-height:1;
            font-weight:1000;
            color:#ef4444;
            background:rgba(239,68,68,.12);
            border:1px solid rgba(239,68,68,.28);
            box-shadow:0 0 24px rgba(239,68,68,.16);
        }

        .tsb-ok .tsb-alert-icon{
            color:#22c55e;
            background:rgba(34,197,94,.12);
            border-color:rgba(34,197,94,.28);
            box-shadow:0 0 24px rgba(34,197,94,.16);
        }

        .tsb-warning .tsb-alert-icon{
            color:#f59e0b;
            background:rgba(245,158,11,.12);
            border-color:rgba(245,158,11,.28);
            box-shadow:0 0 24px rgba(245,158,11,.16);
        }

        .dark .tsb-alert-icon{
            color:#fca5a5;
            background:rgba(239,68,68,.15);
            border-color:rgba(239,68,68,.30);
            box-shadow:0 0 28px rgba(239,68,68,.18);
        }

        .dark .tsb-ok .tsb-alert-icon{
            color:#86efac;
            background:rgba(34,197,94,.14);
            border-color:rgba(34,197,94,.30);
        }

        .dark .tsb-warning .tsb-alert-icon{
            color:#fde68a;
            background:rgba(245,158,11,.14);
            border-color:rgba(245,158,11,.30);
        }

        .tsb-summary-copy{
            min-width:0;
        }

        .tsb-summary-title{
            font-size:1.24rem;
            line-height:1.12;
            font-weight:950;
            color:#0f172a;
            letter-spacing:-.02em;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            max-width:760px;
        }

        .dark .tsb-summary-title{
            color:#ffffff;
        }

        .tsb-summary-title strong{
            color:#ef4444;
            font-weight:1000;
        }

        .tsb-warning .tsb-summary-title strong{
            color:#f59e0b;
        }

        .tsb-ok .tsb-summary-title strong{
            color:#22c55e;
        }

        .tsb-summary-desc{
            margin-top:5px;
            max-width:520px;
            font-size:.82rem;
            line-height:1.35;
            color:#64748b;
        }

        .dark .tsb-summary-desc{
            color:#dbeafe;
        }

        .tsb-right{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
        }

        .tsb-report-card{
            min-width:118px;
            height:62px;
            display:flex;
            align-items:center;
            justify-content:flex-start;
            gap:10px;
            padding:0 14px;
            border-radius:16px;
            border:1px solid rgba(37,99,235,.40);
            background:rgba(37,99,235,.16);
            color:#1d4ed8;
            text-decoration:none;
            transition:all .15s ease;
        }

        .tsb-report-card:hover{
            transform:translateY(-2px);
            border-color:rgba(37,99,235,.70);
            background:rgba(37,99,235,.22);
            box-shadow:0 12px 22px rgba(37,99,235,.18);
        }

        .dark .tsb-report-card{
            background:rgba(37,99,235,.18);
            border-color:rgba(96,165,250,.45);
            color:#bfdbfe;
        }

        .dark .tsb-report-card:hover{
            background:rgba(37,99,235,.28);
            border-color:rgba(96,165,250,.70);
        }

        .tsb-report-icon{
            width:20px;
            height:20px;
            color:#2563eb;
        }

        .dark .tsb-report-icon{
            color:#60a5fa;
        }

        .tsb-report-text{
            display:flex;
            flex-direction:column;
            line-height:1.1;
        }

        .tsb-report-label{
            display:block;
            font-size:.72rem;
            line-height:1;
            font-weight:850;
            color:#2563eb;
        }

        .dark .tsb-report-label{
            color:#bfdbfe;
        }

        .tsb-report-title{
            display:block;
            margin-top:5px;
            font-size:.76rem;
            line-height:1.05;
            font-weight:950;
            color:#1e3a8a;
            white-space:nowrap;
        }

        .dark .tsb-report-title{
            color:#ffffff;
        }

        .tsb-score-card{
            min-width:118px;
            height:62px;
            display:flex;
            align-items:center;
            gap:10px;
            padding:10px 13px;
            border-radius:15px;
            border:1px solid rgba(148,163,184,.16);
            background:rgba(255,255,255,.70);
            box-shadow:inset 0 1px 0 rgba(255,255,255,.05);
        }

        .dark .tsb-score-card{
            background:rgba(255,255,255,.045);
            border-color:rgba(148,163,184,.18);
        }

        .tsb-score-danger{
            border-color:rgba(239,68,68,.30);
            background:rgba(239,68,68,.075);
        }

        .tsb-score-warning{
            border-color:rgba(245,158,11,.30);
            background:rgba(245,158,11,.075);
        }

        .tsb-score-lta{
            border-color:rgba(34,197,94,.32);
            background:rgba(34,197,94,.08);
        }

        .tsb-score-record{
            border-color:rgba(59,130,246,.34);
            background:rgba(59,130,246,.08);
        }

        .dark .tsb-score-lta{
            border-color:rgba(74,222,128,.30);
            background:rgba(34,197,94,.08);
        }

        .dark .tsb-score-record{
            border-color:rgba(96,165,250,.32);
            background:rgba(59,130,246,.09);
        }

        .tsb-record-active{
            border-color:rgba(34,197,94,.55) !important;
            background:rgba(34,197,94,.12) !important;
            box-shadow:0 0 18px rgba(34,197,94,.22), inset 0 1px 0 rgba(255,255,255,.06);
        }

        .tsb-record-active .tsb-score-icon{
            color:#22c55e !important;
            background:rgba(34,197,94,.16) !important;
            border-color:rgba(34,197,94,.35) !important;
        }

        .tsb-record-active .tsb-score-value,
        .tsb-record-active .tsb-score-small{
            color:#22c55e !important;
        }

        .dark .tsb-record-active{
            border-color:rgba(74,222,128,.55) !important;
            background:rgba(34,197,94,.13) !important;
            box-shadow:0 0 22px rgba(34,197,94,.28), inset 0 1px 0 rgba(255,255,255,.06);
        }

        .tsb-score-icon{
            width:32px;
            height:32px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:12px;
            flex-shrink:0;
        }

        .tsb-score-danger .tsb-score-icon{
            color:#ef4444;
            background:rgba(239,68,68,.12);
            border:1px solid rgba(239,68,68,.25);
        }

        .tsb-score-warning .tsb-score-icon{
            color:#f59e0b;
            background:rgba(245,158,11,.12);
            border:1px solid rgba(245,158,11,.25);
        }

        .tsb-score-lta .tsb-score-icon{
            color:#22c55e;
            background:rgba(34,197,94,.12);
            border:1px solid rgba(34,197,94,.25);
        }

        .tsb-score-record .tsb-score-icon{
            color:#3b82f6;
            background:rgba(59,130,246,.12);
            border:1px solid rgba(59,130,246,.25);
        }

        .tsb-score-svg{
            width:18px;
            height:18px;
        }

        .tsb-score-label{
            display:block;
            font-size:.70rem;
            line-height:1;
            font-weight:850;
            color:#64748b;
            white-space:nowrap;
        }

        .dark .tsb-score-label{
            color:#cbd5e1;
        }

        .tsb-score-value{
            display:block;
            margin-top:4px;
            font-size:1.34rem;
            line-height:1;
            font-weight:1000;
            color:#0f172a;
            letter-spacing:-.02em;
        }

        .dark .tsb-score-value{
            color:#ffffff;
        }

        .tsb-score-small{
            display:block;
            margin-top:2px;
            font-size:.66rem;
            line-height:1;
            font-weight:800;
            color:#16a34a;
            white-space:nowrap;
        }

        .dark .tsb-score-small{
            color:#86efac;
        }

        .tsb-score-record .tsb-score-small{
            color:#60a5fa;
        }

        .tsb-modules{
            margin-top:18px;
            display:grid;
            grid-template-columns:repeat(4,minmax(0,1fr));
            gap:10px;
        }

        .tsb-module{
            min-height:72px;
            padding:13px 14px;
            border-radius:15px;
            border:1px solid rgba(148,163,184,.16);
            background:rgba(255,255,255,.64);
            transition:transform .15s ease,border-color .15s ease,background .15s ease,box-shadow .15s ease;
        }

        .dark .tsb-module{
            background:rgba(255,255,255,.035);
            border-color:rgba(148,163,184,.14);
        }

        .tsb-module:hover{
            transform:translateY(-2px);
            border-color:rgba(96,165,250,.36);
            box-shadow:0 10px 18px rgba(0,0,0,.10);
        }

        .tsb-module-expired{
            border-color:rgba(239,68,68,.34);
            background:rgba(239,68,68,.045);
        }

        .dark .tsb-module-expired{
            border-color:rgba(239,68,68,.30);
            background:rgba(239,68,68,.065);
        }

        .tsb-module-inner{
            display:flex;
            align-items:center;
            gap:11px;
            min-width:0;
            width:100%;
        }

        .tsb-module-icon{
            width:28px;
            height:28px;
            flex-shrink:0;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.15rem;
            line-height:1;
        }

        .tsb-module-text{
            min-width:0;
            flex:1;
        }

        .tsb-module-label{
            display:block;
            font-size:.88rem;
            line-height:1.15;
            font-weight:950;
            color:#0f172a;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .dark .tsb-module-label{
            color:#ffffff;
        }

        .tsb-module-stats{
            display:flex;
            align-items:center;
            gap:6px;
            flex-wrap:wrap;
            margin-top:7px;
            font-size:.79rem;
            line-height:1;
            font-weight:900;
        }

        .tsb-expired-link,
        .tsb-expired-text{
            color:#ef4444;
            font-weight:1000;
        }

        .tsb-expired-link{
            text-decoration:none;
            cursor:pointer;
            transition:opacity .15s ease;
        }

        .tsb-expired-link:hover{
            opacity:.82;
            text-decoration:none;
        }

        .tsb-expired-blink{
            animation:tsbTextPulse 2.2s ease-in-out infinite;
        }

        .tsb-soon-text{
            color:#f59e0b;
            font-weight:1000;
        }

        .tsb-sep{
            color:#94a3b8;
            font-weight:900;
        }

        .tsb-chevron{
            margin-left:auto;
            flex-shrink:0;
            color:#93c5fd;
            font-size:1.45rem;
            line-height:1;
            opacity:.9;
        }

        @keyframes tsbTextPulse{
            0%{ text-shadow:0 0 0 rgba(239,68,68,0); }
            50%{ text-shadow:0 0 10px rgba(239,68,68,.75); }
            100%{ text-shadow:0 0 0 rgba(239,68,68,0); }
        }

        @media (max-width:1280px){
            .tsb-modules{
                grid-template-columns:repeat(2,minmax(0,1fr));
            }
        }

        @media (max-width:700px){
            .tsb-wrap{
                padding:18px 16px;
            }

            .tsb-right{
                width:100%;
            }

            .tsb-score-card{
                min-width:0;
                flex:1;
            }

            .tsb-report-card{
                min-width:100%;
                width:100%;
            }

            .tsb-summary-row{
                align-items:flex-start;
            }

            .tsb-alert-icon{
                width:48px;
                height:48px;
                font-size:1.6rem;
            }

            .tsb-summary-title{
                white-space:normal;
            }

            .tsb-modules{
                grid-template-columns:1fr;
            }
        }

        @media (prefers-reduced-motion:reduce){
            .tsb-expired-blink,
            .tsb-module{
                animation:none;
                transition:none;
            }
        }
    </style>
</x-filament-widgets::widget>