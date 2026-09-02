<?php

namespace App\Exports;

use App\Filament\Resources\Observations\ObservationResource;
use App\Models\Observation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

class ObservationsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    protected $observations;

    protected bool $showUserColumn = false;

    /*
     * Visina slike u Excelu.
     *
     * Prije je bila 70 px.
     * Sada je malo veća radi boljeg pregleda.
     */
    private int $imgHeight = 90;

    private int $headingRow = 6;

    private int $firstDataRow = 7;

    public function __construct(
        ?array $observationIds = null
    ) {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $query =
            ObservationResource::getEloquentQuery()
                ->with('user')
                ->orderByDesc('incident_date');

        /*
         * Ako su poslani ID-evi iz filtrirane tablice,
         * izvoz poštuje samo te zapise.
         */
        if ($observationIds !== null) {
            $query->whereIn(
                'observations.id',
                $observationIds
            );
        }

        $this->observations =
            $query->get();
    }

    public function startCell(): string
    {
        return 'A' . $this->headingRow;
    }

    public function collection()
    {
        return $this->observations;
    }

    public function headings(): array
    {
        $headings = [
            'Datum zapažanja',
        ];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge(
            $headings,
            [
                'Vrsta zapažanja',
                'Izvor prijave',
                'Prioritet',
                'Lokacija',
                'Opis',
                'Vrsta opasnosti',
                'Potrebna radnja',
                'Odgovorna osoba',
                'Rok za provedbu',
                'Datum zatvaranja',
                'Broj dana do zatvaranja',
                'Status',
                'Kontakt / ime prijavitelja',
                'E-mail primatelji',
                'Poslano',
                'Komentar',
                'Slika',
            ]
        );
    }

    public function map(
        $observation
    ): array {
        /** @var Observation $observation */

        $incidentDate =
            filled($observation->incident_date)
                ? Carbon::parse(
                    $observation->incident_date
                )
                : null;

        $targetDate =
            filled($observation->target_date)
                ? Carbon::parse(
                    $observation->target_date
                )
                : null;

        $completedAt =
            filled($observation->completed_at)
                ? Carbon::parse(
                    $observation->completed_at
                )
                : null;

        $sentAt =
            filled($observation->sent_at)
                ? Carbon::parse(
                    $observation->sent_at
                )
                : null;

        $closingDays = null;

        if (
            $incidentDate
            && $completedAt
        ) {
            $closingDays =
                $incidentDate
                    ->copy()
                    ->startOfDay()
                    ->diffInDays(
                        $completedAt
                            ->copy()
                            ->startOfDay()
                    );
        }

        $row = [
            $incidentDate
                ? ExcelDate::dateTimeToExcel(
                    $incidentDate
                )
                : null,
        ];

        if ($this->showUserColumn) {
            $row[] =
                $observation
                    ->user
                    ?->name
                ?? '';
        }

        return array_merge(
            $row,
            [
                $this->observationTypeLabel(
                    $observation->observation_type
                ),

                $this->sourceLabel(
                    $observation->source
                ),

                $this->priorityLabel(
                    $observation->priority
                ),

                $observation->location,

                $observation->item,

                $observation
                    ->potential_incident_type,

                $observation->action,

                $observation->responsible,

                $targetDate
                    ? ExcelDate::dateTimeToExcel(
                        $targetDate
                    )
                    : null,

                $completedAt
                    ? ExcelDate::dateTimeToExcel(
                        $completedAt
                    )
                    : null,

                $closingDays,

                $this->statusLabel(
                    $observation->status
                ),

                $observation
                    ->reporter_contact
                ?? '',

                $this->emails(
                    $observation
                        ->notification_emails
                    ?? null
                ),

                $sentAt
                    ? ExcelDate::dateTimeToExcel(
                        $sentAt
                    )
                    : null,

                $observation->comments,

                /*
                 * Slika se ne zapisuje kao vrijednost ćelije.
                 * Dodaje se kroz drawings().
                 */
                null,
            ]
        );
    }

    public function columnFormats(): array
    {
        if ($this->showUserColumn) {
            /*
             * A = datum zapažanja
             * K = rok
             * L = datum zatvaranja
             * Q = poslano
             */
            return [
                'A' => 'dd.mm.yyyy',
                'K' => 'dd.mm.yyyy',
                'L' => 'dd.mm.yyyy',
                'Q' => 'dd.mm.yyyy hh:mm',
            ];
        }

        /*
         * Bez stupca korisnika:
         *
         * A = datum zapažanja
         * J = rok
         * K = datum zatvaranja
         * P = poslano
         */
        return [
            'A' => 'dd.mm.yyyy',
            'J' => 'dd.mm.yyyy',
            'K' => 'dd.mm.yyyy',
            'P' => 'dd.mm.yyyy hh:mm',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | SLIKE
    |--------------------------------------------------------------------------
    |
    | Stara verzija slala je originalnu fotografiju direktno u XLSX.
    |
    | Fotografija s mobitela može imati:
    | - 3000 - 5000 px
    | - nekoliko MB
    | - EXIF Orientation
    |
    | Zato je Excel izvoz bio spor i velik, a neke slike bile su bočno.
    |
    | Nova verzija:
    | - učita sliku u GD
    | - pročita EXIF Orientation
    | - pravilno je rotira
    | - smanji je na max. 600 x 600 px
    | - tek tada je ugradi u Excel
    |
    */

    public function drawings(): array
    {
        $drawings = [];

        $imageColumn =
            $this->showUserColumn
                ? 'S'
                : 'R';

        foreach (
            $this->observations
            as $index => $observation
        ) {
            if (
                blank(
                    $observation->picture_path
                )
            ) {
                continue;
            }

            $fullPath =
                $this->pictureFullPath(
                    (string)
                    $observation->picture_path
                );

            if (! $fullPath) {
                continue;
            }

            $image =
                $this->prepareImageForExcel(
                    $fullPath
                );

            if (! $image) {
                continue;
            }

            $row =
                $index
                + $this->firstDataRow;

            $drawing =
                new MemoryDrawing();

            $drawing->setName(
                'slika_' . $row
            );

            $drawing->setDescription(
                'Slika zapažanja'
            );

            /*
             * U MemoryDrawing stavljamo već:
             * - rotiranu
             * - smanjenu
             * fotografiju.
             */
            $drawing->setImageResource(
                $image
            );

            $drawing->setRenderingFunction(
                MemoryDrawing::RENDERING_JPEG
            );

            $drawing->setMimeType(
                MemoryDrawing::MIMETYPE_JPEG
            );

            $drawing->setHeight(
                $this->imgHeight
            );

            $drawing->setCoordinates(
                $imageColumn . $row
            );

            $drawing->setOffsetX(8);
            $drawing->setOffsetY(5);

            $drawing->setResizeProportional(
                true
            );

            $drawings[] = $drawing;
        }

        return $drawings;
    }

    private function pictureFullPath(
        string $picturePath
    ): ?string {
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

        if ($relativePath === '') {
            return null;
        }

        /*
         * Ne dopuštamo izlazak iz public storagea.
         */
        if (
            preg_match(
                '#(^|/)\.\.(/|$)#',
                $relativePath
            ) === 1
        ) {
            return null;
        }

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

        return $fullPath;
    }

    private function prepareImageForExcel(
        string $fullPath
    ) {
        /*
         * Ako PHP GD nije dostupan,
         * sliku preskačemo umjesto da ponovno
         * ugrađujemo ogromni original.
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

        /*
         * Više nam ne treba originalni binary.
         */
        unset($binary);

        if ($image === false) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | EXIF ORIENTATION
        |--------------------------------------------------------------------------
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

                $image =
                    $this->applyExifOrientation(
                        $image,
                        $orientation
                    );
            } catch (\Throwable $exception) {
                /*
                 * Ako EXIF nije dostupan ili je
                 * neispravan, izvoz se nastavlja.
                 */
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SMANJIVANJE
        |--------------------------------------------------------------------------
        |
        | Za prikaz slike veličine oko 90 px nema razloga
        | ugrađivati fotografiju od 4000 px.
        |
        | 600 px ostavlja dovoljno kvalitete čak i ako
        | korisnik malo poveća sliku u Excelu.
        |
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

        $maxWidth = 600;
        $maxHeight = 600;

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

        /*
         * Ako je slika već dovoljno mala,
         * ipak je prebacujemo u novi JPEG resource.
         *
         * Time standardiziramo fotografije za XLSX.
         */
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
         * Bijela pozadina za PNG/WebP/GIF slike
         * koje mogu sadržavati transparentnost.
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

        $copied =
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

        if (! $copied) {
            imagedestroy(
                $thumbnail
            );

            return null;
        }

        return $thumbnail;
    }

    private function applyExifOrientation(
        $image,
        int $orientation
    ) {
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
             * Rotacija 180°.
             */
            case 3:
                $image =
                    $this->rotateImage(
                        $image,
                        180
                    );

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
             * Mirror + 90°.
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

                $image =
                    $this->rotateImage(
                        $image,
                        -90
                    );

                break;

            /*
             * 90° udesno.
             */
            case 6:
                $image =
                    $this->rotateImage(
                        $image,
                        -90
                    );

                break;

            /*
             * Mirror + 90° ulijevo.
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

                $image =
                    $this->rotateImage(
                        $image,
                        90
                    );

                break;

            /*
             * 90° ulijevo.
             */
            case 8:
                $image =
                    $this->rotateImage(
                        $image,
                        90
                    );

                break;
        }

        return $image;
    }

    private function rotateImage(
        $image,
        int $angle
    ) {
        $rotated =
            @imagerotate(
                $image,
                $angle,
                0
            );

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class =>
                function (
                    AfterSheet $event
                ): void {
                    $sheet =
                        $event
                            ->sheet
                            ->getDelegate();

                    $lastColumn =
                        $this->showUserColumn
                            ? 'S'
                            : 'R';

                    $lastDataRow =
                        max(
                            $this->headingRow,
                            $this
                                ->observations
                                ->count()
                            + $this->headingRow
                        );

                    $user =
                        auth()->user();

                    /*
                    |--------------------------------------------------------------------------
                    | NASLOV
                    |--------------------------------------------------------------------------
                    */

                    $sheet->mergeCells(
                        "A1:{$lastColumn}1"
                    );

                    $sheet->setCellValue(
                        'A1',
                        'ZNR LIDER – POPIS ZAPAŽANJA'
                    );

                    $sheet
                        ->getStyle('A1')
                        ->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 16,
                                'name' =>
                                    'DejaVu Sans',
                                'color' => [
                                    'rgb' =>
                                        'FFFFFF',
                                ],
                            ],

                            'fill' => [
                                'fillType' =>
                                    Fill::FILL_SOLID,

                                'startColor' => [
                                    'rgb' =>
                                        '111827',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::HORIZONTAL_CENTER,

                                'vertical' =>
                                    Alignment::VERTICAL_CENTER,
                            ],
                        ]);

                    $sheet
                        ->getRowDimension(1)
                        ->setRowHeight(28);

                    /*
                    |--------------------------------------------------------------------------
                    | PODATCI IZVOZA
                    |--------------------------------------------------------------------------
                    */

                    $sheet->mergeCells(
                        "A2:{$lastColumn}2"
                    );

                    $sheet->setCellValue(
                        'A2',
                        'Izvoz izradio: '
                        . (
                            $user?->name
                            ?? '-'
                        )
                        . ' | Datum izvoza: '
                        . now()->format(
                            'd.m.Y. H:i'
                        )
                    );

                    $sheet
                        ->getStyle('A2')
                        ->applyFromArray([
                            'font' => [
                                'name' =>
                                    'DejaVu Sans',

                                'size' => 10,

                                'italic' =>
                                    true,

                                'color' => [
                                    'rgb' =>
                                        '374151',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::HORIZONTAL_CENTER,
                            ],
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | SAŽETAK
                    |--------------------------------------------------------------------------
                    */

                    $total =
                        $this
                            ->observations
                            ->count();

                    $nearMiss =
                        $this
                            ->observations
                            ->where(
                                'observation_type',
                                'Near Miss'
                            )
                            ->count();

                    $negative =
                        $this
                            ->observations
                            ->where(
                                'observation_type',
                                'Negative Observation'
                            )
                            ->count();

                    $positive =
                        $this
                            ->observations
                            ->where(
                                'observation_type',
                                'Positive Observation'
                            )
                            ->count();

                    $qrPublic =
                        $this
                            ->observations
                            ->where(
                                'source',
                                'qr_public'
                            )
                            ->count();

                    $internal =
                        $total
                        - $qrPublic;

                    $notStarted =
                        $this
                            ->observations
                            ->where(
                                'status',
                                'Not started'
                            )
                            ->count();

                    $inProgress =
                        $this
                            ->observations
                            ->where(
                                'status',
                                'In progress'
                            )
                            ->count();

                    $completed =
                        $this
                            ->observations
                            ->where(
                                'status',
                                'Complete'
                            )
                            ->count();

                    $sheet->mergeCells(
                        "A3:{$lastColumn}3"
                    );

                    $sheet->setCellValue(
                        'A3',
                        'Ukupno: '
                        . $total
                        . ' | Near Miss: '
                        . $nearMiss
                        . ' | Negativna: '
                        . $negative
                        . ' | Pozitivna: '
                        . $positive
                        . ' | QR prijave: '
                        . $qrPublic
                        . ' | Interno: '
                        . $internal
                        . ' | Nije započeto: '
                        . $notStarted
                        . ' | U tijeku: '
                        . $inProgress
                        . ' | Završeno: '
                        . $completed
                    );

                    $sheet
                        ->getStyle('A3')
                        ->applyFromArray([
                            'font' => [
                                'name' =>
                                    'DejaVu Sans',

                                'size' => 10,

                                'bold' =>
                                    true,

                                'color' => [
                                    'rgb' =>
                                        '111827',
                                ],
                            ],

                            'fill' => [
                                'fillType' =>
                                    Fill::FILL_SOLID,

                                'startColor' => [
                                    'rgb' =>
                                        'F3F4F6',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::HORIZONTAL_CENTER,

                                'vertical' =>
                                    Alignment::VERTICAL_CENTER,

                                'wrapText' =>
                                    true,
                            ],
                        ]);

                    $sheet
                        ->getRowDimension(3)
                        ->setRowHeight(30);

                    /*
                    |--------------------------------------------------------------------------
                    | LEGENDA
                    |--------------------------------------------------------------------------
                    */

                    $sheet->mergeCells(
                        "A4:{$lastColumn}4"
                    );

                    $sheet->setCellValue(
                        'A4',
                        'Legenda: CRVENO = isteklo / nije započeto | '
                        . 'ŽUTO = rok istječe u sljedećih 30 dana / u tijeku | '
                        . 'ZELENO = završeno'
                    );

                    $sheet
                        ->getStyle('A4')
                        ->applyFromArray([
                            'font' => [
                                'name' =>
                                    'DejaVu Sans',

                                'size' => 9,

                                'bold' =>
                                    true,

                                'color' => [
                                    'rgb' =>
                                        '4B5563',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::HORIZONTAL_CENTER,
                            ],
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | FONT CIJELOG IZVOZA
                    |--------------------------------------------------------------------------
                    */

                    $sheet
                        ->getStyle(
                            "A1:{$lastColumn}{$lastDataRow}"
                        )
                        ->getFont()
                        ->setName(
                            'DejaVu Sans'
                        )
                        ->setSize(10);

                    /*
                    |--------------------------------------------------------------------------
                    | ZAGLAVLJE TABLICE
                    |--------------------------------------------------------------------------
                    */

                    $sheet
                        ->getStyle(
                            "A{$this->headingRow}:{$lastColumn}{$this->headingRow}"
                        )
                        ->applyFromArray([
                            'font' => [
                                'bold' =>
                                    true,

                                'color' => [
                                    'rgb' =>
                                        'FFFFFF',
                                ],

                                'name' =>
                                    'DejaVu Sans',

                                'size' => 10,
                            ],

                            'fill' => [
                                'fillType' =>
                                    Fill::FILL_SOLID,

                                'startColor' => [
                                    'rgb' =>
                                        '1F2937',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::HORIZONTAL_CENTER,

                                'vertical' =>
                                    Alignment::VERTICAL_CENTER,

                                'wrapText' =>
                                    true,
                            ],

                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' =>
                                        Border::BORDER_THIN,

                                    'color' => [
                                        'rgb' =>
                                            '9CA3AF',
                                    ],
                                ],
                            ],
                        ]);

                    $sheet
                        ->getRowDimension(
                            $this->headingRow
                        )
                        ->setRowHeight(32);

                    /*
                    |--------------------------------------------------------------------------
                    | TIJELO TABLICE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $this
                            ->observations
                            ->isNotEmpty()
                    ) {
                        $sheet
                            ->getStyle(
                                "A{$this->firstDataRow}:{$lastColumn}{$lastDataRow}"
                            )
                            ->applyFromArray([
                                'alignment' => [
                                    'vertical' =>
                                        Alignment::VERTICAL_CENTER,

                                    'horizontal' =>
                                        Alignment::HORIZONTAL_LEFT,

                                    'wrapText' =>
                                        true,
                                ],

                                'borders' => [
                                    'allBorders' => [
                                        'borderStyle' =>
                                            Border::BORDER_THIN,

                                        'color' => [
                                            'rgb' =>
                                                'D1D5DB',
                                        ],
                                    ],
                                ],
                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ŠIRINE STUPACA
                    |--------------------------------------------------------------------------
                    */

                    if ($this->showUserColumn) {
                        /*
                         * Sa stupcem Korisnik:
                         *
                         * A Datum
                         * B Korisnik
                         * C Vrsta
                         * D Izvor
                         * E Prioritet
                         * F Lokacija
                         * G Opis
                         * H Vrsta opasnosti
                         * I Potrebna radnja
                         * J Odgovorna osoba
                         * K Rok
                         * L Datum zatvaranja
                         * M Dani zatvaranja
                         * N Status
                         * O Kontakt
                         * P E-mail
                         * Q Poslano
                         * R Komentar
                         * S Slika
                         */
                        $widths = [
                            'A' => 15,
                            'B' => 24,
                            'C' => 24,
                            'D' => 17,
                            'E' => 14,
                            'F' => 22,
                            'G' => 42,
                            'H' => 32,
                            'I' => 42,
                            'J' => 23,
                            'K' => 17,
                            'L' => 18,
                            'M' => 19,
                            'N' => 18,
                            'O' => 28,
                            'P' => 34,
                            'Q' => 19,
                            'R' => 35,

                            /*
                             * Slika je prije bila 15.
                             */
                            'S' => 24,
                        ];

                        $sourceColumn = 'D';
                        $priorityColumn = 'E';
                        $targetDateColumn = 'K';
                        $completedDateColumn = 'L';
                        $closingDaysColumn = 'M';
                        $statusColumn = 'N';
                        $imageColumn = 'S';
                    } else {
                        /*
                         * Bez stupca Korisnik.
                         */
                        $widths = [
                            'A' => 15,
                            'B' => 24,
                            'C' => 17,
                            'D' => 14,
                            'E' => 22,
                            'F' => 42,
                            'G' => 32,
                            'H' => 42,
                            'I' => 23,
                            'J' => 17,
                            'K' => 18,
                            'L' => 19,
                            'M' => 18,
                            'N' => 28,
                            'O' => 34,
                            'P' => 19,
                            'Q' => 35,

                            /*
                             * Slika je prije bila 15.
                             */
                            'R' => 24,
                        ];

                        $sourceColumn = 'C';
                        $priorityColumn = 'D';
                        $targetDateColumn = 'J';
                        $completedDateColumn = 'K';
                        $closingDaysColumn = 'L';
                        $statusColumn = 'M';
                        $imageColumn = 'R';
                    }

                    foreach (
                        $widths
                        as $column => $width
                    ) {
                        $sheet
                            ->getColumnDimension(
                                $column
                            )
                            ->setWidth(
                                $width
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CENTRIRANI STUPCI
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $this
                            ->observations
                            ->isNotEmpty()
                    ) {
                        $centerColumns = [
                            'A',
                            $sourceColumn,
                            $priorityColumn,
                            $targetDateColumn,
                            $completedDateColumn,
                            $closingDaysColumn,
                            $statusColumn,
                            $imageColumn,
                        ];

                        foreach (
                            $centerColumns
                            as $column
                        ) {
                            $sheet
                                ->getStyle(
                                    "{$column}{$this->firstDataRow}:{$column}{$lastDataRow}"
                                )
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_CENTER
                                );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | VISINA REDOVA
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $this->observations
                        as $index => $observation
                    ) {
                        $row =
                            $index
                            + $this->firstDataRow;

                        $sheet
                            ->getRowDimension(
                                $row
                            )
                            ->setRowHeight(
                                filled(
                                    $observation
                                        ->picture_path
                                )
                                    /*
                                     * 90 px slike ≈ 67.5 pt,
                                     * dodajemo malo prostora.
                                     */
                                    ? 74
                                    : 38
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | BOJE
                    |--------------------------------------------------------------------------
                    */

                    $today =
                        Carbon::today();

                    foreach (
                        $this->observations
                        as $index => $observation
                    ) {
                        $row =
                            $index
                            + $this->firstDataRow;

                        /*
                        |--------------------------------------------------------------------------
                        | ROK ZA PROVEDBU
                        |--------------------------------------------------------------------------
                        |
                        | Isto kao Radna oprema:
                        |
                        | isteklo = čisto crveno
                        | do 30 dana = čisto žuto
                        |
                        */

                        $targetDate =
                            filled(
                                $observation
                                    ->target_date
                            )
                                ? Carbon::parse(
                                    $observation
                                        ->target_date
                                )->startOfDay()
                                : null;

                        if (
                            $targetDate
                            && $observation
                                ->status
                                !== 'Complete'
                        ) {
                            if (
                                $targetDate->lt(
                                    $today
                                )
                            ) {
                                $this->styleCell(
                                    $sheet,
                                    "{$targetDateColumn}{$row}",
                                    'FF0000',
                                    '000000'
                                );
                            } elseif (
                                $targetDate->lte(
                                    $today
                                        ->copy()
                                        ->addDays(30)
                                )
                            ) {
                                $this->styleCell(
                                    $sheet,
                                    "{$targetDateColumn}{$row}",
                                    'FFFF00',
                                    '000000'
                                );
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | PRIORITET
                        |--------------------------------------------------------------------------
                        */

                        [
                            $priorityBackground,
                            $priorityText,
                        ] =
                            $this->priorityColors(
                                $observation
                                    ->priority
                            );

                        $this->styleCell(
                            $sheet,
                            "{$priorityColumn}{$row}",
                            $priorityBackground,
                            $priorityText
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | STATUS
                        |--------------------------------------------------------------------------
                        |
                        | Nije započeto = crveno
                        | U tijeku = žuto
                        | Završeno = zeleno
                        |
                        */

                        [
                            $statusBackground,
                            $statusText,
                        ] =
                            $this->statusColors(
                                $observation
                                    ->status
                            );

                        $this->styleCell(
                            $sheet,
                            "{$statusColumn}{$row}",
                            $statusBackground,
                            $statusText
                        );

                        /*
                         * QR prijava.
                         */
                        if (
                            $observation
                                ->source
                            === 'qr_public'
                        ) {
                            $this->styleCell(
                                $sheet,
                                "{$sourceColumn}{$row}",
                                'DCFCE7',
                                '166534'
                            );
                        }

                        /*
                         * Datum zatvaranja i broj dana.
                         */
                        if (
                            $observation
                                ->status
                            === 'Complete'
                            && filled(
                                $observation
                                    ->completed_at
                            )
                        ) {
                            $this->styleCell(
                                $sheet,
                                "{$completedDateColumn}{$row}",
                                '00B050',
                                'FFFFFF'
                            );

                            $this->styleCell(
                                $sheet,
                                "{$closingDaysColumn}{$row}",
                                '00B050',
                                'FFFFFF'
                            );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | EXCEL FUNKCIONALNOSTI
                    |--------------------------------------------------------------------------
                    */

                    $sheet->freezePane(
                        'A'
                        . $this->firstDataRow
                    );

                    $sheet->setAutoFilter(
                        "A{$this->headingRow}:{$lastColumn}{$lastDataRow}"
                    );

                    /*
                     * Landscape ispis.
                     */
                    $sheet
                        ->getPageSetup()
                        ->setOrientation(
                            \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                        );

                    $sheet
                        ->getPageSetup()
                        ->setFitToWidth(1);

                    $sheet
                        ->getPageSetup()
                        ->setFitToHeight(0);

                    $sheet
                        ->getPageMargins()
                        ->setTop(0.4)
                        ->setRight(0.3)
                        ->setBottom(0.4)
                        ->setLeft(0.3);

                    $sheet
                        ->getPageSetup()
                        ->setRowsToRepeatAtTopByStartAndEnd(
                            $this->headingRow,
                            $this->headingRow
                        );
                },
        ];
    }

    private function observationTypeLabel(
        ?string $state
    ): string {
        return match ($state) {
            'Near Miss' =>
                'NM - Skoro nezgoda',

            'Negative Observation' =>
                'Negativno zapažanje',

            'Positive Observation' =>
                'Pozitivno zapažanje',

            default =>
                $state ?? '',
        };
    }

    private function sourceLabel(
        ?string $state
    ): string {
        return $state === 'qr_public'
            ? 'QR prijava'
            : 'Interni unos';
    }

    private function priorityLabel(
        ?string $state
    ): string {
        return match ($state) {
            'low' =>
                'Nisko',

            'medium' =>
                'Srednje',

            'high' =>
                'Visoko',

            'critical' =>
                'Kritično',

            default =>
                $state ?? '',
        };
    }

    private function statusLabel(
        ?string $state
    ): string {
        return match ($state) {
            'Not started' =>
                'Nije započeto',

            'In progress' =>
                'U tijeku',

            'Complete' =>
                'Završeno',

            default =>
                $state ?? '',
        };
    }

    private function emails(
        $value
    ): string {
        if (is_array($value)) {
            return collect($value)
                ->map(
                    fn ($email) =>
                        trim(
                            (string) $email
                        )
                )
                ->filter()
                ->unique()
                ->implode(', ');
        }

        return trim(
            (string) (
                $value
                ?? ''
            )
        );
    }

    private function priorityColors(
        ?string $priority
    ): array {
        return match ($priority) {

            /*
            * KRITIČNO
            * Tamnije crveno.
            */
            'critical' => [
                'C00000',
                'FFFFFF',
            ],

            /*
            * VISOKO
            * Isto jako crveno kao istekli rok.
            */
            'high' => [
                'FF0000',
                'FFFFFF',
            ],

            /*
            * SREDNJE
            * Isto jako žuto kao rok koji uskoro istječe.
            */
            'medium' => [
                'FFFF00',
                '000000',
            ],

            /*
            * NISKO
            * Narančasto.
            */
            'low' => [
                'F4B183',
                '000000',
            ],

            default => [
                'F3F4F6',
                '374151',
            ],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | BOJE STATUSA
    |--------------------------------------------------------------------------
    |
    | Namjerno nisu pastelne kao prije.
    |
    | Koristimo jake boje radi usklađivanja
    | s ostalim Excel izvozima:
    |
    | Nije započeto = CRVENO
    | U tijeku       = ŽUTO
    | Završeno       = ZELENO
    |
    */

    private function statusColors(
        ?string $status
    ): array {
        return match ($status) {

            'Not started' => [
                'FF0000',
                'FFFFFF',
            ],

            'In progress' => [
                'FFFF00',
                '000000',
            ],

            'Complete' => [
                '00B050',
                'FFFFFF',
            ],

            default => [
                'F3F4F6',
                '374151',
            ],
        };
    }
    private function styleCell(
        $sheet,
        string $cell,
        string $backgroundColor,
        string $fontColor
    ): void {
        $sheet
            ->getStyle($cell)
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            );

        $sheet
            ->getStyle($cell)
            ->getFill()
            ->getStartColor()
            ->setRGB(
                $backgroundColor
            );

        $sheet
            ->getStyle($cell)
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setRGB(
                $fontColor
            );

        $sheet
            ->getStyle($cell)
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );
    }
}