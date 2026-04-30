@php
    use Illuminate\Support\Carbon;

    $toList = function ($value): array {
        if (is_array($value)) return array_values(array_filter($value));

        $value = trim((string) $value);
        if ($value === '') return [];

        return array_values(array_filter(preg_split('/\s*,\s*/', $value)));
    };

    $chunkLines = function (array $list, int $perLine): string {
        return collect(array_chunk($list, $perLine))
            ->map(fn ($chunk) => implode(', ', $chunk))
            ->implode("\n");
    };

    // 🔥 BASE64 helper (ovo rješava sve probleme)
    $img = function ($path) {
        if (!file_exists($path)) return null;

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    };

    $candidates = function (string $code): array {
        $code = strtoupper(trim($code));

        return [
            public_path("images/ghs/{$code}.png"),
            public_path("images/ghs/{$code}.gif"),
            public_path("piktogrami/{$code}.png"),
            public_path("piktogrami/{$code}.gif"),
            public_path("images/piktogrami/{$code}.png"),
        ];
    };

    $title = 'Kemikalije';

    $columns = [
        ['key' => 'product_name', 'label' => 'Ime proizvoda'],
        ['key' => 'cas_number', 'label' => 'CAS', 'class' => 'center'],
        ['key' => 'ufi_number', 'label' => 'UFI', 'class' => 'center'],
        ['key' => 'pictograms', 'label' => 'Piktogrami', 'class' => 'center'],
        ['key' => 'h_statements', 'label' => 'H oznake', 'class' => 'center'],
        ['key' => 'p_statements', 'label' => 'P oznake'],
        ['key' => 'usage_location', 'label' => 'Mjesto upotrebe'],
        ['key' => 'annual_quantity', 'label' => 'Količina', 'class' => 'center'],
        ['key' => 'gvi_kgvi', 'label' => 'GVI / KGVI', 'class' => 'center'],
        ['key' => 'voc', 'label' => 'VOC', 'class' => 'center'],
        ['key' => 'stl_hzjz', 'label' => 'STL – HZJZ', 'class' => 'center'],
        ['key' => 'attachments', 'label' => 'Prilozi', 'class' => 'center'],
    ];

    $rows = $chemicals->map(function ($c) use ($toList, $chunkLines, $candidates, $img) {

        $pict = '<div class="pikt-wrap">';

        foreach ($toList($c->hazard_pictograms ?? null) as $code) {
            foreach ($candidates($code) as $path) {

                $src = $img($path);

                if ($src) {
                    $pict .= '<img src="'.$src.'">';
                    break;
                }
            }
        }

        $pict .= '</div>';

        return [
            'product_name' => e($c->product_name),
            'cas_number' => e($c->cas_number),
            'ufi_number' => e($c->ufi_number),
            'pictograms' => $pict,
            'h_statements' => nl2br(e($chunkLines($toList($c->h_statements ?? null), 2))),
            'p_statements' => nl2br(e($chunkLines($toList($c->p_statements ?? null), 2))),
            'usage_location' => e($c->usage_location),
            'annual_quantity' => e($c->annual_quantity),
            'gvi_kgvi' => e($c->gvi_kgvi),
            'voc' => e($c->voc),
            'stl_hzjz' => filled($c->stl_hzjz ?? null)
                ? Carbon::parse($c->stl_hzjz)->format('d.m.Y.')
                : '',
            'attachments' => is_array($c->attachments ?? null) ? count($c->attachments) : 0,
        ];
    });
@endphp

@include('pdf.partials.report-table', [
    'title' => $title,
    'columns' => $columns,
    'rows' => $rows,
])