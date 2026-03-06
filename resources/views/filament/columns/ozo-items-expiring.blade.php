@php
    use Illuminate\Support\Carbon;
    use App\Support\ExpiryBadge;

    $items = collect($getRecord()->items ?? [])->sort(function ($a, $b) {
        $aHasEnd = ! blank($a->end_date);
        $bHasEnd = ! blank($b->end_date);

        if ($aHasEnd && ! $bHasEnd) {
            return -1;
        }

        if (! $aHasEnd && $bHasEnd) {
            return 1;
        }

        if (! $aHasEnd && ! $bHasEnd) {
            $aIssue = $a->issue_date ? Carbon::parse($a->issue_date)->timestamp : 0;
            $bIssue = $b->issue_date ? Carbon::parse($b->issue_date)->timestamp : 0;

            return $bIssue <=> $aIssue;
        }

        $aEnd = Carbon::parse($a->end_date)->timestamp;
        $bEnd = Carbon::parse($b->end_date)->timestamp;

        if ($aEnd !== $bEnd) {
            return $bEnd <=> $aEnd; // najkasniji istek gore
        }

        $aIssue = $a->issue_date ? Carbon::parse($a->issue_date)->timestamp : 0;
        $bIssue = $b->issue_date ? Carbon::parse($b->issue_date)->timestamp : 0;

        if ($aIssue !== $bIssue) {
            return $bIssue <=> $aIssue;
        }

        $aDuration = (int) ($a->duration_months ?? 0);
        $bDuration = (int) ($b->duration_months ?? 0);

        return $bDuration <=> $aDuration;
    })->values();

    $soonDays = 30;

    $styleMap = [
        'danger'  => 'display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:9999px;border:1px solid rgba(239,68,68,.9);background:rgba(239,68,68,.18);color:#fecaca;font-weight:700;line-height:1.2;',
        'warning' => 'display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:9999px;border:1px solid rgba(245,158,11,.95);background:rgba(245,158,11,.18);color:#fde68a;font-weight:700;line-height:1.2;',
        'success' => 'display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:9999px;border:1px solid rgba(34,197,94,.9);background:rgba(34,197,94,.16);color:#bbf7d0;font-weight:700;line-height:1.2;',
        'gray'    => 'display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:9999px;border:1px solid rgba(156,163,175,.7);background:rgba(156,163,175,.10);color:#e5e7eb;font-weight:600;line-height:1.2;',
    ];

    $svg = [
        'heroicon-o-check-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:block"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
        'heroicon-o-exclamation-triangle' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:block"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 17h.01"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>',
        'heroicon-o-x-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:block"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9l-6 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 9l6 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
        null => '',
    ];
@endphp

<div style="display:flex; flex-direction:column; gap:6px;">
    @forelse($items as $item)
        @php
            $date = $item->end_date ? Carbon::parse($item->end_date)->startOfDay() : null;
            $status = ExpiryBadge::color($date, $soonDays);
            $iconKey = ExpiryBadge::icon($date, $soonDays);
            $tooltip = ExpiryBadge::tooltip($date, $soonDays);
            $style = $styleMap[$status] ?? $styleMap['gray'];
            $text = $date ? $date->format('d.m.Y.') : '—';
        @endphp

        <div style="min-height:30px; display:flex; align-items:center; white-space:nowrap;">
            <span style="{{ $style }}" title="{{ $tooltip }}">
                {!! $svg[$iconKey] ?? '' !!}
                <span>{{ $text }}</span>
            </span>
        </div>
    @empty
        <div style="min-height:30px; display:flex; align-items:center; white-space:nowrap;">
            <span style="{{ $styleMap['gray'] }}" title="Rok nije definiran">—</span>
        </div>
    @endforelse
</div>