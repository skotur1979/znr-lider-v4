@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $today = Carbon::today();

    $typeLabel = fn ($state) => match ($state) {
        'Near Miss' => 'NM - Skoro nezgoda',
        'Negative Observation' => 'Negativno zapažanje',
        'Positive Observation' => 'Pozitivno zapažanje',
        default => (string) $state,
    };

    $statusLabel = fn ($state) => match ($state) {
        'Not started' => 'Nije započeto',
        'In progress' => 'U tijeku',
        'Complete' => 'Završeno',
        default => (string) $state,
    };

    $sourceLabel = fn ($state) =>
        $state === 'qr_public'
            ? 'QR prijava'
            : 'Interno';

    $rokClass = function (
        $date,
        $status
    ) use ($today) {

        if (
            ! $date
            || $status === 'Complete'
        ) {
            return '';
        }

        $dt =
            Carbon::parse($date);

        if ($dt->lt($today)) {
            return 'rok-expired';
        }

        if (
            $dt->lte(
                $today
                    ->copy()
                    ->addDays(30)
            )
        ) {
            return 'rok-soon';
        }

        return '';
    };

    $imageDataUri =
        function (
            ?string $picturePath
        ): ?string {

            if (! $picturePath) {
                return null;
            }

            $fullPath =
                storage_path(
                    'app/public/'
                    . $picturePath
                );

            if (
                ! file_exists(
                    $fullPath
                )
            ) {
                return null;
            }

            $ext =
                strtolower(
                    pathinfo(
                        $fullPath,
                        PATHINFO_EXTENSION
                    )
                );

            $mime =
                match ($ext) {
                    'jpg',
                    'jpeg' =>
                        'image/jpeg',

                    'png' =>
                        'image/png',

                    'gif' =>
                        'image/gif',

                    'webp' =>
                        'image/webp',

                    default =>
                        null,
                };

            if (! $mime) {
                return null;
            }

            return 'data:'
                . $mime
                . ';base64,'
                . base64_encode(
                    file_get_contents(
                        $fullPath
                    )
                );
        };

    $title =
        'Zapažanja';

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

        .observation-img {
            width: 42px !important;
            height: 42px !important;
            max-width: 42px !important;
            max-height: 42px !important;
            object-fit: cover;
            border-radius: 3px;
        }

        .source-qr {
            font-weight: bold;
        }
    ';

    $columns = [
        [
            'key' => 'incident_date',
            'label' => 'Datum',
            'width' => '5%',
            'class' => 'center',
        ],
        [
            'key' => 'observation_type',
            'label' => 'Vrsta zapažanja',
            'width' => '8%',
        ],
        [
            'key' => 'source',
            'label' => 'Izvor',
            'width' => '6%',
            'class' => 'center',
        ],
        [
            'key' => 'location',
            'label' => 'Lokacija',
            'width' => '7%',
        ],
        [
            'key' => 'item',
            'label' => 'Opis',
            'width' => '10%',
        ],
        [
            'key' => 'potential_incident_type',
            'label' => 'Vrsta opasnosti',
            'width' => '9%',
        ],
        [
            'key' => 'action',
            'label' => 'Potrebna radnja',
            'width' => '10%',
        ],
        [
            'key' => 'responsible',
            'label' => 'Odgovorna osoba',
            'width' => '8%',
        ],
        [
            'key' => 'target_date',
            'label' => 'Rok',
            'width' => '6%',
            'class' => 'center',
        ],
        [
            'key' => 'status',
            'label' => 'Status',
            'width' => '7%',
            'class' => 'center',
        ],
        [
            'key' => 'reporter_contact',
            'label' => 'Kontakt / ime prijavitelja',
            'width' => '8%',
        ],
        [
            'key' => 'comments',
            'label' => 'Komentar',
            'width' => '9%',
        ],
        [
            'key' => 'picture',
            'label' => 'Slika',
            'width' => '5%',
            'class' => 'center',
        ],
    ];

    $rows = $observations->map(
        function ($o) use (
            $typeLabel,
            $statusLabel,
            $sourceLabel,
            $rokClass,
            $imageDataUri
        ) {
            $incident =
                $o->incident_date
                    ? Carbon::parse(
                        $o->incident_date
                    )
                    : null;

            $target =
                $o->target_date
                    ? Carbon::parse(
                        $o->target_date
                    )
                    : null;

            $img =
                $imageDataUri(
                    $o->picture_path
                    ?? null
                );

            return [
                'incident_date' =>
                    $incident
                        ? $incident->format(
                            'd.m.Y.'
                        )
                        : '',

                'observation_type' =>
                    e(
                        $typeLabel(
                            $o->observation_type
                        )
                    ),

                'source' =>
                    e(
                        $sourceLabel(
                            $o->source
                        )
                    ),

                'location' =>
                    e($o->location),

                'item' =>
                    e(
                        Str::limit(
                            (string) $o->item,
                            70
                        )
                    ),

                'potential_incident_type' =>
                    e(
                        Str::limit(
                            (string)
                            $o->potential_incident_type,
                            55
                        )
                    ),

                'action' =>
                    e(
                        Str::limit(
                            (string) $o->action,
                            70
                        )
                    ),

                'responsible' =>
                    e($o->responsible),

                'target_date' =>
                    '<div class="'
                    . $rokClass(
                        $target,
                        $o->status
                    )
                    . '">'
                    . (
                        $target
                            ? $target->format(
                                'd.m.Y.'
                            )
                            : ''
                    )
                    . '</div>',

                'status' =>
                    e(
                        $statusLabel(
                            $o->status
                        )
                    ),

                'reporter_contact' =>
                    e(
                        Str::limit(
                            (string)
                            $o->reporter_contact,
                            45
                        )
                    ),

                'comments' =>
                    e(
                        Str::limit(
                            (string)
                            $o->comments,
                            55
                        )
                    ),

                'picture' =>
                    $img
                        ? '<img src="'
                            . $img
                            . '" class="observation-img" width="42" height="42">'
                        : '',
            ];
        }
    );
@endphp

@include(
    'pdf.partials.report-table',
    [
        'title' =>
            $title,

        'columns' =>
            $columns,

        'rows' =>
            $rows,

        'extraStyles' =>
            $extraStyles,
    ]
)