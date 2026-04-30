@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $today = Carbon::today();

    $typeLabel = fn ($state) => match ($state) {
        'LTA' => 'LTA – Ozljeda na radu',
        'MTA' => 'MTA – Pružanje PP izvan tvrtke',
        'FAA' => 'FAA – Pružanje PP u tvrtki',
        default => (string) $state,
    };

    $imageDataUri = function (?string $imagePath): ?string {
        if (! $imagePath) {
            return null;
        }

        $fullPath = storage_path('app/public/' . $imagePath);

        if (! file_exists($fullPath)) {
            return null;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => null,
        };

        if (! $mime) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
    };

    $title = 'Incidenti';

    $extraStyles = '
        .rok-expired {
            background: #ff0000;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 4px 2px;
        }

        .rok-soon {
            background: #ffff00;
            color: #000000;
            font-weight: bold;
            text-align: center;
            padding: 4px 2px;
        }

        .incident-img {
            width: 42px !important;
            height: 42px !important;
            max-width: 42px !important;
            max-height: 42px !important;
            object-fit: cover;
            border-radius: 3px;
        }

        .incident-type-main {
            font-weight: bold;
            display: block;
        }

        .incident-type-sub {
            font-size: 6px;
            color: #6b7280;
            display: block;
            margin-top: 2px;
        }
    ';

    $columns = [
        ['key' => 'location', 'label' => 'Lokacija', 'width' => '8%'],
        ['key' => 'type_of_incident', 'label' => 'Vrsta incidenta', 'width' => '10%'],
        ['key' => 'date_occurred', 'label' => 'Datum nastanka', 'width' => '8%', 'class' => 'center'],
        ['key' => 'date_of_return', 'label' => 'Povratak', 'width' => '8%', 'class' => 'center'],
        ['key' => 'working_days_lost', 'label' => 'Izgubljeni dani', 'width' => '6%', 'class' => 'center'],
        ['key' => 'injured_body_part', 'label' => 'Ozlijeđeni dio tijela', 'width' => '11%'],
        ['key' => 'causes_of_injury', 'label' => 'Uzrok', 'width' => '12%'],
        ['key' => 'accident_injury_type', 'label' => 'Tip ozljede', 'width' => '12%'],
        ['key' => 'other', 'label' => 'Napomena', 'width' => '11%'],
        ['key' => 'image', 'label' => 'Slika', 'width' => '6%', 'class' => 'center'],
        ['key' => 'attachments', 'label' => 'Prilozi', 'width' => '5%', 'class' => 'center'],
    ];

    $rows = $incidents->map(function ($i) use ($today, $typeLabel, $imageDataUri) {
        $occurred = $i->date_occurred ? Carbon::parse($i->date_occurred) : null;
        $return = $i->date_of_return ? Carbon::parse($i->date_of_return) : null;

        $returnClass = '';

        if ($return) {
            if ($return->lt($today)) {
                $returnClass = 'rok-expired';
            } elseif ($return->lte($today->copy()->addDays(30))) {
                $returnClass = 'rok-soon';
            }
        }

        $img = $imageDataUri($i->image_path ?? null);

        $attachmentsCount = is_array($i->investigation_report ?? null)
            ? count($i->investigation_report)
            : 0;

        return [
            'location' => e($i->location),
            'type_of_incident' =>
                '<span class="incident-type-main">' . e($i->type_of_incident) . '</span>' .
                '<span class="incident-type-sub">' . e($typeLabel($i->type_of_incident)) . '</span>',

            'date_occurred' => $occurred ? $occurred->format('d.m.Y.') : '',
            'date_of_return' => '<div class="' . $returnClass . '">' . ($return ? $return->format('d.m.Y.') : '') . '</div>',
            'working_days_lost' => e($i->working_days_lost ?? ''),
            'injured_body_part' => e(Str::limit((string) $i->injured_body_part, 70)),
            'causes_of_injury' => e(Str::limit((string) $i->causes_of_injury, 90)),
            'accident_injury_type' => e(Str::limit((string) $i->accident_injury_type, 90)),
            'other' => e(Str::limit((string) $i->other, 90)),
            'image' => $img ? '<img src="' . $img . '" class="incident-img" width="42" height="42">' : '',
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