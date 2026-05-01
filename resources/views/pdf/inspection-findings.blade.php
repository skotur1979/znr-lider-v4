@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $today = Carbon::today();

    $fmt = fn ($d) => $d ? Carbon::parse($d)->format('d.m.Y.') : '';

    $findingStatusLabel = fn (?string $state) => match ($state) {
        'ok' => 'Uredno',
        'recommendation' => 'Preporuka',
        'noncompliance' => 'Nepravilnost',
        'critical' => 'Kritična nepravilnost',
        default => $state ?: '-',
    };

    $workflowStatusLabel = fn (?string $state) => match ($state) {
        'open' => 'Nije započeto',
        'in_progress' => 'U tijeku',
        'closed' => 'Zatvoreno',
        'resolved_no_action' => 'Riješeno bez akcija',
        'converted_to_observation' => 'Pretvoreno u zapažanje',
        'rejected' => 'Odbačeno',
        default => $state ?: '-',
    };

    $rokClass = function ($date, ?string $status) use ($today) {
        if (! $date) {
            return '';
        }

        if (in_array($status, ['closed', 'rejected', 'resolved_no_action'], true)) {
            return 'status-complete';
        }

        $dt = Carbon::parse($date);

        if ($dt->lt($today)) {
            return 'rok-expired';
        }

        if ($dt->lte($today->copy()->addDays(30))) {
            return 'rok-soon';
        }

        return '';
    };

    $statusClass = function (?string $status) {
        return match ($status) {
            'closed', 'resolved_no_action' => 'status-complete',
            'in_progress' => 'status-warning',
            'rejected' => 'rok-expired',
            default => '',
        };
    };

    $title = 'Nalazi nadzora';

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

        .status-complete {
            background: #00b050;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }

        .status-warning {
            background: #ffff00;
            color: #000000;
            font-weight: bold;
            text-align: center;
        }

        .finding-title {
            font-weight: bold;
        }

        .inspection-meta {
            margin-bottom: 8px;
            font-size: 8px;
        }
    ';

    $columns = [
        ['key' => 'category', 'label' => 'Područje', 'width' => '11%', 'class' => 'center'],
        ['key' => 'description', 'label' => 'Što je uočeno / pronađeno', 'width' => '23%', 'tdClass' => 'wrap finding-title'],
        ['key' => 'finding_status', 'label' => 'Vrsta', 'width' => '11%', 'class' => 'center'],
        ['key' => 'workflow_status', 'label' => 'Status postupanja', 'width' => '12%', 'class' => 'center'],
        ['key' => 'action_required', 'label' => 'Treba akcija', 'width' => '8%', 'class' => 'center'],
        ['key' => 'responsible_person', 'label' => 'Odgovorna osoba', 'width' => '12%'],
        ['key' => 'due_date', 'label' => 'Rok', 'width' => '8%', 'class' => 'center'],
        ['key' => 'resolution_note', 'label' => 'Napomena / rješenje', 'width' => '12%'],
    ];

    $rows = $findings
        ->values()
        ->map(function ($f) use ($fmt, $findingStatusLabel, $workflowStatusLabel, $rokClass, $statusClass) {
            return [
                'category' => e($f->category),
                'description' => e(Str::limit((string) $f->description, 140)),
                'finding_status' => e($findingStatusLabel($f->finding_status)),
                'workflow_status' =>
                    '<div class="' . $statusClass($f->workflow_status) . '">' .
                        e($workflowStatusLabel($f->workflow_status)) .
                    '</div>',
                'action_required' => $f->action_required ? 'DA' : 'NE',
                'responsible_person' => e($f->responsible_person),
                'due_date' =>
                    '<div class="' . $rokClass($f->due_date, $f->workflow_status) . '">' .
                        $fmt($f->due_date) .
                    '</div>',
                'resolution_note' => e(Str::limit((string) $f->resolution_note, 90)),
            ];
        });
@endphp

@include('pdf.partials.report-table', [
    'title' => $title,
    'columns' => $columns,
    'rows' => $rows,
    'extraStyles' => $extraStyles,
])