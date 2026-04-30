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

    $certClass = function ($d) use ($today) {
        if (! $d) {
            return '';
        }

        $dt = Carbon::parse($d);

        if ($dt->lt($today)) {
            return 'cert-expired';
        }

        if ($dt->lte($today->copy()->addDays(30))) {
            return 'cert-soon';
        }

        return '';
    };

    $certSummary = function ($e) use ($certClass) {
        $certs = $e->certificates?->sortBy('valid_until') ?? collect();

        if ($certs->isEmpty()) {
            return '';
        }

        $html = '';
        $length = 0;

        foreach ($certs as $c) {
            $title = trim((string) $c->title);

            if ($title === '') {
                continue;
            }

            $until = $c->valid_until
                ? Carbon::parse($c->valid_until)->format('d.m.Y.')
                : null;

            $text = $until ? $title . ' (do ' . $until . ')' : $title;
            $class = $certClass($c->valid_until);

            $html .= '<span class="cert-item ' . $class . '">' . e($text) . '</span>';

            $length += mb_strlen($text);

            if ($length > 170) {
                $html .= '<span class="cert-item">…</span>';
                break;
            }
        }

        return $html;
    };

    $title = 'Zaposlenici';

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

        .employee-name {
            font-weight: bold;
        }

        .cert-item {
            display: block;
            padding: 2px 3px;
            margin-bottom: 2px;
            line-height: 1.25;
        }

        .cert-expired {
            background: #ff0000;
            color: #ffffff;
            font-weight: bold;
        }

        .cert-soon {
            background: #ffff00;
            color: #000000;
            font-weight: bold;
        }
    ';

    $columns = [
        ['key' => 'name', 'label' => 'Ime i prezime', 'width' => '10%', 'tdClass' => 'wrap employee-name'],
        ['key' => 'workplace', 'label' => 'Radno mjesto', 'width' => '10%'],
        ['key' => 'medical_examination_valid_until', 'label' => 'Liječnički (do)', 'width' => '10%', 'class' => 'center'],
        ['key' => 'article', 'label' => 'Članak 3. točke', 'width' => '10%'],
        ['key' => 'occupational_safety_valid_from', 'label' => 'Zaštita na radu (od)', 'width' => '10%', 'class' => 'center'],
        ['key' => 'first_aid_valid_from', 'label' => 'Prva pomoć (od)', 'width' => '10%', 'class' => 'center'],
        ['key' => 'toxicology_valid_until', 'label' => 'Toksikologija (do)', 'width' => '10%', 'class' => 'center'],
        ['key' => 'employers_authorization_valid_until', 'label' => 'Ovlaštenik ZNR (do)', 'width' => '10%', 'class' => 'center'],
        ['key' => 'certificates', 'label' => 'Ostale edukacije', 'width' => '17%'],
    ];

    $rows = $employees->map(function ($e) use ($fmt, $rokClass, $certSummary) {
        return [
            'name' => e($e->name),
            'workplace' => e($e->workplace),

            'medical_examination_valid_until' =>
                '<div class="' . $rokClass($e->medical_examination_valid_until) . '">' .
                    $fmt($e->medical_examination_valid_until) .
                '</div>',

            'article' => e($e->article),

            'occupational_safety_valid_from' => $fmt($e->occupational_safety_valid_from),
            'first_aid_valid_from' => $fmt($e->first_aid_valid_from),

            'toxicology_valid_until' =>
                '<div class="' . $rokClass($e->toxicology_valid_until) . '">' .
                    $fmt($e->toxicology_valid_until) .
                '</div>',

            'employers_authorization_valid_until' =>
                '<div class="' . $rokClass($e->employers_authorization_valid_until) . '">' .
                    $fmt($e->employers_authorization_valid_until) .
                '</div>',

            'certificates' => $certSummary($e),
        ];
    });
@endphp

@include('pdf.partials.report-table', [
    'title' => $title,
    'columns' => $columns,
    'rows' => $rows,
    'extraStyles' => $extraStyles,
])