<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kpi extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'KPI';

    protected $fillable = [
        'name',
        'slug',
        'category',
        'unit',
        'target_value',
        'warning_offset',
        'direction',
        'calculation_type',
        'source_key',
        'formula_text',
        'description',
        'is_active',
        'show_on_dashboard',
        'sort_order',
        'user_id',
    ];

    protected $casts = [
        'target_value' => 'float',
        'warning_offset' => 'float',
        'is_active' => 'boolean',
        'show_on_dashboard' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $kpi) {
            if (blank($kpi->slug)) {
                $kpi->slug = Str::slug($kpi->name);
            }
        });

        static::updating(function (self $kpi) {
            if ($kpi->isDirty('name') && blank($kpi->slug)) {
                $kpi->slug = Str::slug($kpi->name);
            }
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(KpiValue::class)->orderBy('year')->orderBy('month');
    }

    public function targetOverrides(): HasMany
    {
        return $this->hasMany(KpiTargetOverride::class)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id');
    }

    public function latestValue(): ?KpiValue
    {
        return $this->hasMany(KpiValue::class)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();
    }

    protected function currentOwnerId(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'ownerId')) {
            return $user->ownerId() ?: $user->id;
        }

        return $user->parent_user_id ?: $user->id;
    }

    public function valueFor(int $month, int $year): ?KpiValue
    {
        $ownerId = $this->currentOwnerId();

        return $this->values()
            ->where('month', $month)
            ->where('year', $year)
            ->when($ownerId, fn ($query) => $query->where('user_id', $ownerId))
            ->first();
    }

    public function previousMonthValue(int $month, int $year): ?KpiValue
    {
        $current = Carbon::create($year, $month, 1)->subMonth();

        return $this->valueFor((int) $current->month, (int) $current->year);
    }

    public function sameMonthLastYearValue(int $month, int $year): ?KpiValue
    {
        return $this->valueFor($month, $year - 1);
    }

    public function targetOverrideFor(?int $userId = null): ?KpiTargetOverride
    {
        $userId ??= $this->currentOwnerId();

        if (! $userId) {
            return null;
        }

        return $this->targetOverrides()
            ->where('user_id', $userId)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->first();
    }

    public function targetOverrideForPeriod(int $month, int $year, ?int $userId = null): ?KpiTargetOverride
    {
        $userId ??= $this->currentOwnerId();

        if (! $userId) {
            return null;
        }

        return $this->targetOverrides()
            ->where('user_id', $userId)
            ->whereNotNull('month')
            ->whereNotNull('year')
            ->where(function ($query) use ($month, $year) {
                $query->where('year', '<', $year)
                    ->orWhere(function ($q) use ($month, $year) {
                        $q->where('year', $year)
                            ->where('month', '<=', $month);
                    });
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->first();
    }

    public function effectiveTargetValue(?int $userId = null): ?float
    {
        $override = $this->targetOverrideFor($userId);

        return $override?->target_value ?? $this->target_value;
    }

    public function effectiveWarningOffset(?int $userId = null): ?float
    {
        $override = $this->targetOverrideFor($userId);

        return $override?->warning_offset ?? $this->warning_offset;
    }

    public function effectiveTargetValueForPeriod(int $month, int $year, ?int $userId = null): ?float
    {
        $override = $this->targetOverrideForPeriod($month, $year, $userId);

        return $override?->target_value ?? $this->target_value;
    }

    public function effectiveWarningOffsetForPeriod(int $month, int $year, ?int $userId = null): ?float
    {
        $override = $this->targetOverrideForPeriod($month, $year, $userId);

        return $override?->warning_offset ?? $this->warning_offset;
    }

    public function evaluateStatus(?float $value, ?int $userId = null): string
    {
        $targetValue = $this->effectiveTargetValue($userId);
        $warningOffset = (float) ($this->effectiveWarningOffset($userId) ?? 0);

        if ($value === null || $targetValue === null) {
            return 'neutral';
        }

        $target = (float) $targetValue;

        return match ($this->direction) {
            'lower_better' => $this->evaluateLowerBetter($value, $target, $warningOffset),
            'higher_better' => $this->evaluateHigherBetter($value, $target, $warningOffset),
            'target_value' => $this->evaluateTargetValue($value, $target, $warningOffset),
            default => 'neutral',
        };
    }

    public function evaluateStatusForPeriod(?float $value, int $month, int $year, ?int $userId = null): string
    {
        $targetValue = $this->effectiveTargetValueForPeriod($month, $year, $userId);
        $warningOffset = (float) ($this->effectiveWarningOffsetForPeriod($month, $year, $userId) ?? 0);

        if ($value === null || $targetValue === null) {
            return 'neutral';
        }

        $target = (float) $targetValue;

        return match ($this->direction) {
            'lower_better' => $this->evaluateLowerBetter($value, $target, $warningOffset),
            'higher_better' => $this->evaluateHigherBetter($value, $target, $warningOffset),
            'target_value' => $this->evaluateTargetValue($value, $target, $warningOffset),
            default => 'neutral',
        };
    }

    public function monthlyTrendForYear(?int $year = null, ?int $userId = null): Collection
    {
        $year ??= now()->year;
        $userId ??= $this->currentOwnerId();

        return collect(range(1, 12))->map(function (int $month) use ($year, $userId) {
            $record = $this->valueFor($month, $year);
            $target = $this->effectiveTargetValueForPeriod($month, $year, $userId);

            return [
                'month' => $month,
                'label' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                'value' => $record?->value,
                'formatted' => $this->formatNumberOnly($record?->value),
                'has_value' => $record?->value !== null,
                'target_value' => $target,
                'formatted_target' => $this->formatNumberOnly($target),
            ];
        });
    }

    protected function evaluateLowerBetter(float $value, float $target, float $warningOffset): string
    {
        if ($value <= $target) {
            return 'success';
        }

        if ($warningOffset > 0 && $value <= ($target + $warningOffset)) {
            return 'warning';
        }

        return 'danger';
    }

    protected function evaluateHigherBetter(float $value, float $target, float $warningOffset): string
    {
        if ($value >= $target) {
            return 'success';
        }

        if ($warningOffset > 0 && $value >= ($target - $warningOffset)) {
            return 'warning';
        }

        return 'danger';
    }

    protected function evaluateTargetValue(float $value, float $target, float $warningOffset): string
    {
        $diff = abs($value - $target);

        if ($diff == 0.0) {
            return 'success';
        }

        if ($warningOffset > 0 && $diff <= $warningOffset) {
            return 'warning';
        }

        return 'danger';
    }

    public function formatValue(?float $value): string
    {
        if ($value === null) {
            return '-';
        }

        $formatted = number_format($value, 2, ',', '.');

        return $this->unit ? "{$formatted} {$this->unit}" : $formatted;
    }

    public function formatNumberOnly(?float $value): string
    {
        if ($value === null) {
            return '-';
        }

        return number_format($value, 2, ',', '.');
    }

    public function getCurrentStatusAttribute(): string
    {
        return $this->evaluateStatus($this->latestValue()?->value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}