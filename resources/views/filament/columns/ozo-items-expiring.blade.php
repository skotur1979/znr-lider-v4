@php
use Illuminate\Support\Carbon;
use App\Support\ExpiryBadge;

/** @var \App\Models\PPELog|null $record */
$record = $getRecord();
$items  = $record?->items ?? collect();

$soonDays = 30;

// Badge stil (kao Machines)
$styleMap = [
    'danger'  => 'display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:9999px;border:1px solid rgba(239,68,68,.9);background:rgba(239,68,68,.18);color:#fecaca;font-weight:700;line-height:1.2;',
    'warning' => 'display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:9999px;border:1px solid rgba(245,158,11,.95);background:rgba(245,158,11,.18);color:#fde68a;font-weight:700;line-height:1.2;',
    'success' => 'display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:9999px;border:1px solid rgba(34,197,94,.9);background:rgba(34,197,94,.16);color:#bbf7d0;font-weight:700;line-height:1.2;',
    'gray'    => 'display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:9999px;border:1px solid rgba(156,163,175,.7);background:rgba(156,163,175,.10);color:#e5e7eb;font-weight:600;line-height:1.2;',
];

// SVG ikone
$svg = [
    'heroicon-o-check-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
    'heroicon-o-exclamation-triangle' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 17h.01"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>',
    'heroicon-o-x-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9l-6 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 9l6 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
    null => '',
];
@endphp

<div style="display:flex;flex-direction:column;gap:6px;">
@forelse($items as $item)

    @php
        $end = blank($item->end_date) ? null : Carbon::parse($item->end_date)->startOfDay();

        if (! $end) {
            $status  = 'gray';
            $text    = '—';
            $tip     = ExpiryBadge::tooltip(null, $soonDays);
            $iconKey = null;
        } else {
            $status  = ExpiryBadge::color($end, $soonDays);
            $text    = $end->format('d.m.Y.');
            $tip     = ExpiryBadge::tooltip($end, $soonDays);
            $iconKey = ExpiryBadge::icon($end, $soonDays);
        }

        $style = $styleMap[$status] ?? $styleMap['gray'];
    @endphp

    {{-- ključ: svaki red ima istu visinu --}}
    <div style="min-height:30px;display:flex;align-items:center;">
        <span style="{{ $style }}" title="{{ $tip }}">
            {!! $svg[$iconKey] !!}
            <span>{{ $text }}</span>
        </span>
    </div>

@empty

    <div style="min-height:30px;display:flex;align-items:center;">
        <span style="{{ $styleMap['gray'] }}">—</span>
    </div>

@endforelse
</div>