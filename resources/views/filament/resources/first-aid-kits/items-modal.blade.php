@php
    use Carbon\Carbon;

    /** @var \App\Models\FirstAidKit $record */

    $items = $record->items ?? collect();
    $today = Carbon::today();

    // Svi valid_until datumi koji postoje
    $dates = $items
        ->pluck('valid_until')
        ->filter()
        ->map(fn ($date) => Carbon::parse($date)->startOfDay());

    if ($dates->isEmpty()) {
        $label = '—';
        $color = 'gray';
    } else {
        $minDate = $dates->min();
        $days = $today->diffInDays($minDate, false); // negativno = isteklo

        if ($days < 0) {
            $label = 'Isteklo: ' . $minDate->format('d.m.Y');
            $color = 'danger';
        } elseif ($days <= 30) {
            $label = 'Ističe: ' . $minDate->format('d.m.Y');
            $color = 'warning';
        } else {
            $label = $minDate->format('d.m.Y');
            $color = 'success';
        }
    }
@endphp

<span class="
    inline-flex items-center rounded-md px-2 py-1 text-sm font-medium
    @if($color === 'danger') bg-danger-600/10 text-danger-600
    @elseif($color === 'warning') bg-warning-600/10 text-warning-600
    @elseif($color === 'success') bg-success-600/10 text-success-600
    @else text-gray-500
    @endif
">
    {{ $label }}
</span>