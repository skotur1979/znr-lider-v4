@php
    $latestValue = $record->latestValue();

    $targetMonth = $latestValue?->month ?? now()->month;
    $targetYear = $latestValue?->year ?? now()->year;

    $effectiveTarget = $record->effectiveTargetValueForPeriod(
        (int) $targetMonth,
        (int) $targetYear,
        $ownerId
    );

    $currentStatus = $latestValue
        ? $record->evaluateStatusForPeriod(
            $latestValue?->value,
            (int) $latestValue->month,
            (int) $latestValue->year,
            $ownerId
        )
        : 'neutral';

    $statusText = match($currentStatus) {
        'success' => 'U cilju',
        'warning' => 'Upozorenje',
        'danger' => 'Izvan cilja',
        default => 'Bez cilja',
    };

    $statusClass = match($currentStatus) {
        'success' => 'success',
        'warning' => 'warning',
        'danger' => 'danger',
        default => 'neutral',
    };

    $points = collect($trend)->values();

    $numericValues = $points->pluck('value')->filter(fn ($v) => $v !== null);
    $numericTargets = $points->pluck('target_value')->filter(fn ($v) => $v !== null);

    $min = 0;
    $max = $numericValues->max();

    if ($max === null) {
        $max = 1;
    }

    $targetMax = $numericTargets->max();

    if ($targetMax !== null) {
        $max = max((float) $max, (float) $targetMax);
    }

    if ($effectiveTarget !== null) {
        $max = max((float) $max, (float) $effectiveTarget);
    }

    $max = (float) $max * 1.15;

    if ($max <= 0) {
        $max = 1;
    }

    $width = 900;
    $height = 280;
    $paddingX = 60;
    $paddingYTop = 30;
    $paddingYBottom = 40;
    $chartLeft = $paddingX;
    $chartRight = 850;
    $chartTop = $paddingYTop;
    $chartBottom = $height - $paddingYBottom;
    $chartHeight = $chartBottom - $chartTop;

    $count = max($points->count(), 1);
    $stepX = ($chartRight - $chartLeft) / max($count - 1, 1);

    $gridLines = [];
    for ($i = 0; $i <= 5; $i++) {
        $value = ($max / 5) * $i;
        $y = $chartBottom - (($value - $min) / (($max - $min) ?: 1) * $chartHeight);

        $gridLines[] = [
            'value' => $value,
            'formatted' => number_format($value, 0, ',', '.'),
            'y' => round($y, 2),
        ];
    }

    $svgPoints = [];

    foreach ($points as $index => $item) {
        $x = $chartLeft + ($index * $stepX);
        $value = $item['value'];

        if ($value === null) {
            $svgPoints[] = null;
            continue;
        }

        $normalized = ((float) $value - $min) / (($max - $min) ?: 1);
        $y = $chartBottom - ($normalized * $chartHeight);

        $svgPoints[] = [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'label' => $item['label'],
            'value' => $item['formatted'],
        ];
    }

    $polyline = collect($svgPoints)
        ->filter()
        ->map(fn ($p) => $p['x'] . ',' . $p['y'])
        ->implode(' ');

    $targetPoints = [];

    foreach ($points as $index => $item) {
        $x = $chartLeft + ($index * $stepX);
        $target = $item['target_value'] ?? null;

        if ($target === null) {
            $targetPoints[] = null;
            continue;
        }

        $normalized = ((float) $target - $min) / (($max - $min) ?: 1);
        $y = $chartBottom - ($normalized * $chartHeight);

        $targetPoints[] = [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'value' => $record->formatNumberOnly($target),
        ];
    }

    $filteredTargetPoints = collect($targetPoints)->filter()->values();

    $targetStepPath = '';

    if ($filteredTargetPoints->isNotEmpty()) {
        $firstTargetPoint = $filteredTargetPoints->first();

        $targetStepPath = 'M ' . $firstTargetPoint['x'] . ' ' . $firstTargetPoint['y'];

        for ($i = 1; $i < $filteredTargetPoints->count(); $i++) {
            $previousPoint = $filteredTargetPoints[$i - 1];
            $currentPoint = $filteredTargetPoints[$i];

            $targetStepPath .= ' L ' . $currentPoint['x'] . ' ' . $previousPoint['y'];
            $targetStepPath .= ' L ' . $currentPoint['x'] . ' ' . $currentPoint['y'];
        }
    }

    $lastTargetPoint = $filteredTargetPoints->last();
@endphp

<style>
    .kpi-sheet {
        background: #ffffff;
        color: #111827;
        border: 1px solid #d1d5db;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
        margin-bottom: 20px;
    }

    .dark .kpi-sheet {
        background: #111827;
        color: #f9fafb;
        border-color: rgba(255,255,255,.10);
        box-shadow: 0 10px 28px rgba(0, 0, 0, .35);
    }

    .kpi-section-title {
        text-align: center;
        font-size: 15px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 10px 16px;
        border-bottom: 1px solid #d1d5db;
        background: #f9fafb;
    }

    .dark .kpi-section-title {
        background: rgba(255,255,255,.03);
        border-bottom-color: rgba(255,255,255,.10);
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        padding: 16px;
    }

    .kpi-box {
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 12px;
        background: rgba(255,255,255,.65);
    }

    .dark .kpi-box {
        background: rgba(255,255,255,.02);
        border-color: rgba(255,255,255,.10);
    }

    .kpi-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        opacity: .75;
        margin-bottom: 4px;
    }

    .kpi-value {
        font-size: 15px;
        font-weight: 700;
        word-break: break-word;
    }

    .kpi-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .kpi-badge.success {
        background: rgba(34, 197, 94, .12);
        color: #15803d;
    }

    .kpi-badge.warning {
        background: rgba(245, 158, 11, .12);
        color: #b45309;
    }

    .kpi-badge.danger {
        background: rgba(239, 68, 68, .12);
        color: #b91c1c;
    }

    .kpi-badge.neutral {
        background: rgba(107, 114, 128, .12);
        color: #4b5563;
    }

    .dark .kpi-badge.success { color: #86efac; }
    .dark .kpi-badge.warning { color: #fcd34d; }
    .dark .kpi-badge.danger { color: #fca5a5; }
    .dark .kpi-badge.neutral { color: #d1d5db; }

    .kpi-chart-wrap {
        padding: 16px;
        border-top: 1px solid #d1d5db;
    }

    .dark .kpi-chart-wrap {
        border-top-color: rgba(255,255,255,.10);
    }

    .kpi-chart-legend {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
        font-size: 12px;
        font-weight: 700;
        opacity: .85;
        margin-bottom: 12px;
    }

    .kpi-chart-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .kpi-chart-legend-line {
        width: 28px;
        height: 0;
        border-top: 3px solid currentColor;
        display: inline-block;
    }

    .kpi-chart-legend-target {
        width: 28px;
        height: 0;
        border-top: 2px dashed #ef4444;
        display: inline-block;
    }

    @media (max-width: 1100px) {
        .kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .kpi-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="kpi-sheet">
    <div class="kpi-section-title">Pregled KPI-a</div>

    <div class="kpi-grid">
        <div class="kpi-box">
            <span class="kpi-label">Naziv KPI-a</span>
            <div class="kpi-value">{{ $record->name }}</div>
        </div>

        <div class="kpi-box">
            <span class="kpi-label">Kategorija</span>
            <div class="kpi-value">{{ $record->category }}</div>
        </div>

        <div class="kpi-box">
            <span class="kpi-label">Jedinica</span>
            <div class="kpi-value">{{ $record->unit ?: '-' }}</div>
        </div>

        <div class="kpi-box">
            <span class="kpi-label">Cilj</span>
            <div class="kpi-value">{{ $record->formatNumberOnly($effectiveTarget) }}</div>
        </div>

        <div class="kpi-box">
            <span class="kpi-label">Tip</span>
            <div class="kpi-value">
                {{ match($record->calculation_type) {
                    'manual' => 'Ručno',
                    'automatic' => 'Automatski',
                    'formula' => 'Formula',
                    default => $record->calculation_type,
                } }}
            </div>
        </div>

        <div class="kpi-box">
            <span class="kpi-label">Status</span>
            <div class="kpi-value">
                <span class="kpi-badge {{ $statusClass }}">{{ $statusText }}</span>
            </div>
        </div>

        <div class="kpi-box">
            <span class="kpi-label">Zadnja vrijednost</span>
            <div class="kpi-value">{{ $record->formatNumberOnly($latestValue?->value) }}</div>
        </div>

        <div class="kpi-box">
            <span class="kpi-label">Zadnji period</span>
            <div class="kpi-value">
                @if($latestValue)
                    {{ sprintf('%02d/%s', $latestValue->month, $latestValue->year) }}
                @else
                    -
                @endif
            </div>
        </div>
    </div>

    <div class="kpi-chart-wrap">
        <div style="font-size:15px;font-weight:800;margin-bottom:8px;">Trend po mjesecima ({{ now()->year }})</div>
        <div style="font-size:13px;opacity:.75;margin-bottom:12px;">Pregled mjesečnih vrijednosti za odabrani KPI</div>

        <div class="kpi-chart-legend">
            <span class="kpi-chart-legend-item">
                <span class="kpi-chart-legend-line text-primary-500"></span>
                Vrijednost
            </span>

            <span class="kpi-chart-legend-item">
                <span class="kpi-chart-legend-target"></span>
                Cilj kroz godinu
            </span>
        </div>

        <div style="overflow-x:auto;">
            <svg viewBox="0 0 900 280" class="min-w-[900px] w-full">
                @foreach($gridLines as $line)
                    <line
                        x1="{{ $chartLeft }}"
                        y1="{{ $line['y'] }}"
                        x2="{{ $chartRight }}"
                        y2="{{ $line['y'] }}"
                        stroke="rgba(148,163,184,0.25)"
                        stroke-width="1"
                    />

                    <text
                        x="{{ $chartLeft - 12 }}"
                        y="{{ $line['y'] + 4 }}"
                        text-anchor="end"
                        font-size="11"
                        fill="currentColor"
                        class="text-gray-500"
                    >
                        {{ $line['formatted'] }}
                    </text>
                @endforeach

                <line
                    x1="{{ $chartLeft }}"
                    y1="{{ $chartTop }}"
                    x2="{{ $chartLeft }}"
                    y2="{{ $chartBottom }}"
                    stroke="rgba(148,163,184,0.35)"
                    stroke-width="1"
                />

                <line
                    x1="{{ $chartLeft }}"
                    y1="{{ $chartBottom }}"
                    x2="{{ $chartRight }}"
                    y2="{{ $chartBottom }}"
                    stroke="rgba(148,163,184,0.35)"
                    stroke-width="1"
                />

                @if($targetStepPath !== '')
                    <path
                        d="{{ $targetStepPath }}"
                        fill="none"
                        stroke="#ef4444"
                        stroke-width="2"
                        stroke-dasharray="6 6"
                    />

                    @if($lastTargetPoint)
                        <text
                            x="{{ $chartRight - 6 }}"
                            y="{{ max(12, $lastTargetPoint['y'] - 6) }}"
                            text-anchor="end"
                            font-size="11"
                            font-weight="800"
                            fill="#ef4444"
                        >
                            CILJ: {{ $lastTargetPoint['value'] }}
                        </text>
                    @endif
                @endif

                @if($polyline !== '')
                    <polyline
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        class="text-primary-500"
                        points="{{ $polyline }}"
                    />
                @endif

                @foreach($svgPoints as $point)
                    @if($point)
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5" class="fill-primary-500" />

                        <text
                            x="{{ $point['x'] }}"
                            y="{{ max(12, $point['y'] - 12) }}"
                            text-anchor="middle"
                            font-size="11"
                            font-weight="800"
                            fill="currentColor"
                            class="text-primary-500"
                        >
                            {{ $point['value'] }}
                        </text>
                    @endif
                @endforeach

                @foreach($points as $index => $item)
                    @php $x = $chartLeft + ($index * $stepX); @endphp
                    <text
                        x="{{ $x }}"
                        y="{{ $height - 12 }}"
                        text-anchor="middle"
                        font-size="11"
                        fill="currentColor"
                        class="text-gray-500"
                    >
                        {{ $item['label'] }}
                    </text>
                @endforeach
            </svg>
        </div>
    </div>
</div>