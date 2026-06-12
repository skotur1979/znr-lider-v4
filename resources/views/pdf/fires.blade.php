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

    $title = 'Vatrogasni aparati';

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
        ['key' => 'place', 'label' => 'Mjesto', 'width' => '12%'],
        ['key' => 'type', 'label' => 'Tip', 'width' => '5%', 'class' => 'center'],
        ['key' => 'factory_number_year_of_production', 'label' => 'Tvorn. broj', 'width' => '9%', 'class' => 'center'],
        ['key' => 'serial_label_number', 'label' => 'Ser. broj', 'width' => '8%', 'class' => 'center'],
        ['key' => 'examination_valid_from', 'label' => 'Periodički servis', 'width' => '9%', 'class' => 'center'],
        ['key' => 'examination_valid_until', 'label' => 'Vrijedi do', 'width' => '8%', 'class' => 'center'],
        ['key' => 'service', 'label' => 'Serviser', 'width' => '9%'],
        ['key' => 'regular_examination_valid_from', 'label' => 'Redovni pregled', 'width' => '9%', 'class' => 'center'],
        ['key' => 'regular_examination_valid_until', 'label' => 'Redovni pregled vrijedi do', 'width' => '9%', 'class' => 'center'],
        ['key' => 'visible', 'label' => 'Uočljivost', 'width' => '6%', 'class' => 'center'],
        ['key' => 'remark', 'label' => 'Nedostaci', 'width' => '11%'],
        ['key' => 'action', 'label' => 'Otklanjanje', 'width' => '11%'],
    ];

    $rows = $fires->map(function ($f) use ($fmt, $rokClass) {
        return [
            'place' => e($f->place),
            'type' => e($f->type),
            'factory_number_year_of_production' => e($f->factory_number_year_of_production),
            'serial_label_number' => e($f->serial_label_number),
            'examination_valid_from' => $fmt($f->examination_valid_from),
            'examination_valid_until' => '<div class="' . $rokClass($f->examination_valid_until) . '">' . $fmt($f->examination_valid_until) . '</div>',
            'service' => e($f->service),
            'regular_examination_valid_from' => $fmt($f->regular_examination_valid_from),
            'regular_examination_valid_until' => '<div class="' . $rokClass($f->regular_examination_valid_until) . '">' . $fmt($f->regular_examination_valid_until) . '</div>',
            'visible' => e($f->visible),
            'remark' => e($f->remark),
            'action' => e($f->action),
        ];
    });
@endphp

@include('pdf.partials.report-table', [
    'title' => $title,
    'columns' => $columns,
    'rows' => $rows,
    'extraStyles' => $extraStyles,
])