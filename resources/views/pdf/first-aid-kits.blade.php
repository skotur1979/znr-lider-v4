@php
    use Illuminate\Support\Carbon;

    $today = Carbon::today();

    $fmt = fn ($d) => $d ? Carbon::parse($d)->format('d.m.Y.') : '';

    $rokClass = function ($d) use ($today) {
        if (! $d) {
            return '';
        }

        $dt = $d instanceof \DateTimeInterface
            ? Carbon::instance($d)
            : Carbon::parse($d);

        if ($dt->lt($today)) {
            return 'rok-expired';
        }

        if ($dt->lte($today->copy()->addDays(30))) {
            return 'rok-soon';
        }

        return '';
    };

    $title = 'Ormarići prve pomoći';

    $extraStyles = '
        .rok-expired {
            background: #ff0000;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }

        .rok-soon {
            background: #ffff00;
            color: #000000;
            font-weight: bold;
            text-align: center;
        }

        .kit-location {
            font-weight: bold;
        }
    ';

    $columns = [
        ['key' => 'location', 'label' => 'Ormarić / lokacija', 'width' => '18%', 'tdClass' => 'wrap kit-location'],
        ['key' => 'inspected_at', 'label' => 'Pregled obavljen', 'width' => '10%', 'class' => 'center'],
        ['key' => 'material_type', 'label' => 'Vrsta materijala', 'width' => '24%'],
        ['key' => 'purpose', 'label' => 'Namjena', 'width' => '24%'],
        ['key' => 'valid_until', 'label' => 'Vrijedi do', 'width' => '10%', 'class' => 'center'],
        ['key' => 'items_count', 'label' => 'Stavki', 'width' => '7%', 'class' => 'center'],
    ];

    $rows = collect();

    foreach ($kits as $kit) {
        $items = collect($kit->items ?? [])
            ->sortBy(fn ($i) => $i->valid_until ? Carbon::parse($i->valid_until)->timestamp : PHP_INT_MAX)
            ->values();

        if ($items->isEmpty()) {
            $rows->push([
                'location' => e($kit->location),
                'inspected_at' => $fmt($kit->inspected_at),
                'material_type' => '',
                'purpose' => '',
                'valid_until' => '',
                'items_count' => (int) ($kit->items_count ?? 0),
            ]);

            continue;
        }

        foreach ($items as $item) {
            $rows->push([
                'location' => e($kit->location),
                'inspected_at' => $fmt($kit->inspected_at),
                'material_type' => e($item->material_type),
                'purpose' => e($item->purpose),
                'valid_until' =>
                    '<div class="' . $rokClass($item->valid_until) . '">' .
                        $fmt($item->valid_until) .
                    '</div>',
                'items_count' => (int) ($kit->items_count ?? $items->count()),
            ]);
        }
    }
@endphp

@include('pdf.partials.report-table', [
    'title' => $title,
    'columns' => $columns,
    'rows' => $rows,
    'extraStyles' => $extraStyles,
])