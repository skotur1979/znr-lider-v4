@php
    use Illuminate\Support\Carbon;

    /** @var \App\Models\PPELog|null $record */
    $record = $getRecord();
    $items  = $record?->items ?? collect();
@endphp

<div style="display:flex;flex-direction:column;gap:4px;white-space:nowrap;">
    @forelse ($items as $item)
        <span>
            {{ $item->issue_date ? Carbon::parse($item->issue_date)->format('d.m.Y.') : '—' }}
        </span>
    @empty
        <span>—</span>
    @endforelse
</div>