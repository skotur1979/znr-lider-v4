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
        $statusClass = fn ($state) => match ($state) {
        'Not started' => 'status-not-started',
        'In progress' => 'status-in-progress',
        'Complete' => 'status-complete',
        default => '',
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

        $dt = Carbon::parse($date);

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


    /*
    |--------------------------------------------------------------------------
    | PRIPREMA FOTOGRAFIJE ZA PDF
    |--------------------------------------------------------------------------
    |
    | Ne ubacujemo originalnu fotografiju u PDF jer fotografije
    | s mobitela mogu imati nekoliko MB.
    |
    | Umjesto toga:
    | - učitamo fotografiju
    | - poštujemo EXIF Orientation
    | - smanjimo je na max. 300 x 300 px
    | - spremimo u memoriji kao JPEG kvalitete 70 %
    | - tek tada pretvorimo u Base64
    |
    | Rezultat:
    | - puno manji PDF
    | - brži izvoz
    | - pravilno okrenute fotografije
    |
    */

    $imageDataUri =
        function (
            ?string $picturePath
        ): ?string {

            if (blank($picturePath)) {
                return null;
            }

            /*
             * Normaliziramo putanju jer neki stariji
             * zapisi mogu sadržavati:
             *
             * observations/slika.jpg
             * storage/observations/slika.jpg
             * /storage/observations/slika.jpg
             */
            $relativePath =
                Str::of($picturePath)
                    ->replaceFirst(
                        '/storage/',
                        ''
                    )
                    ->replaceFirst(
                        'storage/',
                        ''
                    )
                    ->ltrim('/')
                    ->toString();

            $fullPath =
                storage_path(
                    'app/public/'
                    . $relativePath
                );

            if (
                ! is_file($fullPath)
                || ! is_readable($fullPath)
            ) {
                return null;
            }

            /*
             * Ako GD nije dostupan,
             * ne ubacujemo ogromnu originalnu fotografiju.
             *
             * To je sigurnije za DomPDF i memoriju.
             */
            if (
                ! function_exists(
                    'imagecreatefromstring'
                )
            ) {
                return null;
            }

            $binary =
                @file_get_contents(
                    $fullPath
                );

            if ($binary === false) {
                return null;
            }

            $image =
                @imagecreatefromstring(
                    $binary
                );

            unset($binary);

            if ($image === false) {
                return null;
            }


            /*
            |--------------------------------------------------------------------------
            | EXIF ORIJENTACIJA
            |--------------------------------------------------------------------------
            |
            | Fotografije snimljene mobitelom često su fizički spremljene
            | vodoravno, a EXIF govori browseru kako ih prikazati.
            |
            | DomPDF tu informaciju ne mora primijeniti pa je ispravljamo
            | prije stvaranja thumbnaila.
            |
            */

            $extension =
                strtolower(
                    pathinfo(
                        $fullPath,
                        PATHINFO_EXTENSION
                    )
                );

            if (
                in_array(
                    $extension,
                    [
                        'jpg',
                        'jpeg',
                    ],
                    true
                )
                && function_exists(
                    'exif_read_data'
                )
            ) {
                try {
                    $exif =
                        @exif_read_data(
                            $fullPath
                        );

                    $orientation =
                        (int) (
                            $exif['Orientation']
                            ?? 1
                        );

                    switch ($orientation) {

                        /*
                         * Horizontalno zrcaljenje.
                         */
                        case 2:
                            if (
                                function_exists(
                                    'imageflip'
                                )
                            ) {
                                imageflip(
                                    $image,
                                    IMG_FLIP_HORIZONTAL
                                );
                            }
                            break;


                        /*
                         * 180°
                         */
                        case 3:
                            $rotated =
                                imagerotate(
                                    $image,
                                    180,
                                    0
                                );

                            if ($rotated !== false) {
                                imagedestroy($image);
                                $image = $rotated;
                            }
                            break;


                        /*
                         * Vertikalno zrcaljenje.
                         */
                        case 4:
                            if (
                                function_exists(
                                    'imageflip'
                                )
                            ) {
                                imageflip(
                                    $image,
                                    IMG_FLIP_VERTICAL
                                );
                            }
                            break;


                        /*
                         * Zrcaljenje + 90° CW
                         */
                        case 5:
                            if (
                                function_exists(
                                    'imageflip'
                                )
                            ) {
                                imageflip(
                                    $image,
                                    IMG_FLIP_HORIZONTAL
                                );
                            }

                            $rotated =
                                imagerotate(
                                    $image,
                                    -90,
                                    0
                                );

                            if ($rotated !== false) {
                                imagedestroy($image);
                                $image = $rotated;
                            }
                            break;


                        /*
                         * 90° CW
                         */
                        case 6:
                            $rotated =
                                imagerotate(
                                    $image,
                                    -90,
                                    0
                                );

                            if ($rotated !== false) {
                                imagedestroy($image);
                                $image = $rotated;
                            }
                            break;


                        /*
                         * Zrcaljenje + 90° CCW
                         */
                        case 7:
                            if (
                                function_exists(
                                    'imageflip'
                                )
                            ) {
                                imageflip(
                                    $image,
                                    IMG_FLIP_HORIZONTAL
                                );
                            }

                            $rotated =
                                imagerotate(
                                    $image,
                                    90,
                                    0
                                );

                            if ($rotated !== false) {
                                imagedestroy($image);
                                $image = $rotated;
                            }
                            break;


                        /*
                         * 90° CCW
                         */
                        case 8:
                            $rotated =
                                imagerotate(
                                    $image,
                                    90,
                                    0
                                );

                            if ($rotated !== false) {
                                imagedestroy($image);
                                $image = $rotated;
                            }
                            break;
                    }
                } catch (\Throwable $exception) {
                    /*
                     * Ako EXIF nije čitljiv,
                     * nastavljamo s fotografijom kakva jest.
                     */
                }
            }


            /*
            |--------------------------------------------------------------------------
            | SMANJIVANJE SLIKE
            |--------------------------------------------------------------------------
            */

            $sourceWidth =
                imagesx($image);

            $sourceHeight =
                imagesy($image);

            if (
                $sourceWidth <= 0
                || $sourceHeight <= 0
            ) {
                imagedestroy($image);

                return null;
            }

            /*
             * Za prikaz od 42 px nema smisla
             * stavljati fotografiju od 4000 px.
             *
             * 300 px ostavlja više nego dovoljno
             * kvalitete i za PDF/zoom.
             */
            $maxWidth = 300;
            $maxHeight = 300;

            $ratio =
                min(
                    $maxWidth
                        / $sourceWidth,

                    $maxHeight
                        / $sourceHeight,

                    1
                );

            $targetWidth =
                max(
                    1,
                    (int) round(
                        $sourceWidth
                        * $ratio
                    )
                );

            $targetHeight =
                max(
                    1,
                    (int) round(
                        $sourceHeight
                        * $ratio
                    )
                );


            $thumbnail =
                imagecreatetruecolor(
                    $targetWidth,
                    $targetHeight
                );

            if ($thumbnail === false) {
                imagedestroy($image);

                return null;
            }


            /*
             * Bijela podloga jer završni thumbnail
             * pretvaramo u JPEG.
             *
             * Time dobro izgledaju i PNG/WebP slike
             * s prozirnom pozadinom.
             */
            $white =
                imagecolorallocate(
                    $thumbnail,
                    255,
                    255,
                    255
                );

            imagefill(
                $thumbnail,
                0,
                0,
                $white
            );


            $resampled =
                imagecopyresampled(
                    $thumbnail,
                    $image,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $sourceWidth,
                    $sourceHeight
                );

            imagedestroy($image);

            if (! $resampled) {
                imagedestroy($thumbnail);

                return null;
            }


            /*
            |--------------------------------------------------------------------------
            | JPEG KOMPRESIJA
            |--------------------------------------------------------------------------
            |
            | 70 % je više nego dovoljno za thumbnail od 42 x 42 px,
            | a razlika u veličini PDF-a je ogromna.
            |
            */

            ob_start();

            $saved =
                imagejpeg(
                    $thumbnail,
                    null,
                    70
                );

            $compressed =
                ob_get_clean();

            imagedestroy($thumbnail);

            if (
                ! $saved
                || ! is_string($compressed)
                || $compressed === ''
            ) {
                return null;
            }

            return
                'data:image/jpeg;base64,'
                . base64_encode(
                    $compressed
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
        .status-not-started {
            display: inline-block;
            background: #ff0000;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 2px 4px;
        }

        .status-in-progress {
            display: inline-block;
            background: #ffff00;
            color: #000000;
            font-weight: bold;
            text-align: center;
            padding: 2px 4px;
        }

        .status-complete {
            display: inline-block;
            background: #00b050;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 2px 4px;
        }

        .observation-img {
            display: block;

            width: 42px !important;
            height: 42px !important;

            max-width: 42px !important;
            max-height: 42px !important;

            margin: 0 auto;

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
            'label' =>
                'Kontakt / ime prijavitelja',
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


    $rows =
        $observations->map(
            function ($o) use (
            $typeLabel,
            $statusLabel,
            $statusClass,
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
                        e(
                            $o->location
                        ),

                    'item' =>
                        e(
                            Str::limit(
                                (string)
                                $o->item,
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
                                (string)
                                $o->action,
                                70
                            )
                        ),

                    'responsible' =>
                        e(
                            $o->responsible
                        ),

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
                    '<span class="'
                    . $statusClass(
                        $o->status
                    )
                    . '">'
                    . e(
                        $statusLabel(
                            $o->status
                        )
                    )
                    . '</span>',

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
                                . '" class="observation-img" width="42" height="42" alt="">'
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