@php
    use Illuminate\Support\Carbon;

    $today = Carbon::today();

    $fmt = fn ($d) => $d ? Carbon::parse($d)->format('d.m.Y.') : '';

    $rokClass = function ($d) use ($today) {
        if (! $d) return '';

        $dt = Carbon::parse($d);

        if ($dt->lt($today)) return 'rok-expired';
        if ($dt->lte($today->copy()->addDays(30))) return 'rok-soon';

        return '';
    };

    $title = 'Ostala ispitivanja';

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

        .record-name {
            font-weight: bold;
        }
    ';

    $columns = [
        ['key' => 'name', 'label' => 'Naziv', 'width' => '22%', 'tdClass' => 'wrap record-name'],
        ['key' => 'category', 'label' => 'Kategorija', 'width' => '15%', 'class' => 'center'],
        ['key' => 'examiner', 'label' => 'Ispitao', 'width' => '12%', 'class' => 'center'],
        ['key' => 'examination_valid_from', 'label' => 'Datum ispitivanja', 'width' => '10%', 'class' => 'center'],
        ['key' => 'examination_valid_until', 'label' => 'Vrijedi do', 'width' => '10%', 'class' => 'center'],
        ['key' => 'report_number', 'label' => 'Broj izvještaja', 'width' => '11%', 'class' => 'center'],
        ['key' => 'remark', 'label' => 'Napomena', 'width' => '13%'],
        ['key' => 'attachments', 'label' => 'Prilozi', 'width' => '4%', 'class' => 'center'],
    ];

    $rows = $miscellaneouses
        ->values()
        ->map(function ($m) use ($fmt, $rokClass) {
            $attachmentsCount = is_array($m->pdf ?? null)
                ? count($m->pdf)
                : 0;

            return [
                'name' => e($m->name),
                'category' => e($m->category?->name ?? ''),
                'examiner' => e($m->examiner),
                'examination_valid_from' => $fmt($m->examination_valid_from),
                'examination_valid_until' =>
                    '<div class="' . $rokClass($m->examination_valid_until) . '">' .
                        $fmt($m->examination_valid_until) .
                    '</div>',
                'report_number' => e($m->report_number),
                'remark' => e($m->remark),
                'attachments' => $attachmentsCount,
            ];
        });
@endphp

@include('pdf.partials.report-table', [
    'title' => $title,
    'columns' => $columns,
    'rows' => $rows,
    'extraStyles' => $extraStyles,
])