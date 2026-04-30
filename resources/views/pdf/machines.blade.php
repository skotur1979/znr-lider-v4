@php
    use Illuminate\Support\Carbon;

    $today = Carbon::today();

    $fmt = fn ($d) => $d ? Carbon::parse($d)->format('d.m.Y.') : '';

    $rokClass = function ($d) use ($today) {
        if (! $d) {
            return '';
        }

        $dt = Carbon::parse($d);

        if ($dt->lt($today)) {
            return 'rok-expired';
        }

        if ($dt->lte($today->copy()->addDays(30))) {
            return 'rok-soon';
        }

        return '';
    };

    $title = 'Radna oprema';

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
    ';

    $columns = [
        ['key' => 'name', 'label' => 'Naziv', 'width' => '11%'],
        ['key' => 'manufacturer', 'label' => 'Proizvođač', 'width' => '10%'],
        ['key' => 'factory_number', 'label' => 'Tvornički broj', 'width' => '9%', 'class' => 'center'],
        ['key' => 'inventory_number', 'label' => 'Inventarni broj', 'width' => '9%', 'class' => 'center'],
        ['key' => 'examination_valid_from', 'label' => 'Vrijedi od', 'width' => '8%', 'class' => 'center'],
        ['key' => 'examination_valid_until', 'label' => 'Vrijedi do', 'width' => '8%', 'class' => 'center'],
        ['key' => 'examined_by', 'label' => 'Ispitao', 'width' => '10%'],
        ['key' => 'report_number', 'label' => 'Broj izvještaja', 'width' => '9%', 'class' => 'center'],
        ['key' => 'location', 'label' => 'Lokacija', 'width' => '10%'],
        ['key' => 'remark', 'label' => 'Napomena', 'width' => '14%'],
    ];

    $rows = $machines->map(function ($m) use ($fmt, $rokClass) {
        return [
            'name' => e($m->name),
            'manufacturer' => e($m->manufacturer),
            'factory_number' => e($m->factory_number),
            'inventory_number' => e($m->inventory_number),
            'examination_valid_from' => $fmt($m->examination_valid_from),
            'examination_valid_until' => '<div class="' . $rokClass($m->examination_valid_until) . '">' . $fmt($m->examination_valid_until) . '</div>',
            'examined_by' => e($m->examined_by),
            'report_number' => e($m->report_number),
            'location' => e($m->location),
            'remark' => e($m->remark),
        ];
    });
@endphp

@include('pdf.partials.report-table', [
    'title' => $title,
    'columns' => $columns,
    'rows' => $rows,
    'extraStyles' => $extraStyles,
])